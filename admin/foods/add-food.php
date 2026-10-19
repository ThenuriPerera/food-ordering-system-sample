<?php

require_once "../../config/db.php";

$message = "";
$messageType = "";

// Get categories from the database
$categorySql = "SELECT id, name FROM categories ORDER BY name ASC";
$categoryResult = mysqli_query($conn, $categorySql);

if (!$categoryResult) {
    die("Unable to load categories.");
}

// Process the form when submitted
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $name = trim($_POST["name"] ?? "");
    $categoryId = filter_input(
        INPUT_POST,
        "category_id",
        FILTER_VALIDATE_INT
    );
    $description = trim($_POST["description"] ?? "");
    $priceInput = trim($_POST["price"] ?? "");

    // Validate required fields
    if (
        $name === "" ||
        !$categoryId ||
        $priceInput === "" ||
        !is_numeric($priceInput) ||
        (float)$priceInput <= 0
    ) {
        $message = "Please enter a food name, valid category, and price greater than zero.";
        $messageType = "error";
    } else {

        // Confirm that the category exists
        $categoryCheck = mysqli_prepare(
            $conn,
            "SELECT id FROM categories WHERE id = ?"
        );

        mysqli_stmt_bind_param(
            $categoryCheck,
            "i",
            $categoryId
        );

        mysqli_stmt_execute($categoryCheck);
        mysqli_stmt_store_result($categoryCheck);

        if (mysqli_stmt_num_rows($categoryCheck) !== 1) {

            $message = "Please select a valid category.";
            $messageType = "error";

        } else {

            // Insert the food item
            $stmt = mysqli_prepare(
                $conn,
                "INSERT INTO foods
                (category_id, name, description, price, image)
                VALUES (?, ?, ?, ?, NULL)"
            );

            $price = (float)$priceInput;

            mysqli_stmt_bind_param(
                $stmt,
                "issd",
                $categoryId,
                $name,
                $description,
                $price
            );

            if (mysqli_stmt_execute($stmt)) {

                // Redirect to the food list after successful insertion
                mysqli_stmt_close($stmt);
                mysqli_stmt_close($categoryCheck);

                header("Location: foods.php?added=1");
                exit;

            } else {

                $message = "Unable to add the food. Please try again.";
                $messageType = "error";
            }

            mysqli_stmt_close($stmt);
        }

        mysqli_stmt_close($categoryCheck);
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

    <title>Add Food - Foodie Admin</title>

    <link
        rel="stylesheet"
        href="../assets/css/style.css">
</head>

<body>

<header>
    <div class="logo">🍔 Foodie Admin</div>

    <nav>
        <a href="../index.html">Home</a>
        <a href="foods.php">Manage Foods</a>
        <a href="../auth/profile.php">Profile</a>
        <a href="../auth/logout.php">Logout</a>
    </nav>
</header>

<section class="add-food-section">

    <div class="add-food-container">

        <h1>Add New Food</h1>

        <p>Enter the details of the food item.</p>

        <?php if ($message !== ""): ?>
            <div class="form-message <?= htmlspecialchars($messageType) ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="add-food.php">

            <div class="form-group">
                <label for="name">Food Name</label>

                <input
                    type="text"
                    id="name"
                    name="name"
                    maxlength="100"
                    value="<?= htmlspecialchars($_POST["name"] ?? "") ?>"
                    placeholder="Enter food name"
                    required>
            </div>

            <div class="form-group">
                <label for="category_id">Category</label>

                <select
                    id="category_id"
                    name="category_id"
                    required>

                    <option value="">Select a category</option>

                    <?php while ($category = mysqli_fetch_assoc($categoryResult)): ?>

                        <option
                            value="<?= (int)$category["id"] ?>"
                            <?= (string)($_POST["category_id"] ?? "") === (string)$category["id"] ? "selected" : "" ?>>

                            <?= htmlspecialchars($category["name"]) ?>

                        </option>

                    <?php endwhile; ?>

                </select>
            </div>

            <div class="form-group">
                <label for="description">Description</label>

                <textarea
                    id="description"
                    name="description"
                    rows="4"
                    placeholder="Describe the food"><?= htmlspecialchars($_POST["description"] ?? "") ?></textarea>
            </div>

            <div class="form-group">
                <label for="price">Price (Rs.)</label>

                <input
                    type="number"
                    id="price"
                    name="price"
                    min="0.01"
                    step="0.01"
                    value="<?= htmlspecialchars($_POST["price"] ?? "") ?>"
                    placeholder="Enter price"
                    required>
            </div>

            <button
                type="submit"
                class="register-button">

                Add Food

            </button>

        </form>

        <p class="back-link">
            <a href="foods.php">← Back to Manage Foods</a>
        </p>

    </div>

</section>

<footer>
    <p>&copy; 2026 Foodie. All Rights Reserved.</p>
</footer>

</body>
</html>

