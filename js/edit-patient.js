$(document).ready(function() {
    // Initialize form with existing data
    const patientId = $('input[name="patient_id"]').val();
    
    // Initialize dental chart with existing teeth
    initializeDentalChart();

    // Initialize treatments and billing
    initializeTreatments();

    // Initialize visits
    initializeVisits();

    // Handle form submission
    $('#editPatientForm').on('submit', function(e) {
        e.preventDefault();
        
        // Create patientData object
        const patientData = {
            name: $('#patientName').val(),
            date: $('#date').val(),
            sector: $('#sector').val(),
            streetNo: $('#streetNo').val(),
            houseNo: $('#houseNo').val(),
            nonIslamabadAddress: $('#nonIslamabadAddress').val(),
            phone: $('#phone').val(),
            age: $('#age').val(),
            gender: $('#gender').val(),
            occupation: $('#occupation').val(),
            email: $('#email').val(),
            diagnosis: $('#diagnosis').val(),
            treatmentAdvised: $('#treatmentAdvised').val(),
            selectedTeeth: $('#selectedTeethInput').val()
        };

        const formData = new FormData();
        
        // Add all data as JSON strings
        formData.append('patient_id', $('input[name="patient_id"]').val());
        formData.append('patientData', JSON.stringify(patientData));
        
        // Add medical history
        const medicalHistory = {
            heartProblem: $('#heartProblem').is(':checked'),
            bloodPressure: $('#bloodPressure').is(':checked'),
            bleedingDisorder: $('#bleedingDisorder').is(':checked'),
            bloodThinners: $('#bloodThinners').is(':checked'),
            hepatitis: $('#hepatitis').is(':checked'),
            diabetes: $('#diabetes').is(':checked'),
            faintingSpells: $('#faintingSpells').is(':checked'),
            allergyAnesthesia: $('#allergyAnesthesia').is(':checked'),
            malignancy: $('#malignancy').is(':checked'),
            previousSurgery: $('#previousSurgery').is(':checked'),
            epilepsy: $('#epilepsy').is(':checked'),
            asthma: $('#asthma').is(':checked'),
            pregnant: $('#pregnant').is(':checked'),
            phobia: $('#phobia').is(':checked'),
            stomach: $('#stomach').is(':checked'),
            allergy: $('#allergy').is(':checked'),
            drugAllergy: $('#drugAllergy').is(':checked'),
            smoker: $('#smoker').is(':checked'),
            alcoholic: $('#alcoholic').is(':checked')
        };
        formData.append('medicalHistory', JSON.stringify(medicalHistory));

        // Add billing data
        const billing = {
            discountType: $('#discountType').val(),
            discountValue: parseFloat($('#discountValue').val()) || 0,
            netTotal: parseFloat($('#netTotal').text().replace(/Rs\.|,/g, '')) || 0
        };
        console.log("Submitting billing data:", billing);
        formData.append('billing', JSON.stringify(billing));

        // Add treatments data
        formData.append('treatments', JSON.stringify(window.treatments));

        // Add visits data
        formData.append('visits', JSON.stringify(getVisitsData()));

        // Log all form data for debugging
        console.log("Form data being sent:");
        for (let pair of formData.entries()) {
            console.log(pair[0] + ': ' + pair[1]);
        }

        // Submit form
        $.ajax({
            url: 'update_patient.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                console.log('Server response:', response);
                if (response.success) {
                    alert('Patient information updated successfully!');
                    window.location.href = `view_patient.php?id=${$('input[name="patient_id"]').val()}`;
                } else {
                    alert('Error: ' + (response.message || 'Unknown error occurred'));
                    console.error('Error details:', response);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', {
                    status: status,
                    error: error,
                    response: xhr.responseText
                });
                alert('Error updating patient information. Please check console for details.');
            }
        });
    });
});

