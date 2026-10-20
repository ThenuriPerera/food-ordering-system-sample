
<?php
session_start();

require_once "../../config/db.php";

// Allow only logged-in administrators
if (
    !isset($_SESSION["user_id"]) ||
    ($_SESSION["user_role"] ?? "") !== "admin"
) {
    http_response_code(403);
    exit("Access denied. Only administrators can delete food.");
}

// Only accept POST requests
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: foods.php");
    exit;
}

// Verify the CSRF token
$token = $_POST["csrf_token"] ?? "";

if (
    !isset($_SESSION["csrf_token"]) ||
    !is_string($token) ||
    !hash_equals($_SESSION["csrf_token"], $token)
) {
    http_response_code(403);
    exit("Invalid request. Please return to the food list and try again.");
}

// Validate the food ID
$foodId = filter_input(INPUT_POST, "food_id", FILTER_VALIDATE_INT);

if (!$foodId || $foodId < 1) {
    header("Location: foods.php?delete_error=invalid");
    exit;
}

// Do not delete food items used in existing orders
$checkStmt = mysqli_prepare(
    $conn,
    "SELECT COUNT(*) FROM order_items WHERE food_id = ?"
);

mysqli_stmt_bind_param($checkStmt, "i", $foodId);
mysqli_stmt_execute($checkStmt);
mysqli_stmt_bind_result($checkStmt, $orderCount);
mysqli_stmt_fetch($checkStmt);
mysqli_stmt_close($checkStmt);

if ($orderCount > 0) {
    header("Location: foods.php?delete_error=ordered");
    exit;
}

// Delete the selected food
$deleteStmt = mysqli_prepare(
    $conn,
    "DELETE FROM foods WHERE id = ?"
);

mysqli_stmt_bind_param($deleteStmt, "i", $foodId);

if (mysqli_stmt_execute($deleteStmt) && mysqli_stmt_affected_rows($deleteStmt) === 1) {
    mysqli_stmt_close($deleteStmt);
    header("Location: foods.php?deleted=1");
    exit;
}

mysqli_stmt_close($deleteStmt);

header("Location: foods.php?delete_error=failed");
exit;
