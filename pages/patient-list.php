<?php
session_start();
require_once '../includes/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Get all patients with their treatments
$stmt = $pdo->query("
    SELECT 
        p.*,
        dt.treatment_name,
        dt.status
    FROM patients p
    LEFT JOIN dental_treatments dt ON p.id = dt.patient_id
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
    <link rel="stylesheet" href="../css/dashboard-styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <div class="dashboard-container">
        <?php include '../includes/header.php'; ?>
        
        <div class="main-content">
            <div class="header-section">
                <h1>Patient List</h1>
                <a href="patient-form.php" class="btn-primary">
                    <i class="fas fa-user-plus"></i> Add New Patient
                </a>
            </div>

            <!-- Add Search Form -->
            <div class="search-section">
                <form id="searchForm" class="search-form">
                    <div class="search-input-group">
                        <input type="text" 
                               id="searchInput" 
                               placeholder="Search by name or phone number..."
                               class="search-input">
                        <button type="submit" class="search-btn">
                            <i class="fas fa-search"></i> Search
                        </button>
                    </div>
                </form>
            </div>

            <div class="patient-list-container">
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
                        foreach ($patients as $patient): 
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

    <!-- Load scripts at the end of body -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="../js/dashboard.js"></script>
</body>
</html> 