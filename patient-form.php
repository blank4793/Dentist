<?php
session_start();
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $pdo->beginTransaction();

        // Split age/gender field
        $ageGender = explode('/', $_POST['ageGender']);
        $age = trim($ageGender[0]);
        $gender = trim($ageGender[1] ?? '');
        
        // Insert patient information
        $stmt = $pdo->prepare("
            INSERT INTO patients (
                name, date, address, phone, age, gender, 
                occupation, email, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            $_POST['patientName'],
            $_POST['date'],
            $_POST['address'],
            $_POST['phone'],
            $age,
            $gender,
            $_POST['occupation'],
            $_POST['email']
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

        $pdo->commit();
        $success = "Patient added successfully!";
        header("Location: view_patient.php?id=$patientId");
        exit();
    } catch(Exception $e) {
        $pdo->rollBack();
        $error = "Error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>The Dental Clinic - Patient Registration</title>
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
                        <div class="form-row">
                            <div class="form-group">
                                <label for="patientName">PATIENT NAME</label>
                                <input type="text" id="patientName" name="patientName" required>
                            </div>
                            <div class="form-group">
                                <label for="date">DATE</label>
                                <input type="date" id="date" name="date" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="address">ADDRESS</label>
                            <input type="text" id="address" name="address" required>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="phone">PHONE NO</label>
                                <input type="tel" id="phone" name="phone" required>
                            </div>
                            <div class="form-group">
                                <label for="ageGender">AGE/GENDER</label>
                                <input type="text" id="ageGender" name="ageGender" required>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="occupation">OCCUPATION</label>
                                <input type="text" id="occupation" name="occupation">
                            </div>
                            <div class="form-group">
                                <label for="email">EMAIL</label>
                                <input type="email" id="email" name="email">
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

                    <!-- Treatment Section -->
                    <div class="treatment-section">
                        <h3>TREATMENT</h3>
                        <div class="form-row">
                            <div class="form-group">
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
                                    <th>Treatment</th>
                                    <th>Price</th>
                                    <th>Action</th>
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
                                <tbody>
                                    <tr>
                                        <td>1<sup>ST</sup> VISIT</td>
                                        <td><input type="number" class="amount-input" name="visit_amount[]"></td>
                                        <td><input type="number" class="balance-input" name="visit_balance[]"></td>
                                        <td><input type="date" class="date-input" name="visit_date[]"></td>
                                        <td><input type="text" class="treatment-input" name="visit_treatment[]"></td>
                                        <td><input type="text" class="mode-input" name="visit_mode[]"></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="signature-date">
                            <label>SIGNATURE/DATE:</label>
                            <input type="text" id="visitSignature" name="visit_signature">
                        </div>
                    </div>

                    <button type="submit" class="submit-btn">Submit Registration</button>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="script.js"></script>
</body>
</html> 