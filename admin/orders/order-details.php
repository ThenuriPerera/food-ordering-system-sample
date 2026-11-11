<?php
session_start();

require_once "../../config/db.php";

// Check admin access
if (
    !isset($_SESSION["user_id"]) ||
    ($_SESSION["user_role"] ?? "") !== "admin"
) {
    header("Location: ../../auth/login.php");
    exit;
}

// Create CSRF token
if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

// Get order ID
$orderId = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if (!$orderId || $orderId < 1) {
    header("Location: orders.php");
    exit;
}

// Get order information
$stmt = mysqli_prepare(
    $conn,
    "SELECT orders.id, users.name AS customer_name, orders.phone,
            orders.address, orders.total_amount, orders.status,
            orders.created_at
     FROM orders
     INNER JOIN users ON orders.user_id = users.id
     WHERE orders.id = ?"
);

if (!$stmt) {
    die("Unable to retrieve order details.");
}

mysqli_stmt_bind_param($stmt, "i", $orderId);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$order = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

if (!$order) {
    mysqli_close($conn);
    http_response_code(404);
    die("Order not found.");
}

// Get ordered food items
$stmt = mysqli_prepare(
    $conn,
    "SELECT order_items.food_id,
            order_items.quantity,
            order_items.price,
            COALESCE(foods.name, 'Food item no longer available') AS food_name
     FROM order_items
     LEFT JOIN foods ON order_items.food_id = foods.id
     WHERE order_items.order_id = ?"
);

mysqli_stmt_bind_param($stmt, "i", $orderId);
mysqli_stmt_execute($stmt);

$itemsResult = mysqli_stmt_get_result($stmt);

$allowedStatuses = [
    "Pending",
    "Processing",
    "Completed",
    "Cancelled"
];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Order Details - Foodie</title>

    <link rel="stylesheet" href="../../assets/css/style.css">
</head>

<body>

<header>
    <div class="logo">Foodie Admin</div>

    <nav>
        <a href="../dashboard.php">Dashboard</a>
        <a href="orders.php">Orders</a>
        <a href="../../auth/logout.php">Logout</a>
    </nav>
</header>

<section class="food-management">

    <div class="food-management-container">

        <h1>Order Details</h1>

        <?php if (isset($_GET["updated"])): ?>
            <p class="success-message">
                Order status updated successfully.
            </p>
        <?php endif; ?>

        <h2>Customer Information</h2>

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

        <p>
            <strong>Order Date:</strong>
            <?= htmlspecialchars($order["created_at"]) ?>
        </p>

        <h2>Ordered Food Items</h2>

        <div class="table-container">

            <table>

                <thead>
                    <tr>
                        <th>Food Name</th>
                        <th>Quantity</th>
                        <th>Unit Price</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>

                <tbody>

                    <?php while ($item = mysqli_fetch_assoc($itemsResult)): ?>

                        <tr>

                            <td>
                                <?= htmlspecialchars($item["food_name"]) ?>
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
            Total Amount:
            Rs. <?= number_format($order["total_amount"], 2) ?>
        </h2>

        <h2>Update Order Status</h2>

        <p>
            <strong>Current Status:</strong>
            <?= htmlspecialchars($order["status"]) ?>
        </p>

        <form action="update-order-status.php" method="POST">

            <input
                type="hidden"
                name="csrf_token"
                value="<?= htmlspecialchars($_SESSION["csrf_token"]) ?>"
            >

            <input
                type="hidden"
                name="order_id"
                value="<?= (int) $order["id"] ?>"
            >

            <label for="status">Select New Status:</label>

            <select name="status" id="status" required>

                <?php if (!in_array($order["status"], $allowedStatuses, true)): ?>
                    <option
                        value="<?= htmlspecialchars($order["status"]) ?>"
                        selected
                    >
                        <?= htmlspecialchars($order["status"]) ?>
                    </option>
                <?php endif; ?>

                <?php foreach ($allowedStatuses as $status): ?>

                    <option
                        value="<?= htmlspecialchars($status) ?>"
                        <?= $order["status"] === $status ? "selected" : "" ?>
                    >
                        <?= htmlspecialchars($status) ?>
                    </option>

                <?php endforeach; ?>

            </select>

            <button type="submit">
                Update Status
            </button>

        </form>

        <br>

        <a href="orders.php" class="add-food-button">
            Back to Orders
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