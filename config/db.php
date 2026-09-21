<?php

$host = "localhost";
$username = "root";
$password = "5riL@nka";
$database = "food_ordering";

$conn = mysqli_connect(
    $host,
    $username,
    $password,
    $database
);

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

echo "Database connected successfully!";

?>