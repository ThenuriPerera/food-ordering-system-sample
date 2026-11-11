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

// Search orders
$search = isset($_GET["search"]) && is_string($_GET["search"])
    ? trim($_GET["search"])
    : "";

if ($search !== "") {
    $searchValue = "%" . $search . "%";

    $stmt = mysqli_prepare(
        $conn,
        "SELECT orders.id, users.name AS customer_name, orders.phone,
                orders.total_amount, orders.status, orders.created_at
         FROM orders
         INNER JOIN users ON orders.user_id = users.id
         WHERE users.name LIKE ?
            OR CAST(orders.id AS CHAR) LIKE ?
         ORDER BY orders.id DESC"
    );

    mysqli_stmt_bind_param(
        $stmt,
        "ss",
        $searchValue,
        $searchValue
    );
} else {
    $stmt = mysqli_prepare(
        $conn,
        "SELECT orders.id, users.name AS customer_name, orders.phone,
                orders.total_amount, orders.status, orders.created_at
         FROM orders
         INNER JOIN users ON orders.user_id = users.id
         ORDER BY orders.id DESC"
    );
}

if (!$stmt) {
    die("Unable to retrieve orders.");
}

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$errorMessages = [
    "invalid_request" => "Your request could not be verified. Reload the page and try again.",
    "invalid_status" => "The order or selected status is invalid.",
    "not_found" => "The selected order could not be found.",
];
$errorCode = isset($_GET["error"]) && is_string($_GET["error"])
    ? $_GET["error"]
    : "";
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Manage Orders - Foodie</title>

    <link rel="stylesheet" href="../../assets/css/style.css">
</head>

<body>

<header>
    <div class="logo">Foodie Admin</div>

    <nav>
        <a href="../dashboard.php">Dashboard</a>
        <a href="../foods/foods.php">Foods</a>
        <a href="../categories/categories.php">Categories</a>
        <a href="orders.php">Orders</a>
        <a href="../../auth/logout.php">Logout</a>
    </nav>
</header>

<section class="food-management">

    <div class="food-management-container">

        <h1>Manage Orders</h1>

        <p>View and manage customer orders.</p>

        <?php if (isset($_GET["updated"])): ?>
            <p class="success-message">
                Order status updated successfully.
            </p>
        <?php endif; ?>

        <?php if (isset($errorMessages[$errorCode])): ?>
            <p class="form-message error">
                <?= htmlspecialchars($errorMessages[$errorCode]) ?>
            </p>
        <?php endif; ?>

        <!-- Search Form -->
        <form action="orders.php" method="GET">

            <input
                type="text"
                name="search"
                placeholder="Search by order ID or customer name"
                value="<?= htmlspecialchars($search) ?>"
            >

            <button type="submit">Search</button>

            <a href="orders.php">Clear</a>

        </form>

        <div class="table-container">

            <table>

                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Customer Name</th>
                        <th>Phone</th>
                        <th>Total Amount</th>
                        <th>Status</th>
                        <th>Order Date</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody>

                    <?php if (mysqli_num_rows($result) > 0): ?>

                        <?php while ($order = mysqli_fetch_assoc($result)): ?>

                            <tr>

                                <td>
                                    <?= (int) $order["id"] ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($order["customer_name"]) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($order["phone"]) ?>
                                </td>

                                <td>
                                    Rs. <?= number_format($order["total_amount"], 2) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($order["status"]) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($order["created_at"]) ?>
                                </td>

                                <td>
                                    <a
                                        href="order-details.php?id=<?= (int) $order["id"] ?>"
                                        class="add-food-button"
                                    >
                                        View Details
                                    </a>
                                </td>

                            </tr>

                        <?php endwhile; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="7">No orders found.</td>
                        </tr>

                    <?php endif; ?>

                </tbody>

            </table>

        </div>

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