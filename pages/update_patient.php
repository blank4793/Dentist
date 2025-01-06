<?php
header('Content-Type: application/json');
session_start();
require_once '../includes/config.php';

function debug_log($message, $data = null) {
    $log = date('Y-m-d H:i:s') . " - " . $message;
    if ($data !== null) {
        $log .= "\nData: " . print_r($data, true);
    }
    error_log($log . "\n", 3, __DIR__ . "/../logs/patient_update.log");
}

try {
    // Get POST data with validation
    if (!isset($_POST['patient_id'])) {
        throw new Exception('Patient ID is required');
    }
    $patientId = $_POST['patient_id'];
    debug_log("Updating patient ID: " . $patientId);
    
    // Decode and validate JSON data
    $patientData = json_decode($_POST['patientData'] ?? '{}', true);
    $medicalHistory = json_decode($_POST['medicalHistory'] ?? '{}', true);
    $treatments = json_decode($_POST['treatments'] ?? '[]', true);
    $visits = json_decode($_POST['visits'] ?? '[]', true);
    $billing = json_decode($_POST['billing'] ?? '{}', true);

    // Validate decoded data
    if (!$patientData) {
        throw new Exception('Invalid patient data format');
    }

    debug_log("Received data:", [
        'patientData' => $patientData,
        'medicalHistory' => $medicalHistory,
        'treatments' => $treatments,
        'billing' => $billing
    ]);

    // Start transaction
    $pdo->beginTransaction();

    try {
        // 1. Update patient information
        $stmt = $pdo->prepare("
            UPDATE patients SET 
                name = :name,
                date = :date,
                sector = :sector,
                street_no = :street_no,
                house_no = :house_no,
                non_islamabad_address = :non_islamabad_address,
                phone = :phone,
                age = :age,
                gender = :gender,
                occupation = :occupation,
                email = :email,
                diagnosis = :diagnosis,
                treatment_advised = :treatment_advised,
                selected_teeth = :selected_teeth
            WHERE id = :patient_id
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
            'diagnosis' => $patientData['diagnosis'] ?? null,
            'treatment_advised' => $patientData['treatmentAdvised'] ?? null,
            'selected_teeth' => $patientData['selectedTeeth'] ?? null,
            'patient_id' => $patientId
        ]);

        // 2. Update medical history
        $stmt = $pdo->prepare("
            UPDATE medical_history SET 
                heart_problem = :heart_problem,
                blood_pressure = :blood_pressure,
                bleeding_disorder = :bleeding_disorder,
                blood_thinners = :blood_thinners,
                hepatitis = :hepatitis,
                diabetes = :diabetes,
                fainting_spells = :fainting_spells,
                allergy_anesthesia = :allergy_anesthesia,
                malignancy = :malignancy,
                previous_surgery = :previous_surgery,
                epilepsy = :epilepsy,
                asthma = :asthma,
                pregnant = :pregnant,
                phobia = :phobia,
                stomach = :stomach,
                allergy = :allergy,
                drug_allergy = :drug_allergy,
                smoker = :smoker,
                alcoholic = :alcoholic,
                other_conditions = :other_conditions
            WHERE patient_id = :patient_id
        ");

        $stmt->execute([
            'heart_problem' => $medicalHistory['heartProblem'] ?? false,
            'blood_pressure' => $medicalHistory['bloodPressure'] ?? false,
            'bleeding_disorder' => $medicalHistory['bleedingDisorder'] ?? false,
            'blood_thinners' => $medicalHistory['bloodThinners'] ?? false,
            'hepatitis' => $medicalHistory['hepatitis'] ?? false,
            'diabetes' => $medicalHistory['diabetes'] ?? false,
            'fainting_spells' => $medicalHistory['faintingSpells'] ?? false,
            'allergy_anesthesia' => $medicalHistory['allergyAnesthesia'] ?? false,
            'malignancy' => $medicalHistory['malignancy'] ?? false,
            'previous_surgery' => $medicalHistory['previousSurgery'] ?? false,
            'epilepsy' => $medicalHistory['epilepsy'] ?? false,
            'asthma' => $medicalHistory['asthma'] ?? false,
            'pregnant' => $medicalHistory['pregnant'] ?? false,
            'phobia' => $medicalHistory['phobia'] ?? false,
            'stomach' => $medicalHistory['stomach'] ?? false,
            'allergy' => $medicalHistory['allergy'] ?? false,
            'drug_allergy' => $medicalHistory['drugAllergy'] ?? false,
            'smoker' => $medicalHistory['smoker'] ?? false,
            'alcoholic' => $medicalHistory['alcoholic'] ?? false,
            'other_conditions' => $medicalHistory['otherConditions'] ?? '',
            'patient_id' => $patientId
        ]);

        // 3. Update treatments - First delete existing ones
        $stmt = $pdo->prepare("DELETE FROM dental_treatments WHERE patient_id = ?");
        $stmt->execute([$patientId]);

        // Then insert new treatments
        if (!empty($treatments)) {
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

            foreach ($treatments as $treatment) {
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

        // 4. Update billing
        $stmt = $pdo->prepare("
            INSERT INTO billing (
                patient_id, discount_type, discount_value
            ) VALUES (
                :patient_id, :discount_type, :discount_value
            ) ON DUPLICATE KEY UPDATE
                discount_type = VALUES(discount_type),
                discount_value = VALUES(discount_value)
        ");

        $stmt->execute([
            'patient_id' => $patientId,
            'discount_type' => $billing['discountType'],
            'discount_value' => floatval($billing['discountValue'])
        ]);

        // 5. Update visits - First delete existing ones
        $stmt = $pdo->prepare("DELETE FROM visits WHERE patient_id = ?");
        $stmt->execute([$patientId]);

        // Then insert new visits
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

        $pdo->commit();
        echo json_encode([
            'success' => true,
            'message' => 'Patient updated successfully',
            'patientId' => $patientId
        ]);

    } catch (Exception $e) {
        $pdo->rollBack();
        debug_log("Database error: " . $e->getMessage());
        throw $e;
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 