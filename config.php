<?php
$host = 'localhost';
$dbname = 'dental_clinic';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Debug: Print connection status
    echo "<!-- Database connection established -->";
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?> 