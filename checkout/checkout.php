<?php

session_start();

require_once "../config/db.php";

// 1. Check whether the customer is logged in
if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php?next=checkout");
    exit;
}

// 2. Check whether the cart is empty
if (!isset($_SESSION["cart"]) || empty($_SESSION["cart"])) {
    header("Location: ../cart/cart.php?error=empty");
    exit;
}

// 3. Create a CSRF token
if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

// 4. Load cart items from the database
function getCheckoutItems($conn)
{
    $items = [];
    $totalPrice = 0;

    foreach ($_SESSION["cart"] as $foodId => $quantity) {
        $foodId = (int) $foodId;
        $quantity = (int) $quantity;

        if ($foodId < 1 || $quantity < 1) {
            continue;
        }

        $stmt = mysqli_prepare(
            $conn,
            "SELECT id, name, price FROM foods WHERE id = ?"
        );

        mysqli_stmt_bind_param($stmt, "i", $foodId);
        mysqli_stmt_execute($stmt);

        $result = mysqli_stmt_get_result($stmt);
        $food = mysqli_fetch_assoc($result);

        mysqli_stmt_close($stmt);

        if (!$food) {
            continue;
        }

        $food["quantity"] = $quantity;
        $food["subtotal"] = $food["price"] * $quantity;

        $totalPrice += $food["subtotal"];
        $items[] = $food;
    }

    return [
        "items" => $items,
        "total" => $totalPrice
    ];
}

$cartData = getCheckoutItems($conn);
$cartItems = $cartData["items"];
$totalPrice = $cartData["total"];

if (empty($cartItems)) {
    header("Location: ../cart/cart.php?error=invalid");
    exit;
}

// 5. Process the order when the form is submitted
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["place_order"])) {

    // Validate CSRF token
    if (
        !isset($_POST["csrf_token"]) ||
        !hash_equals($_SESSION["csrf_token"], $_POST["csrf_token"])
    ) {
        $error = "Invalid request. Please try again.";
    } else {

        // Get customer information
        $phone = trim($_POST["phone"] ?? "");
        $address = trim($_POST["address"] ?? "");

        // Validate customer information
        if ($phone === "" || $address === "") {
            $error = "Please fill in all delivery information.";
        } elseif (strlen($phone) > 20 || !preg_match('/^[0-9+\-\s()]{7,20}$/', $phone)) {
            $error = "Please enter a valid phone number.";
        } elseif (strlen($address) > 255) {
            $error = "Delivery address must not exceed 255 characters.";
        } else {

            // Recalculate cart information before saving
            $cartData = getCheckoutItems($conn);
            $cartItems = $cartData["items"];
            $totalPrice = $cartData["total"];

            if (empty($cartItems)) {
                $error = "Your cart is empty or contains unavailable food items.";
            } else {

                // Start database transaction
                mysqli_begin_transaction($conn);

                try {

                    // 6. Save order information
                    $userId = (int) $_SESSION["user_id"];
                    $status = "Pending";

                    $stmt = mysqli_prepare(
                        $conn,
                        "INSERT INTO orders
                        (user_id, total_amount, status, address, phone)
                        VALUES (?, ?, ?, ?, ?)"
                    );

                    mysqli_stmt_bind_param(
                        $stmt,
                        "idsss",
                        $userId,
                        $totalPrice,
                        $status,
                        $address,
                        $phone
                    );

                    if (!mysqli_stmt_execute($stmt)) {
                        throw new Exception("Could not save order.");
                    }

                    $orderId = mysqli_insert_id($conn);
                    mysqli_stmt_close($stmt);

                    // 7. Save order items
                    $itemStmt = mysqli_prepare(
                        $conn,
                        "INSERT INTO order_items
                        (order_id, food_id, quantity, price)
                        VALUES (?, ?, ?, ?)"
                    );

                    foreach ($cartItems as $item) {
                        $foodId = (int) $item["id"];
                        $quantity = (int) $item["quantity"];
                        $price = (float) $item["price"];

                        mysqli_stmt_bind_param(
                            $itemStmt,
                            "iiid",
                            $orderId,
                            $foodId,
                            $quantity,
                            $price
                        );

                        if (!mysqli_stmt_execute($itemStmt)) {
                            throw new Exception("Could not save order items.");
                        }
                    }

                    mysqli_stmt_close($itemStmt);

                    // 8. Commit transaction
                    mysqli_commit($conn);

                    // Clear cart after successful order
                    $_SESSION["cart"] = [];

                    // Generate a new CSRF token
                    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));

                    // Redirect to order confirmation
                    header("Location: success.php?order_id=" . $orderId);
                    exit;

                } catch (Exception $e) {

                    // Undo database changes if an error occurs
                    mysqli_rollback($conn);

                    $error = "Unable to place your order. Please try again.";
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Checkout - Foodie</title>

    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body>

<header>
    <div class="logo">Foodie</div>

    <nav>
        <a href="../index.php">Home</a>
        <a href="../menu.html">Menu</a>
        <a href="../cart/cart.php">My Cart</a>
    </nav>
</header>

<section class="food-management">

    <div class="food-management-container">

        <h1>Checkout</h1>

        <p>Complete your order below.</p>

        <?php if ($error !== ""): ?>
            <p class="error-message">
                <?= htmlspecialchars($error) ?>
            </p>
        <?php endif; ?>

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
                            <td><?= htmlspecialchars($item["name"]) ?></td>

                            <td>
                                Rs. <?= number_format($item["price"], 2) ?>
                            </td>

                            <td><?= (int) $item["quantity"] ?></td>

                            <td>
                                Rs. <?= number_format($item["subtotal"], 2) ?>
                            </td>
                        </tr>

                    <?php endforeach; ?>

                </tbody>
            </table>

        </div>

        <h2>
            Total Amount: Rs. <?= number_format($totalPrice, 2) ?>
        </h2>

        <h2>Delivery Information</h2>

        <form action="payments/create-checkout-session.php" method="POST">

            <input
                type="hidden"
                name="csrf_token"
                value="<?= htmlspecialchars($_SESSION["csrf_token"]) ?>"
            >

            <p>
                <strong>Full Name:</strong>
                <?= htmlspecialchars($_SESSION["user_name"] ?? "") ?>
            </p>

            <label for="phone">Phone Number:</label>

            <input
                type="tel"
                id="phone"
                name="phone"
                maxlength="20"
                required
                value="<?= htmlspecialchars($_POST["phone"] ?? "") ?>"
            >

            <label for="address">Delivery Address:</label>

            <textarea
                id="address"
                name="address"
                rows="4"
                maxlength="255"
                required
            ><?= htmlspecialchars($_POST["address"] ?? "") ?></textarea>

            <button type="submit" name="place_order">
                Place Order
            </button>

        </form>

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