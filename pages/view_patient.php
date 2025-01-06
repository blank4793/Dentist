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
        SELECT * FROM patients 
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

    // Get dental treatments
    $stmt = $pdo->prepare("
        SELECT * FROM dental_treatments 
        WHERE patient_id = ? 
        ORDER BY created_at DESC
    ");
    $stmt->execute([$patientId]);
    $treatments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get visits
    $stmt = $pdo->prepare("
        SELECT * FROM visits 
        WHERE patient_id = ? 
        ORDER BY visit_date DESC
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
    <style>
        .patient-details {
            padding: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .section {
            margin-bottom: 30px;
            padding: 20px;
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
            margin-bottom: 10px;
        }

        .info-label {
            font-weight: bold;
            color: #34495e;
        }

        .medical-history-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            padding: 15px;
        }

        .medical-item {
            background-color: #ffeaea;
            padding: 8px 15px;
            border-radius: 4px;
            color: #e74c3c;
            font-weight: 500;
        }

        .medical-item.full-width {
            width: 100%;
            margin-top: 15px;
            background-color: #f8f9fa;
            color: #2c3e50;
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
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
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
    </style>
</head>
<body>
    <div class="dashboard-container">
        <?php include '../includes/header.php'; ?>
        
        <div class="main-content">
            <div class="container">
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
                                // Get selected teeth array
                                $selectedTeethArray = explode(',', $patient['selected_teeth']);
                                
                                // Include the dental chart template
                                include '../templates/dental-chart.html';
                                ?>
                            </div>

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
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($treatments as $treatment): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($treatment['treatment_name']); ?></td>
                                                <td><?php echo htmlspecialchars($treatment['tooth_number']); ?></td>
                                                <td><?php echo htmlspecialchars($treatment['quantity']); ?></td>
                                                <td>₹<?php echo htmlspecialchars($treatment['price_per_unit']); ?></td>
                                                <td>₹<?php echo htmlspecialchars($treatment['total_price']); ?></td>
                                                <td><?php echo htmlspecialchars($treatment['status']); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
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
                                        <?php foreach ($visits as $visit): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($visit['visit_date']); ?></td>
                                                <td><?php echo htmlspecialchars($visit['treatment_done']); ?></td>
                                                <td>₹<?php echo htmlspecialchars($visit['visit_amount']); ?></td>
                                                <td><?php echo htmlspecialchars($visit['visit_mode']); ?></td>
                                                <td>₹<?php echo htmlspecialchars($visit['balance']); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php else: ?>
                                <p>No visits recorded.</p>
                            <?php endif; ?>
                        </div>

                        <!-- Action Buttons -->
                        <div class="action-buttons">
                            <button class="action-button edit-button" onclick="location.href='edit_patient.php?id=<?php echo $patientId; ?>'">
                                Edit Patient
                            </button>
                            <button class="action-button print-button" onclick="window.print()">
                                Print Record
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
    </script>
</body>
</html> 