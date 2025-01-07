$(document).ready(function() {
    console.log('Dashboard JS loaded');

    // Direct click handler for delete buttons
    $(document).on('click', '.btn-delete', function() {
        const patientId = $(this).data('id');
        console.log('Delete clicked for patient ID:', patientId);
        if (patientId) {
            deletePatient(patientId);
        }
    });

    // Handle search form submission
    $('#searchForm').on('submit', function(e) {
        e.preventDefault();
        const searchTerm = $('#searchInput').val().trim();
        
        if (searchTerm.length > 0) {
            searchPatients(searchTerm);
        }
    });

    // Add live search functionality (optional)
    let searchTimeout;
    $('#searchInput').on('input', function() {
        clearTimeout(searchTimeout);
        const searchTerm = $(this).val().trim();
        
        if (searchTerm.length > 2) {
            searchTimeout = setTimeout(() => {
                searchPatients(searchTerm);
            }, 500);
        }
    });
});

function deletePatient(patientId) {
    if (!patientId) {
        console.error('No patient ID provided');
        return;
    }

    if (confirm('Are you sure you want to delete this patient?')) {
        console.log('Sending delete request for patient ID:', patientId);
        
        $.ajax({
            url: 'delete_patient.php',
            type: 'POST',
            data: { patient_id: patientId },
            dataType: 'json',
            success: function(response) {
                console.log('Delete response:', response);
                
                if (response.success) {
                    const row = $(`tr[data-patient-id="${patientId}"]`);
                    if (row.length) {
                        row.fadeOut(300, function() {
                            $(this).remove();
                            updateSerialNumbers();
                        });
                        alert('Patient deleted successfully');
                    } else {
                        console.error('Row not found for patient ID:', patientId);
                    }
                } else {
                    console.error('Delete failed:', response.message);
                    alert('Error: ' + (response.message || 'Failed to delete patient'));
                }
            },
            error: function(xhr, status, error) {
                console.error('Ajax error:', {
                    status: status,
                    error: error,
                    response: xhr.responseText
                });
                alert('Error deleting patient. Please try again.');
            }
        });
    }
}

function updateSerialNumbers() {
    $('.patient-table tbody tr').each(function(index) {
        $(this).find('td:first').text(index + 1);
    });
}

function searchPatients(searchTerm) {
    const tbody = $('.patient-table tbody');
    const searchForm = $('#searchForm');
    
    // Add loading state
    searchForm.addClass('loading');
    
    $.ajax({
        url: 'search_patients.php',
        type: 'GET',
        data: { search: searchTerm },
        success: function(response) {
            if (response.success) {
                tbody.html(response.html);
            } else {
                alert('Error: ' + response.message);
            }
        },
        error: function() {
            alert('Error performing search. Please try again.');
        },
        complete: function() {
            searchForm.removeClass('loading');
        }
    });
} 