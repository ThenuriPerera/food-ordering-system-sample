<?php

require_once "../config/db.php";

$message = "";
$messageType = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // Get form data
    $name = trim($_POST["name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $phone = trim($_POST["phone"] ?? "");
    $password = $_POST["password"] ?? "";

    // 1. Check empty fields
    if ($name === "" || $email === "" || $phone === "" || $password === "") {

        $message = "Please fill in all fields.";
        $messageType = "error";

    }

    // 2. Validate email
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $message = "Please enter a valid email address.";
        $messageType = "error";

    }

    // 3. Check password length
    elseif (strlen($password) < 6) {

        $message = "Password must be at least 6 characters.";
        $messageType = "error";

    }

    else {

        // 4. Check whether email already exists
        $check = mysqli_prepare(
            $conn,
            "SELECT id FROM users WHERE email = ?"
        );

        mysqli_stmt_bind_param(
            $check,
            "s",
            $email
        );

        mysqli_stmt_execute($check);
        mysqli_stmt_store_result($check);

        if (mysqli_stmt_num_rows($check) > 0) {

            $message = "An account with this email already exists.";
            $messageType = "error";

        } else {

            // 5. Hash the password
            $hashedPassword = password_hash(
                $password,
                PASSWORD_DEFAULT
            );

            // 6. Insert user into database
            $stmt = mysqli_prepare(
                $conn,
                "INSERT INTO users
                (name, email, password, phone, role)
                VALUES (?, ?, ?, ?, 'customer')"
            );

            mysqli_stmt_bind_param(
                $stmt,
                "ssss",
                $name,
                $email,
                $hashedPassword,
                $phone
            );

            // 7. Check whether registration was successful
            if (mysqli_stmt_execute($stmt)) {

                $message = "Registration successful! You can now log in.";
                $messageType = "success";

            } else {

                $message = "Registration failed. Please try again.";
                $messageType = "error";
            }

            mysqli_stmt_close($stmt);
        }

        mysqli_stmt_close($check);
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

    <title>Register - Foodie</title>

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


<section class="register-section">

    <div class="register-container">

        <h1>
            Create Account
        </h1>

        <p>
            Register to order your favourite food.
        </p>


        <!-- Success / Error Message -->

        <?php if ($message !== ""): ?>

            <div class="form-message <?= htmlspecialchars($messageType) ?>">

                <?= htmlspecialchars($message) ?>

            </div>

        <?php endif; ?>


        <!-- Registration Form -->

        <form
            id="registerForm"
            method="POST"
            action="register.php">


            <!-- Name -->

            <div class="form-group">

                <label for="name">
                    Full Name
                </label>

                <input
                    type="text"
                    id="name"
                    name="name"
                    placeholder="Enter your name"
                    value="<?= htmlspecialchars($_POST["name"] ?? "") ?>"
                    required>

            </div>


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
                    value="<?= htmlspecialchars($_POST["email"] ?? "") ?>"
                    required>

            </div>


            <!-- Phone -->

            <div class="form-group">

                <label for="phone">
                    Phone
                </label>

                <input
                    type="text"
                    id="phone"
                    name="phone"
                    placeholder="Enter your phone number"
                    value="<?= htmlspecialchars($_POST["phone"] ?? "") ?>"
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


            <!-- Register Button -->

            <button
                type="submit"
                class="register-button">

                Register

            </button>

        </form>


        <!-- Login Link -->

        <p class="login-link">

            Already have an account?

            <a href="login.php">
                Login
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