<?php
header('Content-Type: application/json');
session_start();
require_once '../includes/config.php';

try {
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('User not logged in');
    }

    // Check if patient ID is provided
    if (!isset($_POST['patient_id'])) {
        throw new Exception('Patient ID is required');
    }

    $patientId = $_POST['patient_id'];

    // Start transaction
    $pdo->beginTransaction();

    try {
        // Delete related records first (due to foreign key constraints)
        
        // Delete visits
        $stmt = $pdo->prepare("DELETE FROM visits WHERE patient_id = ?");
        $stmt->execute([$patientId]);

        // Delete treatments
        $stmt = $pdo->prepare("DELETE FROM dental_treatments WHERE patient_id = ?");
        $stmt->execute([$patientId]);

        // Delete billing
        $stmt = $pdo->prepare("DELETE FROM billing WHERE patient_id = ?");
        $stmt->execute([$patientId]);

        // Delete medical history
        $stmt = $pdo->prepare("DELETE FROM medical_history WHERE patient_id = ?");
        $stmt->execute([$patientId]);

        // Finally, delete the patient
        $stmt = $pdo->prepare("DELETE FROM patients WHERE id = ?");
        $stmt->execute([$patientId]);

        $pdo->commit();
        echo json_encode(['success' => true]);

    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 