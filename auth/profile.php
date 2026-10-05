<?php

session_start();


// Check whether user is logged in

if (!isset($_SESSION["user_id"])) {

    header("Location: login.php");
    exit;
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>My Profile - Foodie</title>

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

        <a href="profile.php">
            My Profile
        </a>

        <a href="logout.php">
            Logout
        </a>

    </nav>

</header>


<section class="profile-section">

    <div class="profile-container">

        <h1>
            My Profile
        </h1>

        <p>
            Welcome,
            <strong>
                <?= htmlspecialchars($_SESSION["user_name"]) ?>
            </strong>
        </p>


        <div class="profile-info">

            <p>
                <strong>User ID:</strong>

                <?= htmlspecialchars($_SESSION["user_id"]) ?>
            </p>

            <p>
                <strong>Name:</strong>

                <?= htmlspecialchars($_SESSION["user_name"]) ?>
            </p>

            <p>
                <strong>Email:</strong>

                <?= htmlspecialchars($_SESSION["user_email"]) ?>
            </p>

            <p>
                <strong>Role:</strong>

                <?= htmlspecialchars($_SESSION["user_role"]) ?>
            </p>

        </div>

    </div>

</section>


<footer>

    <p>
        &copy; 2026 Foodie. All Rights Reserved.
    </p>

</footer>

</body>

</html>