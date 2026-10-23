
<?php
 
session_start();

require_once "../../config/db.php";

$message = "";
$messageType = "";

if (isset($_GET["added"]) && $_GET["added"] === "1") {
    $message = "Category added successfully!";
} elseif (isset($_GET["updated"]) && $_GET["updated"] === "1") {
    $message = "Category updated successfully!";
}
// Process the form when submitted
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // Get the submitted values
    $name = trim($_POST["name"] ?? "");
    $description = trim($_POST["description"] ?? "");

    // Validate the category name
    if ($name === "") {

        $message = "Please enter a category name.";
        $messageType = "error";

    } else {

        // Check whether the category already exists
        $checkStmt = mysqli_prepare(
            $conn,
            "SELECT id FROM categories WHERE name = ?"
        );

        mysqli_stmt_bind_param($checkStmt, "s", $name);
        mysqli_stmt_execute($checkStmt);
        mysqli_stmt_store_result($checkStmt);

        if (mysqli_stmt_num_rows($checkStmt) > 0) {

            $message = "This category already exists.";
            $messageType = "error";

        } else {

            // Insert the new category
            $stmt = mysqli_prepare(
                $conn,
                "INSERT INTO categories (name, description)
                 VALUES (?, ?)"
            );

            mysqli_stmt_bind_param(
                $stmt,
                "ss",
                $name,
                $description
            );

            if (mysqli_stmt_execute($stmt)) {

                mysqli_stmt_close($stmt);
                mysqli_stmt_close($checkStmt);

                header("Location: categories.php?added=1");
                exit;

            } else {

                $message = "Unable to add the category. Please try again.";
                $messageType = "error";
            }

            mysqli_stmt_close($stmt);
        }

        mysqli_stmt_close($checkStmt);
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Add Category - Foodie</title>

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

        <h1>Add New Category</h1>

        <p>Enter the category information below.</p>

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
                    value="<?= htmlspecialchars($name ?? "") ?>"
                    required
                >
            </div>

            <div class="form-group">
                <label for="description">Description</label>

                <textarea
                    id="description"
                    name="description"
                    rows="4"
                ><?= htmlspecialchars($description ?? "") ?></textarea>
            </div>

            <button type="submit" class="btn">
                Add Category
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
