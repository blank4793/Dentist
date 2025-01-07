<?php
session_start();
require_once '../includes/config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authorized']);
    exit;
}

$search = $_GET['search'] ?? '';

if (empty($search)) {
    echo json_encode(['success' => false, 'message' => 'Search term is required']);
    exit;
}

try {
    $search = "%{$search}%";
    
    $stmt = $pdo->prepare("
        SELECT * FROM patients 
        WHERE name LIKE ? 
        OR phone LIKE ? 
        ORDER BY created_at DESC
    ");
    
    $stmt->execute([$search, $search]);
    $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $html = '';
    $count = 1;
    
    foreach ($patients as $patient) {
        $html .= "<tr data-patient-id='{$patient['id']}'>";
        $html .= "<td>" . $count++ . "</td>";
        $html .= "<td>" . htmlspecialchars($patient['name']) . "</td>";
        $html .= "<td>" . htmlspecialchars($patient['date']) . "</td>";
        $html .= "<td>" . htmlspecialchars($patient['phone']) . "</td>";
        $html .= "<td class='action-buttons'>";
        $html .= "<a href='view_patient.php?id={$patient['id']}' class='btn btn-view'><i class='fas fa-eye'></i> View</a>";
        $html .= "<a href='edit_patient.php?id={$patient['id']}' class='btn btn-edit'><i class='fas fa-edit'></i> Edit</a>";
        $html .= "<button type='button' class='btn btn-delete' data-id='{$patient['id']}'><i class='fas fa-trash'></i> Delete</button>";
        $html .= "</td></tr>";
    }
    
    if (empty($html)) {
        $html = "<tr><td colspan='5' class='no-results'>No patients found matching your search.</td></tr>";
    }
    
    echo json_encode([
        'success' => true,
        'html' => $html
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error searching patients: ' . $e->getMessage()
    ]);
} 