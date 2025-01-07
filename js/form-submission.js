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

    // Form submission handling
    $('#patientForm').on('submit', function(e) {
        e.preventDefault();
        console.log('Form submission started');

        // Show loading state
        $('.submit-btn').prop('disabled', true).text('Submitting...');

        // Get signature data
        const patientSignature = document.getElementById('patientSignatureData').value;
        const doctorSignature = document.getElementById('doctorSignatureData').value;

        // Collect all form data
        const formData = {
            patientData: {
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
                email: $('#email').val()
            },
            medicalHistory: {
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
                otherConditions: $('#otherConditions').val()
            },
            treatments: window.treatments || [],
            selectedTeeth: $('#selectedTeethInput').val(),
            diagnosis: $('#diagnosis').val(),
            treatmentAdvised: $('#treatmentAdvised').val()
        };

        console.log('Form data collected:', formData);

        // Add this to your form submission data
        const visits = [];
        $('#visitsTableBody tr').each(function() {
            visits.push({
                date: $(this).find('.date-input').val(),
                treatment: $(this).find('.treatment-input').val(),
                amount: parseFloat($(this).find('.amount-paid-input').val()) || 0,
                mode: $(this).find('.mode-input').val(),
                balance: parseFloat($(this).find('.balance-input').val()) || 0
            });
        });

        // Submit the form
        $.ajax({
            url: 'save_patient.php',
            type: 'POST',
            data: {
                patientData: JSON.stringify(formData.patientData),
                medicalHistory: JSON.stringify(formData.medicalHistory),
                treatments: JSON.stringify(formData.treatments),
                selectedTeeth: formData.selectedTeeth,
                diagnosis: formData.diagnosis,
                treatmentAdvised: formData.treatmentAdvised,
                patient_signature_data: patientSignature,
                doctor_signature_data: doctorSignature,
                visits: JSON.stringify(visits),
                discountType: $('#discountType').val(),
                discountValue: $('#discountValue').val()
            },
            success: function(response) {
                console.log('Server response:', response);
                if (response.success) {
                    alert('Patient data saved successfully!');
                    window.location.href = `view_patient.php?id=${response.patientId}`;
                } else {
                    console.error('Server error:', response.message);
                    alert('Error: ' + response.message);
                    $('.submit-btn').prop('disabled', false).text('Submit Registration');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error:', {xhr, status, error});
                alert('Error submitting form. Please try again.');
                $('.submit-btn').prop('disabled', false).text('Submit Registration');
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

    // Treatment selection handling
    $('#treatmentSelect').change(function() {
        const selectedValue = $(this).val();
        if (!selectedValue) return;

        // Get the selected option text and price
        const selectedOption = $(this).find('option:selected');
        const optionText = selectedOption.text();
        const priceMatch = optionText.match(/Rs\. (\d+)/);
        const price = priceMatch ? parseFloat(priceMatch[1]) : 0;
        const treatmentName = optionText.split('(Rs.')[0].trim();

        // Get selected teeth
        const selectedTeethArray = $('#selectedTeethInput').val() ? 
            $('#selectedTeethInput').val().split(',').filter(Boolean) : 
            [];

        // Create new treatment object
        const newTreatment = {
            id: selectedValue,
            name: treatmentName,
            quantity: 1,
            pricePerUnit: price,
            totalPrice: price,
            selectedTeeth: selectedTeethArray
        };

        // Initialize treatments array if it doesn't exist
        if (!window.treatments) {
            window.treatments = [];
        }

        // Add the new treatment
        window.treatments.push(newTreatment);

        // Update displays
        updateTreatmentsTable();
        updateBillingTable();
        calculateNetTotal();

        // Reset select
        $(this).val('');
    });

    // Add this function to format the treatment display
    function formatTreatmentDisplay(treatment) {
        let display = treatment.name;
        if (treatment.selectedTeeth && treatment.selectedTeeth.length > 0) {
            if (treatment.name.includes('Per tooth') || treatment.name.includes('U/L')) {
                display += ` (${treatment.selectedTeeth.join(', ')})`;
            }
        }
        return display;
    }

    // Update the treatments table display
    function updateTreatmentsTable() {
        const tbody = $('#treatmentsTableBody');
        tbody.empty();

        window.treatments.forEach((treatment, index) => {
            const row = $(`
                <tr>
                    <td>${treatment.name}</td>
                    <td>
                        <div class="quantity-controls">
                            <button type="button" class="quantity-btn minus" onclick="updateQuantity(${index}, ${treatment.quantity - 1})">-</button>
                            <input type="number" 
                                   class="quantity-input"
                                   value="${treatment.quantity}" 
                                   min="1"
                                   step="1"
                                   onchange="updateQuantity(${index}, this.value)"
                                   readonly>
                            <button type="button" class="quantity-btn plus" onclick="updateQuantity(${index}, ${treatment.quantity + 1})">+</button>
                        </div>
                    </td>
                    <td>Rs. ${formatPrice(treatment.pricePerUnit)}</td>
                    <td>Rs. ${formatPrice(treatment.totalPrice)}</td>
                    <td>
                        <button type="button" onclick="removeTreatment(${index})" class="remove-btn">Remove</button>
                    </td>
                </tr>
            `);
            tbody.append(row);
        });

        // Update total amount
        let totalAmount = window.treatments.reduce((sum, treatment) => sum + treatment.totalPrice, 0);
        $('#totalAmount').text(`Rs. ${formatPrice(totalAmount)}`);
    }

    // Helper function for price formatting with commas
    function formatPrice(amount) {
        // Convert to number if it's a string
        amount = typeof amount === 'string' ? parseFloat(amount) : amount;
        return amount.toLocaleString('en-IN', {
            maximumFractionDigits: 0,
            minimumFractionDigits: 0
        });
    }

    // Update billing table
    function updateBillingTable() {
        let totalAmount = window.treatments.reduce((sum, treatment) => sum + treatment.totalPrice, 0);
        
        // Update total amount displays with commas
        $('.total-row #totalAmount').text(`Rs. ${formatPrice(totalAmount)}`);
        $('#totalAmount').text(`Rs. ${formatPrice(totalAmount)}`);
        calculateNetTotal();
    }

    // Calculate net total
    function calculateNetTotal() {
        // Get total amount by removing 'Rs. ' and commas
        const totalAmountText = $('#totalAmount').text().replace(/Rs\.|,/g, '').trim();
        const totalAmount = parseFloat(totalAmountText) || 0;
        
        const discountType = $('#discountType').val();
        const discountValue = parseFloat($('#discountValue').val()) || 0;

        let netTotal = totalAmount;
        if (discountType === 'percentage') {
            netTotal = totalAmount * (1 - discountValue / 100);
        } else if (discountType === 'fixed') {
            netTotal = totalAmount - discountValue;
        }

        // Format net total with commas
        $('#netTotal').text(`Rs. ${formatPrice(Math.max(0, netTotal))}`);

        // Keep existing visit balance calculation
        updateVisitBalances();
    }

    // Update quantity function
    window.updateQuantity = function(index, quantity) {
        const qty = Math.max(1, parseInt(quantity) || 1); // Ensure minimum of 1
        if (window.treatments[index]) {
            // Update quantity and recalculate total price
            window.treatments[index].quantity = qty;
            window.treatments[index].totalPrice = qty * window.treatments[index].pricePerUnit;
            
            // Update all displays
            updateTreatmentsTable();
            updateBillingTable();
            calculateNetTotal();
        }
    };

    // Remove treatment function
    window.removeTreatment = function(index) {
        window.treatments.splice(index, 1);
        updateTreatmentsTable();
        updateBillingTable();
        calculateNetTotal();
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

    // Format existing billing amounts
    $('.treatments-table td:nth-child(4), .treatments-table td:nth-child(5)').each(function() {
        const amount = parseFloat($(this).text().replace(/[^0-9.-]+/g, ''));
        if (!isNaN(amount)) {
            $(this).text(`Rs. ${formatPrice(amount)}`);
        }
    });

    // Format existing visit amounts
    $('.visits-table .amount-paid-input, .visits-table .balance-input').each(function() {
        const amount = parseFloat($(this).val());
        if (!isNaN(amount)) {
            $(this).val(formatPrice(amount));
        }
    });

    // Format prices in the treatments table
    $('.treatments-table td:nth-child(4), .treatments-table td:nth-child(5)').each(function() {
        const amount = parseFloat($(this).text().replace(/[^0-9.-]+/g, ''));
        if (!isNaN(amount)) {
            $(this).text(`Rs. ${formatPrice(amount)}`);
        }
    });

    // Format total amount and net total
    const totalAmount = parseFloat($('#totalAmount').text().replace(/[^0-9.-]+/g, '')) || 0;
    $('#totalAmount').text(`Rs. ${formatPrice(totalAmount)}`);
    
    const netTotal = parseFloat($('#netTotal').text().replace(/[^0-9.-]+/g, '')) || 0;
    $('#netTotal').text(`Rs. ${formatPrice(netTotal)}`);
});
