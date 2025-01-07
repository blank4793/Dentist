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
        <div class="logo-container">
            <img src="../assets/images/logo.jpeg" alt="Logo" class="logo">
            <h2>THE DENTAL CLINIC</h2>
        </div>
        <nav>
            <ul>
                <li><a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="patient-form.php"><i class="fas fa-user-plus"></i> Add New Patient</a></li>
                <li><a href="patient-list.php"><i class="fas fa-users"></i> Patient List</a></li>
            </ul>
        </nav>
        <div class="user-section">
            <span id="userName"><?php echo htmlspecialchars($_SESSION['user_name'] ?? ''); ?></span>
            <a href="../pages/logout.php" class="logout-btn">Logout</a>
        </div>
    </div>
    <?php endif; ?> 