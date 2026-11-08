<?php

session_start();

require_once "../config/db.php";

// Initialize the shopping cart
if (!isset($_SESSION["cart"])) {
    $_SESSION["cart"] = [];
}

// Calculate the total price
$totalPrice = 0;
$totalItems = 0;

$cartItems = [];

foreach ($_SESSION["cart"] as $foodId => $quantity) {

    $stmt = mysqli_prepare(
        $conn,
        "SELECT id, name, price
         FROM foods
         WHERE id = ?"
    );

    mysqli_stmt_bind_param($stmt, "i", $foodId);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    $food = mysqli_fetch_assoc($result);

    mysqli_stmt_close($stmt);

    if ($food) {

        $food["quantity"] = $quantity;

        $subtotal = $food["price"] * $quantity;

        $food["subtotal"] = $subtotal;

        $totalPrice += $subtotal;
        $totalItems += $quantity;

        $cartItems[] = $food;
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Shopping Cart - Foodie</title>

    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>

<header>

    <div class="logo">
        Foodie
    </div>

    <nav>
        <a href="index.html">Home</a>
        <a href="menu.html">Menu</a>
        <a href="cart.php">Cart (<?= $totalItems ?>)</a>
    </nav>

</header>

<section class="food-management">

    <div class="food-management-container">

        <h1>My Shopping Cart</h1>

        <?php if (isset($_GET["updated"])): ?>
            <p>Cart quantity updated successfully!</p>
        <?php endif; ?>

        <?php if (isset($_GET["removed"])): ?>
            <p>Item removed from your cart successfully!</p>
        <?php endif; ?>

        <?php if (isset($_GET["error"])): ?>
            <p>Unable to update your cart. Please try again.</p>
        <?php endif; ?>

        <p>Review your selected food items.</p>

        <?php if (count($cartItems) > 0): ?>

            <div class="table-container">

                <table>

                    <thead>
                        <tr>
                            <th>Food Name</th>
                            <th>Price</th>
                            <th>Quantity</th>
                            <th>Subtotal</th>
                            <th>Action</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php foreach ($cartItems as $item): ?>

                            <tr>

                                <td>
                                    <?= htmlspecialchars($item["name"]) ?>
                                </td>

                                <td>
                                    <form action="remove-from-cart.php" method="POST"
                                        onsubmit="return confirm('Remove this item from your cart?');">

                                        <input
                                            type="hidden"
                                            name="food_id"
                                            value="<?= (int) $item["id"] ?>"
                                        >

                                        <button type="submit">Remove</button>
                                    </form>
                                </td>

                                <td>
                                    Rs. <?= number_format($item["price"], 2) ?>
                                </td>

                                <td>
                                     <form action="update-cart.php" method="POST">
                                        <input
                                            type="hidden"
                                            name="food_id"
                                            value="<?= (int) $item["id"] ?>"
                                        >

                                        <input
                                            type="number"
                                            name="quantity"
                                            value="<?= (int) $item["quantity"] ?>"
                                            min="1"
                                            required
                                        >

                                        <button type="submit">Update</button>
                                    </form>
                                </td>

                                <td>
                                    Rs. <?= number_format($item["subtotal"], 2) ?>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

            <h2>
                Total: Rs. <?= number_format($totalPrice, 2) ?>
            </h2>

        <?php else: ?>

            <p>Your shopping cart is empty.</p>

            <a href="menu.html" class="add-food-button">
                Browse Food Menu
            </a>

        <?php endif; ?>

    </div>

</section>

<footer>
    <p>&copy; 2026 Foodie. All Rights Reserved.</p>
</footer>

</body>
</html>

<?php
mysqli_close($conn);
?>
