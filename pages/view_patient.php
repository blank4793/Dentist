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
    // Get patient basic information
    $stmt = $pdo->prepare("
        SELECT * 
        FROM patients 
        WHERE id = ?
    ");
    $stmt->execute([$patientId]);
    $patient = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$patient) {
        throw new Exception('Patient not found');
    }

    // Get medical history
    $stmt = $pdo->prepare("
        SELECT * FROM medical_history 
        WHERE patient_id = ?
    ");
    $stmt->execute([$patientId]);
    $medicalHistory = $stmt->fetch(PDO::FETCH_ASSOC);

    // Get dental treatments with billing info
    $stmt = $pdo->prepare("
        SELECT t.*, b.discount_type, b.discount_value 
        FROM dental_treatments t
        LEFT JOIN billing b ON b.patient_id = t.patient_id
        WHERE t.patient_id = ? 
        ORDER BY t.created_at DESC
    ");
    $stmt->execute([$patientId]);
    $treatments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Debug log
    error_log("Treatments fetched: " . print_r($treatments, true));

    // Get billing information with calculated fields
    $stmt = $pdo->prepare("
        SELECT b.*, 
               CASE 
                   WHEN b.discount_type = 'percentage' THEN (SELECT SUM(total_price) FROM dental_treatments WHERE patient_id = ?) * b.discount_value / 100
                   ELSE b.discount_value 
               END as calculated_discount
        FROM billing b 
        WHERE b.patient_id = ?
    ");
    $stmt->execute([$patientId, $patientId]);
    $billing = $stmt->fetch(PDO::FETCH_ASSOC);

    // Debug log
    error_log("Billing fetched: " . print_r($billing, true));

    // After fetching treatments and billing
    if (empty($treatments)) {
        error_log("Warning: No treatments found for patient ID: $patientId");
    }

    if (empty($billing)) {
        error_log("Warning: No billing found for patient ID: $patientId");
    }

    // Add data validation
    foreach ($treatments as $treatment) {
        if (!isset($treatment['treatment_name']) || !isset($treatment['total_price'])) {
            error_log("Warning: Invalid treatment data found: " . print_r($treatment, true));
        }
    }

    if ($billing) {
        if (!in_array($billing['discount_type'], ['none', 'percentage', 'fixed'])) {
            error_log("Warning: Invalid discount type in billing: " . $billing['discount_type']);
        }
        if (!is_numeric($billing['discount_value'])) {
            error_log("Warning: Invalid discount value in billing: " . $billing['discount_value']);
        }
    }

    // Get visits with proper ordering
    $stmt = $pdo->prepare("
        SELECT * FROM visits 
        WHERE patient_id = ? 
        ORDER BY visit_date DESC, id DESC
    ");
    $stmt->execute([$patientId]);
    $visits = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
    <link rel="stylesheet" href="../css/dashboard-styles.css">
    <link rel="stylesheet" href="../css/styles.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <style>
        .patient-details {
            padding: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .section {
            margin-bottom: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .section h3 {
            color: #2c3e50;
            margin-bottom: 15px;
            border-bottom: 2px solid #3498db;
            padding-bottom: 5px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
        }

        .info-item {
            margin-bottom: 8px;
        }

        .info-label {
            display: block;
            margin-bottom: 4px;
        }

        .medical-history-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 8px;
            padding: 10px;
        }

        .medical-item {
            padding: 10px 15px;
            font-size: 0.95em;
            background: #fff;
            border-radius: 6px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 5px;
        }

        .medical-item::after {
            content: '✓';
            color: #27ae60;
            font-weight: bold;
            font-size: 1.2em;
            margin-left: 10px;
        }

        .medical-item .info-label {
            color: #2c3e50;
            font-weight: 500;
        }

        .medical-history-grid {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
        }

        .medical-item.full-width {
            width: 100%;
            margin-top: 20px;
            background: #fff;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .medical-item.full-width .info-label {
            font-size: 1.1em;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 8px;
            display: block;
        }

        .medical-history-grid p {
            width: 100%;
            text-align: center;
            color: #666;
            padding: 20px;
        }

        .treatments-table, .visits-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        .treatments-table th, .visits-table th,
        .treatments-table td, .visits-table td {
            padding: 10px;
            border: 1px solid #ddd;
            text-align: left;
        }

        .treatments-table th, .visits-table th {
            background-color: #f4f6f8;
        }

        .selected-teeth {
            margin-top: 10px;
            padding: 10px;
            background: #f0f7ff;
            border-radius: 4px;
        }

        .action-buttons {
            margin-top: 20px;
            display: flex;
            gap: 10px;
        }

        .action-button {
            padding: 8px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            color: white;
        }

        .edit-button {
            background-color: #3498db;
        }

        .print-button {
            background-color: #2ecc71;
        }

        .dental-chart-view {
            max-width: 500px;
            margin: 10px 0;
            padding: 15px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            float: left;
            width: 48%;
        }

        .dental-chart-view svg {
            width: 100%;
            height: auto;
        }

        /* Style for highlighted teeth */
        .tooth-highlighted {
            fill: #ffd700 !important;
            stroke: #ffa500;
            stroke-width: 2px;
        }

        .billing-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        .billing-table th,
        .billing-table td {
            padding: 10px;
            border: 1px solid #ddd;
            text-align: left;
        }

        .billing-table th {
            background-color: #f4f6f8;
            font-weight: 600;
        }

        .total-row {
            background-color: #f8f9fa;
        }

        .discount-row {
            background-color: #fff3f3;
        }

        .net-total-row {
            background-color: #e8f4ff;
        }

        .discount-row td,
        .total-row td,
        .net-total-row td {
            font-weight: 500;
        }

        .disclaimer-section {
            margin-top: 30px;
            padding: 20px;
            border-top: 2px solid #ddd;
        }

        .disclaimer-section h3 {
            text-align: center;
            color: #333;
            margin-bottom: 15px;
        }

        .disclaimer-text {
            text-align: justify;
            line-height: 1.6;
            margin-bottom: 20px;
            color: #444;
        }

        .signature-section {
            margin-top: 30px;
            text-align: left;
        }

        .signature-line {
            margin-top: 10px;
            border-top: 1px solid #000;
            width: 300px;
        }

        /* Add a container for diagnosis and treatment advised */
        .dental-info-container {
            float: right;
            width: 48%;
            padding: 15px;
        }

        /* Clear the float */
        .section::after {
            content: "";
            display: table;
            clear: both;
        }

        /* Make diagnosis and treatment advised headings more prominent */
        .dental-info-container .info-label {
            display: block;
            font-size: 1.2em;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 10px;
            padding-bottom: 5px;
            border-bottom: 2px solid #3498db;
        }

        .dental-info-container .info-item {
            margin-bottom: 20px;
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        /* Print styles */
        @media print {
            /* Reset page layout */
            @page {
                size: A4;
                margin: 20mm;
            }

            /* Hide unnecessary elements */
            .dashboard-container > *:not(.main-content),
            .action-buttons,
            nav,
            .sidebar {
                display: none !important;
            }

            /* Basic page setup */
            body {
                background: white !important;
                margin: 0 !important;
                padding: 20mm !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            .main-content {
                margin: 0 !important;
                padding: 0 !important;
                width: 100% !important;
            }

            /* Header and logo */
            .header {
                text-align: center;
                margin-bottom: 20mm;
            }

            .header img.logo {
                width: 100px !important;
                height: auto !important;
            }

            .header h1 {
                font-size: 24pt !important;
                margin-top: 10mm !important;
                color: black !important;
            }

            /* Sections styling */
            .section {
                page-break-inside: avoid;
                margin-bottom: 15mm !important;
                padding: 5mm !important;
                border: 1px solid #000 !important;
                background: white !important;
                box-shadow: none !important;
            }

            .section h3 {
                font-size: 14pt !important;
                color: black !important;
                margin-bottom: 5mm !important;
                border-bottom: 1px solid #000 !important;
            }

            /* Medical history items */
            .medical-item {
                padding: 2mm 4mm !important;
                margin-bottom: 1mm !important;
                border: 1px solid #000 !important;
                background: white !important;
                display: flex !important;
                justify-content: space-between !important;
            }

            .medical-item::after {
                content: '✓';
                color: black !important;
            }

            /* Tables */
            .treatments-table,
            .visits-table {
                width: 100% !important;
                border-collapse: collapse !important;
                margin: 5mm 0 !important;
            }

            .treatments-table th,
            .visits-table th,
            .treatments-table td,
            .visits-table td {
                border: 1px solid #000 !important;
                padding: 2mm !important;
                text-align: left !important;
                color: black !important;
            }

            /* Dental chart section */
            .dental-chart-view {
                width: 45% !important;
                float: left !important;
                margin-right: 5% !important;
            }

            .dental-info-container {
                width: 45% !important;
                float: right !important;
            }

            /* Clear floats */
            .section::after {
                content: '';
                display: table;
                clear: both;
            }

            /* Disclaimer section */
            .disclaimer-section {
                page-break-inside: avoid;
                border-top: 2px solid #000 !important;
                margin-top: 10mm !important;
                padding: 5mm !important;
            }

            .signature-line {
                border-top: 1px solid #000 !important;
                width: 60mm !important;
                margin-top: 10mm !important;
            }

            /* Font settings */
            * {
                font-family: Arial, sans-serif !important;
                line-height: 1.3 !important;
            }

            /* Ensure proper page breaks */
            .section {
                page-break-inside: avoid;
            }

            /* Force background colors and images to print */
            * {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                color-adjust: exact !important;
            }

            /* Highlighted teeth in dental chart */
            .tooth-highlighted {
                fill: #ffd700 !important;
                stroke: #ffa500 !important;
                stroke-width: 2px !important;
            }
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
                </div>

                <?php if (isset($error)): ?>
                    <div class="error-message"><?php echo $error; ?></div>
                <?php else: ?>
                    <div class="patient-details">
                        <!-- Basic Information -->
                        <div class="section">
                            <h3>Patient Information</h3>
                            <div class="info-grid">
                                <div class="info-item">
                                    <span class="info-label">Name:</span>
                                    <span><?php echo htmlspecialchars($patient['name']); ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Date:</span>
                                    <span><?php echo htmlspecialchars($patient['date']); ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Age:</span>
                                    <span><?php echo htmlspecialchars($patient['age']); ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Gender:</span>
                                    <span><?php echo htmlspecialchars($patient['gender']); ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Phone:</span>
                                    <span><?php echo htmlspecialchars($patient['phone']); ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Email:</span>
                                    <span><?php echo htmlspecialchars($patient['email'] ?? 'N/A'); ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Address:</span>
                                    <span>
                                        <?php
                                        $address = [];
                                        if ($patient['sector']) $address[] = "Sector: " . htmlspecialchars($patient['sector']);
                                        if ($patient['street_no']) $address[] = "Street: " . htmlspecialchars($patient['street_no']);
                                        if ($patient['house_no']) $address[] = "House: " . htmlspecialchars($patient['house_no']);
                                        if ($patient['non_islamabad_address']) $address[] = htmlspecialchars($patient['non_islamabad_address']);
                                        echo implode(', ', $address) ?: 'N/A';
                                        ?>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Medical History -->
                        <div class="section">
                            <h3>Medical History</h3>
                            <div class="medical-history-grid">
                                <?php
                                $conditions = [
                                    'heart_problem' => 'Heart Problem',
                                    'blood_pressure' => 'Blood Pressure',
                                    'bleeding_disorder' => 'Bleeding Disorder',
                                    'blood_thinners' => 'Blood Thinners etc. Loprin',
                                    'hepatitis' => 'Hepatitis B or C',
                                    'diabetes' => 'Diabetes /Sugar',
                                    'fainting_spells' => 'Fainting Spells',
                                    'allergy_anesthesia' => 'Allergy to Local Anesthesia',
                                    'malignancy' => 'History of Malignancy',
                                    'previous_surgery' => 'Previous Surgery',
                                    'epilepsy' => 'Epilepsy/ Seizures',
                                    'asthma' => 'Asthma',
                                    'pregnant' => 'Pregnant or Nursing Mother',
                                    'phobia' => 'Phobia to Dental Treatment',
                                    'stomach' => 'Stomach and Digestive Condition',
                                    'allergy' => 'Allergy',
                                    'drug_allergy' => 'Drug Allergy',
                                    'smoker' => 'Smoker',
                                    'alcoholic' => 'Alcoholic'
                                ];

                                $hasConditions = false;
                                foreach ($conditions as $key => $label):
                                    if ($medicalHistory[$key]):
                                        $hasConditions = true;
                                ?>
                                        <div class="medical-item">
                                            <span class="info-label"><?php echo $label; ?></span>
                                        </div>
                                <?php 
                                    endif;
                                endforeach;
                                
                                if (!$hasConditions): 
                                ?>
                                    <p>No medical conditions reported.</p>
                                <?php endif; ?>

                                <?php if (!empty($medicalHistory['other_conditions'])): ?>
                                    <div class="medical-item full-width">
                                        <span class="info-label">Other Conditions:</span>
                                        <span><?php echo htmlspecialchars($medicalHistory['other_conditions']); ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Dental Chart & Treatment -->
                        <div class="section">
                            <h3>Dental Information</h3>
                            
                            <!-- Dental Chart Display -->
                            <div class="dental-chart-view">
                                <?php 
                                $selectedTeethArray = explode(',', $patient['selected_teeth']);
                                include '../templates/dental-chart.html';
                                ?>
                            </div>

                            <!-- Add container for diagnosis and treatment -->
                            <div class="dental-info-container">
                                <?php if ($patient['diagnosis']): ?>
                                    <div class="info-item">
                                        <span class="info-label">Diagnosis:</span>
                                        <span><?php echo nl2br(htmlspecialchars($patient['diagnosis'])); ?></span>
                                    </div>
                                <?php endif; ?>

                                <?php if ($patient['treatment_advised']): ?>
                                    <div class="info-item">
                                        <span class="info-label">Treatment Advised:</span>
                                        <span><?php echo nl2br(htmlspecialchars($patient['treatment_advised'])); ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Treatments -->
                        <div class="section">
                            <h3>Treatments</h3>
                            <?php if (!empty($treatments)): ?>
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
                                        $totalAmount = 0;
                                        foreach ($treatments as $treatment): 
                                            $totalAmount += $treatment['total_price'];
                                        ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($treatment['treatment_name']); ?></td>
                                                <td><?php echo htmlspecialchars($treatment['tooth_number']); ?></td>
                                                <td><?php echo htmlspecialchars($treatment['quantity']); ?></td>
                                                <td>Rs. <?php echo number_format($treatment['price_per_unit'], 2); ?></td>
                                                <td>Rs. <?php echo number_format($treatment['total_price'], 2); ?></td>
                                            </tr>
                                        <?php endforeach; ?>

                                        <!-- Total Amount Row -->
                                        <tr class="total-row">
                                            <td colspan="4"><strong>Total Amount:</strong></td>
                                            <td><strong>Rs. <?php echo number_format($totalAmount, 2); ?></strong></td>
                                        </tr>

                                        <!-- Discount Row -->
                                        <?php if (!empty($billing) && !empty($billing['discount_value'])): ?>
                                            <?php
                                            // Calculate discount amount
                                            $discountAmount = $billing['discount_type'] === 'percentage' 
                                                ? ($totalAmount * $billing['discount_value'] / 100)
                                                : $billing['discount_value'];
                                            
                                            // Calculate net total
                                            $netTotal = $totalAmount - $discountAmount;
                                            ?>
                                            <tr class="discount-row">
                                                <td colspan="4">
                                                    <strong>Discount <?php echo $billing['discount_type'] === 'percentage' 
                                                        ? "({$billing['discount_value']}%)" 
                                                        : "(Rs. " . number_format($billing['discount_value'], 2) . ")"; ?></strong>
                                                </td>
                                                <td><strong>-Rs. <?php echo number_format($discountAmount, 2); ?></strong></td>
                                            </tr>
                                            <tr class="net-total-row">
                                                <td colspan="4"><strong>Net Total:</strong></td>
                                                <td><strong>Rs. <?php echo number_format($netTotal, 2); ?></strong></td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            <?php else: ?>
                                <p>No treatments recorded.</p>
                            <?php endif; ?>
                        </div>

                        <!-- Visits -->
                        <div class="section">
                            <h3>Visits</h3>
                            <?php if (!empty($visits)): ?>
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
                                        $totalPaid = 0;
                                        foreach ($visits as $visit): 
                                            $totalPaid += $visit['visit_amount'];
                                        ?>
                                            <tr>
                                                <td><?php echo date('Y-m-d', strtotime($visit['visit_date'])); ?></td>
                                                <td><?php echo htmlspecialchars($visit['treatment_done']); ?></td>
                                                <td>Rs. <?php echo number_format($visit['visit_amount'], 2); ?></td>
                                                <td><?php echo htmlspecialchars($visit['visit_mode']); ?></td>
                                                <td>Rs. <?php echo number_format($visit['balance'], 2); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php else: ?>
                                <p>No visits recorded.</p>
                            <?php endif; ?>
                        </div>

                        <!-- Add this before the signatures section -->
                        <div class="section">
                            <h3>DISCLAIMER</h3>
                            <div class="disclaimer-content">
                                <p style="font-style: italic;">
                                    I Affirm that the above information is best to my knowledge. I have not concealed any information regarding 
                                    my medical history, I am fully aware that correct history is very important for the outcome of my treatment. 
                                    I also affirm that I have discussed and understood the treatment and cost details. There is no guarantee for 
                                    any treatment however responsibility of treatment may be for taken by the clinic.
                                </p>
                            </div>
                        </div>

                        <!-- Then the signatures section -->
                        <div class="section">
                            <h3>SIGNATURES</h3>
                            <div class="signatures-container">
                                <!-- Patient Signature -->
                                <div class="signature-display">
                                    <h4>Patient Signature</h4>
                                    <?php if (!empty($patient['signature'])): ?>
                                        <img src="data:image/png;base64,<?php echo $patient['signature']; ?>" 
                                             alt="Patient Signature" 
                                             class="signature-image">
                                    <?php else: ?>
                                        <p class="no-signature">No patient signature available</p>
                                    <?php endif; ?>
                                </div>

                                <!-- Doctor Signature -->
                                <div class="signature-display">
                                    <h4>Doctor Signature</h4>
                                    <?php if (!empty($patient['doctor_signature'])): ?>
                                        <img src="data:image/png;base64,<?php echo $patient['doctor_signature']; ?>" 
                                             alt="Doctor Signature" 
                                             class="signature-image">
                                    <?php else: ?>
                                        <p class="no-signature">No doctor signature available</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Finally the action buttons -->
                        <div class="action-buttons">
                            <button class="action-button edit-button" onclick="location.href='edit_patient.php?id=<?php echo $patientId; ?>'">
                                Edit Patient
                            </button>
                            <button class="action-button print-button" onclick="printRecord()">
                                Print Record
                            </button>
                            <button class="action-button save-button" onclick="generatePDF()">
                                Save to PDF
                            </button>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="../js/shared.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Get the selected teeth from PHP
        const selectedTeeth = <?php echo json_encode($selectedTeethArray); ?>;
        
        // Highlight the selected teeth
        selectedTeeth.forEach(toothId => {
            const toothElement = document.querySelector(`#Spots [data-key="${toothId}"]`);
            if (toothElement) {
                toothElement.classList.add('tooth-highlighted');
            }
        });

        // Make the chart read-only
        const toothElements = document.querySelectorAll('#Spots polygon, #Spots path');
        toothElements.forEach(tooth => {
            tooth.style.cursor = 'default';
            tooth.style.pointerEvents = 'none';
        });
    });

    window.jsPDF = window.jspdf.jsPDF;

    function generatePDF() {
        // Get patient name and date first
        const patientNameElement = document.querySelector('.info-grid .info-item:first-child span:last-child');
        const patientName = patientNameElement ? patientNameElement.textContent.trim() : 'patient';
        const date = new Date().toISOString().split('T')[0];

        // Get the logo element
        const logoImg = document.querySelector('.header img.logo');

        const loadingOverlay = document.createElement('div');
        loadingOverlay.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.8);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        `;
        loadingOverlay.innerHTML = '<div style="text-align: center;"><div class="loading-spinner"></div><p>Generating PDF...</p></div>';
        document.body.appendChild(loadingOverlay);

        // Hide action buttons before capturing
        const actionButtons = document.querySelector('.action-buttons');
        actionButtons.style.display = 'none';

        // Create new PDF document
        const doc = new jsPDF('p', 'pt', 'a4');
        const pageWidth = doc.internal.pageSize.getWidth();
        const pageHeight = doc.internal.pageSize.getHeight();
        const margin = 40;
        let currentY = 20;

        // Function to add page number
        function addPageNumber(doc, pageNumber) {
            doc.setFontSize(10);
            doc.text(`Page ${pageNumber}`, pageWidth - 60, pageHeight - 20);
        }

        // Process each section with optimized spacing
        async function processSections() {
            let pageNumber = 1;
            addPageNumber(doc, pageNumber);

            // Add logo and clinic name
            if (logoImg && logoImg.complete) {
                try {
                    // Add logo
                    const logoWidth = 100;
                    const logoHeight = 100 * (logoImg.height / logoImg.width);
                    doc.addImage(
                        logoImg.src,
                        'JPEG',
                        (pageWidth - logoWidth) / 2,
                        currentY,
                        logoWidth,
                        logoHeight
                    );
                    currentY += logoHeight + 10;

                    // Add clinic name
                    doc.setFontSize(20);
                    doc.setFont(undefined, 'bold');
                    const clinicName = "THE DENTAL CLINIC";
                    const textWidth = doc.getStringUnitWidth(clinicName) * doc.getFontSize();
                    doc.text(clinicName, (pageWidth - textWidth) / 2, currentY + 20);
                    
                    currentY += 40; // Space after clinic name
                } catch (error) {
                    console.error('Error adding logo:', error);
                    // Add clinic name even if logo fails
                    doc.setFontSize(20);
                    doc.setFont(undefined, 'bold');
                    const clinicName = "THE DENTAL CLINIC";
                    const textWidth = doc.getStringUnitWidth(clinicName) * doc.getFontSize();
                    doc.text(clinicName, (pageWidth - textWidth) / 2, currentY + 20);
                    currentY += 40;
                }
            }

            // Get all sections
            const sections = document.querySelectorAll('.section');
            
            for (let i = 0; i < sections.length; i++) {
                const section = sections[i];
                
                // Skip empty sections or sections with no visible content
                if (!section.offsetHeight || !section.querySelector('h3')) {
                    continue;
                }

                // Capture section content
                const canvas = await html2canvas(section, {
                    scale: 2,
                    useCORS: true,
                    logging: false,
                    allowTaint: true,
                    height: section.scrollHeight,
                    windowHeight: section.scrollHeight,
                    onclone: function(clonedDoc) {
                        const clonedSection = clonedDoc.querySelector('.section');
                        if (clonedSection) {
                            // Remove any extra padding/margins
                            clonedSection.style.margin = '0';
                            clonedSection.style.padding = '10px';
                        }
                    }
                });

                const imgData = canvas.toDataURL('image/png');
                const imgProps = doc.getImageProperties(imgData);
                const imgWidth = pageWidth - (margin * 2);
                const imgHeight = (imgProps.height * imgWidth) / imgProps.width;

                // Check if we need a new page
                if (currentY + imgHeight > pageHeight - margin) {
                    doc.addPage();
                    currentY = margin;
                    pageNumber++;
                    addPageNumber(doc, pageNumber);
                }

                // Add section content with minimal spacing
                doc.addImage(imgData, 'PNG', margin, currentY, imgWidth, imgHeight);
                currentY += imgHeight + 15; // Reduced spacing between sections
            }

            // Save the PDF
            const filename = `${patientName}_dental_record_${date}.pdf`;
            doc.save(filename);
            actionButtons.style.display = 'flex';
            document.body.removeChild(loadingOverlay);
        }

        // Start processing with error handling
        processSections().catch(error => {
            console.error('Error generating PDF:', error);
            actionButtons.style.display = 'flex';
            document.body.removeChild(loadingOverlay);
            alert('Error generating PDF. Please try again.');
        });
    }

    // Add loading spinner styles
    const style = document.createElement('style');
    style.textContent = `
        .loading-spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #3498db;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            margin: 20px auto;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .save-button {
            background-color: #9b59b6;
        }

        @media print {
            .action-buttons {
                display: none !important;
            }
        }
    `;
    document.head.appendChild(style);

    function printRecord() {
        // Create an iframe
        const printFrame = document.createElement('iframe');
        printFrame.style.position = 'fixed';
        printFrame.style.right = '0';
        printFrame.style.bottom = '0';
        printFrame.style.width = '0';
        printFrame.style.height = '0';
        printFrame.style.border = 'none';
        
        document.body.appendChild(printFrame);
        
        // Get the content to print
        const printContent = document.querySelector('.patient-details').cloneNode(true);
        const header = document.querySelector('.header').cloneNode(true);
        
        // Create the print document
        const printDocument = `
            <!DOCTYPE html>
            <html>
            <head>
                <title>Patient Record</title>
                <style>
                    @media print {
                        body {
                            padding: 20mm;
                            margin: 0;
                            background: white;
                        }
                        .header {
                            text-align: center;
                            margin-bottom: 20mm;
                        }
                        .header img {
                            max-width: 100px;
                            height: auto;
                        }
                        .section {
                            page-break-inside: avoid;
                            margin-bottom: 10mm;
                            padding: 10px;
                            border: 1px solid #ddd;
                        }
                        table {
                            width: 100%;
                            border-collapse: collapse;
                        }
                        th, td {
                            border: 1px solid black;
                            padding: 2mm;
                        }
                        .action-buttons {
                            display: none !important;
                        }
                        .dental-chart-view {
                            width: 45%;
                            float: left;
                            margin-right: 5%;
                        }
                        .dental-info-container {
                            width: 45%;
                            float: right;
                        }
                        .medical-item {
                            padding: 8px;
                            margin-bottom: 5px;
                            border: 1px solid #ddd;
                        }
                        .medical-item::after {
                            content: '✓';
                            float: right;
                        }
                        @page {
                            size: A4;
                            margin: 20mm;
                        }
                    }
                </style>
            </head>
            <body>
                ${header.outerHTML}
                ${printContent.outerHTML}
            </body>
            </html>
        `;
        
        // Write to iframe and print
        const frameDoc = printFrame.contentWindow.document;
        frameDoc.open();
        frameDoc.write(printDocument);
        frameDoc.close();
        
        // Wait for images to load before printing
        printFrame.onload = function() {
            setTimeout(() => {
                printFrame.contentWindow.print();
                // Remove the iframe after printing
                setTimeout(() => {
                    document.body.removeChild(printFrame);
                }, 1000);
            }, 500);
        };
    }

    // Function to reattach event listeners
    function attachEventListeners() {
        // Reattach print button listener
        const printButton = document.querySelector('.print-button');
        if (printButton) {
            printButton.onclick = printRecord;
        }
        
        // Reattach edit button listener
        const editButton = document.querySelector('.edit-button');
        if (editButton) {
            editButton.onclick = function() {
                location.href = `edit_patient.php?id=<?php echo $patientId; ?>`;
            };
        }
        
        // Reattach save PDF button listener
        const saveButton = document.querySelector('.save-button');
        if (saveButton) {
            saveButton.onclick = generatePDF;
        }

        // Reattach dental chart highlighting
        const selectedTeeth = <?php echo json_encode($selectedTeethArray); ?>;
        selectedTeeth.forEach(toothId => {
            const toothElement = document.querySelector(`#Spots [data-key="${toothId}"]`);
            if (toothElement) {
                toothElement.classList.add('tooth-highlighted');
            }
        });
    }
    </script>
</body>
</html> 