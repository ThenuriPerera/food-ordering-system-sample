
<?php

session_start();

require_once "../../config/db.php";

// Retrieve all categories
$sql = "SELECT id, name, description
        FROM categories
        ORDER BY id DESC";

$result = mysqli_query($conn, $sql);

if (!$result) {
    die("Unable to retrieve categories.");
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Manage Categories - Foodie</title>

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
        <a href="../auth/profile.php">Profile</a>
        <a href="../auth/logout.php">Logout</a>
    </nav>
</header>

<section class="food-management">

    <div class="food-management-container">

        <h1>Manage Categories</h1>

        <p>View and manage your food categories.</p>

        <a href="add-category.php" class="add-food-button">
            + Add New Category
        </a>

        <div class="table-container">

            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Category Name</th>
                        <th>Description</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>

                    <?php if (mysqli_num_rows($result) > 0): ?>

                        <?php while ($category = mysqli_fetch_assoc($result)): ?>

                            <tr>
                                <td>
                                    <?= (int)$category["id"] ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($category["name"] ?? "") ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($category["description"] ?? "") ?>
                                </td>

                                <td>
                                    <a
                                        href="edit-category.php?id=<?= (int)$category['id'] ?>"
                                        class="edit-food-button"
                                    >
                                        Edit
                                    </a>
                                </td>
                            </tr>

                        <?php endwhile; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="4">No categories found.</td>
                        </tr>

                    <?php endif; ?>

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
