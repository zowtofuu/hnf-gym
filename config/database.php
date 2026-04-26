<?php
date_default_timezone_set('Asia/Manila');
$DB_HOST = "localhost";
$DB_USER = "root";
$DB_PASS = "";
$DB_NAME = "hnf_underground";

// Create connection
$conn = mysqli_connect($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);

// Check connection
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Set timezone for MySQL connection to ensure correct handling of date/time values
$conn->query("SET time_zone = '+08:00'");

// set charset to utf8mb4 for proper encoding of characters DFS: not essential
mysqli_set_charset($conn, "utf8mb4");
