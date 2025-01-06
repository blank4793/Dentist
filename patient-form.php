<?php
session_start();
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();

        // Sanitize and validate inputs
        $patientName = htmlspecialchars($_POST['patientName'] ?? '');
        $date = $_POST['date'] ?? '';
        $age = intval($_POST['age'] ?? 0);
        $gender = htmlspecialchars($_POST['gender'] ?? '');
        $sector = htmlspecialchars($_POST['sector'] ?? '');
        $streetNo = htmlspecialchars($_POST['streetNo'] ?? '');
        $houseNo = htmlspecialchars($_POST['houseNo'] ?? '');
        $nonIslamabadAddress = htmlspecialchars($_POST['nonIslamabadAddress'] ?? '');
        $phone = htmlspecialchars($_POST['phone'] ?? '');
        $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
        $occupation = htmlspecialchars($_POST['occupation'] ?? '');

        // Insert patient
        $stmt = $pdo->prepare("
            INSERT INTO patients (
                name, date, age, gender, 
                sector, street_no, house_no, non_islamabad_address,
                phone, email, occupation,
                created_at
            ) VALUES (
                ?, ?, ?, ?, 
                ?, ?, ?, ?,
                ?, ?, ?,
                NOW()
            )
        ");

        $stmt->execute([
            $patientName, $date, $age, $gender,
            $sector, $streetNo, $houseNo, $nonIslamabadAddress,
            $phone, $email, $occupation
        ]);

        $patientId = $pdo->lastInsertId();

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

        // Insert treatments if any
        if (isset($_POST['treatments'])) {
            $treatments = json_decode($_POST['treatments'], true);
            foreach ($treatments as $treatment) {
                $stmt = $pdo->prepare("
                    INSERT INTO treatments (
                        patient_id, treatment_name, price, 
                        status, treatment_date
                    ) VALUES (?, ?, ?, 'pending', NOW())
                ");
                $stmt->execute([
                    $patientId,
                    $treatment['name'],
                    $treatment['price']
                ]);
            }
        }

        // Insert visits if any
        if (isset($_POST['visit_date'])) {
            for ($i = 0; $i < count($_POST['visit_date']); $i++) {
                if (!empty($_POST['visit_date'][$i])) {
                    $stmt = $pdo->prepare("
                        INSERT INTO visits (
                            patient_id, visit_date, treatment,
                            amount, payment_mode, balance
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

        // Process dental treatments
        if (isset($_POST['dental_treatments'])) {
            $dentalTreatments = json_decode($_POST['dental_treatments'], true);
            
            foreach ($dentalTreatments as $treatment) {
                $stmt = $pdo->prepare("
                    INSERT INTO dental_treatments (
                        patient_id, 
                        tooth_number, 
                        treatment_type,
                        notes,
                        status,
                        treatment_date,
                        price
                    ) VALUES (?, ?, ?, ?, ?, NOW(), ?)
                ");
                
                $stmt->execute([
                    $patientId,
                    $treatment['tooth_number'],
                    $treatment['treatment_type'],
                    $treatment['notes'] ?? null,
                    'planned',
                    $treatment['price'] ?? null
                ]);
            }
        }

        $pdo->commit();
        $success = "Patient added successfully!";
        header("Location: view_patient.php?id=$patientId");
        exit();
    } catch(Exception $e) {
        $pdo->rollBack();
        $error = "Error: " . $e->getMessage();
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
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="dashboard-styles.css">
</head>
<body>
    <div class="dashboard-container">
        <?php include 'header.php'; ?>
        
        <div class="main-content">
            <div class="container">
                <div class="header">
                    <img src="tooth-icon.png" alt="Dental Clinic Logo" class="logo">
                    <h1>THE DENTAL CLINIC</h1>
                    <h2>PATIENT REGISTRATION AND MEDICAL RECORD</h2>
                </div>

                <?php if (isset($error)): ?>
                    <div class="error-message"><?php echo $error; ?></div>
                <?php endif; ?>

                <form id="patientForm" method="POST">
                    <!-- Personal Information -->
                    <div class="personal-info">
                        <div class="form-row name-date">
                            <div class="form-group">
                                <label for="patientName">PATIENT NAME</label>
                                <input type="text" id="patientName" name="patientName" required>
                            </div>
                            <div class="form-group">
                                <label for="date">DATE</label>
                                <input type="date" id="date" name="date" required>
                            </div>
                        </div>

                        <div class="form-row age-gender">
                            <div class="form-group">
                                <label for="age">AGE</label>
                                <input type="number" id="age" name="age" min="0" required>
                            </div>
                            <div class="form-group">
                                <label for="gender">GENDER</label>
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
                                <label for="phone">PHONE NO</label>
                                <input type="tel" id="phone" name="phone" required>
                            </div>
                            <div class="form-group">
                                <label for="email">EMAIL</label>
                                <input type="email" id="email" name="email">
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
                    <h3>MEDICAL HISTORY</h3>
                    <div class="medical-history">
                        <div class="history-grid">
                            <div class="history-item">
                                <input type="checkbox" id="heartProblem" name="heartProblem">
                                <label for="heartProblem">HEART PROBLEM</label>
                            </div>
                            <div class="history-item">
                                <input type="checkbox" id="bloodPressure" name="bloodPressure">
                                <label for="bloodPressure">BLOOD PRESSURE</label>
                            </div>
                            <div class="history-item">
                                <input type="checkbox" id="bleedingDisorder" name="bleedingDisorder">
                                <label for="bleedingDisorder">BLEEDING DISORDER</label>
                            </div>
                            <div class="history-item">
                                <input type="checkbox" id="bloodThinners" name="bloodThinners">
                                <label for="bloodThinners">BLOOD THINNERS etc. Loprin</label>
                            </div>
                            <div class="history-item">
                                <input type="checkbox" id="hepatitis" name="hepatitis">
                                <label for="hepatitis">HEPATITIS B or C</label>
                            </div>
                            <div class="history-item">
                                <input type="checkbox" id="diabetes" name="diabetes">
                                <label for="diabetes">DIABETES /SUGAR</label>
                            </div>
                            <div class="history-item">
                                <input type="checkbox" id="faintingSpells" name="faintingSpells">
                                <label for="faintingSpells">FAINTING SPELLS</label>
                            </div>
                            <div class="history-item">
                                <input type="checkbox" id="allergyAnesthesia" name="allergyAnesthesia">
                                <label for="allergyAnesthesia">ALLERGY TO LOCAL ANESTHESIA</label>
                            </div>
                            <div class="history-item">
                                <input type="checkbox" id="malignancy" name="malignancy">
                                <label for="malignancy">HISTORY OF MALIGNANCY</label>
                            </div>
                            <div class="history-item">
                                <input type="checkbox" id="previousSurgery" name="previousSurgery">
                                <label for="previousSurgery">DO YOU HAVE ANY PREVIOUS HISTORY OF ANY SURGERY</label>
                            </div>
                            <div class="history-item">
                                <input type="checkbox" id="epilepsy" name="epilepsy">
                                <label for="epilepsy">EPILEPSY/ SEIZURES</label>
                            </div>
                            <div class="history-item">
                                <input type="checkbox" id="asthma" name="asthma">
                                <label for="asthma">ASTHMA</label>
                            </div>
                            <div class="history-item">
                                <input type="checkbox" id="pregnant" name="pregnant">
                                <label for="pregnant">PREGNANT OR NURSING MOTHER</label>
                            </div>
                            <div class="history-item">
                                <input type="checkbox" id="phobia" name="phobia">
                                <label for="phobia">PHOEBIA TO DENTAL TREATMENT</label>
                            </div>
                            <div class="history-item">
                                <input type="checkbox" id="stomach" name="stomach">
                                <label for="stomach">STOMACH AND DIGESTIVE CONDITION</label>
                            </div>
                            <div class="history-item">
                                <input type="checkbox" id="allergy" name="allergy">
                                <label for="allergy">ALLERGY</label>
                            </div>
                            <div class="history-item">
                                <input type="checkbox" id="drugAllergy" name="drugAllergy">
                                <label for="drugAllergy">DRUG ALLERGY</label>
                            </div>
                            <div class="history-item">
                                <input type="checkbox" id="smoker" name="smoker">
                                <label for="smoker">SMOKER...?</label>
                            </div>
                            <div class="history-item">
                                <input type="checkbox" id="alcoholic" name="alcoholic">
                                <label for="alcoholic">ALCOHOLIC...?</label>
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
                        <div class="chart-container">
                            <?php include 'dental-chart.html'; ?>
                            <div class="selected-teeth-info">
                                <h3>Selected Teeth</h3>
                                <div id="selectedTeethList"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Treatment Section -->
                    <div class="treatment-section">
                        <h3>TREATMENT</h3>
                        <div class="form-row">
                            <div class="form-group treatment-select-group">
                                <label for="treatmentSelect">Select Treatment:</label>
                                <select id="treatmentSelect" name="treatment">
                                    <option value="">Select Treatment</option>
                                    <option value="consultation">Consultation (₹1000)</option>
                                    <option value="radiograph">Radiograph (₹1500)</option>
                                    <option value="fillingD">Filling Direct (₹3000)</option>
                                    <option value="fillingI">Filling Indirect (₹2500)</option>
                                    <option value="rct">RCT (₹15000)</option>
                                    <option value="pfmCrownD">PFM Crown Direct (₹12000)</option>
                                    <option value="pfmCrownI">PFM Crown Indirect (₹10000)</option>
                                    <option value="zirconia">Zirconia (₹20000)</option>
                                    <option value="extSimple">Extraction Simple (₹2000)</option>
                                    <option value="extComp">Extraction Complex (₹4000)</option>
                                    <option value="acrylicDent">Acrylic Denture (₹25000)</option>
                                    <option value="ccPlate">CC Plate (₹8000)</option>
                                    <option value="completeDenture">Complete Denture (₹35000)</option>
                                    <option value="flexideDenture">Flexide Denture (₹40000)</option>
                                    <option value="bridgeD">Bridge Direct (₹30000)</option>
                                    <option value="bridgeI">Bridge Indirect (₹25000)</option>
                                    <option value="implant">Implant (₹50000)</option>
                                    <option value="laserTeethWhitening">Laser Teeth Whitening (₹15000)</option>
                                    <option value="postAndCore">Post and Core (₹8000)</option>
                                    <option value="peadFilling">Pead Filling (₹2500)</option>
                                    <option value="peadExt">Pead Extraction (₹2000)</option>
                                    <option value="pulpotomy">Pulpotomy (₹5000)</option>
                                    <option value="toothJewels">Tooth Jewels (₹3000)</option>
                                    <option value="scalingAndPolishing">Scaling and Polishing (₹3500)</option>
                                    <option value="rootPlanning">Root Planning (₹5000)</option>
                                </select>
                            </div>
                        </div>

                        <table class="selected-treatments-table">
                            <thead>
                                <tr>
                                    <th width="30%">Treatment</th>
                                    <th width="15%">Quantity</th>
                                    <th width="20%">Price Per Unit</th>
                                    <th width="20%">Total Price</th>
                                    <th width="15%">Action</th>
                                </tr>
                            </thead>
                            <tbody id="selectedTreatmentsList">
                                <!-- Selected treatments will appear here -->
                            </tbody>
                        </table>
                    </div>

                    <!-- Billing Section -->
                    <div class="billing-section">
                        <h3>BILLING DETAILS</h3>
                        <div class="billing-table">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Treatment</th>
                                        <th>Amount</th>
                                    </tr>
                                </thead>
                                <tbody id="billingList">
                                    <!-- Treatment amounts will be added here dynamically -->
                                </tbody>
                                <tfoot>
                                    <tr class="total-row">
                                        <td>TOTAL AMOUNT</td>
                                        <td><span id="totalAmount">₹0</span></td>
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
                                            <input type="number" id="discountValue" name="discountValue" min="0" placeholder="Enter discount">
                                        </td>
                                    </tr>
                                    <tr class="net-total-row">
                                        <td>NET TOTAL</td>
                                        <td><span id="netTotal">₹0</span></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>

                    <!-- Visits Section -->
                    <div class="visits-section">
                        <h3>VISITS TRACKING</h3>
                        <div class="visits-table">
                            <table>
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
                                        <td><input type="number" class="amount-paid-input" name="visit_amount[]" step="0.01" min="0"></td>
                                        <td><input type="number" class="balance-input" name="visit_balance[]" readonly></td>
                                        <td><input type="date" class="date-input" name="visit_date[]"></td>
                                        <td><input type="text" class="treatment-input" name="visit_treatment[]"></td>
                                        <td><input type="text" class="mode-input" name="visit_mode[]"></td>
                                    </tr>
                                </tbody>
                            </table>
                            <button type="button" id="addVisitRow" class="add-visit-btn">Add Visit</button>
                        </div>
                    </div>

                    <input type="hidden" id="selectedTeethInput" name="selected_teeth" value="">

                    <button type="submit" class="submit-btn">Submit Registration</button>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="script.js"></script>
</body>
</html> 