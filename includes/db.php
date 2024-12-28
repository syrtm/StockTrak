<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "stok_takip";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Veritabanı bağlantısı başarısız: " . $conn->connect_error);
}

// Set charset to utf8
$conn->set_charset("utf8");
?>
