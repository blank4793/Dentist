<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    header('Location: login.php');
    exit();
}

$patientId = $_GET['id'];

try {
    // Get patient data
    $stmt = $pdo->prepare("SELECT * FROM patients WHERE id = ?");
    $stmt->execute([$patientId]);
    $patient = $stmt->fetch();

    if (!$patient) {
        throw new Exception("Patient not found");
    }

    // Get medical history
    $stmt = $pdo->prepare("SELECT * FROM medical_history WHERE patient_id = ?");
    $stmt->execute([$patientId]);
    $medicalHistory = $stmt->fetch();

    // If no medical history exists, create default values
    if (!$medicalHistory) {
        $medicalHistory = [
            'heart_problem' => 0,
            'blood_pressure' => 0,
            'bleeding_disorder' => 0,
            'blood_thinners' => 0,
            'hepatitis' => 0,
            'diabetes' => 0,
            'fainting_spells' => 0,
            'allergy_anesthesia' => 0,
            'malignancy' => 0,
            'previous_surgery' => 0,
            'epilepsy' => 0,
            'asthma' => 0,
            'pregnant' => 0,
            'phobia' => 0,
            'stomach' => 0,
            'allergy' => 0,
            'drug_allergy' => 0,
            'smoker' => 0,
            'alcoholic' => 0,
            'other_conditions' => ''
        ];

        // Insert default medical history
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
            0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
            0, 0, 0, 0, 0, 0, 0, 0, 0, ''
        ]);
    }

    // Get treatments
    $stmt = $pdo->prepare("SELECT * FROM treatments WHERE patient_id = ? ORDER BY treatment_date DESC");
    $stmt->execute([$patientId]);
    $treatments = $stmt->fetchAll();

    // Get visits
    $stmt = $pdo->prepare("SELECT * FROM visits WHERE patient_id = ? ORDER BY visit_date ASC");
    $stmt->execute([$patientId]);
    $visits = $stmt->fetchAll();

} catch(Exception $e) {
    $error = "Error: " . $e->getMessage();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $pdo->beginTransaction();

        // Split age/gender field
        $ageGender = explode('/', $_POST['ageGender']);
        $age = trim($ageGender[0]);
        $gender = trim($ageGender[1] ?? '');

        // Update patient information
        $stmt = $pdo->prepare("
            UPDATE patients 
            SET name = ?, date = ?, address = ?, phone = ?, 
                age = ?, gender = ?, occupation = ?, email = ?
            WHERE id = ?
        ");
        
        $stmt->execute([
            $_POST['patientName'],
            $_POST['date'],
            $_POST['address'],
            $_POST['phone'],
            $age,
            $gender,
            $_POST['occupation'],
            $_POST['email'],
            $patientId
        ]);

        // Update medical history
        $stmt = $pdo->prepare("
            UPDATE medical_history 
            SET heart_problem = ?, blood_pressure = ?, 
                bleeding_disorder = ?, blood_thinners = ?,
                hepatitis = ?, diabetes = ?, 
                fainting_spells = ?, allergy_anesthesia = ?,
                malignancy = ?, previous_surgery = ?,
                epilepsy = ?, asthma = ?,
                pregnant = ?, phobia = ?,
                stomach = ?, allergy = ?,
                drug_allergy = ?, smoker = ?,
                alcoholic = ?, other_conditions = ?
            WHERE patient_id = ?
        ");

        $stmt->execute([
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
            $_POST['otherConditions'] ?? '',
            $patientId
        ]);

        // Update treatments
        if (isset($_POST['treatments'])) {
            $stmt = $pdo->prepare("DELETE FROM treatments WHERE patient_id = ?");
            $stmt->execute([$patientId]);

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

        // Update visits
        if (isset($_POST['visit_date'])) {
            $stmt = $pdo->prepare("DELETE FROM visits WHERE patient_id = ?");
            $stmt->execute([$patientId]);

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
        $success = "Patient updated successfully!";
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
    <title>Edit Patient - Dental Clinic</title>
    <link rel="stylesheet" href="dashboard-styles.css">
    <link rel="stylesheet" href="styles.css">
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
                                <input type="text" id="patientName" name="patientName" 
                                       value="<?php echo htmlspecialchars($patient['name']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="date">DATE</label>
                                <input type="date" id="date" name="date" 
                                       value="<?php echo htmlspecialchars($patient['date']); ?>" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="address">ADDRESS</label>
                            <input type="text" id="address" name="address" 
                                   value="<?php echo htmlspecialchars($patient['address']); ?>" required>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="phone">PHONE NO</label>
                                <input type="tel" id="phone" name="phone" 
                                       value="<?php echo htmlspecialchars($patient['phone']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="ageGender">AGE/GENDER</label>
                                <input type="text" id="ageGender" name="ageGender" 
                                       value="<?php echo htmlspecialchars($patient['age'] . '/' . $patient['gender']); ?>" required>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="occupation">OCCUPATION</label>
                                <input type="text" id="occupation" name="occupation" 
                                       value="<?php echo htmlspecialchars($patient['occupation']); ?>">
                            </div>
                            <div class="form-group">
                                <label for="email">EMAIL</label>
                                <input type="email" id="email" name="email" 
                                       value="<?php echo htmlspecialchars($patient['email']); ?>">
                            </div>
                        </div>
                    </div>

                    <!-- Medical History -->
                    <h3>MEDICAL HISTORY</h3>
                    <div class="medical-history">
                        <div class="history-grid">
                            <div class="history-item">
                                <input type="checkbox" id="heartProblem" name="heartProblem" 
                                       <?php echo $medicalHistory['heart_problem'] ? 'checked' : ''; ?>>
                                <label for="heartProblem">HEART PROBLEM</label>
                            </div>
                            <div class="history-item">
                                <input type="checkbox" id="bloodPressure" name="bloodPressure" 
                                       <?php echo $medicalHistory['blood_pressure'] ? 'checked' : ''; ?>>
                                <label for="bloodPressure">BLOOD PRESSURE</label>
                            </div>
                            <div class="history-item">
                                <input type="checkbox" id="bleedingDisorder" name="bleedingDisorder" 
                                       <?php echo $medicalHistory['bleeding_disorder'] ? 'checked' : ''; ?>>
                                <label for="bleedingDisorder">BLEEDING DISORDER</label>
                            </div>
                            <div class="history-item">
                                <input type="checkbox" id="bloodThinners" name="bloodThinners" 
                                       <?php echo $medicalHistory['blood_thinners'] ? 'checked' : ''; ?>>
                                <label for="bloodThinners">BLOOD THINNERS etc. Loprin</label>
                            </div>
                            <div class="history-item">
                                <input type="checkbox" id="hepatitis" name="hepatitis" 
                                       <?php echo $medicalHistory['hepatitis'] ? 'checked' : ''; ?>>
                                <label for="hepatitis">HEPATITIS B or C</label>
                            </div>
                            <div class="history-item">
                                <input type="checkbox" id="diabetes" name="diabetes" 
                                       <?php echo $medicalHistory['diabetes'] ? 'checked' : ''; ?>>
                                <label for="diabetes">DIABETES /SUGAR</label>
                            </div>
                            <div class="history-item">
                                <input type="checkbox" id="faintingSpells" name="faintingSpells" 
                                       <?php echo $medicalHistory['fainting_spells'] ? 'checked' : ''; ?>>
                                <label for="faintingSpells">FAINTING SPELLS</label>
                            </div>
                            <div class="history-item">
                                <input type="checkbox" id="allergyAnesthesia" name="allergyAnesthesia" 
                                       <?php echo $medicalHistory['allergy_anesthesia'] ? 'checked' : ''; ?>>
                                <label for="allergyAnesthesia">ALLERGY TO LOCAL ANESTHESIA</label>
                            </div>
                            <div class="history-item">
                                <input type="checkbox" id="malignancy" name="malignancy" 
                                       <?php echo $medicalHistory['malignancy'] ? 'checked' : ''; ?>>
                                <label for="malignancy">HISTORY OF MALIGNANCY</label>
                            </div>
                            <div class="history-item">
                                <input type="checkbox" id="previousSurgery" name="previousSurgery" 
                                       <?php echo $medicalHistory['previous_surgery'] ? 'checked' : ''; ?>>
                                <label for="previousSurgery">DO YOU HAVE ANY PREVIOUS HISTORY OF ANY SURGERY</label>
                            </div>
                            <div class="history-item">
                                <input type="checkbox" id="epilepsy" name="epilepsy" 
                                       <?php echo $medicalHistory['epilepsy'] ? 'checked' : ''; ?>>
                                <label for="epilepsy">EPILEPSY/ SEIZURES</label>
                            </div>
                            <div class="history-item">
                                <input type="checkbox" id="asthma" name="asthma" 
                                       <?php echo $medicalHistory['asthma'] ? 'checked' : ''; ?>>
                                <label for="asthma">ASTHMA</label>
                            </div>
                            <div class="history-item">
                                <input type="checkbox" id="pregnant" name="pregnant" 
                                       <?php echo $medicalHistory['pregnant'] ? 'checked' : ''; ?>>
                                <label for="pregnant">PREGNANT OR NURSING MOTHER</label>
                            </div>
                            <div class="history-item">
                                <input type="checkbox" id="phobia" name="phobia" 
                                       <?php echo $medicalHistory['phobia'] ? 'checked' : ''; ?>>
                                <label for="phobia">PHOEBIA TO DENTAL TREATMENT</label>
                            </div>
                            <div class="history-item">
                                <input type="checkbox" id="stomach" name="stomach" 
                                       <?php echo $medicalHistory['stomach'] ? 'checked' : ''; ?>>
                                <label for="stomach">STOMACH AND DIGESTIVE CONDITION</label>
                            </div>
                            <div class="history-item">
                                <input type="checkbox" id="allergy" name="allergy" 
                                       <?php echo $medicalHistory['allergy'] ? 'checked' : ''; ?>>
                                <label for="allergy">ALLERGY</label>
                            </div>
                            <div class="history-item">
                                <input type="checkbox" id="drugAllergy" name="drugAllergy" 
                                       <?php echo $medicalHistory['drug_allergy'] ? 'checked' : ''; ?>>
                                <label for="drugAllergy">DRUG ALLERGY</label>
                            </div>
                            <div class="history-item">
                                <input type="checkbox" id="smoker" name="smoker" 
                                       <?php echo $medicalHistory['smoker'] ? 'checked' : ''; ?>>
                                <label for="smoker">SMOKER...?</label>
                            </div>
                            <div class="history-item">
                                <input type="checkbox" id="alcoholic" name="alcoholic" 
                                       <?php echo $medicalHistory['alcoholic'] ? 'checked' : ''; ?>>
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
                                <?php foreach ($treatments as $treatment): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($treatment['treatment_name']); ?></td>
                                    <td>₹<?php echo number_format($treatment['price']); ?></td>
                                    <td><button type="button" class="remove-btn">Remove</button></td>
                                </tr>
                                <?php endforeach; ?>
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
                                    <?php foreach ($treatments as $treatment): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($treatment['treatment_name']); ?></td>
                                        <td>₹<?php echo number_format($treatment['price']); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr class="total-row">
                                        <td>TOTAL AMOUNT</td>
                                        <td><span id="totalAmount">₹<?php echo number_format(array_sum(array_column($treatments, 'price'))); ?></span></td>
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
                                        <td><span id="netTotal">₹<?php echo number_format(array_sum(array_column($treatments, 'price'))); ?></span></td>
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
                                    <?php 
                                    $visitNumbers = ['1<sup>ST</sup>', '2<sup>ND</sup>', '3<sup>RD</sup>', '4<sup>TH</sup>', '5<sup>TH</sup>'];
                                    foreach ($visits as $index => $visit): 
                                    ?>
                                    <tr>
                                        <td><?php echo $visitNumbers[$index]; ?> VISIT</td>
                                        <td><input type="number" class="amount-paid-input" name="visit_amount[]" 
                                               value="<?php echo htmlspecialchars($visit['amount']); ?>" step="0.01" min="0"></td>
                                        <td><input type="number" class="balance-input" name="visit_balance[]" 
                                               value="<?php echo htmlspecialchars($visit['balance']); ?>" readonly></td>
                                        <td><input type="date" class="date-input" name="visit_date[]" 
                                               value="<?php echo htmlspecialchars($visit['visit_date']); ?>"></td>
                                        <td><input type="text" class="treatment-input" name="visit_treatment[]" 
                                               value="<?php echo htmlspecialchars($visit['treatment']); ?>"></td>
                                        <td><input type="text" class="mode-input" name="visit_mode[]" 
                                               value="<?php echo htmlspecialchars($visit['payment_mode']); ?>"></td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <!-- Add empty row for new visit -->
                                    <tr>
                                        <td><?php echo $visitNumbers[count($visits)]; ?> VISIT</td>
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
                        
                        <div class="signature-date">
                            <label>SIGNATURE/DATE:</label>
                            <input type="text" id="visitSignature" name="visit_signature" 
                                   value="<?php echo htmlspecialchars($patient['visit_signature'] ?? ''); ?>">
                        </div>
                    </div>

                    <button type="submit" class="submit-btn">Update Patient</button>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="script.js"></script>
</body>
</html> 