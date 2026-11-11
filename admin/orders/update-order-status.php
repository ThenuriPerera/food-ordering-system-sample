<?php

session_start();

require_once "../../config/db.php";

if (!isset($_SESSION["user_id"]) || ($_SESSION["user_role"] ?? "") !== "admin") {
    header("Location: ../../auth/login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: orders.php");
    exit;
}

if (
    !isset($_POST["csrf_token"]) ||
    !is_string($_POST["csrf_token"]) ||
    !isset($_SESSION["csrf_token"]) ||
    !hash_equals($_SESSION["csrf_token"], $_POST["csrf_token"])
) {
    header("Location: orders.php?error=invalid_request");
    exit;
}

$orderId = filter_input(INPUT_POST, "order_id", FILTER_VALIDATE_INT);
$status = trim($_POST["status"] ?? "");

$allowedStatuses = [
    "Pending",
    "Processing",
    "Completed",
    "Cancelled"
];

if (!$orderId || $orderId < 1 || !in_array($status, $allowedStatuses, true)) {
    header("Location: orders.php?error=invalid_status");
    exit;
}

$checkStmt = mysqli_prepare($conn, "SELECT id FROM orders WHERE id = ?");
if (!$checkStmt) {
    die("Unable to verify the order.");
}

mysqli_stmt_bind_param($checkStmt, "i", $orderId);
mysqli_stmt_execute($checkStmt);
$orderExists = mysqli_stmt_get_result($checkStmt);
$foundOrder = mysqli_fetch_assoc($orderExists);
mysqli_stmt_close($checkStmt);

if (!$foundOrder) {
    header("Location: orders.php?error=not_found");
    exit;
}

$stmt = mysqli_prepare(
    $conn,
    "UPDATE orders SET status = ? WHERE id = ?"
);

if (!$stmt) {
    die("Unable to prepare the order status update.");
}

mysqli_stmt_bind_param($stmt, "si", $status, $orderId);

if (!mysqli_stmt_execute($stmt)) {
    mysqli_stmt_close($stmt);
    mysqli_close($conn);
    die("Unable to update the order status.");
}

mysqli_stmt_close($stmt);
mysqli_close($conn);

header("Location: order-details.php?id=" . (int) $orderId . "&updated=1");
exit;
