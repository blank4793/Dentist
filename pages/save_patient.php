<?php
// At the very top of the file
ob_start();
header('Content-Type: application/json');
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
require_once '../includes/config.php';

// Function to log debug information
function debug_log($message, $data = null) {
    $log = date('Y-m-d H:i:s') . " - " . $message;
    if ($data !== null) {
        $log .= "\nData: " . print_r($data, true);
    }
    error_log($log . "\n", 3, __DIR__ . "/../logs/save_patient.log");
}

try {
    debug_log("Starting patient save process");
    
    // Validate session
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('User not logged in');
    }

    // Get and validate POST data
    if (empty($_POST['patientData'])) {
        throw new Exception('Patient data is missing');
    }

    // Get POST data
    $patientData = json_decode($_POST['patientData'], true);
    if (!$patientData) {
        throw new Exception('Invalid patient data format');
    }

    $medicalHistory = json_decode($_POST['medicalHistory'], true);
    $treatments = json_decode($_POST['treatments'], true);
    $visits = json_decode($_POST['visits'], true);
    $billing = json_decode($_POST['billing'], true);
    $selectedTeeth = $_POST['selectedTeeth'] ?? '';

    debug_log("Received data:", [
        'patientData' => $patientData,
        'treatments' => $treatments,
        'billing' => $billing
    ]);

    // Start transaction
    $pdo->beginTransaction();

    try {
        // 1. Insert patient
        $stmt = $pdo->prepare("
            INSERT INTO patients (
                name, date, sector, street_no, house_no, 
                non_islamabad_address, phone, age, gender, 
                occupation, email, diagnosis, treatment_advised, 
                selected_teeth
            ) VALUES (
                :name, :date, :sector, :street_no, :house_no,
                :non_islamabad_address, :phone, :age, :gender,
                :occupation, :email, :diagnosis, :treatment_advised,
                :selected_teeth
            )
        ");

        $stmt->execute([
            'name' => $patientData['name'],
            'date' => $patientData['date'],
            'sector' => $patientData['sector'],
            'street_no' => $patientData['streetNo'],
            'house_no' => $patientData['houseNo'],
            'non_islamabad_address' => $patientData['nonIslamabadAddress'],
            'phone' => $patientData['phone'],
            'age' => $patientData['age'],
            'gender' => $patientData['gender'],
            'occupation' => $patientData['occupation'],
            'email' => $patientData['email'],
            'diagnosis' => $_POST['diagnosis'] ?? null,
            'treatment_advised' => $_POST['treatmentAdvised'] ?? null,
            'selected_teeth' => $selectedTeeth
        ]);

        $patientId = $pdo->lastInsertId();

        // 2. Insert medical history
        $stmt = $pdo->prepare("
            INSERT INTO medical_history (
                patient_id, heart_problem, blood_pressure, 
                bleeding_disorder, blood_thinners, hepatitis, 
                diabetes, fainting_spells, allergy_anesthesia,
                malignancy, previous_surgery, epilepsy, asthma,
                pregnant, phobia, stomach, allergy, drug_allergy,
                smoker, alcoholic, other_conditions
            ) VALUES (
                :patient_id, :heart_problem, :blood_pressure,
                :bleeding_disorder, :blood_thinners, :hepatitis,
                :diabetes, :fainting_spells, :allergy_anesthesia,
                :malignancy, :previous_surgery, :epilepsy, :asthma,
                :pregnant, :phobia, :stomach, :allergy, :drug_allergy,
                :smoker, :alcoholic, :other_conditions
            )
        ");

        $stmt->execute([
            'patient_id' => $patientId,
            'heart_problem' => $medicalHistory['heartProblem'],
            'blood_pressure' => $medicalHistory['bloodPressure'],
            'bleeding_disorder' => $medicalHistory['bleedingDisorder'],
            'blood_thinners' => $medicalHistory['bloodThinners'],
            'hepatitis' => $medicalHistory['hepatitis'],
            'diabetes' => $medicalHistory['diabetes'],
            'fainting_spells' => $medicalHistory['faintingSpells'],
            'allergy_anesthesia' => $medicalHistory['allergyAnesthesia'],
            'malignancy' => $medicalHistory['malignancy'],
            'previous_surgery' => $medicalHistory['previousSurgery'],
            'epilepsy' => $medicalHistory['epilepsy'],
            'asthma' => $medicalHistory['asthma'],
            'pregnant' => $medicalHistory['pregnant'],
            'phobia' => $medicalHistory['phobia'],
            'stomach' => $medicalHistory['stomach'],
            'allergy' => $medicalHistory['allergy'],
            'drug_allergy' => $medicalHistory['drugAllergy'],
            'smoker' => $medicalHistory['smoker'],
            'alcoholic' => $medicalHistory['alcoholic'],
            'other_conditions' => $medicalHistory['otherConditions']
        ]);

        // 3. Insert dental treatments
        if (!empty($treatments)) {
            debug_log("Processing treatments:", [
                'treatments' => $treatments,
                'first_treatment_teeth' => $treatments[0]['selectedTeeth'] ?? null,
                'teeth_type' => gettype($treatments[0]['selectedTeeth'] ?? null)
            ]);

            $stmt = $pdo->prepare("
                INSERT INTO dental_treatments (
                    patient_id, tooth_number, treatment_name,
                    quantity, price_per_unit, total_price,
                    status
                ) VALUES (
                    :patient_id, :tooth_number, :treatment_name,
                    :quantity, :price_per_unit, :total_price,
                    'planned'
                )
            ");

            foreach ($treatments as $index => $treatment) {
                debug_log("Processing treatment " . ($index + 1), [
                    'treatment' => $treatment,
                    'selectedTeeth' => $treatment['selectedTeeth'],
                    'selectedTeeth_type' => gettype($treatment['selectedTeeth'])
                ]);
                
                // Check if selectedTeeth is already a string
                $toothNumbers = is_array($treatment['selectedTeeth']) 
                    ? implode(',', $treatment['selectedTeeth']) 
                    : $treatment['selectedTeeth'];

                $stmt->execute([
                    'patient_id' => $patientId,
                    'tooth_number' => $toothNumbers,
                    'treatment_name' => $treatment['name'],
                    'quantity' => $treatment['quantity'],
                    'price_per_unit' => $treatment['pricePerUnit'],
                    'total_price' => $treatment['totalPrice']
                ]);
            }
        }

        // Insert billing information
        $stmt = $pdo->prepare("
            INSERT INTO billing (
                patient_id, 
                discount_type, 
                discount_value
            ) VALUES (
                :patient_id,
                :discount_type,
                :discount_value
            )
        ");

        $stmt->execute([
            'patient_id' => $patientId,
            'discount_type' => $billing['discountType'],
            'discount_value' => floatval($billing['discountValue'])
        ]);

        // 4. Insert visits
        if (!empty($visits)) {
            $stmt = $pdo->prepare("
                INSERT INTO visits (
                    patient_id, visit_date, treatment_done,
                    visit_amount, visit_mode, balance
                ) VALUES (
                    :patient_id, :visit_date, :treatment_done,
                    :visit_amount, :visit_mode, :balance
                )
            ");

            foreach ($visits as $visit) {
                if (!empty($visit['date'])) {
                    $stmt->execute([
                        'patient_id' => $patientId,
                        'visit_date' => $visit['date'],
                        'treatment_done' => $visit['treatment'],
                        'visit_amount' => $visit['amount'],
                        'visit_mode' => $visit['mode'],
                        'balance' => $visit['balance']
                    ]);
                }
            }
        }

        // Commit transaction
        $pdo->commit();
        ob_clean();
        echo json_encode([
            'success' => true,
            'patientId' => $patientId,
            'message' => 'Patient added successfully'
        ]);

    } catch (Exception $e) {
        $pdo->rollBack();
        debug_log("Database error: " . $e->getMessage());
        debug_log("Stack trace: " . $e->getTraceAsString());
        throw new Exception('Database error: ' . $e->getMessage());
    }

} catch (Exception $e) {
    ob_clean();
    debug_log("Error: " . $e->getMessage());
    debug_log("Stack trace: " . $e->getTraceAsString());
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'details' => $e->getTraceAsString()
    ]);
} 