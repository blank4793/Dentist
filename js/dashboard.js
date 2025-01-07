function deletePatient(patientId) {
    if (!confirm('Are you sure you want to delete this patient? This action cannot be undone.')) {
        return;
    }

    $.ajax({
        url: 'delete_patient.php',
        type: 'POST',
        data: { patient_id: patientId },
        success: function(response) {
            if (response.success) {
                // Remove the row from the table
                $(`tr:has(button[onclick="deletePatient(${patientId})"])`).remove();
                alert('Patient deleted successfully');
            } else {
                alert('Error: ' + (response.message || 'Failed to delete patient'));
            }
        },
        error: function() {
            alert('Error: Failed to delete patient');
        }
    });
} 