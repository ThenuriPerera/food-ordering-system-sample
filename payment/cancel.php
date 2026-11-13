<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

$orderId = filter_input(INPUT_GET, "order_id", FILTER_VALIDATE_INT);

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Payment Cancelled</title>
</head>

<body>

    <h1>Payment Cancelled</h1>

    <p>Your payment was not completed.</p>

    <p>Your order has not been marked as paid.</p>

    <?php if ($orderId): ?>
        <p>Order ID: <?php echo htmlspecialchars((string) $orderId); ?></p>
    <?php endif; ?>

    <a href="../checkout.php">Return to Checkout</a>

    <br><br>

    <a href="../menu.html">Continue Shopping</a>

</body>

</html>