<?php

session_start();

require_once "../config/db.php";

if (!isset($_SESSION["user_id"]) || ($_SESSION["user_role"] ?? "") !== "admin") {
    header("Location: login.php");
    exit;
}

$stats = [
    "foods" => 0,
    "categories" => 0,
    "orders" => 0,
    "pending_orders" => 0,
    "revenue" => 0.0,
];

foreach (
    [
        "SELECT COUNT(*) AS total FROM foods",
        "SELECT COUNT(*) AS total FROM categories",
        "SELECT COUNT(*) AS total FROM orders",
        "SELECT COUNT(*) AS total FROM orders WHERE status = 'Pending'",
        "SELECT COALESCE(SUM(total_amount), 0) AS total FROM orders"
    ] as $sql
) {
    $result = mysqli_query($conn, $sql);
    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        if ($sql === "SELECT COUNT(*) AS total FROM foods") {
            $stats["foods"] = (int) ($row["total"] ?? 0);
        } elseif ($sql === "SELECT COUNT(*) AS total FROM categories") {
            $stats["categories"] = (int) ($row["total"] ?? 0);
        } elseif ($sql === "SELECT COUNT(*) AS total FROM orders") {
            $stats["orders"] = (int) ($row["total"] ?? 0);
        } elseif ($sql === "SELECT COUNT(*) AS total FROM orders WHERE status = 'Pending'") {
            $stats["pending_orders"] = (int) ($row["total"] ?? 0);
        } else {
            $stats["revenue"] = (float) ($row["total"] ?? 0);
        }
        mysqli_free_result($result);
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Foodie</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body>

<header>
    <div class="logo">Foodie Admin</div>
    <nav>
        <a href="dashboard.php">Dashboard</a>
        <a href="foods/foods.php">Foods</a>
        <a href="categories/categories.php">Categories</a>
        <a href="orders/orders.php">Orders</a>
        <a href="../auth/logout.php">Logout</a>
    </nav>
</header>

<section class="food-management">
    <div class="food-management-container">
        <h1>Admin Dashboard</h1>
        <p>Welcome back, <?= htmlspecialchars($_SESSION["user_name"] ?? "Admin") ?>.</p>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Metric</th>
                        <th>Value</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Total Foods</td>
                        <td><?= (int) $stats["foods"] ?></td>
                    </tr>
                    <tr>
                        <td>Total Categories</td>
                        <td><?= (int) $stats["categories"] ?></td>
                    </tr>
                    <tr>
                        <td>Total Orders</td>
                        <td><?= (int) $stats["orders"] ?></td>
                    </tr>
                    <tr>
                        <td>Pending Orders</td>
                        <td><?= (int) $stats["pending_orders"] ?></td>
                    </tr>
                    <tr>
                        <td>Revenue</td>
                        <td>Rs. <?= number_format($stats["revenue"], 2) ?></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div style="display: flex; gap: 16px; flex-wrap: wrap; margin-top: 24px;">
            <a href="foods/foods.php" class="add-food-button">Manage Foods</a>
            <a href="categories/categories.php" class="add-food-button">Manage Categories</a>
            <a href="orders/orders.php" class="add-food-button">Manage Orders</a>
        </div>
    </div>
</section>

<footer>
    <p>&copy; 2026 Foodie. All Rights Reserved.</p>
</footer>

</body>
</html>

<?php mysqli_close($conn); ?>
