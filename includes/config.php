<?php
try {
    $pdo = new PDO(
        "mysql:host=localhost;dbname=dental_clinic;charset=utf8mb4",
        "root",
        "",
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    die(json_encode([
        'success' => false,
        'message' => 'Database connection failed'
    ]));
}

require_once __DIR__ . '/functions.php';
?> 