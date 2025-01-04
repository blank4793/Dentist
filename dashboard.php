<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require_once 'config.php';

// Debug session
echo "<pre>Session: "; print_r($_SESSION); echo "</pre>";

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Get total patients count
$stmt = $pdo->query("SELECT COUNT(*) FROM patients");
$totalPatients = $stmt->fetchColumn();

// Get today's appointments
$stmt = $pdo->prepare("SELECT COUNT(*) FROM treatments WHERE treatment_date = CURDATE()");
$stmt->execute();
$todayAppointments = $stmt->fetchColumn();

// Get recent patients
$stmt = $pdo->prepare("
    SELECT p.*, t.treatment_name, t.status 
    FROM patients p 
    LEFT JOIN treatments t ON p.id = t.patient_id 
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
    <link rel="stylesheet" href="dashboard-styles.css">
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="logo-section">
                <img src="tooth-icon.png" alt="Dental Clinic Logo" class="logo">
                <h2>THE DENTAL CLINIC</h2>
            </div>
            <nav class="nav-menu">
                <a href="dashboard.php" class="nav-item active">
                    <i class="icon">📊</i> Dashboard
                </a>
                <a href="patient-form.php" class="nav-item">
                    <i class="icon">➕</i> Add New Patient
                </a>
                <a href="patient-list.php" class="nav-item">
                    <i class="icon">📋</i> Patient List
                </a>
                <a href="appointments.php" class="nav-item">
                    <i class="icon">📅</i> Appointments
                </a>
            </nav>
            <div class="user-section">
                <span id="userName"><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                <a href="logout.php" class="logout-btn">Logout</a>
            </div>
        </div>

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
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Date</th>
                            <th>Treatment</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentPatients as $patient): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($patient['name']); ?></td>
                            <td><?php echo date('Y-m-d', strtotime($patient['created_at'])); ?></td>
                            <td><?php echo htmlspecialchars($patient['treatment_name']); ?></td>
                            <td><?php echo htmlspecialchars($patient['status']); ?></td>
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