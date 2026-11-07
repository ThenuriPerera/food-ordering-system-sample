<?php

session_start();

require_once "../config/db.php";

// Initialize the cart if it does not exist
if (!isset($_SESSION["cart"])) {
    $_SESSION["cart"] = [];
}

// Accept POST requests only
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: menu.html");
    exit;
}

// Get food ID and quantity
$foodId = filter_input(
    INPUT_POST,
    "food_id",
    FILTER_VALIDATE_INT
);

$quantity = filter_input(
    INPUT_POST,
    "quantity",
    FILTER_VALIDATE_INT
);

// Validate food ID and quantity
if (!$foodId || $foodId < 1 || !$quantity || $quantity < 1) {
    header("Location: menu.html?error=invalid");
    exit;
}

// Check whether the food item exists
$stmt = mysqli_prepare(
    $conn,
    "SELECT id FROM foods WHERE id = ?"
);

mysqli_stmt_bind_param($stmt, "i", $foodId);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$food = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

if (!$food) {
    mysqli_close($conn);
    header("Location: menu.html?error=not_found");
    exit;
}

// Add the food item to the cart
if (isset($_SESSION["cart"][$foodId])) {

    $_SESSION["cart"][$foodId] += $quantity;

} else {

    $_SESSION["cart"][$foodId] = $quantity;
}

mysqli_close($conn);

// Redirect to shopping cart
header("Location: cart.php?added=1");
exit;

?>
