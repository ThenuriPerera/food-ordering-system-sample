<?php

session_start();

require_once "../../config/db.php";

// Generate a CSRF token
if (!isset($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

// Display status messages
$message = "";
$messageType = "success";

if (isset($_GET["added"]) && $_GET["added"] === "1") {
    $message = "Food added successfully!";
} elseif (isset($_GET["updated"]) && $_GET["updated"] === "1") {
    $message = "Food updated successfully!";
} elseif (isset($_GET["deleted"]) && $_GET["deleted"] === "1") {
    $message = "Food deleted successfully!";
} elseif (isset($_GET["delete_error"])) {
    $messageType = "error";

    switch ($_GET["delete_error"]) {
        case "ordered":
            $message = "This food is used in existing orders and cannot be deleted.";
            break;

        case "invalid":
            $message = "Invalid food ID.";
            break;

        default:
            $message = "Unable to delete the food item.";
    }
}

// Retrieve food items and their categories
$sql = "SELECT foods.*, categories.name AS category_name
        FROM foods
        INNER JOIN categories
        ON foods.category_id = categories.id
        ORDER BY foods.id DESC";

$result = mysqli_query($conn, $sql);

if (!$result) {
    die("Unable to retrieve food items.");
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Manage Foods - Foodie</title>

    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body>

<header>

    <div class="logo">
        🍔 Foodie Admin
    </div>

    <nav>
        <a href="../index.html">Home</a>
        <a href="foods.php">Manage Foods</a>
        <a href="../auth/profile.php">Profile</a>
        <a href="../auth/logout.php">Logout</a>
    </nav>

</header>


<section class="food-management">

    <div class="food-management-container">

        <h1>Manage Foods</h1>

        <?php if ($message !== ""): ?>
            <p class="form-message <?= htmlspecialchars($messageType) ?>">
                <?= htmlspecialchars($message) ?>
            </p>
        <?php endif; ?>

        <p>View and manage all foods in the system.</p>

        <a href="add-food.php" class="add-food-button">
            + Add New Food

        </a>


        <div class="table-container">

            <table>

                <thead>

                    <tr>
                        <th>ID</th>
                        <th>Food Name</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Description</th>
                        <th>Actions</th>
                    </tr>

                </thead>

                <tbody>

                    <?php while ($food = mysqli_fetch_assoc($result)): ?>

                        <tr>

                            <td>
                                <?= (int)$food["id"] ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($food["name"] ?? "") ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($food["category_name"] ?? "") ?>
                            </td>

                            <td>
                                Rs. <?= htmlspecialchars((string)$food["price"]) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($food["description"] ?? "") ?>
                            </td>

                            <td>
                                <a
                                    href="edit-food.php?id=<?= (int)$food['id'] ?>"
                                    class="edit-food-button"
                                >
                                    Edit
                                </a>

                                <form
                                    action="delete-food.php"
                                    method="POST"
                                    onsubmit="return confirm('Are you sure you want to delete this food item?');"
                                    style="display: inline;"
                                >
                                    <input
                                        type="hidden"
                                        name="food_id"
                                        value="<?= (int)$food['id'] ?>"
                                    >

                                    <input
                                        type="hidden"
                                        name="csrf_token"
                                        value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>"
                                    >

                                    <button
                                        type="submit"
                                        class="delete-food-button"
                                    >
                                        Delete
                                    </button>
                                </form>
                            </td>

                        </tr>

                    <?php endwhile; ?>

                </tbody>

            </table>

        </div>

    </div>

</section>


<footer>
    <p>&copy; 2026 Foodie. All Rights Reserved.</p>
</footer>


</body>

</html>
