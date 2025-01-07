<?php
session_start();
require_once '../includes/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Get all patients with their latest treatment
$stmt = $pdo->query("
    SELECT 
        p.*,
        dt.treatment_name,
        dt.status,
        dt.price_per_unit,
        dt.quantity,
        dt.total_price
    FROM patients p
    LEFT JOIN (
        SELECT patient_id, treatment_name, status, price_per_unit, quantity, total_price,
               ROW_NUMBER() OVER (PARTITION BY patient_id ORDER BY created_at DESC) as rn
        FROM dental_treatments
    ) dt ON p.id = dt.patient_id AND dt.rn = 1
    ORDER BY p.created_at DESC
");
$patients = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient List - Dental Clinic</title>
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="../css/dashboard-styles.css">
</head>
<body>
    <div class="dashboard-container">
        <?php include '../includes/header.php'; ?>
        
        <div class="main-content">
            <div class="header-section">
                <h1>Patient List</h1>
                <div class="action-buttons">
                    <a href="patient-form.php" class="btn-primary">
                        <i class="icon">➕</i> Add New Patient
                    </a>
                </div>
            </div>

            <div class="patient-list-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Phone</th>
                            <th>Age/Gender</th>
                            <th>Latest Treatment</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($patients as $patient): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($patient['name']); ?></td>
                            <td><?php echo htmlspecialchars($patient['phone']); ?></td>
                            <td><?php echo htmlspecialchars($patient['age'] . '/' . $patient['gender']); ?></td>
                            <td><?php echo htmlspecialchars($patient['treatment_name'] ?? 'No treatment'); ?></td>
                            <td><?php echo htmlspecialchars($patient['status'] ?? 'N/A'); ?></td>
                            <td><?php echo $patient['treatment_date'] ? date('Y-m-d', strtotime($patient['treatment_date'])) : 'N/A'; ?></td>
                            <td>
                                <a href="edit_patient.php?id=<?php echo $patient['id']; ?>" class="btn-edit">Edit</a>
                                <a href="view_patient.php?id=<?php echo $patient['id']; ?>" class="btn-view">View</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html> 