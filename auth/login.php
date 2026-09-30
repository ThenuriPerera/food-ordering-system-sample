<?php

session_start();

require_once "../config/db.php";

$message = "";
$messageType = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // Get form data
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    // Check empty fields
    if ($email === "" || $password === "") {

        $message = "Please enter your email and password.";
        $messageType = "error";

    }

    // Validate email
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $message = "Please enter a valid email address.";
        $messageType = "error";

    }

    else {

        // Find user by email
        $stmt = mysqli_prepare(
            $conn,
            "SELECT id, name, email, password, role
             FROM users
             WHERE email = ?"
        );

        mysqli_stmt_bind_param(
            $stmt,
            "s",
            $email
        );

        mysqli_stmt_execute($stmt);

        $result = mysqli_stmt_get_result($stmt);

        // Check whether user exists
        if (mysqli_num_rows($result) === 1) {

            $user = mysqli_fetch_assoc($result);

            // Verify password
            if (password_verify($password, $user["password"])) {

                // Store user information in session
                $_SESSION["user_id"] = $user["id"];
                $_SESSION["user_name"] = $user["name"];
                $_SESSION["user_email"] = $user["email"];
                $_SESSION["user_role"] = $user["role"];

                $message = "Login successful!";
                $messageType = "success";

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

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>Login - Foodie</title>

    <link
        rel="stylesheet"
        href="../assets/css/style.css">

</head>

<body>

<header>

    <div class="logo">
        🍔 Foodie
    </div>

    <nav>

        <a href="../index.html">
            Home
        </a>

        <a href="../menu.html">
            Menu
        </a>

        <a href="register.php">
            Register
        </a>

        <a href="login.php">
            Login
        </a>

    </nav>

</header>


<section class="login-section">

    <div class="login-container">

        <h1>
            Login
        </h1>

        <p>
            Login to your Foodie account.
        </p>

         <?php if ($message !== ""): ?>

            <div class="form-message <?= htmlspecialchars($messageType) ?>">

                <?= htmlspecialchars($message) ?>

            </div>

        <?php endif; ?>

        <form
            id="loginForm"
            method="POST"
            action="login.php">


            <!-- Email -->

            <div class="form-group">

                <label for="email">
                    Email
                </label>

                <input
                    type="email"
                    id="email"
                    name="email"
                    placeholder="Enter your email"
                    required>

            </div>


            <!-- Password -->

            <div class="form-group">

                <label for="password">
                    Password
                </label>

                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="Enter your password"
                    required>

            </div>


            <!-- Login Button -->

            <button
                type="submit"
                class="login-button">

                Login

            </button>

        </form>


        <p class="register-link">

            Don't have an account?

            <a href="register.php">
                Register
            </a>

        </p>

    </div>

</section>


<footer>

    <p>
        &copy; 2026 Foodie. All Rights Reserved.
    </p>

</footer>


</body>

</html>