$(document).ready(function() {
    // Treatment prices mapping
    const treatmentPrices = {
        consultation: 1000,
        radiograph: 1500,
        fillingD: 3000,
        fillingI: 2500,
        rct: 15000,
        pfmCrownD: 12000,
        pfmCrownI: 10000,
        zirconia: 20000,
        extSimple: 2000,
        extComp: 4000,
        acrylicDent: 25000,
        ccPlate: 8000,
        completeDenture: 35000,
        flexideDenture: 40000,
        bridgeD: 30000,
        bridgeI: 25000,
        implant: 50000,
        laserTeethWhitening: 15000,
        postAndCore: 8000,
        peadFilling: 2500,
        peadExt: 2000,
        pulpotomy: 5000,
        toothJewels: 3000,
        scalingAndPolishing: 3500,
        rootPlanning: 5000
    };

    // Handle treatment selection
    $('#treatmentSelect').change(function() {
        const selectedValue = $(this).val();
        if (selectedValue) {
            const treatmentText = $(this).find('option:selected').text();
            const price = treatmentPrices[selectedValue];
            
            // Add row to table
            const newRow = `
                <tr>
                    <td>${treatmentText.split(' (₹')[0]}</td>
                    <td>₹${price.toLocaleString()}</td>
                    <td><button type="button" class="remove-btn">Remove</button></td>
                </tr>
            `;
            $('#selectedTreatmentsList').append(newRow);
            
            // Reset select
            $(this).val('');
            
            // Update totals
            updateTotalAmount();
        }
    });

    // Handle remove button clicks
    $(document).on('click', '.remove-btn', function() {
        $(this).closest('tr').remove();
        updateTotalAmount();
    });

    // Update total amount
    function updateTotalAmount() {
        let total = 0;
        $('#selectedTreatmentsList tr').each(function() {
            const priceText = $(this).find('td:eq(1)').text();
            const price = parseInt(priceText.replace(/[^0-9]/g, ''));
            total += price;
        });

        $('#totalAmount').text(`₹${total.toLocaleString()}`);
        updateNetTotal();
    }

    // Update net total after discount
    function updateNetTotal() {
        const totalAmount = parseInt($('#totalAmount').text().replace(/[^0-9]/g, ''));
        const discountType = $('#discountType').val();
        const discountValue = parseFloat($('#discountValue').val()) || 0;
        
        let netTotal = totalAmount;
        if (discountType === 'percentage') {
            netTotal = totalAmount - (totalAmount * (discountValue / 100));
        } else {
            netTotal = totalAmount - discountValue;
        }

        $('#netTotal').text(`₹${netTotal.toLocaleString()}`);
    }

    // Handle discount changes
    $('#discountType, #discountValue').on('change input', updateNetTotal);

    // Add new visit row
    $('.add-visit-row').click(function() {
        const newRow = `
            <tr>
                <td><input type="date" class="date-input" name="visit_date[]"></td>
                <td><input type="text" class="treatment-input" name="visit_treatment[]"></td>
                <td><input type="number" class="amount-input" name="visit_amount[]"></td>
                <td><input type="text" class="mode-input" name="visit_mode[]"></td>
                <td><input type="number" class="balance-input" name="visit_balance[]"></td>
                <td><button type="button" class="remove-visit-row">Remove</button></td>
            </tr>
        `;
        $('.visits-table tbody').append(newRow);
    });

    // Remove visit row
    $(document).on('click', '.remove-visit-row', function() {
        $(this).closest('tr').remove();
    });

    // Form submission
    $('#patientForm').on('submit', function(e) {
        // Collect selected treatments
        const treatments = [];
        $('#selectedTreatmentsList tr').each(function() {
            treatments.push({
                name: $(this).find('td:eq(0)').text(),
                price: parseInt($(this).find('td:eq(1)').text().replace(/[^0-9]/g, ''))
            });
        });

        // Add treatments to form data
        const treatmentsInput = $('<input>')
            .attr('type', 'hidden')
            .attr('name', 'treatments')
            .val(JSON.stringify(treatments));
        $(this).append(treatmentsInput);

        // Add billing details
        const billingDetails = {
            totalAmount: parseInt($('#totalAmount').text().replace(/[^0-9]/g, '')),
            discountType: $('#discountType').val(),
            discountValue: parseFloat($('#discountValue').val()) || 0,
            netTotal: parseInt($('#netTotal').text().replace(/[^0-9]/g, ''))
        };

        const billingInput = $('<input>')
            .attr('type', 'hidden')
            .attr('name', 'billing')
            .val(JSON.stringify(billingDetails));
        $(this).append(billingInput);

        return true;
    });
}); 