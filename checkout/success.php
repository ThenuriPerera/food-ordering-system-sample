<?php

session_start();

require_once "config/db.php";

// Check customer login
if (!isset($_SESSION["user_id"])) {
    header("Location: auth/login.php");
    exit;
}

// Get order ID
$orderId = filter_input(INPUT_GET, "order_id", FILTER_VALIDATE_INT);

if (!$orderId || $orderId < 1) {
    header("Location: index.html");
    exit;
}

// Retrieve order information
$stmt = mysqli_prepare(
    $conn,
    "SELECT id, customer_name, phone, address,
            total_amount, status, created_at
     FROM orders
     WHERE id = ? AND user_id = ?"
);

mysqli_stmt_bind_param(
    $stmt,
    "ii",
    $orderId,
    $_SESSION["user_id"]
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$order = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

// Check order exists
if (!$order) {
    mysqli_close($conn);
    die("Order not found.");
}

// Retrieve ordered food items
$stmt = mysqli_prepare(
    $conn,
    "SELECT order_items.quantity,
            order_items.price,
            foods.name
     FROM order_items
     INNER JOIN foods
     ON order_items.food_id = foods.id
     WHERE order_items.order_id = ?"
);

mysqli_stmt_bind_param($stmt, "i", $orderId);
mysqli_stmt_execute($stmt);

$itemsResult = mysqli_stmt_get_result($stmt);

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Order Successful - Foodie</title>

    <link rel="stylesheet"
          href="assets/css/style.css">
</head>

<body>

<header>
    <div class="logo">Foodie</div>

    <nav>
        <a href="index.html">Home</a>
        <a href="menu.html">Menu</a>
        <a href="auth/profile.php">My Profile</a>
    </nav>
</header>

<section class="food-management">

    <div class="food-management-container">

        <h1>Order Placed Successfully!</h1>

        <p>Thank you for ordering with Foodie.</p>

        <h2>Order Details</h2>

        <p>
            <strong>Order ID:</strong>
            <?= (int) $order["id"] ?>
        </p>

        <p>
            <strong>Customer Name:</strong>
            <?= htmlspecialchars($order["customer_name"]) ?>
        </p>

        <p>
            <strong>Phone:</strong>
            <?= htmlspecialchars($order["phone"]) ?>
        </p>

        <p>
            <strong>Delivery Address:</strong>
            <?= htmlspecialchars($order["address"]) ?>
        </p>

        <h2>Ordered Items</h2>

        <div class="table-container">

            <table>

                <thead>
                    <tr>
                        <th>Food</th>
                        <th>Quantity</th>
                        <th>Price</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>

                <tbody>

                    <?php while ($item = mysqli_fetch_assoc($itemsResult)): ?>

                        <tr>

                            <td>
                                <?= htmlspecialchars($item["name"]) ?>
                            </td>

                            <td>
                                <?= (int) $item["quantity"] ?>
                            </td>

                            <td>
                                Rs. <?= number_format($item["price"], 2) ?>
                            </td>

                            <td>
                                Rs. <?= number_format(
                                    $item["price"] * $item["quantity"],
                                    2
                                ) ?>
                            </td>

                        </tr>

                    <?php endwhile; ?>

                </tbody>

            </table>

        </div>

        <h2>
            Total Amount: Rs.
            <?= number_format($order["total_amount"], 2) ?>
        </h2>

        <p>
            <strong>Order Status:</strong>
            <?= htmlspecialchars($order["status"]) ?>
        </p>

        <p>
            <strong>Order Date:</strong>
            <?= htmlspecialchars($order["created_at"]) ?>
        </p>

        <a href="menu.html" class="add-food-button">
            Continue Shopping
        </a>

    </div>

</section>

<footer>
    <p>&copy; 2026 Foodie. All Rights Reserved.</p>
</footer>

</body>
</html>

<?php

mysqli_stmt_close($stmt);
mysqli_close($conn);

?>