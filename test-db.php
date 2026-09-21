<?php

require_once "config/db.php";

$sql = "SELECT * FROM categories";

$result = mysqli_query($conn, $sql);

if (!$result) {
    die("Query failed: " . mysqli_error($conn));
}

echo "<h1>Categories</h1>";

while ($row = mysqli_fetch_assoc($result)) {

    echo $row["id"] . " - ";
    echo $row["name"];
    echo "<br>";
}

?>