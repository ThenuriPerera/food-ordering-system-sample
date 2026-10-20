
<?php
require_once "../config/db.php";

$message = "";
$messageType = "";

// Get the food ID from the URL
$foodId = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if (!$foodId || $foodId < 1) {
    die("Invalid food ID.");
}

// Get the existing food item
$stmt = mysqli_prepare(
    $conn,
    "SELECT id, category_id, name, description, price
     FROM foods
     WHERE id = ?"
);

mysqli_stmt_bind_param($stmt, "i", $foodId);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$food = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

if (!$food) {
    die("Food item not found.");
}

// Process the submitted edit form
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $name = trim($_POST["name"] ?? "");
    $categoryId = filter_input(
        INPUT_POST,
        "category_id",
        FILTER_VALIDATE_INT
    );
    $description = trim($_POST["description"] ?? "");
    $priceInput = trim($_POST["price"] ?? "");

    if (
        $name === "" ||
        !$categoryId ||
        $priceInput === "" ||
        !is_numeric($priceInput) ||
        (float)$priceInput <= 0
    ) {
        $message = "Enter a food name, valid category, and price greater than zero.";
        $messageType = "error";

    } else {

        // Check that the selected category exists
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

            // Update the existing food item
            $updateStmt = mysqli_prepare(
                $conn,
                "UPDATE foods
                 SET category_id = ?,
                     name = ?,
                     description = ?,
                     price = ?
                 WHERE id = ?"
            );

            $price = (float)$priceInput;

            mysqli_stmt_bind_param(
                $updateStmt,
                "issdi",
                $categoryId,
                $name,
                $description,
                $price,
                $foodId
            );

            if (mysqli_stmt_execute($updateStmt)) {
                mysqli_stmt_close($updateStmt);
                mysqli_stmt_close($categoryCheck);
                mysqli_stmt_close($stmt);

                header("Location: foods.php?updated=1");
                exit;

            } else {
                $message = "Unable to update the food. Please try again.";
                $messageType = "error";
            }

            mysqli_stmt_close($updateStmt);
        }

        mysqli_stmt_close($categoryCheck);
    }

    // Keep submitted values in the form if validation fails
    $food["name"] = $name;
    $food["category_id"] = $categoryId ?: $food["category_id"];
    $food["description"] = $description;
    $food["price"] = $priceInput;
}

// Load categories for the dropdown
$categoryResult = mysqli_query(
    $conn,
    "SELECT id, name FROM categories ORDER BY name ASC"
);

if (!$categoryResult) {
    die("Unable to load categories.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Edit Food</title>

    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<section class="add-food-section">
    <div class="add-food-container">

        <h1>Edit Food</h1>
        <p>Update the food information below.</p>

        <?php if ($message !== ""): ?>
            <p class="form-message <?= htmlspecialchars($messageType) ?>">
                <?= htmlspecialchars($message) ?>
            </p>
        <?php endif; ?>

        <form method="POST">

            <div class="form-group">
                <label for="name">Food Name</label>
                <input
                    type="text"
                    id="name"
                    name="name"
                    value="<?= htmlspecialchars((string)$food["name"]) ?>"
                    required
                >
            </div>

            <div class="form-group">
                <label for="category_id">Category</label>

                <select id="category_id" name="category_id" required>
                    <option value="">Select Category</option>

                    <?php while ($category = mysqli_fetch_assoc($categoryResult)): ?>
                        <option
                            value="<?= (int)$category["id"] ?>"
                            <?= (string)$food["category_id"] === (string)$category["id"] ? "selected" : "" ?>
                        >
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
                ><?= htmlspecialchars((string)$food["description"]) ?></textarea>
            </div>

            <div class="form-group">
                <label for="price">Price (Rs.)</label>
                <input
                    type="number"
                    id="price"
                    name="price"
                    min="0.01"
                    step="0.01"
                    value="<?= htmlspecialchars((string)$food["price"]) ?>"
                    required
                >
            </div>

            <button type="submit" class="btn">
                Update Food
            </button>

        </form>

        <p class="back-link">
            <a href="foods.php">Back to Food List</a>
        </p>

    </div>
</section>

</body>
</html>
