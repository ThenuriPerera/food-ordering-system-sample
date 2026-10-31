<?php

session_start();

require_once "../config/db.php";

// Allow only logged-in administrators
if (
    !isset($_SESSION["user_id"]) ||
    !isset($_SESSION["user_role"]) ||
    $_SESSION["user_role"] !== "admin"
) {
    http_response_code(403);
    exit("Access denied. Admin access is required.");
}

// Accept POST requests only
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: categories.php");
    exit;
}

// Validate the CSRF token
$submittedToken = $_POST["csrf_token"] ?? "";
$sessionToken = $_SESSION["csrf_token"] ?? "";

if (
    $sessionToken === "" ||
    !is_string($submittedToken) ||
    !hash_equals($sessionToken, $submittedToken)
) {
    exit("Invalid security token. Please go back and try again.");
}

// Validate the category ID
$categoryId = filter_input(
    INPUT_POST,
    "category_id",
    FILTER_VALIDATE_INT
);

if (!$categoryId || $categoryId < 1) {
    header("Location: categories.php?delete_error=invalid");
    exit;
}

// Check whether this category is used by any food items
$checkStmt = mysqli_prepare(
    $conn,
    "SELECT id FROM foods WHERE category_id = ? LIMIT 1"
);

mysqli_stmt_bind_param($checkStmt, "i", $categoryId);
mysqli_stmt_execute($checkStmt);
mysqli_stmt_store_result($checkStmt);

if (mysqli_stmt_num_rows($checkStmt) > 0) {
    mysqli_stmt_close($checkStmt);

    header("Location: categories.php?delete_error=used");
    exit;
}

mysqli_stmt_close($checkStmt);

// Delete the category
$deleteStmt = mysqli_prepare(
    $conn,
    "DELETE FROM categories WHERE id = ?"
);

mysqli_stmt_bind_param($deleteStmt, "i", $categoryId);

if (mysqli_stmt_execute($deleteStmt)) {

    if (mysqli_stmt_affected_rows($deleteStmt) === 1) {
        header("Location: categories.php?deleted=1");
    } else {
        header("Location: categories.php?delete_error=invalid");
    }

} else {
    header("Location: categories.php?delete_error=failed");
}

mysqli_stmt_close($deleteStmt);
mysqli_close($conn);

exit;
