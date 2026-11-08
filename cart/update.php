<?php

session_start();

// Accept POST requests only
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: cart.php");
    exit;
}

// Get food ID and quantity
$foodId = filter_input(INPUT_POST, "food_id", FILTER_VALIDATE_INT);
$quantity = filter_input(INPUT_POST, "quantity", FILTER_VALIDATE_INT);

// Validate input
if (!$foodId || $foodId < 1 || $quantity === false || $quantity < 1) {
    header("Location: cart.php?error=invalid");
    exit;
}

// Check whether the food exists in the cart
if (!isset($_SESSION["cart"][$foodId])) {
    header("Location: cart.php?error=not_found");
    exit;
}

// Update quantity
$_SESSION["cart"][$foodId] = $quantity;

// Redirect to cart
header("Location: cart.php?updated=1");
exit;

?>