<?php

session_start();

require_once "../config/db.php";

if (isset($_SESSION["user_id"]) && ($_SESSION["user_role"] ?? "") === "admin") {
    header("Location: dashboard.php");
    exit;
}

$message = "";
$messageType = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($email === "" || $password === "") {
        $message = "Please enter your email and password.";
        $messageType = "error";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Please enter a valid email address.";
        $messageType = "error";
    } else {
        $stmt = mysqli_prepare(
            $conn,
            "SELECT id, name, email, password, role
             FROM users
             WHERE email = ?"
        );

        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if (mysqli_num_rows($result) === 1) {
            $user = mysqli_fetch_assoc($result);

            if (password_verify($password, $user["password"])) {
                if (($user["role"] ?? "") !== "admin") {
                    $message = "This account does not have admin access.";
                    $messageType = "error";
                } else {
                    $_SESSION["user_id"] = (int) $user["id"];
                    $_SESSION["user_name"] = $user["name"];
                    $_SESSION["user_email"] = $user["email"];
                    $_SESSION["user_role"] = "admin";

                    header("Location: dashboard.php");
                    exit;
                }
            } else {
                $message = "Incorrect email or password.";
                $messageType = "error";
            }
        } else {
            $message = "Incorrect email or password.";
            $messageType = "error";
        }

        mysqli_stmt_close($stmt);
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Foodie</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body>

<header>
    <div class="logo">🍔 Foodie Admin</div>
    <nav>
        <a href="../index.php">Home</a>
        <a href="../menu.html">Menu</a>
        <a href="login.php">Admin Login</a>
    </nav>
</header>

<section class="login-section">
    <div class="login-container">
        <h1>Admin Login</h1>
        <p>Sign in to manage food items, categories, and customer orders.</p>

        <?php if ($message !== ""): ?>
            <div class="form-message <?= htmlspecialchars($messageType) ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" placeholder="Enter admin email" required>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Enter admin password" required>
            </div>

            <button type="submit" class="add-food-button">Login</button>
        </form>
    </div>
</section>

<footer>
    <p>&copy; 2026 Foodie. All Rights Reserved.</p>
</footer>

</body>
</html>
