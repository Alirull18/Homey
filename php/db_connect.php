<?php

define('DB_HOST', '127.0.0.1');
define('DB_USER', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'db_homey');

$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}


$dbc = $conn;


mysqli_set_charset($conn, "utf8mb4");
?>
