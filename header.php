<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>The Dental Clinic</title>
    <link rel="stylesheet" href="dashboard-styles.css">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <?php if (isset($_SESSION['user_id'])): ?>
    <div class="sidebar">
        <div class="logo-section">
            <img src="tooth-icon.png" alt="Dental Clinic Logo" class="logo">
            <h2>THE DENTAL CLINIC</h2>
        </div>
        <nav class="nav-menu">
            <a href="dashboard.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                <i class="icon">📊</i> Dashboard
            </a>
            <a href="patient-form.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'patient-form.php' ? 'active' : ''; ?>">
                <i class="icon">➕</i> Add New Patient
            </a>
            <a href="patient-list.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'patient-list.php' ? 'active' : ''; ?>">
                <i class="icon">📋</i> Patient List
            </a>
            <a href="appointments.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'appointments.php' ? 'active' : ''; ?>">
                <i class="icon">📅</i> Appointments
            </a>
        </nav>
        <div class="user-section">
            <span id="userName"><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
            <a href="logout.php" class="logout-btn">Logout</a>
        </div>
    </div>
    <?php endif; ?> 