function initializeDentalChart() {
    // First, initialize the dental chart functionality
    const selectedTeeth = new Set();
    const selectedTeethList = document.getElementById('selectedTeethList');
    const selectedTeethInput = document.getElementById('selectedTeethInput');

    // Get all tooth elements
    const toothElements = document.querySelectorAll('#Spots polygon, #Spots path');

    // Add click handlers to teeth
    toothElements.forEach(tooth => {
        tooth.addEventListener('click', function() {
            const toothId = this.getAttribute('data-key');
            
            if (selectedTeeth.has(toothId)) {
                // Deselect tooth
                selectedTeeth.delete(toothId);
                this.classList.remove('selected');
                removeToothFromList(toothId);
            } else {
                // Select tooth
                selectedTeeth.add(toothId);
                this.classList.add('selected');
                addToothToList(toothId);
            }
            
            // Update hidden input
            updateSelectedTeethInput();
        });
    });

    // Load existing selected teeth from database
    const existingTeeth = selectedTeethInput.value;
    if (existingTeeth) {
        const teethArray = existingTeeth.split(',').map(t => t.trim()).filter(t => t);
        console.log('Loading existing teeth:', teethArray);

        teethArray.forEach(toothId => {
            // Add to Set
            selectedTeeth.add(toothId);
            
            // Highlight tooth on chart
            const tooth = document.querySelector(`#Spots [data-key="${toothId}"]`);
            if (tooth) {
                tooth.classList.add('selected');
            }
            
            // Add to visual list
            const toothName = toothNames[toothId] || `Tooth ${toothId}`;
            const toothItem = document.createElement('div');
            toothItem.className = 'selected-tooth-item';
            toothItem.setAttribute('data-tooth-id', toothId);
            toothItem.innerHTML = `
                ${toothName} (Tooth ${toothId})
                <span class="remove-tooth" onclick="removeToothSelection('${toothId}')">&times;</span>
            `;
            selectedTeethList.appendChild(toothItem);
        });
    }

    // Function to remove tooth from selection
    window.removeToothSelection = function(toothId) {
        selectedTeeth.delete(toothId);
        const tooth = document.querySelector(`#Spots [data-key="${toothId}"]`);
        if (tooth) {
            tooth.classList.remove('selected');
        }
        const toothItem = document.querySelector(`.selected-tooth-item[data-tooth-id="${toothId}"]`);
        if (toothItem) {
            toothItem.remove();
        }
        updateSelectedTeethInput();
    };

    // Function to update hidden input
    function updateSelectedTeethInput() {
        selectedTeethInput.value = Array.from(selectedTeeth).join(',');
    }

    // Function to add tooth to list
    function addToothToList(toothId) {
        // Check if tooth is already in list
        if (document.querySelector(`.selected-tooth-item[data-tooth-id="${toothId}"]`)) {
            return;
        }

        const toothName = toothNames[toothId] || `Tooth ${toothId}`;
        const toothItem = document.createElement('div');
        toothItem.className = 'selected-tooth-item';
        toothItem.setAttribute('data-tooth-id', toothId);
        toothItem.innerHTML = `
            ${toothName} (Tooth ${toothId})
            <span class="remove-tooth" onclick="removeToothSelection('${toothId}')">&times;</span>
        `;
        selectedTeethList.appendChild(toothItem);
    }

    // Function to remove tooth from list
    function removeToothFromList(toothId) {
        const toothItem = document.querySelector(`.selected-tooth-item[data-tooth-id="${toothId}"]`);
        if (toothItem) {
            toothItem.remove();
        }
    }
}

// Tooth name mapping (add at the top of the file)
const toothNames = {
    // Upper Right (18-11)
    18: "Upper Right Third Molar",
    17: "Upper Right Second Molar",
    16: "Upper Right First Molar",
    15: "Upper Right Second Premolar",
    14: "Upper Right First Premolar",
    13: "Upper Right Canine",
    12: "Upper Right Lateral Incisor",
    11: "Upper Right Central Incisor",
    
    // Upper Left (21-28)
    21: "Upper Left Central Incisor",
    22: "Upper Left Lateral Incisor",
    23: "Upper Left Canine",
    24: "Upper Left First Premolar",
    25: "Upper Left Second Premolar",
    26: "Upper Left First Molar",
    27: "Upper Left Second Molar",
    28: "Upper Left Third Molar",
    
    // Lower Left (38-31)
    38: "Lower Left Third Molar",
    37: "Lower Left Second Molar",
    36: "Lower Left First Molar",
    35: "Lower Left Second Premolar",
    34: "Lower Left First Premolar",
    33: "Lower Left Canine",
    32: "Lower Left Lateral Incisor",
    31: "Lower Left Central Incisor",
    
    // Lower Right (41-48)
    41: "Lower Right Central Incisor",
    42: "Lower Right Lateral Incisor",
    43: "Lower Right Canine",
    44: "Lower Right First Premolar",
    45: "Lower Right Second Premolar",
    46: "Lower Right First Molar",
    47: "Lower Right Second Molar",
    48: "Lower Right Third Molar"
};

