<?php

session_start();

require_once "../../config/db.php";

$message = "";
$messageType = "";


// Get the category ID from the URL
$categoryId = filter_input(
    INPUT_GET,
    "id",
    FILTER_VALIDATE_INT
);

if (!$categoryId || $categoryId < 1) {
    die("Invalid category ID.");
}

// Retrieve the existing category
$stmt = mysqli_prepare(
    $conn,
    "SELECT id, name, description
     FROM categories
     WHERE id = ?"
);

mysqli_stmt_bind_param($stmt, "i", $categoryId);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$category = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

if (!$category) {
    die("Category not found.");
}

// Process the submitted form
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $name = trim($_POST["name"] ?? "");
    $description = trim($_POST["description"] ?? "");

    if ($name === "") {

        $message = "Please enter a category name.";
        $messageType = "error";

    } else {

        // Check whether another category has the same name
        $checkStmt = mysqli_prepare(
            $conn,
            "SELECT id
             FROM categories
             WHERE name = ? AND id != ?"
        );

        mysqli_stmt_bind_param(
            $checkStmt,
            "si",
            $name,
            $categoryId
        );

        mysqli_stmt_execute($checkStmt);
        mysqli_stmt_store_result($checkStmt);

        if (mysqli_stmt_num_rows($checkStmt) > 0) {

            $message = "Another category already uses this name.";
            $messageType = "error";

        } else {

            // Update the category
            $updateStmt = mysqli_prepare(
                $conn,
                "UPDATE categories
                 SET name = ?, description = ?
                 WHERE id = ?"
            );

            mysqli_stmt_bind_param(
                $updateStmt,
                "ssi",
                $name,
                $description,
                $categoryId
            );

            if (mysqli_stmt_execute($updateStmt)) {

                mysqli_stmt_close($updateStmt);
                mysqli_stmt_close($checkStmt);

                header("Location: categories.php?updated=1");
                exit;

            } else {

                $message = "Unable to update the category.";
                $messageType = "error";
            }

            mysqli_stmt_close($updateStmt);
        }

        mysqli_stmt_close($checkStmt);
    }

    // Keep the entered values if validation fails
    $category["name"] = $name;
    $category["description"] = $description;
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Edit Category - Foodie</title>

    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body>

<header>
    <div class="logo">
        Foodie Admin
    </div>

    <nav>
        <a href="../index.html">Home</a>
        <a href="foods.php">Manage Foods</a>
        <a href="categories.php">Manage Categories</a>
    </nav>
</header>

<section class="add-food-section">

    <div class="add-food-container">

        <h1>Edit Category</h1>

        <p>Update the category information below.</p>

        <?php if ($message !== ""): ?>
            <p class="form-message <?= htmlspecialchars($messageType) ?>">
                <?= htmlspecialchars($message) ?>
            </p>
        <?php endif; ?>

        <form method="POST" action="">

            <div class="form-group">
                <label for="name">Category Name</label>

                <input
                    type="text"
                    id="name"
                    name="name"
                    maxlength="100"
                    value="<?= htmlspecialchars($category["name"] ?? "") ?>"
                    required
                >
            </div>

            <div class="form-group">
                <label for="description">Description</label>

                <textarea
                    id="description"
                    name="description"
                    rows="4"
                ><?= htmlspecialchars($category["description"] ?? "") ?></textarea>
            </div>

            <button type="submit" class="btn">
                Update Category
            </button>

        </form>

        <p class="back-link">
            <a href="categories.php">Back to Categories</a>
        </p>

    </div>

</section>

<footer>
    <p>&copy; 2026 Foodie. All Rights Reserved.</p>
</footer>

</body>
</html>
