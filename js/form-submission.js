console.log('Form submission script loaded');
console.log('jQuery version:', $.fn.jquery);

function formatAmount(amount) {
    // Convert to number if it's a string
    amount = typeof amount === 'string' ? parseFloat(amount) : amount;
    
    // Format with commas for thousands
    return amount.toLocaleString('en-IN', {
        maximumFractionDigits: 0,
        minimumFractionDigits: 0
    });
}

$(document).ready(function() {
    // Clear any existing treatments
    window.treatments = [];
    
    // Treatment selection handling
    $('#treatmentSelect').on('change', function() {
        const selectedOption = $(this).find('option:selected');
        if (!selectedOption.val()) return;

        const treatmentText = selectedOption.text();
        const priceMatch = treatmentText.match(/Rs\. (\d+)/);
        if (!priceMatch) return;

        const price = parseFloat(priceMatch[1]);
        const treatmentName = treatmentText.split('(')[0].trim();

        // Add new row to treatments table
        const newRow = `
            <tr>
                <td>${treatmentName}</td>
                <td>
                    <div class="quantity-controls">
                        <button type="button" class="quantity-btn minus">-</button>
                        <input type="number" class="quantity-input" value="1" min="1" readonly>
                        <button type="button" class="quantity-btn plus">+</button>
                    </div>
                </td>
                <td>Rs. ${formatAmount(price)}</td>
                <td class="total">Rs. ${formatAmount(price)}</td>
                <td>
                    <button type="button" class="remove-treatment">Remove</button>
                </td>
            </tr>
        `;

        $('#treatmentsTableBody').append(newRow);
        calculateAllTotals();
        $(this).val('');
    });

    // Handle quantity buttons
    $(document).on('click', '.quantity-btn', function() {
        const input = $(this).siblings('.quantity-input');
        const row = $(this).closest('tr');
        let currentValue = parseInt(input.val()) || 1;
        
        // Get the base price from the Price/Unit column
        const basePrice = parseFloat(row.find('td:eq(2)').text().replace(/[^0-9.-]+/g, ''));
        
        if ($(this).hasClass('plus')) {
            currentValue += 1;
        } else if ($(this).hasClass('minus') && currentValue > 1) {
            currentValue -= 1;
        }
        
        input.val(currentValue);
        
        // Calculate new total for this row
        const rowTotal = basePrice * currentValue;
        row.find('.total').text(`Rs. ${formatAmount(rowTotal)}`);
        
        calculateAllTotals();
    });

    // Handle treatment removal
    $(document).on('click', '.remove-treatment', function() {
        $(this).closest('tr').remove();
        calculateAllTotals();
    });

    // Calculate all totals
    function calculateAllTotals() {
        let grandTotal = 0;
        
        // Calculate totals for each row and update billing list
        $('#billingList').empty();
        
        $('#treatmentsTableBody tr').each(function() {
            const treatment = $(this).find('td:eq(0)').text();
            const quantity = parseInt($(this).find('.quantity-input').val()) || 1;
            const basePrice = parseFloat($(this).find('td:eq(2)').text().replace(/[^0-9]/g, ''));
            const rowTotal = basePrice * quantity;
            
            $(this).find('.total').text(`Rs. ${formatAmount(rowTotal)}`);
            grandTotal += rowTotal;
            
            $('#billingList').append(`
                <tr>
                    <td>${treatment}</td>
                    <td>Rs. ${formatAmount(rowTotal)}</td>
                </tr>
            `);
        });

        // Update all total displays
        $('#totalAmount').text(`Rs. ${formatAmount(grandTotal)}`);
        $('#billingTotalAmount').text(`Rs. ${formatAmount(grandTotal)}`);
        
        // Recalculate net total with current discount
        calculateNetTotal(grandTotal);
    }

    // Calculate net total with discount
    function calculateNetTotal(total) {
        const discountType = $('#discountType').val();
        const discountValue = parseFloat($('#discountValue').val()) || 0;
        
        // Get total amount as a clean number
        const totalAmount = parseInt(total.toString().replace(/[^\d]/g, ''));
        let netTotal = totalAmount;

        if (discountType === 'percentage' && discountValue > 0) {
            // Calculate percentage discount
            const discountAmount = Math.round((totalAmount * discountValue) / 100);
            netTotal = totalAmount - discountAmount;
        } else if (discountType === 'fixed' && discountValue > 0) {
            // Calculate fixed discount
            netTotal = totalAmount - discountValue;
        }

        // Debug logs
        console.log({
            totalAmount,
            discountType,
            discountValue,
            netTotal
        });

        // Format and display the net total
        $('#netTotal').text(`Rs. ${formatAmount(netTotal)}`);
    }

    // Helper function to parse amounts from text
    function parseAmount(amountText) {
        // Remove 'Rs. ' and any commas, then parse as float
        return parseFloat(amountText.replace(/[^0-9.-]+/g, '')) || 0;
    }

    // Handle discount changes
    $('#discountType, #discountValue').on('change input', function() {
        // Get the current total amount
        const totalAmount = $('#billingTotalAmount').text();
        const cleanTotal = parseInt(totalAmount.toString().replace(/[^\d]/g, ''));
        
        if (cleanTotal > 0) {
            calculateNetTotal(cleanTotal);
        }
    });

    // Handle amount paid changes in visits section
    $(document).on('input', '.amount-paid-input', function() {
        const currentRow = $(this).closest('tr');
        const netTotal = parseInt($('#netTotal').text().replace(/[^\d]/g, '')) || 0;
        
        // Calculate total paid in previous rows
        let totalPaidBefore = 0;
        currentRow.prevAll('tr').each(function() {
            const prevPaid = parseInt($(this).find('.amount-paid-input').val()) || 0;
            totalPaidBefore += prevPaid;
        });

        // Calculate available balance for current row
        const availableBalance = netTotal - totalPaidBefore;
        const currentPaid = parseInt($(this).val()) || 0;
        const currentBalance = availableBalance - currentPaid;

        // Update current row's balance
        currentRow.find('.balance-input').val(`Rs. ${formatAmount(Math.max(0, currentBalance))}`);

        // Update all subsequent rows' balances
        let remainingBalance = currentBalance;
        currentRow.nextAll('tr').each(function() {
            const nextPaid = parseInt($(this).find('.amount-paid-input').val()) || 0;
            remainingBalance = remainingBalance - nextPaid;
            $(this).find('.balance-input').val(`Rs. ${formatAmount(Math.max(0, remainingBalance))}`);
        });
    });

    // Initialize first row balance
    function initializeFirstRowBalance() {
        const netTotal = parseInt($('#netTotal').text().replace(/[^\d]/g, '')) || 0;
        const firstRow = $('#visitsTableBody tr').first();
        const amountPaid = parseInt(firstRow.find('.amount-paid-input').val()) || 0;
        const balance = netTotal - amountPaid;
        firstRow.find('.balance-input').val(`Rs. ${formatAmount(balance)}`);
    }

    // Update when net total changes
    $('#netTotal').on('DOMSubtreeModified', function() {
        initializeFirstRowBalance();
    });

    // Initialize on page load
    $(document).ready(function() {
        initializeFirstRowBalance();
    });

    // Add Visit button functionality
    $('#addVisitRow').on('click', function() {
        const visitCount = $('#visitsTableBody tr').length + 1;
        let suffix = 'TH';
        if (visitCount === 1) suffix = 'ST';
        else if (visitCount === 2) suffix = 'ND';
        else if (visitCount === 3) suffix = 'RD';

        // Get balance from previous row
        const previousBalance = $('#visitsTableBody tr:last .balance-input').val();
        const initialBalance = previousBalance ? previousBalance : `Rs. ${formatAmount(parseInt($('#netTotal').text().replace(/[^\d]/g, '')) || 0)}`;

        // Create new visit row
        const newRow = `
            <tr>
                <td>${visitCount}<sup>${suffix}</sup> VISIT</td>
                <td>
                    <input type="number" 
                           class="amount-paid-input" 
                           name="visit_amount[]" 
                           min="0" 
                           step="1"
                           style="text-align: left;">
                </td>
                <td>
                    <input type="text" 
                           class="balance-input" 
                           name="visit_balance[]" 
                           readonly 
                           value="${initialBalance}">
                </td>
                <td><input type="date" class="date-input" name="visit_date[]"></td>
                <td><input type="text" class="treatment-input" name="visit_treatment[]"></td>
                <td>
                    <select name="visit_mode[]" class="mode-input" required>
                        <option value="">Select Payment Mode</option>
                        <option value="cash">Cash</option>
                        <option value="card">Card</option>
                        <option value="insurance">Insurance</option>
                    </select>
                    <button type="button" class="remove-visit-btn">×</button>
                </td>
            </tr>
        `;
        
        // Add new row to table
        $('#visitsTableBody').append(newRow);
    });

    // Add the remove visit handler
    $(document).on('click', '.remove-visit-btn', function() {
        // Don't allow removing the first visit
        if ($('#visitsTableBody tr').length > 1) {
            $(this).closest('tr').remove();
            
            // Renumber the remaining visits and update balances
            updateVisitNumbersAndBalances();
        }
    });

    // Add this function to handle visit numbers and balances
    function updateVisitNumbersAndBalances() {
        const netTotal = parseInt($('#netTotal').text().replace(/[^\d]/g, '')) || 0;
        let remainingBalance = netTotal;

        $('#visitsTableBody tr').each(function(index) {
            // Update visit numbers
            const count = index + 1;
            let suffix = 'TH';
            if (count === 1) suffix = 'ST';
            else if (count === 2) suffix = 'ND';
            else if (count === 3) suffix = 'RD';
            
            $(this).find('td:first').html(`${count}<sup>${suffix}</sup> VISIT`);

            // Update balances
            const amountPaid = parseInt($(this).find('.amount-paid-input').val()) || 0;
            remainingBalance = remainingBalance - amountPaid;
            $(this).find('.balance-input').val(`Rs. ${formatAmount(Math.max(0, remainingBalance))}`);
        });
    }

    // Form submission handler
    $('#patientForm').on('submit', function(e) {
        e.preventDefault();
        
        // Create FormData object
        const formData = new FormData(this);
        
        // Add patient basic data
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
            email: $('#email').val()
        };

        // Add medical history data
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
            otherConditions: $('#otherConditions').val()
        };

        // Get selected teeth
        const selectedTeeth = [];
        $('#Spots [data-key].selected').each(function() {
            selectedTeeth.push($(this).attr('data-key'));
        });
        formData.append('selectedTeeth', selectedTeeth.join(','));

        // Add treatments data
        const treatments = [];
        $('#treatmentsTableBody tr').each(function() {
            const row = $(this);
            treatments.push({
                name: row.find('td:first').text(),
                quantity: parseInt(row.find('.quantity-input').val()),
                pricePerUnit: parseFloat(row.find('td:nth-child(3)').text().replace(/[^0-9.-]+/g, '')),
                totalPrice: parseFloat(row.find('.total').text().replace(/[^0-9.-]+/g, '')),
                selectedTeeth: selectedTeeth
            });
        });

        // Add diagnosis and treatment advised
        formData.append('diagnosis', $('#diagnosis').val());
        formData.append('treatmentAdvised', $('#treatmentAdvised').val());

        // Add JSON stringified data
        formData.append('patientData', JSON.stringify(patientData));
        formData.append('medicalHistory', JSON.stringify(medicalHistory));
        formData.append('treatments', JSON.stringify(treatments));

        // Add billing information
        formData.append('discountType', $('#discountType').val());
        formData.append('discountValue', $('#discountValue').val());

        // Visits data is already part of the form fields as arrays:
        // visit_date[], visit_amount[], visit_treatment[], visit_mode[], visit_balance[]

        // Submit form
        $.ajax({
            url: 'save_patient.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                console.log('Response:', response);
                if (response.success) {
                    alert('Patient data saved successfully!');
                    window.location.href = 'view_patient.php?id=' + response.patientId;
                } else {
                    alert('Error: ' + response.message);
                    console.error('Error details:', response.details);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', error);
                console.error('Status:', status);
                console.error('Response:', xhr.responseText);
                alert('Error saving patient data: ' + error);
            }
        });
    });
});
