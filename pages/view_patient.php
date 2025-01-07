<?php
session_start();
require_once '../includes/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Check if patient ID is provided
if (!isset($_GET['id'])) {
    header('Location: patient-list.php');
    exit();
}

$patientId = $_GET['id'];

try {
    // Get Patient Information
    $stmt = $pdo->prepare("
        SELECT *, 
               LENGTH(signature) as sig_length, 
               LENGTH(doctor_signature) as doc_sig_length 
        FROM patients 
        WHERE id = ?
    ");
    $stmt->execute([$patientId]);
    $patient = $stmt->fetch(PDO::FETCH_ASSOC);

    // Debug log
    error_log("Patient ID: " . $patientId);
    error_log("Signature length: " . ($patient['sig_length'] ?? 'null'));
    error_log("Doctor signature length: " . ($patient['doc_sig_length'] ?? 'null'));

    if (!$patient) {
        throw new Exception('Patient not found');
    }

    // Get Medical History
    $stmt = $pdo->prepare("SELECT * FROM medical_history WHERE patient_id = ?");
    $stmt->execute([$patientId]);
    $medicalHistory = $stmt->fetch(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    $error = $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Details - The Dental Clinic</title>
    <link rel="stylesheet" href="../css/styles.css">
    <style>
        .section-title {
            color: #333;
            border-bottom: 2px solid #007bff;
            padding-bottom: 5px;
            margin-bottom: 20px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            padding: 15px;
        }

        .info-item {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .info-item label {
            font-weight: bold;
            min-width: 100px;
            color: #333;
        }

        .info-item span {
            flex: 1;
        }

        .info-item[style*="grid-column"] {
            margin-top: 10px;
        }

        .medical-history {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-top: 15px;
        }

        .condition-item {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .condition-item .checkmark {
            color: #28a745;
            font-weight: bold;
        }

        .dental-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            align-items: start;
        }

        .dental-chart {
            grid-column: 1;
        }

        .diagnosis-treatment {
            grid-column: 2;
        }

        .tooth-highlighted {
            fill: #ffd700 !important; /* Yellow highlight */
            stroke: #ffa500;  /* Orange border */
            stroke-width: 2px;
        }

        .dental-chart {
            max-width: 500px;
            margin: 0 auto;
        }

        .dental-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            align-items: start;
        }

        .diagnosis-treatment {
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .diagnosis-treatment h4 {
            margin-bottom: 10px;
            color: #333;
        }

        .treatments-table, .visits-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        .treatments-table th,
        .treatments-table td,
        .visits-table th,
        .visits-table td {
            padding: 10px;
            border: 1px solid #dee2e6;
            text-align: left;
        }

        .treatments-table th,
        .visits-table th {
            background-color: #f8f9fa;
            font-weight: bold;
        }

        .total-row {
            background-color: #f8f9fa;
            font-weight: bold;
        }

        .discount-row {
            background-color: #fff3cd;
        }

        .net-total-row {
            background-color: #d4edda;
            font-weight: bold;
            color: #28a745;
        }

        .net-total-row td:last-child {
            color: #28a745;
            font-size: 1.1em;
        }

        .disclaimer-content {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin: 15px 0;
        }

        .disclaimer-text {
            font-style: italic;
            line-height: 1.6;
            color: #333;
            text-align: justify;
        }

        .signatures-container {
            display: flex;
            justify-content: space-between;
            gap: 30px;
            margin: 20px 0;
        }

        .signature-box {
            flex: 1;
            text-align: center;
            padding: 20px;
            border: 1px solid #dee2e6;
            border-radius: 8px;
        }

        .signature-box h4 {
            margin-bottom: 15px;
            color: #333;
            font-weight: bold;
        }

        .signature-image {
            max-width: 100%;
            height: auto;
            border-bottom: 1px solid #000;
        }

        .signature-placeholder {
            padding: 20px;
            border-bottom: 1px solid #000;
            color: #666;
            font-style: italic;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <?php include '../includes/header.php'; ?>
        
        <div class="main-content">
            <div class="container">
                <div class="header">
                    <img src="../assets/images/logo.jpeg" alt="Dental Clinic Logo" class="logo">
                    <h1>THE DENTAL CLINIC</h1>
                    <h2>PATIENT DETAILS</h2>
                </div>

                <?php if (isset($error)): ?>
                    <div class="error-message"><?php echo $error; ?></div>
                <?php else: ?>

                    <!-- Patient Information -->
                    <div class="section">
                        <h3 class="section-title">Patient Information</h3>
                        <div class="info-grid">
                            <div class="info-item">
                                <label>Patient ID:</label>
                                <span><?php echo htmlspecialchars($patient['patient_id']); ?></span>
                            </div>
                            <div class="info-item">
                                <label>Name:</label>
                                <span><?php echo htmlspecialchars($patient['name']); ?></span>
                            </div>
                            <div class="info-item">
                                <label>Date:</label>
                                <span><?php echo date('Y-m-d', strtotime($patient['date'])); ?></span>
                            </div>
                            <div class="info-item">
                                <label>Age:</label>
                                <span><?php echo htmlspecialchars($patient['age']); ?></span>
                            </div>
                            <div class="info-item">
                                <label>Gender:</label>
                                <span><?php echo htmlspecialchars($patient['gender']); ?></span>
                            </div>
                            <div class="info-item">
                                <label>Phone:</label>
                                <span><?php echo htmlspecialchars($patient['phone']); ?></span>
                            </div>
                            <div class="info-item">
                                <label>Email:</label>
                                <span><?php echo htmlspecialchars($patient['email'] ?? 'N/A'); ?></span>
                            </div>
                            <div class="info-item">
                                <label>Occupation:</label>
                                <span><?php echo htmlspecialchars($patient['occupation'] ?? 'N/A'); ?></span>
                            </div>
                            <div class="info-item" style="grid-column: 1 / -1;">
                                <label>Address:</label>
                                <span>
                                    <?php
                                    $address_parts = [];
                                    if (!empty($patient['sector'])) $address_parts[] = "Sector: " . htmlspecialchars($patient['sector']);
                                    if (!empty($patient['street_no'])) $address_parts[] = "Street: " . htmlspecialchars($patient['street_no']);
                                    if (!empty($patient['house_no'])) $address_parts[] = "House: " . htmlspecialchars($patient['house_no']);
                                    if (!empty($patient['non_islamabad_address'])) $address_parts[] = htmlspecialchars($patient['non_islamabad_address']);
                                    echo !empty($address_parts) ? implode(', ', $address_parts) : 'N/A';
                                    ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Medical History -->
                    <div class="section">
                        <h3 class="section-title">Medical History</h3>
                        <div class="medical-history">
                            <?php
                            $conditions = [
                                'heart_problem' => 'Heart Problem',
                                'blood_pressure' => 'Blood Pressure',
                                'bleeding_disorder' => 'Bleeding Disorder',
                                'diabetes' => 'Diabetes /Sugar',
                                'fainting_spells' => 'Fainting Spells',
                                'epilepsy' => 'Epilepsy/ Seizures',
                                'phobia' => 'Phobia to Dental Treatment'
                            ];

                            foreach ($conditions as $key => $label) {
                                if (!empty($medicalHistory[$key])) {
                                    echo '<div class="condition-item">';
                                    echo '<span>' . $label . '</span>';
                                    echo '<span class="checkmark">✓</span>';
                                    echo '</div>';
                                }
                            }
                            ?>
                        </div>
                    </div>

                    <!-- Dental Information -->
                    <div class="section">
                        <h3 class="section-title">Dental Information</h3>
                        <div class="dental-info">
                            <div class="dental-chart">
                                <?php 
                                // Get selected teeth array before including the chart
                                $selectedTeethArray = !empty($patient['selected_teeth']) ? 
                                    explode(',', $patient['selected_teeth']) : [];
                                
                                // Include the dental chart
                                include '../templates/dental-chart.html'; 
                                ?>
                            </div>
                            <div class="diagnosis-treatment">
                                <div class="diagnosis">
                                    <h4>Diagnosis:</h4>
                                    <p><?php echo nl2br(htmlspecialchars($patient['diagnosis'])); ?></p>
                                </div>
                                <div class="treatment">
                                    <h4>Treatment Advised:</h4>
                                    <p><?php echo nl2br(htmlspecialchars($patient['treatment_advised'])); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Treatments Section -->
                    <div class="section">
                        <h3 class="section-title">Treatments</h3>
                        <table class="treatments-table">
                            <thead>
                                <tr>
                                    <th>Treatment</th>
                                    <th>Teeth</th>
                                    <th>Quantity</th>
                                    <th>Price/Unit</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                // Get treatments
                                $stmt = $pdo->prepare("
                                    SELECT * FROM dental_treatments 
                                    WHERE patient_id = ?
                                ");
                                $stmt->execute([$patientId]);
                                $treatments = $stmt->fetchAll(PDO::FETCH_ASSOC);

                                $totalAmount = 0;
                                foreach ($treatments as $treatment): 
                                    $totalAmount += $treatment['total_price'];
                                ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($treatment['treatment_name']); ?></td>
                                        <td><?php echo htmlspecialchars($treatment['tooth_number']); ?></td>
                                        <td><?php echo $treatment['quantity']; ?></td>
                                        <td>Rs. <?php echo number_format($treatment['price_per_unit'], 2); ?></td>
                                        <td>Rs. <?php echo number_format($treatment['total_price'], 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr class="total-row">
                                    <td colspan="4">Total Amount:</td>
                                    <td>Rs. <?php echo number_format($totalAmount, 2); ?></td>
                                </tr>
                                <?php
                                // Get billing info
                                $stmt = $pdo->prepare("SELECT * FROM billing WHERE patient_id = ?");
                                $stmt->execute([$patientId]);
                                $billing = $stmt->fetch(PDO::FETCH_ASSOC);

                                if ($billing && $billing['discount_type'] !== 'none'):
                                    $discountAmount = $billing['discount_type'] === 'percentage' 
                                        ? ($totalAmount * $billing['discount_value'] / 100)
                                        : $billing['discount_value'];
                                ?>
                                    <tr class="discount-row">
                                        <td colspan="4">
                                            Discount (<?php echo $billing['discount_type'] === 'percentage' 
                                                ? $billing['discount_value'] . '%'
                                                : 'Fixed'; ?>)
                                        </td>
                                        <td>-Rs. <?php echo number_format($discountAmount, 2); ?></td>
                                    </tr>
                                    <tr class="net-total-row">
                                        <td colspan="4">Net Total:</td>
                                        <td>Rs. <?php echo number_format($totalAmount - $discountAmount, 2); ?></td>
                                    </tr>
                                <?php endif; ?>
                            </tfoot>
                        </table>
                    </div>

                    <!-- Visits Section -->
                    <div class="section">
                        <h3 class="section-title">Visits</h3>
                        <table class="visits-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Treatment Done</th>
                                    <th>Amount</th>
                                    <th>Payment Mode</th>
                                    <th>Balance</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $stmt = $pdo->prepare("
                                    SELECT * FROM visits 
                                    WHERE patient_id = ? 
                                    ORDER BY visit_date ASC
                                ");
                                $stmt->execute([$patientId]);
                                $visits = $stmt->fetchAll(PDO::FETCH_ASSOC);

                                foreach ($visits as $visit): ?>
                                    <tr>
                                        <td><?php echo date('Y-m-d', strtotime($visit['visit_date'])); ?></td>
                                        <td><?php echo htmlspecialchars($visit['treatment_done']); ?></td>
                                        <td>Rs. <?php echo number_format($visit['visit_amount'], 2); ?></td>
                                        <td><?php echo ucfirst($visit['visit_mode']); ?></td>
                                        <td>Rs. <?php echo number_format($visit['balance'], 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Disclaimer Section -->
                    <div class="section">
                        <h3 class="section-title">DISCLAIMER</h3>
                        <div class="disclaimer-content">
                            <p class="disclaimer-text">
                                I Affirm that the above information is best to my knowledge. I have not concealed any information regarding 
                                my medical history, I am fully aware that correct history is very important for the outcome of my treatment. 
                                I also affirm that I have discussed and understood the treatment and cost details. There is no guarantee for 
                                any treatment however responsibility of treatment may be for taken by the clinic.
                            </p>
                        </div>
                    </div>

                    <!-- Signatures Section -->
                    <div class="section">
                        <h3 class="section-title">SIGNATURES</h3>
                        <div class="signatures-container">
                            <div class="signature-box">
                                <h4>Patient Signature</h4>
                                <?php if (!empty($patient['signature'])): ?>
                                    <img src="data:image/png;base64,<?php echo htmlspecialchars($patient['signature']); ?>" 
                                         alt="Patient's Signature" 
                                         class="signature-image">
                                    <?php error_log("Patient signature exists and is being displayed"); ?>
                                <?php else: ?>
                                    <div class="signature-placeholder">No signature available</div>
                                    <?php error_log("No patient signature found in database"); ?>
                                <?php endif; ?>
                            </div>
                            <div class="signature-box">
                                <h4>Doctor Signature</h4>
                                <?php if (!empty($patient['doctor_signature'])): ?>
                                    <img src="data:image/png;base64,<?php echo htmlspecialchars($patient['doctor_signature']); ?>" 
                                         alt="Doctor's Signature" 
                                         class="signature-image">
                                    <?php error_log("Doctor signature exists and is being displayed"); ?>
                                <?php else: ?>
                                    <div class="signature-placeholder">No signature available</div>
                                    <?php error_log("No doctor signature found in database"); ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Get selected teeth from PHP
            const selectedTeeth = <?php echo json_encode($selectedTeethArray); ?>;
            console.log('Selected teeth:', selectedTeeth); // Debug log

            // Highlight selected teeth
            selectedTeeth.forEach(toothId => {
                const toothElement = document.querySelector(`[data-key="${toothId}"]`);
                if (toothElement) {
                    toothElement.classList.add('tooth-highlighted');
                    console.log('Highlighting tooth:', toothId); // Debug log
                }
            });

            // Make the chart read-only
            const toothElements = document.querySelectorAll('#Spots polygon, #Spots path');
            toothElements.forEach(tooth => {
                tooth.style.cursor = 'default';
                tooth.style.pointerEvents = 'none';
            });
        });
    </script>
</body>
</html> 