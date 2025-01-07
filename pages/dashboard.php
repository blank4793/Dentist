<?php
session_start();
require_once '../includes/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Get total patients count
$stmt = $pdo->query("SELECT COUNT(*) FROM patients");
$totalPatients = $stmt->fetchColumn();

// Get today's appointments
$stmt = $pdo->prepare("SELECT COUNT(*) FROM dental_treatments WHERE DATE(created_at) = CURDATE()");
$stmt->execute();
$todayAppointments = $stmt->fetchColumn();

// Get recent patients
$stmt = $pdo->prepare("
    SELECT p.*, t.treatment_name, t.status 
    FROM patients p 
    LEFT JOIN dental_treatments t ON p.id = t.patient_id 
    ORDER BY p.created_at DESC 
    LIMIT 10
");
$stmt->execute();
$recentPatients = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dental Clinic - Dashboard</title>
    <link rel="stylesheet" href="../css/dashboard-styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="../js/dashboard.js"></script>
</head>
<body>
    <div class="dashboard-container">
        <?php 
        include '../includes/header.php'; 
        ?>
        
        <!-- Main Content -->
        <div class="main-content">
            <div class="header-section">
                <h1>Dashboard</h1>
                <div class="action-buttons">
                    <a href="patient-form.php" class="btn-primary">
                        <i class="icon">➕</i> Add New Patient
                    </a>
                </div>
            </div>
            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Total Patients</h3>
                    <p class="stat-number"><?php echo $totalPatients; ?></p>
                </div>
                <div class="stat-card">
                    <h3>Today's Appointments</h3>
                    <p class="stat-number"><?php echo $todayAppointments; ?></p>
                </div>
            </div>

            <div class="recent-patients">
                <h2>Recent Patients</h2>
                <table class="patient-table">
                    <thead>
                        <tr>
                            <th>Sr. No</th>
                            <th>Name</th>
                            <th>Date</th>
                            <th>Phone</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $serialNumber = 1;
                        foreach ($recentPatients as $patient): 
                        ?>
                        <tr data-patient-id="<?php echo $patient['id']; ?>">
                            <td><?php echo $serialNumber++; ?></td>
                            <td><?php echo htmlspecialchars($patient['name']); ?></td>
                            <td><?php echo htmlspecialchars($patient['date']); ?></td>
                            <td><?php echo htmlspecialchars($patient['phone']); ?></td>
                            <td class="action-buttons">
                                <a href="view_patient.php?id=<?php echo $patient['id']; ?>" class="btn btn-view">
                                    <i class="fas fa-eye"></i> View
                                </a>
                                <a href="edit_patient.php?id=<?php echo $patient['id']; ?>" class="btn btn-edit">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                                <button type="button" class="btn btn-delete" data-id="<?php echo $patient['id']; ?>">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="../js/script.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="../js/dashboard.js"></script> 