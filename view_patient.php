<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    header('Location: login.php');
    exit();
}

$patientId = $_GET['id'];

// Get patient details
$stmt = $pdo->prepare("SELECT * FROM patients WHERE id = ?");
$stmt->execute([$patientId]);
$patient = $stmt->fetch();

// Get medical history
$stmt = $pdo->prepare("SELECT * FROM medical_history WHERE patient_id = ?");
$stmt->execute([$patientId]);
$medicalHistory = $stmt->fetch();

// Get treatments
$stmt = $pdo->prepare("SELECT * FROM treatments WHERE patient_id = ? ORDER BY treatment_date DESC");
$stmt->execute([$patientId]);
$treatments = $stmt->fetchAll();

// Get visits
$stmt = $pdo->prepare("SELECT * FROM visits WHERE patient_id = ? ORDER BY visit_date DESC");
$stmt->execute([$patientId]);
$visits = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Patient - Dental Clinic</title>
    <link rel="stylesheet" href="dashboard-styles.css">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="dashboard-container">
        <?php include 'header.php'; ?>
        
        <div class="main-content">
            <div class="header-section">
                <h1>Patient Details</h1>
                <div class="action-buttons">
                    <a href="edit_patient.php?id=<?php echo $patientId; ?>" class="btn-primary">Edit Patient</a>
                    <a href="patient-list.php" class="btn-secondary">Back to List</a>
                </div>
            </div>

            <div class="patient-details">
                <section class="detail-section">
                    <h2>Personal Information</h2>
                    <div class="detail-grid">
                        <div class="detail-item">
                            <label>Name:</label>
                            <span><?php echo htmlspecialchars($patient['name']); ?></span>
                        </div>
                        <div class="detail-item">
                            <label>Age/Gender:</label>
                            <span><?php echo htmlspecialchars($patient['age'] . '/' . $patient['gender']); ?></span>
                        </div>
                        <div class="detail-item">
                            <label>Phone:</label>
                            <span><?php echo htmlspecialchars($patient['phone']); ?></span>
                        </div>
                        <div class="detail-item">
                            <label>Email:</label>
                            <span><?php echo htmlspecialchars($patient['email']); ?></span>
                        </div>
                        <div class="detail-item">
                            <label>Address:</label>
                            <span><?php echo htmlspecialchars($patient['address']); ?></span>
                        </div>
                    </div>
                </section>

                <section class="detail-section">
                    <h2>Medical History</h2>
                    <div class="medical-history-grid">
                        <?php foreach ($medicalHistory as $key => $value): ?>
                            <?php if ($key !== 'id' && $key !== 'patient_id'): ?>
                            <div class="history-item">
                                <label><?php echo ucwords(str_replace('_', ' ', $key)); ?>:</label>
                                <span><?php echo $value ? 'Yes' : 'No'; ?></span>
                            </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </section>

                <section class="detail-section">
                    <h2>Treatments</h2>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Treatment</th>
                                <th>Price</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($treatments as $treatment): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($treatment['treatment_name']); ?></td>
                                <td>₹<?php echo number_format($treatment['price']); ?></td>
                                <td><?php echo htmlspecialchars($treatment['status']); ?></td>
                                <td><?php echo $treatment['treatment_date']; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </section>

                <section class="detail-section">
                    <h2>Visits</h2>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Treatment</th>
                                <th>Amount Paid</th>
                                <th>Mode</th>
                                <th>Balance</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($visits as $visit): ?>
                            <tr>
                                <td><?php echo $visit['visit_date']; ?></td>
                                <td><?php echo htmlspecialchars($visit['treatment']); ?></td>
                                <td>₹<?php echo number_format($visit['amount']); ?></td>
                                <td><?php echo htmlspecialchars($visit['payment_mode']); ?></td>
                                <td>₹<?php echo number_format($visit['balance']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </section>
            </div>
        </div>
    </div>
</body>
</html> 