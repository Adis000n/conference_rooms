<?php
$conn = new mysqli('localhost', 'root', '', 'system_rezerwacji_sal_konferencyjnych');

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");
