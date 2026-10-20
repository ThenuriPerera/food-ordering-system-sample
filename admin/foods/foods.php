<?php

require_once "../../config/db.php";

$message = "";

if (isset($_GET["added"]) && $_GET["added"] === "1") {
    $message = "Food added successfully!";
}

$sql = "SELECT foods.*, categories.name AS category_name
        FROM foods
        INNER JOIN categories
        ON foods.category_id = categories.id
        ORDER BY foods.id DESC";

$result = mysqli_query($conn, $sql);

if (!$result) {
    die("Query failed: " . mysqli_error($conn));
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>Manage Foods - Foodie</title>

    <link
        rel="stylesheet"
        href="../assets/css/style.css">

</head>

<body>

<header>

    <div class="logo">
        🍔 Foodie Admin
    </div>

    <nav>

        <a href="../../index.html">
            Home
        </a>

        <a href="foods.php">
            Manage Foods
        </a>

        <a href="../auth/profile.php">
            Profile
        </a>

        <a href="../../auth/logout.php">
            Logout
        </a>

    </nav>

</header>


<section class="food-management">

    <div class="food-management-container">

        <h1>
            Manage Foods
        </h1>

        <?php if ($message !== ""): ?>
            <p class="form-message success">
                <?= htmlspecialchars($message) ?>
            </p>
        <?php endif; ?>

        <p>
            View all foods in the system.
        </p>


        <a
            href="add-food.php"
            class="add-food-button">

            + Add New Food

        </a>


        <div class="table-container">

            <table>

                <thead>

                    <tr>

                        <th>
                            ID
                        </th>

                        <th>
                            Food Name
                        </th>

                        <th>
                            Category
                        </th>

                        <th>
                            Price
                        </th>

                        <th>
                            Description
                        </th>

                        <th>
                            Actions
                        </th>

                    </tr>

                </thead>

                <tbody>

                    <?php while ($food = mysqli_fetch_assoc($result)): ?>

                        <tr>

                            <td>
                                <?= htmlspecialchars($food["id"]) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($food["name"]) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($food["category_name"]) ?>
                            </td>

                            <td>
                                Rs. <?= htmlspecialchars($food["price"]) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($food["description"]) ?>
                            </td>

                            <td>

                                <a href="#">
                                    Edit
                                </a>

                                |

                                <a href="#">
                                    Delete
                                </a>

                            </td>

                        </tr>

                    <?php endwhile; ?>

                </tbody>

            </table>

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