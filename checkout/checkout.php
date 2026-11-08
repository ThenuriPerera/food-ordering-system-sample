<?php

session_start();

require_once "../config/db.php";

// Check whether the cart exists
if (!isset($_SESSION["cart"]) || empty($_SESSION["cart"])) {
    header("Location: cart.php?error=empty");
    exit;
}

// Calculate cart total
$totalPrice = 0;
$totalItems = 0;
$cartItems = [];

foreach ($_SESSION["cart"] as $foodId => $quantity) {

    $stmt = mysqli_prepare(
        $conn,
        "SELECT id, name, price FROM foods WHERE id = ?"
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

// Check whether valid food items remain
if (empty($cartItems)) {
    header("Location: cart.php?error=empty");
    exit;
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Checkout - Foodie</title>

    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>

    <header>
        <div class="logo">Foodie</div>

        <nav>
            <a href="index.html">Home</a>
            <a href="menu.html">Menu</a>
            <a href="cart.php">My Cart</a>
        </nav>
    </header>

    <section class="food-management">

        <div class="food-management-container">

            <h1>Checkout</h1>

            <p>Complete your order below.</p>

            <h2>Order Summary</h2>

          <div class="table-container">
              <table>
                  <thead>
                      <tr>
                          <th>Food Name</th>
                          <th>Price</th>
                          <th>Quantity</th>
                          <th>Subtotal</th>
                      </tr>
                  </thead>

                  <tbody>
                      <?php foreach ($cartItems as $item): ?>
                          <tr>
                              <td>
                                  <?= htmlspecialchars($item["name"]) ?>
                              </td>

                              <td>
                                  Rs. <?= number_format($item["price"], 2) ?>
                              </td>

                              <td>
                                  <?= (int) $item["quantity"] ?>
                              </td>

                              <td>
                                  Rs. <?= number_format($item["subtotal"], 2) ?>
                              </td>
                          </tr>
                      <?php endforeach; ?>
                  </tbody>
              </table>
          </div>
        <div>
          <h2>
              Total Amount: Rs. <?= number_format($totalPrice, 2) ?>
          </h2>

        </div>
        <h2>Delivery Information</h2>

        <form action="checkout.php" method="POST">

            <label for="customer_name">Full Name:</label>

            <input
                type="text"
                id="customer_name"
                name="customer_name"
                required
            >

            <label for="phone">Phone Number:</label>

            <input
                type="tel"
                id="phone"
                name="phone"
                required
            >

            <label for="address">Delivery Address:</label>

            <textarea
                id="address"
                name="address"
                rows="4"
                required
            ></textarea>

            <button type="submit" name="place_order">
                Place Order
            </button>

        </form>

      </section>

    <footer>
        <p>&copy; 2026 Foodie. All Rights Reserved.</p>
    </footer>

</body>
</html>

<?php
mysqli_close($conn);
?>