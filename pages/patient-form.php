<?php
session_start();
require_once '../includes/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();

        // Insert patient
        $stmt = $pdo->prepare("
            INSERT INTO patients (
                name, date, sector, street_no, house_no, 
                non_islamabad_address, phone, age, gender, 
                occupation, email, diagnosis, treatment_advised, 
                selected_teeth
            ) VALUES (
                ?, ?, ?, ?, ?, 
                ?, ?, ?, ?, 
                ?, ?, ?, ?, 
                ?
            )
        ");

        $values = [
            $_POST['patientName'],
            $_POST['date'],
            $_POST['sector'],
            $_POST['streetNo'],
            $_POST['houseNo'],
            $_POST['nonIslamabadAddress'],
            $_POST['phone'],
            $_POST['age'],
            $_POST['gender'],
            $_POST['occupation'],
            $_POST['email'],
            $_POST['diagnosis'],
            $_POST['treatmentAdvised'],
            $_POST['selected_teeth']
        ];

        $stmt->execute($values);
        $patientId = $pdo->lastInsertId(); // Get the patient ID first

        // Now show debug information
        echo "<div style='
            position: fixed; 
            top: 20px; 
            right: 20px; 
            width: 80%; 
            max-width: 800px; 
            max-height: 80vh; 
            overflow-y: auto; 
            background: #f5f5f5; 
            padding: 20px; 
            margin: 20px; 
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.2);
            z-index: 9999;
        '>";
        echo "<h3 style='margin-top: 0;'>POST Data:</h3>";
        echo "<pre style='background: white; padding: 10px; overflow-x: auto;'>";
        print_r($_POST);
        echo "</pre>";

        echo "<h3>Values Being Inserted:</h3>";
        echo "<pre style='background: white; padding: 10px; overflow-x: auto;'>";
        print_r($values);
        echo "</pre>";

        echo "<button onclick='this.parentElement.style.display=\"none\";' style='
            position: absolute;
            top: 10px;
            right: 10px;
            padding: 5px 10px;
            background: #dc3545;
            color: white;
            border: none;
            border-radius: 3px;
            cursor: pointer;
        '>Close</button>";
        
        echo "<a href='view_patient.php?id=$patientId' style='
            display: inline-block;
            margin-top: 10px;
            padding: 10px 20px;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
        '>Continue to Patient View</a>";
        echo "</div>";

        // Insert medical history
        $stmt = $pdo->prepare("
            INSERT INTO medical_history (
                patient_id, heart_problem, blood_pressure, 
                bleeding_disorder, blood_thinners, hepatitis, 
                diabetes, fainting_spells, allergy_anesthesia,
                malignancy, previous_surgery, epilepsy, asthma,
                pregnant, phobia, stomach, allergy, drug_allergy,
                smoker, alcoholic, other_conditions
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
            )
        ");
        
        $stmt->execute([
            $patientId,
            isset($_POST['heartProblem']) ? 1 : 0,
            isset($_POST['bloodPressure']) ? 1 : 0,
            isset($_POST['bleedingDisorder']) ? 1 : 0,
            isset($_POST['bloodThinners']) ? 1 : 0,
            isset($_POST['hepatitis']) ? 1 : 0,
            isset($_POST['diabetes']) ? 1 : 0,
            isset($_POST['faintingSpells']) ? 1 : 0,
            isset($_POST['allergyAnesthesia']) ? 1 : 0,
            isset($_POST['malignancy']) ? 1 : 0,
            isset($_POST['previousSurgery']) ? 1 : 0,
            isset($_POST['epilepsy']) ? 1 : 0,
            isset($_POST['asthma']) ? 1 : 0,
            isset($_POST['pregnant']) ? 1 : 0,
            isset($_POST['phobia']) ? 1 : 0,
            isset($_POST['stomach']) ? 1 : 0,
            isset($_POST['allergy']) ? 1 : 0,
            isset($_POST['drugAllergy']) ? 1 : 0,
            isset($_POST['smoker']) ? 1 : 0,
            isset($_POST['alcoholic']) ? 1 : 0,
            $_POST['otherConditions'] ?? ''
        ]);

        // Insert treatments
        if (!empty($_POST['treatments'])) {
            $treatments = json_decode($_POST['treatments'], true);
            
            $stmt = $pdo->prepare("
                INSERT INTO dental_treatments (
                    patient_id, 
                    tooth_number,
                    treatment_name, 
                    quantity,
                    price_per_unit,
                    total_price,
                    discount_type,
                    discount_value,
                    net_total,
                    notes,
                    status
                ) VALUES (
                    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'planned'
                )
            ");

            foreach ($treatments as $treatment) {
                $stmt->execute([
                    $patientId,
                    $treatment['tooth_number'],
                    $treatment['name'],
                    $treatment['quantity'],
                    $treatment['pricePerUnit'],
                    $treatment['totalPrice'],
                    $_POST['discountType'],
                    $_POST['discountValue'],
                    $treatment['netTotal'],
                    $treatment['notes'] ?? null
                ]);
            }
        }

        // Insert visits if any
        if (isset($_POST['visit_date'])) {
            for ($i = 0; $i < count($_POST['visit_date']); $i++) {
                if (!empty($_POST['visit_date'][$i])) {
                    $stmt = $pdo->prepare("
                        INSERT INTO visits (
                            patient_id, 
                            visit_date, 
                            treatment_done,
                            visit_amount, 
                            visit_mode, 
                            balance
                        ) VALUES (?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $patientId,
                        $_POST['visit_date'][$i],
                        $_POST['visit_treatment'][$i],
                        $_POST['visit_amount'][$i],
                        $_POST['visit_mode'][$i],
                        $_POST['visit_balance'][$i]
                    ]);
                }
            }
        }

        $pdo->commit();
        $success = "Patient added successfully!";
        header("Location: view_patient.php?id=$patientId");
        exit();
    } catch(Exception $e) {
        $pdo->rollBack();
        $error = "Error: " . $e->getMessage();
        echo "<div style='background: #fee; padding: 20px; margin: 20px; border-radius: 5px; color: #c00;'>";
        echo "<h3>Error:</h3>";
        echo $error;
        echo "</div>";
    }
}

