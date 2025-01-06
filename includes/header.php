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
    <link rel="stylesheet" href="../css/dashboard-styles.css">
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body>
    <?php if (isset($_SESSION['user_id'])): ?>
    <div class="sidebar">
        <div class="logo-section">
            <img src="../assets/images/logo.jpeg" alt="Dental Clinic Logo" class="dashboard-logo">
            <h2>THE DENTAL CLINIC</h2>
        </div>
        <nav class="nav-menu">
            <a href="../pages/dashboard.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                <i class="icon">📊</i> Dashboard
            </a>
            <a href="../pages/patient-form.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'patient-form.php' ? 'active' : ''; ?>">
                <i class="icon">➕</i> Add New Patient
            </a>
            <a href="../pages/patient-list.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'patient-list.php' ? 'active' : ''; ?>">
                <i class="icon">📋</i> Patient List
            </a>
            <a href="../pages/appointments.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'appointments.php' ? 'active' : ''; ?>">
                <i class="icon">📅</i> Appointments
            </a>
        </nav>
        <div class="user-section">
            <span id="userName"><?php echo htmlspecialchars($_SESSION['user_name'] ?? ''); ?></span>
            <a href="../pages/logout.php" class="logout-btn">Logout</a>
        </div>
    </div>
    <?php endif; ?> 