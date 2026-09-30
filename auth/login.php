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