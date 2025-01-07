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
    // Get patient data
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

    // Get billing information separately
    $stmt = $pdo->prepare("
        SELECT * FROM billing WHERE patient_id = ?
    ");
    $stmt->execute([$patientId]);
    $billing = $stmt->fetch(PDO::FETCH_ASSOC);

    // Debug log
    error_log("Treatments data: " . print_r($treatments, true));
    error_log("Billing data: " . print_r($billing, true));

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
    <title>Edit Patient - The Dental Clinic</title>
    <link rel="stylesheet" href="../css/dashboard-styles.css">
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="../css/dental_chart.css">
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
                    <form id="editPatientForm" method="POST">
                        <input type="hidden" name="patient_id" value="<?php echo $patientId; ?>">
                        
                        <!-- Personal Information -->
                        <div class="form-section">
                            <div class="form-row name-date">
                                <div class="form-group">
                                    <label for="patientName">PATIENT NAME *</label>
                                    <input type="text" 
                                           id="patientName" 
                                           name="patientName" 
                                           required 
                                           pattern="[A-Za-z\s\-'.]{2,100}"
                                           value="<?php echo htmlspecialchars($patient['name']); ?>"
                                           title="Name should only contain letters, spaces, hyphens, and apostrophes">
                                </div>
                                <div class="form-group">
                                    <label for="date">DATE *</label>
                                    <input type="date" 
                                           id="date" 
                                           name="date" 
                                           required
                                           value="<?php echo htmlspecialchars($patient['date']); ?>">
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
                                           required
                                           value="<?php echo htmlspecialchars($patient['age']); ?>">
                                </div>
                                <div class="form-group">
                                    <label for="gender">GENDER *</label>
                                    <select id="gender" name="gender" required>
                                        <option value="">Select Gender</option>
                                        <option value="Male" <?php echo $patient['gender'] === 'Male' ? 'selected' : ''; ?>>Male</option>
                                        <option value="Female" <?php echo $patient['gender'] === 'Female' ? 'selected' : ''; ?>>Female</option>
                                        <option value="Other" <?php echo $patient['gender'] === 'Other' ? 'selected' : ''; ?>>Other</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Address fields -->
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="sector">SECTOR</label>
                                    <input type="text" 
                                           id="sector" 
                                           name="sector" 
                                           value="<?php echo htmlspecialchars($patient['sector']); ?>"
                                           placeholder="e.g., F-8, G-9">
                                </div>
                                <div class="form-group">
                                    <label for="streetNo">STREET NO</label>
                                    <input type="text" 
                                           id="streetNo" 
                                           name="streetNo"
                                           value="<?php echo htmlspecialchars($patient['street_no']); ?>">
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="houseNo">HOUSE NO</label>
                                    <input type="text" 
                                           id="houseNo" 
                                           name="houseNo"
                                           value="<?php echo htmlspecialchars($patient['house_no']); ?>">
                                </div>
                                <div class="form-group">
                                    <label for="nonIslamabadAddress">NON ISLAMABAD RESIDENCE</label>
                                    <input type="text" 
                                           id="nonIslamabadAddress" 
                                           name="nonIslamabadAddress"
                                           value="<?php echo htmlspecialchars($patient['non_islamabad_address']); ?>"
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
                                           value="<?php echo htmlspecialchars($patient['phone']); ?>"
                                           title="Phone number should be 10-15 digits, optionally starting with +">
                                </div>
                                <div class="form-group">
                                    <label for="email">EMAIL</label>
                                    <input type="email" 
                                           id="email" 
                                           name="email"
                                           value="<?php echo htmlspecialchars($patient['email']); ?>">
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="occupation">OCCUPATION</label>
                                    <input type="text" 
                                           id="occupation" 
                                           name="occupation"
                                           value="<?php echo htmlspecialchars($patient['occupation']); ?>">
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
                                        <input type="checkbox" id="heartProblem" name="heartProblem" 
                                               <?php echo $medicalHistory['heart_problem'] ? 'checked' : ''; ?>>
                                    </div>
                                    <div class="history-row">
                                        <span class="condition-label">BLOOD PRESSURE</span>
                                        <input type="checkbox" id="bloodPressure" name="bloodPressure"
                                               <?php echo $medicalHistory['blood_pressure'] ? 'checked' : ''; ?>>
                                    </div>
                                    <div class="history-row">
                                        <span class="condition-label">BLEEDING DISORDER</span>
                                        <input type="checkbox" id="bleedingDisorder" name="bleedingDisorder"
                                               <?php echo $medicalHistory['bleeding_disorder'] ? 'checked' : ''; ?>>
                                    </div>
                                    <div class="history-row">
                                        <span class="condition-label">BLOOD THINNERS etc. Loprin</span>
                                        <input type="checkbox" id="bloodThinners" name="bloodThinners"
                                               <?php echo $medicalHistory['blood_thinners'] ? 'checked' : ''; ?>>
                                    </div>
                                    <div class="history-row">
                                        <span class="condition-label">HEPATITIS B or C</span>
                                        <input type="checkbox" id="hepatitis" name="hepatitis"
                                               <?php echo $medicalHistory['hepatitis'] ? 'checked' : ''; ?>>
                                    </div>
                                    <div class="history-row">
                                        <span class="condition-label">DIABETES/SUGAR</span>
                                        <input type="checkbox" id="diabetes" name="diabetes"
                                               <?php echo $medicalHistory['diabetes'] ? 'checked' : ''; ?>>
                                    </div>
                                    <div class="history-row">
                                        <span class="condition-label">FAINTING SPELLS</span>
                                        <input type="checkbox" id="faintingSpells" name="faintingSpells"
                                               <?php echo $medicalHistory['fainting_spells'] ? 'checked' : ''; ?>>
                                    </div>
                                    <div class="history-row">
                                        <span class="condition-label">ALLERGY TO LOCAL ANESTHESIA</span>
                                        <input type="checkbox" id="allergyAnesthesia" name="allergyAnesthesia"
                                               <?php echo $medicalHistory['allergy_anesthesia'] ? 'checked' : ''; ?>>
                                    </div>
                                    <div class="history-row">
                                        <span class="condition-label">HISTORY OF MALIGNANCY</span>
                                        <input type="checkbox" id="malignancy" name="malignancy"
                                               <?php echo $medicalHistory['malignancy'] ? 'checked' : ''; ?>>
                                    </div>
                                    <div class="history-row">
                                        <span class="condition-label">DO YOU HAVE ANY PREVIOUS HISTORY OF ANY SURGERY</span>
                                        <input type="checkbox" id="previousSurgery" name="previousSurgery"
                                               <?php echo $medicalHistory['previous_surgery'] ? 'checked' : ''; ?>>
                                    </div>
                                </div>
                                <div class="medical-history-column">
                                    <div class="history-row">
                                        <span class="condition-label">EPILEPSY/SEIZURES</span>
                                        <input type="checkbox" id="epilepsy" name="epilepsy"
                                               <?php echo $medicalHistory['epilepsy'] ? 'checked' : ''; ?>>
                                    </div>
                                    <div class="history-row">
                                        <span class="condition-label">ASTHMA</span>
                                        <input type="checkbox" id="asthma" name="asthma"
                                               <?php echo $medicalHistory['asthma'] ? 'checked' : ''; ?>>
                                    </div>
                                    <div class="history-row">
                                        <span class="condition-label">PREGNANT OR NURSING MOTHER</span>
                                        <input type="checkbox" id="pregnant" name="pregnant"
                                               <?php echo $medicalHistory['pregnant'] ? 'checked' : ''; ?>>
                                    </div>
                                    <div class="history-row">
                                        <span class="condition-label">PHOEBIA TO DENTAL TREATMENT</span>
                                        <input type="checkbox" id="phobia" name="phobia"
                                               <?php echo $medicalHistory['phobia'] ? 'checked' : ''; ?>>
                                    </div>
                                    <div class="history-row">
                                        <span class="condition-label">STOMACH AND DIGESTIVE CONDITION</span>
                                        <input type="checkbox" id="stomach" name="stomach"
                                               <?php echo $medicalHistory['stomach'] ? 'checked' : ''; ?>>
                                    </div>
                                    <div class="history-row">
                                        <span class="condition-label">ALLERGY</span>
                                        <input type="checkbox" id="allergy" name="allergy"
                                               <?php echo $medicalHistory['allergy'] ? 'checked' : ''; ?>>
                                    </div>
                                    <div class="history-row">
                                        <span class="condition-label">DRUG ALLERGY</span>
                                        <input type="checkbox" id="drugAllergy" name="drugAllergy"
                                               <?php echo $medicalHistory['drug_allergy'] ? 'checked' : ''; ?>>
                                    </div>
                                    <div class="history-row">
                                        <span class="condition-label">SMOKER...?</span>
                                        <input type="checkbox" id="smoker" name="smoker"
                                               <?php echo $medicalHistory['smoker'] ? 'checked' : ''; ?>>
                                    </div>
                                    <div class="history-row">
                                        <span class="condition-label">ALCOHOLIC...?</span>
                                        <input type="checkbox" id="alcoholic" name="alcoholic"
                                               <?php echo $medicalHistory['alcoholic'] ? 'checked' : ''; ?>>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Dental Chart -->
                        <div class="dental-chart-section">
                            <h3>DENTAL CHART</h3>
                            <div class="chart-container">
                                <?php include '../templates/dental-chart.html'; ?>
                                <div class="selected-teeth-info">
                                    <h4>Selected Teeth</h4>
                                    <div id="selectedTeethList"></div>
                                    <input type="hidden" id="selectedTeethInput" name="selected_teeth" 
                                           value="<?php echo htmlspecialchars($patient['selected_teeth']); ?>">
                                </div>
                            </div>
                        </div>

                        <!-- Diagnosis Section -->
                        <div class="diagnosis-section">
                            <h3>DIAGNOSIS</h3>
                            <div class="form-row full-width">
                                <div class="form-group">
                                    <textarea id="diagnosis" name="diagnosis" class="auto-expand"><?php echo htmlspecialchars($patient['diagnosis']); ?></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Treatment Advised Section -->
                        <div class="treatment-advised-section">
                            <h3>TREATMENT ADVISED</h3>
                            <div class="form-row full-width">
                                <div class="form-group">
                                    <textarea id="treatmentAdvised" name="treatmentAdvised" class="auto-expand"><?php echo htmlspecialchars($patient['treatment_advised']); ?></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Treatment Section -->
                        <div class="treatment-section">
                            <h3>TREATMENT</h3>
                            <div class="form-row">
                                <div class="form-group treatment-select-group">
                                    <label for="treatmentSelect">SELECT TREATMENT:</label>
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
                                    <button type="button" id="addTreatment" class="add-treatment-btn">Add Treatment</button>
                                </div>
                            </div>

                            <div class="treatments-container">
                                <table class="treatments-table">
                                    <thead>
                                        <tr>
                                            <th>Treatment</th>
                                            <th>Teeth</th>
                                            <th>Quantity</th>
                                            <th>Price/Unit</th>
                                            <th>Total</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="treatmentsTableBody">
                                        <!-- Treatments will be loaded here -->
                                    </tbody>
                                </table>
                            </div>

                            <div class="billing-summary">
                                <div class="billing-row">
                                    <span class="billing-label">Total Amount:</span>
                                    <span id="totalAmount" class="billing-value">₹0.00</span>
                                </div>
                                <div class="billing-row">
                                    <span class="billing-label">Discount Type:</span>
                                    <select id="discountType" name="discount_type" class="discount-select">
                                        <option value="none" <?php echo ($billing['discount_type'] ?? '') === 'none' ? 'selected' : ''; ?>>None</option>
                                        <option value="percentage" <?php echo ($billing['discount_type'] ?? '') === 'percentage' ? 'selected' : ''; ?>>Percentage</option>
                                        <option value="fixed" <?php echo ($billing['discount_type'] ?? '') === 'fixed' ? 'selected' : ''; ?>>Fixed Amount</option>
                                    </select>
                                </div>
                                <div class="billing-row">
                                    <span class="billing-label">Discount Value:</span>
                                    <input type="number" 
                                           id="discountValue" 
                                           name="discount_value" 
                                           value="<?php echo htmlspecialchars($billing['discount_value'] ?? '0'); ?>" 
                                           min="0" 
                                           class="discount-input">
                                </div>
                                <div class="billing-row">
                                    <span class="billing-label">Discount Amount:</span>
                                    <span id="discountAmount" class="billing-value">₹0.00</span>
                                </div>
                                <div class="billing-row">
                                    <span class="billing-label">Net Total:</span>
                                    <span id="netTotal" class="billing-value">₹0.00</span>
                                </div>
                            </div>
                        </div>

                        <!-- Visits Table -->
                        <div class="visits-section">
                            <h3>VISITS</h3>
                            <table class="visits-table">
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
                                    <!-- Existing visits will be loaded here via JavaScript -->
                                </tbody>
                            </table>
                            <button type="button" id="addVisitRow" class="add-visit-btn">Add Visit</button>
                        </div>

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

                        <div class="section">
                            <h3>SIGNATURES (View Only)</h3>
                            <div class="signatures-container">
                                <!-- Patient Signature -->
                                <div class="signature-display">
                                    <h4>Patient Signature</h4>
                                    <?php if (!empty($patient['signature'])): ?>
                                        <img src="data:image/png;base64,<?php echo $patient['signature']; ?>" 
                                             alt="Patient Signature" 
                                             class="signature-image"
                                             style="pointer-events: none;">
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
                                             class="signature-image"
                                             style="pointer-events: none;">
                                    <?php else: ?>
                                        <p class="no-signature">No doctor signature available</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="signature-note">
                                <p><em>Note: Signatures cannot be edited. To update signatures, please create a new form.</em></p>
                            </div>
                        </div>

                        <button type="submit" class="submit-btn">Update Patient</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="../js/shared.js"></script>
    <script src="../js/edit-patient.js"></script>

    <!-- Add this script to pass PHP data to JavaScript -->
    <script>
        // Pass existing data to JavaScript
        const existingTreatments = <?php echo json_encode($treatments); ?>;
        const existingVisits = <?php echo json_encode($visits); ?>;
        const existingBilling = <?php echo json_encode($billing); ?>;
    </script>

    <style>
    /* Matching styles from patient-form.php */
    .treatment-section {
        background: #fff;
        padding: 20px;
        border-radius: 8px;
        margin-bottom: 20px;
    }

    .treatment-select-group {
        display: flex;
        gap: 15px;
        align-items: center;
        margin-bottom: 20px;
    }

    .treatment-dropdown {
        flex: 1;
        padding: 8px;
        border: 1px solid #ddd;
        border-radius: 4px;
    }

    .add-treatment-btn {
        padding: 8px 15px;
        background: #3498db;
        color: white;
        border: none;
        border-radius: 4px;
        cursor: pointer;
    }

    .treatments-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 20px;
    }

    .treatments-table th,
    .treatments-table td {
        padding: 12px;
        border: 1px solid #ddd;
        text-align: left;
    }

    .treatments-table th {
        background: #f8f9fa;
        font-weight: 600;
    }

    .quantity-input {
        width: 80px;
        padding: 5px;
        border: 1px solid #ddd;
        border-radius: 4px;
    }

    .delete-btn {
        padding: 5px 10px;
        background: #e74c3c;
        color: white;
        border: none;
        border-radius: 4px;
        cursor: pointer;
    }

    .billing-summary {
        background: #f8f9fa;
        padding: 15px;
        border-radius: 4px;
    }

    .billing-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 8px 0;
    }

    .billing-label {
        font-weight: 500;
        color: #2c3e50;
    }

    .billing-value {
        font-weight: 600;
        color: #2c3e50;
    }

    .discount-select,
    .discount-input {
        padding: 5px;
        border: 1px solid #ddd;
        border-radius: 4px;
        width: 150px;
    }

    /* Add/Update these styles */
    .medical-history {
        background: #fff;
        padding: 20px;
        border-radius: 8px;
        margin-bottom: 20px;
    }

    .medical-history h3 {
        color: #2c3e50;
        margin-bottom: 20px;
        border-bottom: 2px solid #007bff;
        padding-bottom: 10px;
    }

    .medical-history-table {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 10px;
    }

    .history-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 10px;
        background: #f8f9fa;
        border: 1px solid #e9ecef;
        border-radius: 4px;
    }

    .condition-label {
        font-weight: 500;
        color: #2c3e50;
    }

    input[type="checkbox"] {
        width: 18px;
        height: 18px;
        margin-left: 10px;
        cursor: pointer;
        accent-color: #007bff;
    }
    </style>
</body>
</html> 