// Add this where you want to display existing treatments
function getExistingTreatments($patientId) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT * FROM dental_treatments 
        WHERE patient_id = ? 
        ORDER BY created_at DESC
    ");
    $stmt->execute([$patientId]);
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// If editing an existing patient
if (isset($patientId)) {
    $existingTreatments = getExistingTreatments($patientId);
    // Add this to your JavaScript section
    echo "<script>
        window.existingTreatments = " . json_encode($existingTreatments) . ";
    </script>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>The Dental Clinic - Patient Registration</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="../css/dashboard-styles.css">
    <script src="https://cdn.jsdelivr.net/npm/signature_pad@4.1.5/dist/signature_pad.umd.min.js"></script>
    <script src="../js/signature-pad.js"></script>
    <style>
        /* Add these styles for the dental chart */
        .dental-chart-section {
            margin: 20px 0;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
        }
        
        .dental-chart {
            max-width: 600px;
            margin: 0 auto;
        }
        
        .selected-teeth-info {
            margin-top: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 4px;
        }
        
        #selectedTeethList {
            min-height: 50px;
            padding: 10px;
            border: 1px dashed #ddd;
            margin-top: 10px;
        }

        .medical-history {
            padding: 20px;
            background: #fff;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .medical-history h3 {
            color: #2c3e50;
            margin-bottom: 20px;
            border-bottom: 2px solid #3498db;
            padding-bottom: 10px;
        }

        .medical-history-grid {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .history-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 15px;
            background-color: #f8f9fa;
            border: 1px solid #ddd;
        }

        .condition-label {
            font-weight: 500;
            color: #2c3e50;
            flex-grow: 1;
        }

        input[type="checkbox"] {
            width: 20px;
            height: 20px;
            margin-left: 15px;
            cursor: pointer;
        }

        .other-condition-input {
            width: 60%;
            padding: 5px 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        /* Add hover effect */
        .history-row:hover {
            background-color: #f0f0f0;
        }

        /* Make checkboxes more visible */
        input[type="checkbox"] {
            accent-color: #3498db;
        }

        .medical-history {
            padding: 20px;
            background: #fff;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .medical-history h3 {
            color: #2c3e50;
            margin-bottom: 20px;
            border-bottom: 2px solid #3498db;
            padding-bottom: 10px;
        }

        .medical-history-table {
            display: flex;
            gap: 20px;
        }

        .medical-history-column {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .history-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 15px;
            background-color: #f8f9fa;
            border: 1px solid #ddd;
        }

        .condition-label {
            font-weight: 500;
            color: #2c3e50;
            flex-grow: 1;
        }

        input[type="checkbox"] {
            width: 20px;
            height: 20px;
            margin-left: 15px;
            cursor: pointer;
            accent-color: #3498db;
        }

        /* Add hover effect */
        .history-row:hover {
            background-color: #f0f0f0;
        }

        .treatment-select-group label {
            display: block;
            font-weight: bold;
            margin-bottom: 8px;
            width: 100%;  /* Ensure full width */
            white-space: nowrap;  /* Prevent text wrapping */
            padding-right: 5px;  /* Add a little padding */
        }

        .patient-id-section {
            margin: 20px auto;
            max-width: 200px;  /* Made smaller */
            text-align: center;
        }

        .patient-id-group {
            margin-bottom: 20px;  /* Reduced margin */
        }

        .patient-id-group label {
            display: block;
            font-weight: bold;
            margin-bottom: 5px;  /* Reduced margin */
            color: #2c3e50;
            font-size: 1em;  /* Slightly smaller font */
        }

        .patient-id-input {
            width: 150px;  /* Made input field smaller */
            padding: 8px;
            text-align: center;
            font-size: 1em;
            border: 2px solid #3498db;
            border-radius: 4px;
            margin: 0 auto;
        }

        .patient-id-input:focus {
            outline: none;
            border-color: #2980b9;
            box-shadow: 0 0 5px rgba(52, 152, 219, 0.5);
        }

        .center-content {
            display: flex;
            justify-content: center;
            align-items: center;
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
                    <h2>PATIENT REGISTRATION AND MEDICAL RECORD</h2>
                </div>

                <?php if (isset($error)): ?>
                    <div class="error-message"><?php echo $error; ?></div>
                <?php endif; ?>

                <form id="patientForm" method="POST" action="save_patient.php">
                    <!-- Modified Patient ID section -->
                    <div class="patient-id-section">
                        <div class="patient-id-group">
                            <label for="patientId">Patient ID</label>
                            <input type="text" 
                                   class="patient-id-input" 
                                   id="patientId" 
                                   name="patientId" 
                                   pattern="[A-Za-z0-9]+" 
                                   title="Enter letters and numbers only"
                                   required>
                        </div>
                    </div>

                    <!-- Personal Information -->
                    <div class="personal-info">
                        <div class="form-row name-date">
                            <div class="form-group">
                                <label for="patientName">PATIENT NAME *</label>
                                <input type="text" 
                                       id="patientName" 
                                       name="patientName" 
                                       required 
                                       pattern="[A-Za-z\s\-'.]{2,100}"
                                       title="Name should only contain letters, spaces, hyphens, and apostrophes">
                            </div>
                            <div class="form-group">
                                <label for="date">DATE *</label>
                                <input type="date" 
                                       id="date" 
                                       name="date" 
                                       required>
                            </div>
                        </div>

                        <div class="form-row age-gender">
                            <div class="form-group">
                                <label for="age">AGE *</label>
                                <input type="number" 
                                       id="age" 
                                       name="age" 
                                       min="0" 
                                       max="150" 
                                       required>
                            </div>
                            <div class="form-group">
                                <label for="gender">GENDER *</label>
                                <select id="gender" name="gender" required>
                                    <option value="">Select Gender</option>
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                        </div>

                        <!-- Address fields (now blended with other fields) -->
                        <div class="form-row">
                            <div class="form-group">
                                <label for="sector">SECTOR</label>
                                <input type="text" id="sector" name="sector" placeholder="e.g., F-8, G-9">
                            </div>
                            <div class="form-group">
                                <label for="streetNo">STREET NO</label>
                                <input type="text" id="streetNo" name="streetNo">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="houseNo">HOUSE NO</label>
                                <input type="text" id="houseNo" name="houseNo">
                            </div>
                            <div class="form-group">
                                <label for="nonIslamabadAddress">NON ISLAMABAD RESIDENCE</label>
                                <input type="text" id="nonIslamabadAddress" name="nonIslamabadAddress" 
                                       placeholder="Enter complete address if outside Islamabad">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="phone">PHONE *</label>
                                <input type="tel" 
                                       id="phone" 
                                       name="phone" 
                                       required 
                                       pattern="\+?\d{10,15}"
                                       title="Phone number should be 10-15 digits, optionally starting with +">
                            </div>
                            <div class="form-group">
                                <label for="email">EMAIL</label>
                                <input type="email" 
                                       id="email" 
                                       name="email">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="occupation">OCCUPATION</label>
                                <input type="text" id="occupation" name="occupation">
                            </div>
                        </div>
                    </div>

                    <!-- Medical History -->
                    <div class="medical-history">
                        <h3>MEDICAL HISTORY</h3>
                        <div class="medical-history-table">
                            <div class="medical-history-column">
                                <div class="history-row">
                                    <span class="condition-label">HEART PROBLEM</span>
                                    <input type="checkbox" id="heartProblem" name="heartProblem">
                                </div>
                                <div class="history-row">
                                    <span class="condition-label">BLOOD PRESSURE</span>
                                    <input type="checkbox" id="bloodPressure" name="bloodPressure">
                                </div>
                                <div class="history-row">
                                    <span class="condition-label">BLEEDING DISORDER</span>
                                    <input type="checkbox" id="bleedingDisorder" name="bleedingDisorder">
                                </div>
                                <div class="history-row">
                                    <span class="condition-label">BLOOD THINNERS etc. Loprin</span>
                                    <input type="checkbox" id="bloodThinners" name="bloodThinners">
                                </div>
                                <div class="history-row">
                                    <span class="condition-label">HEPATITIS B or C</span>
                                    <input type="checkbox" id="hepatitis" name="hepatitis">
                                </div>
                                <div class="history-row">
                                    <span class="condition-label">DIABETES/SUGAR</span>
                                    <input type="checkbox" id="diabetes" name="diabetes">
                                </div>
                                <div class="history-row">
                                    <span class="condition-label">FAINTING SPELLS</span>
                                    <input type="checkbox" id="faintingSpells" name="faintingSpells">
                                </div>
                                <div class="history-row">
                                    <span class="condition-label">ALLERGY TO LOCAL ANESTHESIA</span>
                                    <input type="checkbox" id="allergyAnesthesia" name="allergyAnesthesia">
                                </div>
                                <div class="history-row">
                                    <span class="condition-label">HISTORY OF MALIGNANCY</span>
                                    <input type="checkbox" id="malignancy" name="malignancy">
                                </div>
                                <div class="history-row">
                                    <span class="condition-label">DO YOU HAVE ANY PREVIOUS HISTORY OF ANY SURGERY</span>
                                    <input type="checkbox" id="previousSurgery" name="previousSurgery">
                                </div>
                            </div>
                            <div class="medical-history-column">
                                <div class="history-row">
                                    <span class="condition-label">EPILEPSY/SEIZURES</span>
                                    <input type="checkbox" id="epilepsy" name="epilepsy">
                                </div>
                                <div class="history-row">
                                    <span class="condition-label">ASTHMA</span>
                                    <input type="checkbox" id="asthma" name="asthma">
                                </div>
                                <div class="history-row">
                                    <span class="condition-label">PREGNANT OR NURSING MOTHER</span>
                                    <input type="checkbox" id="pregnant" name="pregnant">
                                </div>
                                <div class="history-row">
                                    <span class="condition-label">PHOEBIA TO DENTAL TREATMENT</span>
                                    <input type="checkbox" id="phobia" name="phobia">
                                </div>
                                <div class="history-row">
                                    <span class="condition-label">STOMACH AND DIGESTIVE CONDITION</span>
                                    <input type="checkbox" id="stomach" name="stomach">
                                </div>
                                <div class="history-row">
                                    <span class="condition-label">ALLERGY</span>
                                    <input type="checkbox" id="allergy" name="allergy">
                                </div>
                                <div class="history-row">
                                    <span class="condition-label">DRUG ALLERGY</span>
                                    <input type="checkbox" id="drugAllergy" name="drugAllergy">
                                </div>
                                <div class="history-row">
                                    <span class="condition-label">SMOKER...?</span>
                                    <input type="checkbox" id="smoker" name="smoker">
                                </div>
                                <div class="history-row">
                                    <span class="condition-label">ALCOHOLIC...?</span>
                                    <input type="checkbox" id="alcoholic" name="alcoholic">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Diagnosis Section -->
                    <div class="diagnosis-section">
                        <h3>DIAGNOSIS</h3>
                        <div class="form-row full-width">
                            <div class="form-group">
                                <textarea id="diagnosis" name="diagnosis" class="auto-expand" placeholder="Enter diagnosis"></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Treatment Advised Section -->
                    <div class="treatment-advised-section">
                        <h3>TREATMENT ADVISED</h3>
                        <div class="form-row full-width">
                            <div class="form-group">
                                <textarea id="treatmentAdvised" name="treatmentAdvised" class="auto-expand" placeholder="Enter treatment advice"></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Dental Chart Section -->
                    <div class="dental-chart-section">
                        <h3>DENTAL CHART</h3>
                        <div class="dental-chart">
                            <?php include '../templates/dental-chart.html'; ?>
                        </div>
                        <div class="selected-teeth-info">
                            <h4>Selected Teeth</h4>
                            <div id="selectedTeethList"></div>
                            <input type="hidden" id="selectedTeethInput" name="selected_teeth" value="">
                        </div>
                    </div>

                    <!-- Treatment Section -->
                    <div class="treatment-section">
                        <h3>TREATMENT</h3>
                        <div class="form-row">
                            <div class="form-group treatment-select-group">
                                <label for="treatmentSelect">SELECT TREATMENT</label>
                                <select id="treatmentSelect" name="treatment" class="treatment-dropdown">
                                    <option value="">Select Treatment</option>
                                    <option value="consultation">Consultation (Rs. 2500)</option>
                                    <option value="radiograph">Radiograph (Rs. 500)</option>
                                    <option value="fillingD">Filling (D) (Rs. 8000)</option>
                                    <option value="pulpotomy">Pulpotomy (Rs. 9000)</option>
                                    <option value="rct">RCT (Rs. 18000)</option>
                                    <option value="pfmCrownD">PFM Crown (D) (Rs. 22000)</option>
                                    <option value="postAndCore">Post & Core build up (Rs. 8500)</option>
                                    <option value="zirconia">Zirconia (Rs. 26000)</option>
                                    <option value="extSimple">Ext (simple) (Rs. 9000)</option>
                                    <option value="extComp">Ext (Comp) (Rs. 12000)</option>
                                    <option value="impaction">Impaction (Rs. 35000)</option>
                                    <option value="minorSurgery">Minor Surgery (Rs. 8000)</option>
                                    <option value="scalingPolishing">Scaling and polishing (Rs. 12000)</option>
                                    <option value="rootPlanning">Root Planning (Rs. 10000)</option>
                                    <option value="veneers">U Veneers (Rs. 12000)</option>
                                    <option value="acrylicDent">Acrylic Dent U/L (Rs. 7000)</option>
                                    <option value="ccPlate">C.C Plate U/L per arch (Rs. 75000)</option>
                                    <option value="completeDenture">Complete Denture U/L (Rs. 80000)</option>
                                    <option value="flexideDenture">Flexide Denture U/L (Rs. 70000)</option>
                                    <option value="bridgeWork">Bridge Work (PFM) Per tooth (Rs. 18000)</option>
                                    <option value="prophyPolish">Prophy Polish (Rs. 10000)</option>
                                    <option value="implant">Implant Per tooth (Rs. 125000)</option>
                                    <option value="laserTeethWhitening">Laser Teeth Whitening (Rs. 28000)</option>
                                    <option value="peadFilling">Pead Filling (Rs. 4500)</option>
                                    <option value="peadExt">Pead Ext (Rs. 4500)</option>
                                    <option value="toothJewels">Tooth Jewels (Rs. 6000)</option>
                                    <option value="wisdomToothExt">Wisdom Tooth Ext (simple) (Rs. 12000)</option>
                                    <option value="eMaxCrowns">E-Max Crowns (Rs. 40000)</option>
                                    <option value="completeDentureSoft">Complete denture (soft liner) U/L (Rs. 115000)</option>
                                    <option value="digitalImpressions">Digital Impressions (Rs. 5000)</option>
                                </select>
                            </div>
                        </div>

                        <!-- Treatment Table -->
                        <table class="treatments-table">
                            <thead>
                                <tr>
                                    <th>Treatment</th>
                                    <th>Quantity</th>
                                    <th>Price/Unit</th>
                                    <th>Total</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody id="treatmentsTableBody">
                                <!-- Treatments will be added here dynamically -->
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="3" style="text-align: right;"><strong>Total Amount:</strong></td>
                                    <td colspan="2"><span id="totalAmount">Rs. 0</span></td>
                                </tr>
                            </tfoot>
                        </table>

                        <!-- Hidden input for treatments data -->
                        <input type="hidden" id="treatmentsInput" name="treatments" value="">
                    </div>

                    <!-- Billing Section -->
                    <div class="billing-section">
                        <h3>BILLING DETAILS</h3>
                        <div class="billing-table">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Treatment</th>
                                        <th>Total Amount</th>
                                    </tr>
                                </thead>
                                <tbody id="billingList">
                                    <!-- Treatments will be added here dynamically -->
                                </tbody>
                                <tfoot>
                                    <tr class="total-row">
                                        <td>TOTAL AMOUNT</td>
                                        <td><span id="billingTotalAmount">Rs.0.00</span></td>
                                    </tr>
                                    <tr class="discount-row">
                                        <td>
                                            DISCOUNT
                                            <select id="discountType" name="discountType">
                                                <option value="percentage">Percentage (%)</option>
                                                <option value="fixed">Fixed Amount</option>
                                            </select>
                                        </td>
                                        <td>
                                            <input type="number" 
                                                   id="discountValue" 
                                                   name="discountValue" 
                                                   min="0" 
                                                   step="any"
                                                   value="0">
                                        </td>
                                    </tr>
                                    <tr class="net-total-row">
                                        <td>NET TOTAL</td>
                                        <td><span id="netTotal">Rs.0.00</span></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>

                    <!-- Visits Section -->
                    <div class="visits-section">
                        <h3>VISITS TRACKING</h3>
                        <div class="visits-table">
                            <table id="visitsTable">
                                <thead>
                                    <tr>
                                        <th>NO. OF VISITS</th>
                                        <th>AMOUNT PAID</th>
                                        <th>BALANCE</th>
                                        <th>DATE</th>
                                        <th>TREATMENT DONE IN A VISIT</th>
                                        <th>MODE</th>
                                    </tr>
                                </thead>
                                <tbody id="visitsTableBody">
                                    <tr>
                                        <td>1<sup>ST</sup> VISIT</td>
                                        <td>
                                            <input type="number" 
                                                   class="amount-paid-input" 
                                                   name="visit_amount[]" 
                                                   min="0" 
                                                   step="1"
                                                   style="text-align: left;">
                                        </td>
                                        <td>
                                            <input type="text" 
                                                   class="balance-input" 
                                                   name="visit_balance[]" 
                                                   readonly>
                                        </td>
                                        <td><input type="date" class="date-input" name="visit_date[]"></td>
                                        <td><input type="text" class="treatment-input" name="visit_treatment[]"></td>
                                        <td>
                                            <select name="visit_mode[]" class="mode-input" required>
                                                <option value="">Select Payment Mode</option>
                                                <option value="cash">Cash</option>
                                                <option value="card">Card</option>
                                                <option value="insurance">Insurance</option>
                                            </select>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                            <button type="button" id="addVisitRow" class="add-visit-btn">Add Visit</button>
                        </div>
                    </div>

                    <input type="hidden" id="treatmentsInput" name="treatments" value="">

                    <!-- Disclaimer Section -->
                    <div class="form-section">
                        <h3>PLEASE READ THIS CAREFULLY</h3>
                        <div class="disclaimer-content">
                            <p style="font-style: italic;">I Affirm that the above information is best to my knowledge. I have not concealed any information regarding my medical history. I am fully aware that correct history is very important for the outcome of my treatment. I also affirm that I have discussed and understood the treatment and cost details. There is no guarantee for any treatment however responsibility of treatment may be for taken by the clinic.</p>
                            
                            <div class="signatures-container">
                                <!-- Patient Signature -->
                                <div class="signature-section">
                                    <label><strong>PATIENT SIGNATURE</strong></label>
                                    <div class="signature-pad-wrapper">
                                        <canvas id="patientSignaturePad"></canvas>
                                        <input type="hidden" name="patient_signature_data" id="patientSignatureData">
                                    </div>
                                    <div class="signature-buttons">
                                        <button type="button" class="btn btn-secondary" id="clearPatientSignature">Clear Signature</button>
                                    </div>
                                </div>

                                <!-- Doctor Signature -->
                                <div class="signature-section">
                                    <label><strong>DOCTOR SIGNATURE</strong></label>
                                    <div class="signature-pad-wrapper">
                                        <canvas id="doctorSignaturePad"></canvas>
                                        <input type="hidden" name="doctor_signature_data" id="doctorSignatureData">
                                    </div>
                                    <div class="signature-buttons">
                                        <button type="button" class="btn btn-secondary" id="clearDoctorSignature">Clear Signature</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="submit-btn">Submit Registration</button>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script>
        // Check if jQuery is loaded
        if (typeof jQuery === 'undefined') {
            console.error('jQuery is not loaded!');
        } else {
            console.log('jQuery is loaded');
        }
    </script>
    <script src="../js/shared.js"></script>
    <script src="../js/form-submission.js"></script>
    <script src="../js/dental-chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/signature_pad@4.1.5/dist/signature_pad.umd.min.js"></script>
    <script src="../js/signature-pad.js"></script>
</body>
</html> 