
<?php

require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/../config/stripe_config.php";

// Stripe sends webhook requests using POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    exit("Method not allowed");
}

// Read the original, unmodified request body
$payload = file_get_contents("php://input");

// Get Stripe signature header
$signatureHeader = $_SERVER["HTTP_STRIPE_SIGNATURE"] ?? "";

if (!$payload || !$signatureHeader) {
    http_response_code(400);
    exit("Missing webhook information");
}

// Verify Stripe webhook signature
function verifyStripeSignature($payload, $header, $secret)
{
    $timestamp = null;
    $signatures = [];

    $parts = explode(",", $header);

    foreach ($parts as $part) {

        $values = explode("=", trim($part), 2);

        if (count($values) !== 2) {
            continue;
        }

        if ($values[0] === "t") {
            $timestamp = $values[1];
        }

        if ($values[0] === "v1") {
            $signatures[] = $values[1];
        }
    }

    if (
        !$timestamp ||
        !ctype_digit($timestamp) ||
        empty($signatures)
    ) {
        return false;
    }

    // Reject old or future webhook requests
    if (abs(time() - (int) $timestamp) > 300) {
        return false;
    }

    // Calculate expected signature
    $signedPayload = $timestamp . "." . $payload;

    $expectedSignature = hash_hmac(
        "sha256",
        $signedPayload,
        $secret
    );

    // Compare signatures securely
    foreach ($signatures as $signature) {

        if (
            hash_equals($expectedSignature, $signature)
        ) {
            return true;
        }
    }

    return false;
}

// Verify signature before processing event
if (
    !verifyStripeSignature(
        $payload,
        $signatureHeader,
        STRIPE_WEBHOOK_SECRET
    )
) {
    http_response_code(400);
    exit("Invalid Stripe signature");
}

// Decode verified JSON
$event = json_decode($payload, true);

if (!is_array($event)) {
    http_response_code(400);
    exit("Invalid JSON event");
}

// Get event information
$eventType = $event["type"] ?? "";

$session = $event["data"]["object"] ?? [];

if (!is_array($session)) {
    http_response_code(400);
    exit("Invalid event data");
}

// Process supported Checkout events
$successEvents = [
    "checkout.session.completed",
    "checkout.session.async_payment_succeeded"
];

$failureEvents = [
    "checkout.session.async_payment_failed",
    "checkout.session.expired"
];

if (
    !in_array($eventType, $successEvents, true) &&
    !in_array($eventType, $failureEvents, true)
) {
    // Ignore unrelated events
    http_response_code(200);
    exit("Event ignored");
}

try {

    // Read order information from Stripe metadata
    $orderId = filter_var(
        $session["metadata"]["order_id"] ?? null,
        FILTER_VALIDATE_INT
    );

    $sessionId = $session["id"] ?? "";

    $sessionUserId = filter_var(
        $session["metadata"]["user_id"] ?? null,
        FILTER_VALIDATE_INT
    );

    if (
        !$orderId ||
        !$sessionUserId ||
        !is_string($sessionId) ||
        $sessionId === ""
    ) {
        throw new Exception("Invalid order metadata");
    }

    // Find the matching order and Stripe session
    $stmt = $conn->prepare(
        "SELECT user_id, total_amount, payment_status
         FROM orders
         WHERE id = ? AND stripe_session_id = ?"
    );

    $stmt->bind_param("is", $orderId, $sessionId);

    $stmt->execute();

    $order = $stmt->get_result()->fetch_assoc();

    $stmt->close();

    if (!$order) {
        throw new Exception("Matching order not found");
    }

    if ((int) $order["user_id"] !== $sessionUserId) {
        throw new Exception("Customer verification failed");
    }

    // Handle successful payments
    if (in_array($eventType, $successEvents, true)) {

        // A completed session is not always paid
        if (($session["payment_status"] ?? "") !== "paid") {
            http_response_code(200);
            exit("Payment is not completed yet");
        }

        // Verify amount and currency
        $expectedAmount = (int) round(
            (float) $order["total_amount"] * 100
        );

        $receivedAmount = (int) ($session["amount_total"] ?? -1);

        $receivedCurrency = strtolower(
            $session["currency"] ?? ""
        );

        if (
            $receivedAmount !== $expectedAmount ||
            $receivedCurrency !== STRIPE_CURRENCY
        ) {
            throw new Exception("Payment amount mismatch");
        }

        $paymentIntentId = $session["payment_intent"] ?? null;

        if (!is_string($paymentIntentId)) {
            $paymentIntentId = null;
        }

        // Update payment only if it is not already paid
        $updateStmt = $conn->prepare(
            "UPDATE orders
             SET payment_status = 'paid',
                 stripe_payment_intent_id = ?
             WHERE id = ?
             AND stripe_session_id = ?
             AND payment_status <> 'paid'"
        );

        $updateStmt->bind_param(
            "sis",
            $paymentIntentId,
            $orderId,
            $sessionId
        );

        $updateStmt->execute();
        $updateStmt->close();
    }

    // Handle failed or expired payments
    if (in_array($eventType, $failureEvents, true)) {

        $updateStmt = $conn->prepare(
            "UPDATE orders
             SET payment_status = 'failed'
             WHERE id = ?
             AND stripe_session_id = ?
             AND payment_status = 'unpaid'"
        );

        $updateStmt->bind_param(
            "is",
            $orderId,
            $sessionId
        );

        $updateStmt->execute();
        $updateStmt->close();
    }

    // Tell Stripe that the event was received
    http_response_code(200);

    echo "Webhook processed successfully";

} catch (Throwable $e) {

    // Log the error without exposing sensitive details
    error_log("Stripe Webhook Error: " . $e->getMessage());

    // A 500 response allows Stripe to retry delivery
    http_response_code(500);

    echo "Webhook processing failed";
}

?>
