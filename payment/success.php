
<?php

session_start();

require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/../config/stripe_api.php";

// Customer must be logged in
if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

$userId = (int) $_SESSION["user_id"];

$sessionId = $_GET["session_id"] ?? "";

if (
    !is_string($sessionId) ||
    !preg_match('/^cs_(test|live)_[A-Za-z0-9]+$/', $sessionId)
) {
    die("Invalid payment session.");
}

try {

    // Retrieve Checkout Session from Stripe
    $stripeSession = stripeApiRequest(
        "GET",
        "/checkout/sessions/" . rawurlencode($sessionId)
    );

    // Verify payment
    if (
        ($stripeSession["payment_status"] ?? "") !== "paid" ||
        ($stripeSession["mode"] ?? "") !== "payment"
    ) {
        die("Payment has not been completed.");
    }

    // Verify customer and order information
    $orderId = (int) ($stripeSession["metadata"]["order_id"] ?? 0);

    $stripeUserId = (int) (
        $stripeSession["metadata"]["user_id"] ?? 0
    );

    if (
        $orderId <= 0 ||
        $stripeUserId !== $userId ||
        (int) ($stripeSession["client_reference_id"] ?? 0) !== $userId
    ) {
        die("Payment information does not match your account.");
    }

    // Find the order belonging to this customer
    $stmt = $conn->prepare(
        "SELECT total_amount, payment_status, stripe_session_id
         FROM orders
         WHERE id = ? AND user_id = ?"
    );

    $stmt->bind_param("ii", $orderId, $userId);
    $stmt->execute();

    $order = $stmt->get_result()->fetch_assoc();

    $stmt->close();

    if (!$order || $order["stripe_session_id"] !== $sessionId) {
        die("Order information could not be verified.");
    }

    // Verify amount and currency
    $expectedAmount = (int) round((float) $order["total_amount"] * 100);

    if (
        (int) ($stripeSession["amount_total"] ?? -1) !== $expectedAmount ||
        strtolower($stripeSession["currency"] ?? "") !== STRIPE_CURRENCY
    ) {
        die("Payment amount verification failed.");
    }

    // Update payment information
    $paymentIntentId = $stripeSession["payment_intent"] ?? null;

    $updateStmt = $conn->prepare(
        "UPDATE orders
         SET payment_status = 'paid',
             stripe_payment_intent_id = ?
         WHERE id = ?
         AND user_id = ?
         AND stripe_session_id = ?"
    );

    $updateStmt->bind_param(
        "siis",
        $paymentIntentId,
        $orderId,
        $userId,
        $sessionId
    );

    $updateStmt->execute();
    $updateStmt->close();

    // Clear cart only after payment verification
    unset($_SESSION["cart"]);

    // Go to existing order success page
    header(
        "Location: ../order-success.php?order_id=" . $orderId
    );
    exit;

} catch (Throwable $e) {

    error_log("Stripe Verification Error: " . $e->getMessage());

    die("Unable to verify payment. Please contact the administrator.");
}
