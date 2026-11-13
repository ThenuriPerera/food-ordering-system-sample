
<?php

session_start();

require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/../config/stripe_api.php";

// Check customer login
if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

// Accept POST only
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../checkout.php");
    exit;
}

// Verify CSRF token
if (
    !isset($_POST["csrf_token"], $_SESSION["csrf_token"]) ||
    !hash_equals($_SESSION["csrf_token"], $_POST["csrf_token"])
) {
    die("Invalid request. Please refresh and try again.");
}

// Get customer information
$userId = (int) $_SESSION["user_id"];

$customerName = trim($_POST["customer_name"] ?? "");
$phone = trim($_POST["phone"] ?? "");
$address = trim($_POST["address"] ?? "");

if (
    $customerName === "" ||
    $phone === "" ||
    $address === ""
) {
    die("Please complete all checkout fields.");
}

if (
    strlen($customerName) > 100 ||
    strlen($address) > 255 ||
    !preg_match('/^[0-9+\-\s()]{7,20}$/', $phone)
) {
    die("Please enter valid customer information.");
}

// Check cart
if (empty($_SESSION["cart"])) {
    die("Your shopping cart is empty.");
}

$cartItems = [];
$totalAmount = 0;

try {

    // Get current food prices from database
    $foodStmt = $conn->prepare(
        "SELECT id, name, price
         FROM foods
         WHERE id = ?"
    );

    foreach ($_SESSION["cart"] as $foodId => $quantity) {

        $foodId = (int) $foodId;
        $quantity = (int) $quantity;

        if ($foodId <= 0 || $quantity <= 0 || $quantity > 99) {
            throw new Exception("Invalid cart item.");
        }

        $foodStmt->bind_param("i", $foodId);
        $foodStmt->execute();

        $food = $foodStmt->get_result()->fetch_assoc();

        if (!$food) {
            throw new Exception("A food item is no longer available.");
        }

        $price = (float) $food["price"];
        $subtotal = $price * $quantity;

        $cartItems[] = [
            "food_id" => $foodId,
            "name" => $food["name"],
            "price" => $price,
            "quantity" => $quantity,
            "subtotal" => $subtotal
        ];

        $totalAmount += $subtotal;
    }

    $foodStmt->close();

    if (empty($cartItems) || $totalAmount <= 0) {
        throw new Exception("Invalid order total.");
    }

    // Create order and order items in a transaction
    $conn->begin_transaction();

    $status = "Pending";

    $orderStmt = $conn->prepare(
        "INSERT INTO orders
        (user_id, customer_name, total_amount, status, address, phone)
        VALUES (?, ?, ?, ?, ?, ?)"
    );

    $orderStmt->bind_param(
        "isdsss",
        $userId,
        $customerName,
        $totalAmount,
        $status,
        $address,
        $phone
    );

    $orderStmt->execute();

    $orderId = $conn->insert_id;

    $orderStmt->close();

    $itemStmt = $conn->prepare(
        "INSERT INTO order_items
        (order_id, food_id, quantity, price)
        VALUES (?, ?, ?, ?)"
    );

    foreach ($cartItems as $item) {

        $foodId = $item["food_id"];
        $quantity = $item["quantity"];
        $price = $item["price"];

        $itemStmt->bind_param(
            "iiid",
            $orderId,
            $foodId,
            $quantity,
            $price
        );

        $itemStmt->execute();
    }

    $itemStmt->close();

    $conn->commit();

    // Prepare Stripe line items
    $lineItems = [];

    foreach ($cartItems as $index => $item) {

        $lineItems["line_items[$index][price_data][currency]"]
            = STRIPE_CURRENCY;

        $lineItems["line_items[$index][price_data][product_data][name]"]
            = $item["name"];

        $lineItems["line_items[$index][price_data][unit_amount]"]
            = (int) round($item["price"] * 100);

        $lineItems["line_items[$index][quantity]"]
            = $item["quantity"];
    }

    // Create Checkout Session parameters
    $payload = array_merge($lineItems, [

        "mode" => "payment",

        "success_url" =>
            BASE_URL . "/payments/success.php?session_id={CHECKOUT_SESSION_ID}",

        "cancel_url" =>
            BASE_URL . "/payments/cancel.php?order_id=" . $orderId,

        "client_reference_id" => (string) $userId,

        "metadata[order_id]" => (string) $orderId,

        "metadata[user_id]" => (string) $userId,

        "payment_intent_data[metadata][order_id]"
            => (string) $orderId

    ]);

    // Add customer email if available
    if (!empty($_SESSION["user_email"])) {
        $payload["customer_email"] = $_SESSION["user_email"];
    }

    // Send request to Stripe
    $stripeSession = stripeApiRequest(
        "POST",
        "/checkout/sessions",
        $payload,
        "foodie-order-" . $orderId
    );

    if (empty($stripeSession["id"]) || empty($stripeSession["url"])) {
        throw new Exception("Stripe did not return a Checkout URL.");
    }

    $sessionId = $stripeSession["id"];

    // Save Stripe session ID
    $updateStmt = $conn->prepare(
        "UPDATE orders
         SET stripe_session_id = ?
         WHERE id = ? AND user_id = ?"
    );

    $updateStmt->bind_param(
        "sii",
        $sessionId,
        $orderId,
        $userId
    );

    $updateStmt->execute();

    $updateStmt->close();

    // Redirect customer to Stripe Checkout
    header("Location: " . $stripeSession["url"], true, 303);
    exit;

} catch (Throwable $e) {

    if ($conn->errno) {
        $conn->rollback();
    }

    error_log("Stripe Checkout Error: " . $e->getMessage());

    die(
        "Unable to start payment. Please check your information "
        . "or contact the administrator."
    );
}
