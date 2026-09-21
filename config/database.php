<?php

$host = "sql209.infinityfree.com";
$username = "if0_42929623";
$password = "CuStOdIo888";
$database = "if0_42929623_event_management";

$conn = new mysqli(
    $host,
    $username,
    $password,
    $database
);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");
