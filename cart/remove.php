<?php

session_start();

// Accept POST requests only
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: cart.php");
    exit;
}

// Get food ID
$foodId = filter_input(INPUT_POST, "food_id", FILTER_VALIDATE_INT);

// Validate food ID
if (!$foodId || $foodId < 1) {
    header("Location: cart.php?error=invalid");
    exit;
}

// Remove food from cart
if (isset($_SESSION["cart"][$foodId])) {
    unset($_SESSION["cart"][$foodId]);
}

// Redirect to cart
header("Location: cart.php?removed=1");
exit;

?>