function initializeTreatments() {
    // Initialize treatments array
    window.treatments = [];
    
    // Add treatment options to select (matching patient-form.php)
    const treatmentSelect = $('#treatmentSelect');
    const treatmentOptions = {
        'consultation': { name: 'Consultation', price: 1000 },
        'radiograph': { name: 'Radiograph', price: 1500 },
        'fillingD': { name: 'Filling Direct', price: 3000 },
        'fillingI': { name: 'Filling Indirect', price: 2500 },
        'rct': { name: 'RCT', price: 15000 },
        'pfmCrownD': { name: 'PFM Crown Direct', price: 12000 },
        'pfmCrownI': { name: 'PFM Crown Indirect', price: 10000 },
        'zirconia': { name: 'Zirconia', price: 20000 },
        'extSimple': { name: 'Extraction Simple', price: 2000 },
        'extComp': { name: 'Extraction Complex', price: 4000 },
        'acrylicDent': { name: 'Acrylic Denture', price: 25000 },
        'ccPlate': { name: 'CC Plate', price: 8000 },
        'completeDenture': { name: 'Complete Denture', price: 35000 },
        'flexideDenture': { name: 'Flexide Denture', price: 40000 },
        'bridgeD': { name: 'Bridge Direct', price: 30000 },
        'bridgeI': { name: 'Bridge Indirect', price: 25000 },
        'implant': { name: 'Implant', price: 50000 },
        'laserTeethWhitening': { name: 'Laser Teeth Whitening', price: 15000 },
        'postAndCore': { name: 'Post and Core', price: 8000 },
        'peadFilling': { name: 'Pediatric Filling', price: 2500 },
        'peadExt': { name: 'Pediatric Extraction', price: 2000 }
    };

    // Populate treatment select with the same styling
    treatmentSelect.empty().append('<option value="">Select Treatment</option>');
    Object.entries(treatmentOptions).forEach(([value, details]) => {
        treatmentSelect.append(`<option value="${value}">${details.name} (Rs. ${details.price})</option>`);
    });

    // Load existing treatments
    if (existingTreatments && existingTreatments.length > 0) {
        existingTreatments.forEach(treatment => {
            window.treatments.push({
                name: treatment.treatment_name,
                quantity: parseInt(treatment.quantity),
                pricePerUnit: parseFloat(treatment.price_per_unit),
                totalPrice: parseFloat(treatment.total_price),
                selectedTeeth: treatment.tooth_number ? treatment.tooth_number.split(',') : []
            });
        });

        // Set discount type and value if they exist in billing
        if (existingBilling) {
            $('#discountType').val(existingBilling.discount_type);
            $('#discountValue').val(existingBilling.discount_value);
        }
    }

    updateTreatmentsTable();
    updateBillingTable();
}

// Treatment selection handling
$('#treatmentSelect').on('change', function() {
    const selectedValue = $(this).val();
    if (!selectedValue) return;

    const selectedText = $(this).find('option:selected').text();
    const treatmentName = selectedText.split('(')[0].trim();
    const price = parseFloat(selectedText.match(/Rs\. (\d+)/)[1]);

    // Create new treatment
    const treatment = {
        name: treatmentName,
        quantity: 1,
        pricePerUnit: price,
        totalPrice: price,
        selectedTeeth: $('#selectedTeethInput').val().split(',').filter(Boolean)
    };

    // Add to treatments array
    window.treatments.push(treatment);

    // Update displays
    updateTreatmentsTable();
    updateBillingTable();

    // Reset select
    $(this).val('');
});

function updateTreatmentsTable() {
    const tbody = $('#treatmentsTableBody');
    tbody.empty();

    window.treatments.forEach((treatment, index) => {
        const row = `
            <tr>
                <td>${treatment.name}</td>
                <td>${treatment.selectedTeeth.join(', ') || 'None'}</td>
                <td>
                    <input type="number" class="quantity-input" value="${treatment.quantity}" min="1"
                           onchange="updateTreatmentQuantity(${index}, this.value)">
                </td>
                <td>Rs. ${treatment.pricePerUnit.toFixed(2)}</td>
                <td>Rs. ${treatment.totalPrice.toFixed(2)}</td>
                <td>
                    <button type="button" class="delete-btn">Remove</button>
                </td>
            </tr>
        `;
        tbody.append(row);
    });

    // Add click handlers for delete buttons
    $('.delete-btn').click(function() {
        const index = $(this).closest('tr').index();
        deleteTreatment(index);
    });
}

function updateTreatmentQuantity(index, quantity) {
    quantity = parseInt(quantity);
    if (quantity < 1) quantity = 1;
    
    window.treatments[index].quantity = quantity;
    window.treatments[index].totalPrice = quantity * window.treatments[index].pricePerUnit;
    
    updateTreatmentsTable();
    updateBillingTable();
}

function deleteTreatment(index) {
    window.treatments.splice(index, 1);
    updateTreatmentsTable();
    updateBillingTable();
}

