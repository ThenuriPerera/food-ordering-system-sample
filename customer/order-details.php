<?php

session_start();

require_once "../config/db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

$orderId = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if (!$orderId || $orderId < 1) {
    header("Location: orders.php");
    exit;
}

$userId = (int) $_SESSION["user_id"];
$stmt = mysqli_prepare(
    $conn,
    "SELECT id, total_amount, status, created_at, phone, address
     FROM orders
     WHERE id = ? AND user_id = ?"
);

if (!$stmt) {
    die("Unable to retrieve order details.");
}

mysqli_stmt_bind_param($stmt, "ii", $orderId, $userId);
mysqli_stmt_execute($stmt);
$order = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$order) {
    mysqli_close($conn);
    http_response_code(404);
    die("Order not found.");
}

$itemsStmt = mysqli_prepare(
    $conn,
    "SELECT order_items.quantity, order_items.price,
            COALESCE(foods.name, 'Food item no longer available') AS food_name
     FROM order_items
     LEFT JOIN foods ON order_items.food_id = foods.id
     WHERE order_items.order_id = ?"
);

if (!$itemsStmt) {
    die("Unable to retrieve the items for this order.");
}

mysqli_stmt_bind_param($itemsStmt, "i", $orderId);
mysqli_stmt_execute($itemsStmt);
$itemsResult = mysqli_stmt_get_result($itemsStmt);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Details - Foodie</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body>

<header>
    <div class="logo">Foodie</div>
    <nav>
        <a href="../index.php">Home</a>
        <a href="../menu.html">Menu</a>
        <a href="orders.php">My Orders</a>
        <a href="../auth/profile.php">Profile</a>
        <a href="../auth/logout.php">Logout</a>
    </nav>
</header>

<section class="food-management">
    <div class="food-management-container">
        <h1>Order Details</h1>

        <p><strong>Order ID:</strong> <?= (int) $order["id"] ?></p>
        <p><strong>Status:</strong> <?= htmlspecialchars($order["status"]) ?></p>
        <p><strong>Order Date:</strong> <?= htmlspecialchars($order["created_at"]) ?></p>
        <p><strong>Phone:</strong> <?= htmlspecialchars($order["phone"]) ?></p>
        <p><strong>Delivery Address:</strong> <?= htmlspecialchars($order["address"]) ?></p>

        <h2>Ordered Items</h2>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Food</th>
                        <th>Quantity</th>
                        <th>Unit Price</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($itemsResult) > 0): ?>
                        <?php while ($item = mysqli_fetch_assoc($itemsResult)): ?>
                            <tr>
                                <td><?= htmlspecialchars($item["food_name"]) ?></td>
                                <td><?= (int) $item["quantity"] ?></td>
                                <td>Rs. <?= number_format($item["price"], 2) ?></td>
                                <td>
                                    Rs. <?= number_format(
                                        $item["price"] * $item["quantity"],
                                        2
                                    ) ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4">No items were found for this order.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <h2>Total Amount: Rs. <?= number_format($order["total_amount"], 2) ?></h2>
        <a href="orders.php" class="add-food-button">Back to My Orders</a>
    </div>
</section>

<footer>
    <p>&copy; 2026 Foodie. All Rights Reserved.</p>
</footer>

</body>
</html>

<?php
mysqli_stmt_close($itemsStmt);
mysqli_close($conn);
?>