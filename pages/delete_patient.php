<?php
session_start();
require_once '../includes/config.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

// Debug log function that writes to XAMPP logs folder
function debug_log($message) {
    $log_file = "C:/xampp/htdocs/dentist/logs/delete_log.txt";
    $timestamp = date('Y-m-d H:i:s');
    $log_message = "[{$timestamp}] " . print_r($message, true) . "\n";
    
    // Create logs directory if it doesn't exist
    if (!file_exists(dirname($log_file))) {
        mkdir(dirname($log_file), 0777, true);
    }
    
    // Append to log file
    file_put_contents($log_file, $log_message, FILE_APPEND);
}

debug_log("Delete request received");
debug_log("POST data: " . print_r($_POST, true));

if (!isset($_SESSION['user_id'])) {
    debug_log("Unauthorized access attempt - User not logged in");
    echo json_encode(['success' => false, 'message' => 'Not authorized']);
    exit;
}

if (!isset($_POST['patient_id']) || !is_numeric($_POST['patient_id'])) {
    debug_log("Invalid patient ID received: " . print_r($_POST, true));
    echo json_encode(['success' => false, 'message' => 'Invalid patient ID']);
    exit;
}

try {
    $patientId = (int)$_POST['patient_id'];
    debug_log("Starting deletion process for patient ID: " . $patientId);

    $pdo->beginTransaction();
    debug_log("Transaction started");

    // Delete related records first
    $tables = ['medical_history', 'dental_treatments', 'visits'];
    
    foreach ($tables as $table) {
        $stmt = $pdo->prepare("DELETE FROM $table WHERE patient_id = ?");
        $stmt->execute([$patientId]);
        $rowCount = $stmt->rowCount();
        debug_log("Deleted {$rowCount} rows from {$table} table");
    }

    // Delete the patient
    $stmt = $pdo->prepare("DELETE FROM patients WHERE id = ?");
    $stmt->execute([$patientId]);
    $deletedRows = $stmt->rowCount();
    debug_log("Deleted {$deletedRows} rows from patients table");

    if ($deletedRows === 0) {
        throw new Exception("Patient ID {$patientId} not found or already deleted");
    }

    $pdo->commit();
    debug_log("Transaction committed successfully");
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    $pdo->rollBack();
    debug_log("Error occurred: " . $e->getMessage());
    debug_log("Transaction rolled back");
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
} 