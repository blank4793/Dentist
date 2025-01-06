$(document).ready(function() {
    // Add these validation functions at the start
    function validatePatientData(patientData) {
        const errors = [];

        // Required fields
        if (!patientData.name) errors.push("Patient name is required");
        if (!patientData.date) errors.push("Date is required");
        if (!patientData.phone) errors.push("Phone number is required");

        // Name validation (letters, spaces, and basic punctuation only)
        if (!/^[A-Za-z\s\-'.]{2,100}$/.test(patientData.name)) {
            errors.push("Name contains invalid characters or is too short/long");
        }

        // Age validation (0-150)
        if (patientData.age < 0 || patientData.age > 150) {
            errors.push("Age must be between 0 and 150");
        }

        // Phone validation (10-15 digits, allowing +, -, and spaces)
        const cleanPhone = patientData.phone.replace(/[-()\s]/g, '');
        if (!/^\+?\d{10,15}$/.test(cleanPhone)) {
            errors.push("Invalid phone number format");
        }

        // Email validation if provided
        if (patientData.email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(patientData.email)) {
            errors.push("Invalid email format");
        }

        return errors;
    }

    function validateTreatments(treatments) {
        const errors = [];

        if (!treatments.length) {
            errors.push("At least one treatment must be selected");
        }

        treatments.forEach((treatment, index) => {
            if (!treatment.name) {
                errors.push(`Treatment #${index + 1}: Name is required`);
            }
            if (treatment.quantity < 1) {
                errors.push(`Treatment #${index + 1}: Quantity must be at least 1`);
            }
            if (treatment.pricePerUnit <= 0) {
                errors.push(`Treatment #${index + 1}: Price must be greater than 0`);
            }
        });

        return errors;
    }

    function validateVisits(visits) {
        const errors = [];

        visits.forEach((visit, index) => {
            if (!visit.date) {
                errors.push(`Visit #${index + 1}: Date is required`);
            }
            if (visit.amount < 0) {
                errors.push(`Visit #${index + 1}: Amount cannot be negative`);
            }
            if (!['cash', 'card', 'insurance'].includes(visit.mode)) {
                errors.push(`Visit #${index + 1}: Invalid payment mode`);
            }
        });

        return errors;
    }

    // Form validation and submission
    $('#patientForm').on('submit', function(e) {
        e.preventDefault();
        
        // Validate ENUM fields
        const gender = $('#gender').val();
        if (!['Male', 'Female', 'Other'].includes(gender)) {
            alert('Please select a valid gender');
            return;
        }

        // Collect basic patient info
        const patientData = {
            name: $('#patientName').val().trim(),
            date: $('#date').val(),
            sector: $('#sector').val().trim(),
            streetNo: $('#streetNo').val().trim(),
            houseNo: $('#houseNo').val().trim(),
            nonIslamabadAddress: $('#nonIslamabadAddress').val().trim(),
            phone: $('#phone').val().trim(),
            age: parseInt($('#age').val()) || 0,
            gender: gender,
            occupation: $('#occupation').val().trim(),
            email: $('#email').val().trim()
        };

        // Basic validation
        if (!patientData.name || !patientData.date || !patientData.phone) {
            alert('Please fill in all required fields (Name, Date, Phone)');
            return;
        }

        // Validate phone number format
        if (!/^\d{10,}$/.test(patientData.phone.replace(/[-()\s]/g, ''))) {
            alert('Please enter a valid phone number');
            return;
        }

        // Validate email if provided
        if (patientData.email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(patientData.email)) {
            alert('Please enter a valid email address');
            return;
        }

        // Collect treatments data with validation
        const treatmentsData = window.treatments.map(treatment => ({
            name: treatment.name,
            quantity: parseInt(treatment.quantity) || 1,
            pricePerUnit: parseFloat(treatment.pricePerUnit) || 0,
            totalPrice: parseFloat(treatment.totalPrice) || 0,
            selectedTeeth: treatment.selectedTeeth || ''
        }));

        // Collect visits data with ENUM validation
        const visitsData = [];
        $('#visitsTableBody tr').each(function() {
            const mode = $(this).find('.mode-input').val();
            if (!['cash', 'card', 'insurance'].includes(mode)) {
                alert('Invalid payment mode selected');
                return;
            }

            const amount = parseFloat($(this).find('.amount-paid-input').val()) || 0;
            const balance = parseFloat($(this).find('.balance-input').val()) || 0;

            visitsData.push({
                date: $(this).find('.date-input').val(),
                treatment: $(this).find('.treatment-input').val(),
                amount: amount,
                mode: mode,
                balance: balance
            });
        });

        // Collect billing data
        const billingData = {
            totalAmount: parseFloat($('#totalAmount').text().replace(/Rs\.|,/g, '')) || 0,
            discountType: $('#discountType').val(),
            discountValue: parseFloat($('#discountValue').val()) || 0,
            netTotal: parseFloat($('#netTotal').text().replace(/Rs\.|,/g, '')) || 0
        };

        // Validate discount type
        if (!['percentage', 'fixed'].includes(billingData.discountType)) {
            alert('Invalid discount type');
            return;
        }

        // Validate all data
        const patientErrors = validatePatientData(patientData);
        const treatmentErrors = validateTreatments(treatmentsData);
        const visitErrors = validateVisits(visitsData);

        // Collect medical history
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
            alcoholic: $('#alcoholic').is(':checked'),
            otherConditions: $('#otherConditions').val() || ''
        };

        // Validate medical history (optional)
        function validateMedicalHistory(medicalHistory) {
            const errors = [];
            // Add any specific medical history validations here if needed
            return errors;
        }

        // Add medical history validation to the overall validation
        const medicalHistoryErrors = validateMedicalHistory(medicalHistory);
        const allErrors = [
            ...patientErrors, 
            ...treatmentErrors, 
            ...visitErrors,
            ...medicalHistoryErrors
        ];

        if (allErrors.length > 0) {
            alert('Please correct the following errors:\n\n' + allErrors.join('\n'));
            return;
        }

        // Add all data to form data
        const formData = new FormData(this);
        formData.append('patientData', JSON.stringify(patientData));
        formData.append('medicalHistory', JSON.stringify(medicalHistory));
        formData.append('treatments', JSON.stringify(treatmentsData));
        formData.append('visits', JSON.stringify(visitsData));
        formData.append('billing', JSON.stringify(billingData));
        formData.append('selectedTeeth', $('#selectedTeethInput').val());
        formData.append('diagnosis', $('#diagnosis').val());
        formData.append('treatmentAdvised', $('#treatmentAdvised').val());

        // Submit form with improved error handling
        $.ajax({
            url: 'save_patient.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                console.log('Server response:', response);
                if (response.success) {
                    alert('Patient added successfully!');
                    window.location.href = `view_patient.php?id=${response.patientId}`;
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
                alert('Error submitting form. Please check console for details.');
            }
        });
    });

    // Dental Chart Handling
    const selectedTeeth = new Set();
    
    // Get all tooth elements from SVG
    const toothElements = document.querySelectorAll('#Spots polygon, #Spots path');
    
    // Tooth name mapping (FDI/ISO system)
    const toothNames = {
        18: "Upper Right Third Molar",
        17: "Upper Right Second Molar",
        16: "Upper Right First Molar",
        15: "Upper Right Second Premolar",
        14: "Upper Right First Premolar",
        13: "Upper Right Canine",
        12: "Upper Right Lateral Incisor",
        11: "Upper Right Central Incisor",
        21: "Upper Left Central Incisor",
        22: "Upper Left Lateral Incisor",
        23: "Upper Left Canine",
        24: "Upper Left First Premolar",
        25: "Upper Left Second Premolar",
        26: "Upper Left First Molar",
        27: "Upper Left Second Molar",
        28: "Upper Left Third Molar",
        38: "Lower Left Third Molar",
        37: "Lower Left Second Molar",
        36: "Lower Left First Molar",
        35: "Lower Left Second Premolar",
        34: "Lower Left First Premolar",
        33: "Lower Left Canine",
        32: "Lower Left Lateral Incisor",
        31: "Lower Left Central Incisor",
        41: "Lower Right Central Incisor",
        42: "Lower Right Lateral Incisor",
        43: "Lower Right Canine",
        44: "Lower Right First Premolar",
        45: "Lower Right Second Premolar",
        46: "Lower Right First Molar",
        47: "Lower Right Second Molar",
        48: "Lower Right Third Molar"
    };

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

    // Add tooth to the list display
    function addToothToList(toothId) {
        // First check if tooth is already in list
        if (document.querySelector(`.selected-tooth-item[data-tooth-id="${toothId}"]`)) {
            return; // Skip if already exists
        }

        const toothItem = document.createElement('div');
        toothItem.className = 'selected-tooth-item';
        toothItem.setAttribute('data-tooth-id', toothId);
        
        toothItem.innerHTML = `
            ${toothNames[toothId]} (Tooth ${toothId})
            <span class="remove-tooth" onclick="removeToothSelection('${toothId}')">&times;</span>
        `;
        
        document.getElementById('selectedTeethList').appendChild(toothItem);
    }

    // Remove tooth from the list display
    function removeToothFromList(toothId) {
        const toothItem = document.querySelector(`.selected-tooth-item[data-tooth-id="${toothId}"]`);
        if (toothItem) {
            toothItem.remove();
        }
    }

    // Update hidden input with selected teeth
    function updateSelectedTeethInput() {
        const selectedTeethInput = document.getElementById('selectedTeethInput');
        if (selectedTeethInput) {
            selectedTeethInput.value = Array.from(selectedTeeth).join(',');
        }
    }

    // Global function to remove tooth selection
    window.removeToothSelection = function(toothId) {
        const tooth = document.querySelector(`#Spots [data-key="${toothId}"]`);
        if (tooth) {
            tooth.classList.remove('selected');
        }
        selectedTeeth.delete(toothId);
        removeToothFromList(toothId);
        updateSelectedTeethInput();
    };

    // Treatment Section
    window.treatments = []; // Store treatments globally

    // Treatment prices mapping
    const treatmentPrices = {
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

    // Treatment selection handling
    $('#treatmentSelect').on('change', function() {
        const selectedValue = $(this).val();
        if (!selectedValue) return;

        const selectedText = $(this).find('option:selected').text();
        const treatmentName = selectedText.split('(')[0].trim();
        const price = treatmentPrices[selectedValue].price;

        // Create new treatment
        const treatment = {
            name: treatmentName,
            quantity: 1,
            pricePerUnit: price,
            totalPrice: price,
            // Convert to array if it's a comma-separated string
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

    // Update treatments table
    function updateTreatmentsTable() {
        const tbody = $('#selectedTreatmentsList');
        tbody.empty();

        window.treatments.forEach((treatment, index) => {
            const row = $(`
                <tr>
                    <td>${treatment.name}</td>
                    <td>
                        <input type="number" value="${treatment.quantity}" min="1" 
                               onchange="updateQuantity(${index}, this.value)">
                    </td>
                    <td>Rs. ${treatment.pricePerUnit.toFixed(2)}</td>
                    <td>Rs. ${treatment.totalPrice.toFixed(2)}</td>
                    <td>
                        <button type="button" onclick="removeTreatment(${index})" class="remove-btn">
                            Remove
                        </button>
                    </td>
                </tr>
            `);
            tbody.append(row);
        });
    }

    // Update billing table
    function updateBillingTable() {
        const tbody = $('#billingList');
        tbody.empty();

        let totalAmount = 0;
        window.treatments.forEach(treatment => {
            totalAmount += treatment.totalPrice;
            const row = $(`
                <tr>
                    <td>${treatment.name}</td>
                    <td>${treatment.quantity}</td>
                    <td>Rs. ${treatment.pricePerUnit.toFixed(2)}</td>
                    <td>Rs. ${treatment.totalPrice.toFixed(2)}</td>
                </tr>
            `);
            tbody.append(row);
        });

        $('#totalAmount').text(`Rs. ${totalAmount.toFixed(2)}`);
        calculateNetTotal();
    }

    // Calculate net total with discount
    function calculateNetTotal() {
        const totalAmount = parseFloat($('#totalAmount').text().replace(/Rs\.|,/g, '')) || 0;
        const discountType = $('#discountType').val();
        const discountValue = parseFloat($('#discountValue').val()) || 0;

        let netTotal = totalAmount;
        if (discountType === 'percentage') {
            netTotal = totalAmount * (1 - discountValue / 100);
        } else {
            netTotal = totalAmount - discountValue;
        }

        $('#netTotal').text(`Rs. ${Math.max(0, netTotal).toFixed(2)}`);
        updateVisitBalances();
    }

    // Global functions for treatment management
    window.updateQuantity = function(index, quantity) {
        const qty = parseInt(quantity) || 1;
        window.treatments[index].quantity = qty;
        window.treatments[index].totalPrice = qty * window.treatments[index].pricePerUnit;
        updateTreatmentsTable();
        updateBillingTable();
    };

    window.removeTreatment = function(index) {
        window.treatments.splice(index, 1);
        updateTreatmentsTable();
        updateBillingTable();
    };

    // Handle discount changes
    $('#discountType, #discountValue').on('change input', function() {
        calculateNetTotal();
    });

    // Visit Section
    function updateVisitBalances() {
        const netTotal = parseFloat($('#netTotal').text().replace(/Rs\.|,/g, '')) || 0;
        let remainingBalance = netTotal;

        $('#visitsTableBody tr').each(function() {
            const amountPaidInput = $(this).find('.amount-paid-input');
            const balanceInput = $(this).find('.balance-input');
            const amountPaid = parseFloat(amountPaidInput.val()) || 0;

            remainingBalance -= amountPaid;
            balanceInput.val(remainingBalance.toFixed(2));
        });
    }

    // Add visit row functionality
    $('#addVisitRow').on('click', function() {
        const visitCount = $('#visitsTableBody tr').length + 1;
        const suffix = getVisitSuffix(visitCount);
        
        const newRow = `
            <tr>
                <td>${visitCount}<sup>${suffix}</sup> VISIT</td>
                <td><input type="number" class="amount-paid-input" name="visit_amount[]" step="0.01" min="0"></td>
                <td><input type="number" class="balance-input" name="visit_balance[]" readonly></td>
                <td><input type="date" class="date-input" name="visit_date[]"></td>
                <td><input type="text" class="treatment-input" name="visit_treatment[]"></td>
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
    });

    // Helper function for visit number suffix
    function getVisitSuffix(num) {
        if (num >= 11 && num <= 13) return 'TH';
        switch (num % 10) {
            case 1: return 'ST';
            case 2: return 'ND';
            case 3: return 'RD';
            default: return 'TH';
        }
    }

    // Listen for changes in amount paid inputs
    $(document).on('input', '.amount-paid-input', function() {
        updateVisitBalances();
    });

    // Update visit balances when net total changes
    $('#netTotal').on('DOMSubtreeModified', function() {
        updateVisitBalances();
    });

    // Initialize first visit row
    const firstVisitRow = `
        <tr>
            <td>1<sup>ST</sup> VISIT</td>
            <td><input type="number" class="amount-paid-input" name="visit_amount[]" step="0.01" min="0"></td>
            <td><input type="number" class="balance-input" name="visit_balance[]" readonly></td>
            <td><input type="date" class="date-input" name="visit_date[]"></td>
            <td><input type="text" class="treatment-input" name="visit_treatment[]"></td>
            <td>
                <select name="visit_mode[]" class="mode-input">
                    <option value="cash">Cash</option>
                    <option value="card">Card</option>
                    <option value="insurance">Insurance</option>
                </select>
            </td>
        </tr>
    `;
    
    // Add first visit row if table is empty
    if ($('#visitsTableBody tr').length === 0) {
        $('#visitsTableBody').append(firstVisitRow);
        updateVisitBalances();
    }
});