function updateBillingTable() {
    console.log("Updating billing table");
    
    // Calculate total amount
    const totalAmount = window.treatments.reduce((sum, t) => sum + t.totalPrice, 0);
    console.log("Total amount:", totalAmount);

    // Get discount type and value
    const discountType = $('#discountType').val();
    const discountValue = parseFloat($('#discountValue').val()) || 0;
    console.log("Discount type:", discountType, "Discount value:", discountValue);

    // Calculate net total
    let netTotal = totalAmount;
    let discountAmount = 0;

    if (discountType === 'percentage') {
        discountAmount = totalAmount * (discountValue / 100);
        netTotal = totalAmount - discountAmount;
    } else if (discountType === 'fixed') {
        discountAmount = discountValue;
        netTotal = totalAmount - discountValue;
    }

    console.log("Discount amount:", discountAmount, "Net total:", netTotal);

    // Update display
    $('#totalAmount').text(`Rs. ${totalAmount.toFixed(2)}`);
    $('#discountAmount').text(`Rs. ${discountAmount.toFixed(2)}`);
    $('#netTotal').text(`Rs. ${netTotal.toFixed(2)}`);
}

// Add event listeners for discount changes with logging
$('#discountType, #discountValue').on('change input', function() {
    console.log("Discount changed:", {
        type: $('#discountType').val(),
        value: $('#discountValue').val()
    });
    updateBillingTable();
});

function initializeVisits() {
    // Clear existing visits
    $('#visitsTableBody').empty();
    
    // Add existing visits
    if (existingVisits && existingVisits.length > 0) {
        existingVisits.forEach((visit, index) => {
            const visitRow = `
                <tr>
                    <td>${index + 1}<sup>${getOrdinalSuffix(index + 1)}</sup> VISIT</td>
                    <td><input type="number" class="amount-paid-input" name="visit_amount[]" value="${visit.visit_amount}" step="0.01" min="0"></td>
                    <td><input type="number" class="balance-input" name="visit_balance[]" value="${visit.balance}" readonly></td>
                    <td><input type="date" class="date-input" name="visit_date[]" value="${visit.visit_date}"></td>
                    <td><input type="text" class="treatment-input" name="visit_treatment[]" value="${visit.treatment_done}"></td>
                    <td>
                        <select name="visit_mode[]" class="mode-input">
                            <option value="cash" ${visit.visit_mode === 'cash' ? 'selected' : ''}>Cash</option>
                            <option value="card" ${visit.visit_mode === 'card' ? 'selected' : ''}>Card</option>
                            <option value="insurance" ${visit.visit_mode === 'insurance' ? 'selected' : ''}>Insurance</option>
                        </select>
                    </td>
                </tr>
            `;
            $('#visitsTableBody').append(visitRow);
        });
    } else {
        // Add first empty visit row if no visits exist
        addVisitRow();
    }
    
    // Update balances
    updateVisitBalances();
}

function getOrdinalSuffix(number) {
    const j = number % 10,
          k = number % 100;
    if (j == 1 && k != 11) return "ST";
    if (j == 2 && k != 12) return "ND";
    if (j == 3 && k != 13) return "RD";
    return "TH";
}

function getVisitsData() {
    const visitsData = [];
    $('#visitsTableBody tr').each(function() {
        visitsData.push({
            date: $(this).find('.date-input').val(),
            treatment: $(this).find('.treatment-input').val(),
            amount: $(this).find('.amount-paid-input').val(),
            mode: $(this).find('.mode-input').val(),
            balance: $(this).find('.balance-input').val()
        });
    });
    return visitsData;
}

// Add visit row functionality
$('#addVisitRow').click(function() {
    addVisitRow();
});

function addVisitRow() {
    const visitsCount = $('#visitsTableBody tr').length;
    const newRow = `
        <tr>
            <td>${visitsCount + 1}<sup>${getOrdinalSuffix(visitsCount + 1)}</sup> VISIT</td>
            <td><input type="number" class="amount-paid-input" name="visit_amount[]" value="0" step="0.01" min="0"></td>
            <td><input type="number" class="balance-input" name="visit_balance[]" value="0" readonly></td>
            <td><input type="date" class="date-input" name="visit_date[]" value=""></td>
            <td><input type="text" class="treatment-input" name="visit_treatment[]" value=""></td>
            <td>
                <select name="visit_mode[]" class="mode-input">
                    <option value="cash">Cash</option>
                    <option value="card">Card</option>
                    <option value="insurance">Insurance</option>
                </select>
            </td>
        </tr>
    `;
    $('#visitsTableBody').append(newRow);
    updateVisitBalances();
}

function updateVisitBalances() {
    let remainingBalance = parseFloat($('#netTotal').text().replace(/Rs\.|,/g, '')) || 0;
    
    $('#visitsTableBody tr').each(function() {
        const amountPaid = parseFloat($(this).find('.amount-paid-input').val()) || 0;
        remainingBalance -= amountPaid;
        $(this).find('.balance-input').val(Math.max(0, remainingBalance.toFixed(2)));
    });
}

// Update balances when amount paid changes
$(document).on('input', '.amount-paid-input', updateVisitBalances); 