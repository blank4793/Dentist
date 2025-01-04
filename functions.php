<?php
function getPatientData($pdo, $patientId) {
    $stmt = $pdo->prepare("SELECT * FROM patients WHERE id = ?");
    $stmt->execute([$patientId]);
    return $stmt->fetch();
}

function getMedicalHistory($pdo, $patientId) {
    $stmt = $pdo->prepare("SELECT * FROM medical_history WHERE patient_id = ?");
    $stmt->execute([$patientId]);
    return $stmt->fetch();
}

function getTreatments($pdo, $patientId) {
    $stmt = $pdo->prepare("SELECT * FROM treatments WHERE patient_id = ? ORDER BY treatment_date DESC");
    $stmt->execute([$patientId]);
    return $stmt->fetchAll();
}

function getVisits($pdo, $patientId) {
    $stmt = $pdo->prepare("SELECT * FROM visits WHERE patient_id = ? ORDER BY visit_date ASC");
    $stmt->execute([$patientId]);
    return $stmt->fetchAll();
}

function calculateTotals($treatments) {
    $totalAmount = 0;
    foreach ($treatments as $treatment) {
        $totalAmount += $treatment['price'];
    }
    return $totalAmount;
}

function updatePatient($pdo, $patientId, $data) {
    // Implementation for updating patient data
}

function updateMedicalHistory($pdo, $patientId, $data) {
    // Implementation for updating medical history
}

function addTreatment($pdo, $patientId, $treatment) {
    // Implementation for adding new treatment
}

function addVisit($pdo, $patientId, $visit) {
    // Implementation for adding new visit
} 