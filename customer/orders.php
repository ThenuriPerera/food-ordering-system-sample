<?php

session_start();

require_once "../config/db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

$userId = (int) $_SESSION["user_id"];
$stmt = mysqli_prepare(
    $conn,
    "SELECT id, total_amount, status, created_at
     FROM orders
     WHERE user_id = ?
     ORDER BY id DESC"
);

if (!$stmt) {
    die("Unable to retrieve your orders.");
}

mysqli_stmt_bind_param($stmt, "i", $userId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - Foodie</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body>

<header>
    <div class="logo">Foodie</div>
    <nav>
        <a href="../index.php">Home</a>
        <a href="../menu.html">Menu</a>
        <a href="../auth/profile.php">Profile</a>
        <a href="../auth/logout.php">Logout</a>
    </nav>
</header>

<section class="food-management">
    <div class="food-management-container">
        <h1>My Orders</h1>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Order ID</th>
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
                                <td><?= (int) $order["id"] ?></td>
                                <td>Rs. <?= number_format($order["total_amount"], 2) ?></td>
                                <td><?= htmlspecialchars($order["status"]) ?></td>
                                <td><?= htmlspecialchars($order["created_at"]) ?></td>
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
                            <td colspan="5">You have not placed any orders yet.</td>
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