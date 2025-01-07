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

    let treatments = [];

    document.getElementById('treatmentSelect').addEventListener('change', function() {
        const select = this;
        const selectedOption = select.options[select.selectedIndex];
        if (!selectedOption.value) return;

        // Get price from treatmentPrices mapping instead of parsing text
        const treatmentName = selectedOption.text.split('(')[0].trim();
        const pricePerUnit = treatmentPrices[selectedOption.value]; // Use the mapping
        
        if (!pricePerUnit) {
            console.error('Price not found for treatment:', selectedOption.value);
            return;
        }

        // Default quantity is 1
        const quantity = 1;
        const totalPrice = quantity * pricePerUnit;

        // Create treatment object
        const treatment = {
            name: treatmentName,
            quantity: quantity,
            pricePerUnit: pricePerUnit,
            totalPrice: totalPrice
        };

        // Add to treatments array
        treatments.push(treatment);
        
        // Update hidden input with stringified treatments
        document.querySelector('input[name="treatments"]').value = JSON.stringify(treatments);
        
        // Update tables
        updateTreatmentsTable();
        updateBillingTable();

        // Reset select
        select.selectedIndex = 0;
    });

    function updateTreatmentsTable() {
        const tbody = document.getElementById('selectedTreatmentsList');
        tbody.innerHTML = treatments.map((treatment, index) => `
            <tr>
                <td>${treatment.name}</td>
                <td><input type="number" value="${treatment.quantity}" min="1" onchange="updateQuantity(${index}, this.value)"></td>
                <td>₹${treatment.pricePerUnit.toFixed(2)}</td>
                <td>₹${treatment.totalPrice.toFixed(2)}</td>
                <td><button type="button" class="remove-btn" onclick="removeTreatment(${index})">Remove</button></td>
            </tr>
        `).join('');
    }

    function updateBillingTable() {
        const tbody = document.getElementById('billingList');
        tbody.innerHTML = treatments.map(treatment => `
            <tr>
                <td>${treatment.name}</td>
                <td>${treatment.quantity}</td>
                <td>₹${treatment.pricePerUnit.toFixed(2)}</td>
                <td>₹${treatment.totalPrice.toFixed(2)}</td>
            </tr>
        `).join('');
        
        // Update totals
        const total = treatments.reduce((sum, t) => sum + t.totalPrice, 0);
        document.getElementById('totalAmount').textContent = `₹${total.toFixed(2)}`;
        updateNetTotal();
    }

    function updateQuantity(index, newQuantity) {
        treatments[index].quantity = parseInt(newQuantity);
        treatments[index].totalPrice = treatments[index].quantity * treatments[index].pricePerUnit;
        
        // Update hidden input
        document.querySelector('input[name="treatments"]').value = JSON.stringify(treatments);
        
        // Update tables
        updateTreatmentsTable();
        updateBillingTable();
    }

    function removeTreatment(index) {
        treatments.splice(index, 1);
        
        // Update hidden input
        document.querySelector('input[name="treatments"]').value = JSON.stringify(treatments);
        
        // Update tables
        updateTreatmentsTable();
        updateBillingTable();
    }

    function updateNetTotal() {
        const total = treatments.reduce((sum, t) => sum + t.totalPrice, 0);
        const discountType = document.getElementById('discountType').value;
        const discountValue = parseFloat(document.getElementById('discountValue').value) || 0;
        
        let netTotal = total;
        if (discountType === 'percentage') {
            netTotal = total * (1 - discountValue / 100);
        } else {
            netTotal = total - discountValue;
        }
        
        document.getElementById('netTotal').textContent = `₹${netTotal.toFixed(2)}`;
    }

    // Add event listeners for discount changes
    document.getElementById('discountType').addEventListener('change', updateNetTotal);
    document.getElementById('discountValue').addEventListener('input', updateNetTotal);

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
            const row = $(this);
            const treatment = {
                name: row.find('td:eq(0)').text(),
                quantity: parseInt(row.find('.quantity-input').val()) || 1,
                pricePerUnit: parseInt(row.find('.price-per-unit').text().replace(/[₹,]/g, '')),
                totalPrice: parseInt(row.find('.total-price').text().replace(/[₹,]/g, ''))
            };
            treatments.push(treatment);
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

    // Visit section logic
    $(document).ready(function() {
        let totalBillingAmount = 0;

        // Function to update balance based on total billing amount
        function updateBalance() {
            // Get total billing amount from billing section (remove ₹ and any commas)
            totalBillingAmount = parseFloat($('#netTotal').text().replace(/[₹,]/g, '')) || 0;
            let totalPaidSoFar = 0;

            // Calculate total amount paid across all visits
            $('.amount-paid-input').each(function() {
                totalPaidSoFar += parseFloat($(this).val()) || 0;
            });

            // Calculate remaining balance
            const remainingBalance = totalBillingAmount - totalPaidSoFar;

            // Update balance in all rows
            $('.balance-input').each(function() {
                $(this).val(remainingBalance);
            });

            // Update totals row
            updateTotalsRow(totalPaidSoFar, remainingBalance);
        }

        // Function to update totals row
        function updateTotalsRow(totalPaid, remainingBalance) {
            let totalsRow = $('#visitsTableBody tr.totals-row');
            if (totalsRow.length === 0) {
                totalsRow = $('<tr class="totals-row">')
                    .append('<td>Totals</td>')
                    .append(`<td>₹${totalPaid}</td>`)
                    .append(`<td>₹${remainingBalance}</td>`)
                    .append('<td colspan="3"></td>');
                $('#visitsTableBody').append(totalsRow);
            } else {
                totalsRow.find('td:eq(1)').text(`₹${totalPaid}`);
                totalsRow.find('td:eq(2)').text(`₹${remainingBalance}`);
            }
        }

        // Listen for changes in amount paid inputs
        $(document).on('input', '.amount-paid-input', updateBalance);

        // Add visit row functionality
        $('#addVisitRow').on('click', function() {
            const visitCount = $('#visitsTableBody tr:not(.totals-row)').length + 1;
            const suffix = getNumberSuffix(visitCount);
            
            const newRow = `
                <tr>
                    <td>${visitCount}<sup>${suffix}</sup> VISIT</td>
                    <td><input type="number" class="amount-paid-input" name="visit_amount[]" step="1" min="0"></td>
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
            
            // Insert new row before totals row
            const totalsRow = $('#visitsTableBody tr.totals-row');
            if (totalsRow.length > 0) {
                totalsRow.before(newRow);
            } else {
                $('#visitsTableBody').append(newRow);
            }
            
            updateBalance();
        });

        // Helper function for number suffix
        function getNumberSuffix(num) {
            if (num >= 11 && num <= 13) return 'TH';
            switch (num % 10) {
                case 1: return 'ST';
                case 2: return 'ND';
                case 3: return 'RD';
                default: return 'TH';
            }
        }

        // Update balance when billing total changes
        $('#netTotal').on('DOMSubtreeModified', function() {
            updateBalance();
        });

        // Initial balance update
        updateBalance();
    });
}); 

// Keep the DentalChart class implementation 

// Tooth Chart Handling
document.addEventListener('DOMContentLoaded', function() {
    const selectedTeeth = new Set();
    const selectedTeethList = document.getElementById('selectedTeethList');
    
    // Get all tooth polygons and paths from SVG
    const toothElements = document.querySelectorAll('#Spots polygon, #Spots path');
    
    // Tooth name mapping using FDI/ISO system
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
            
            // Update hidden input with selected teeth
            updateSelectedTeethInput();
        });
    });
    
    function addToothToList(toothId) {
        const toothItem = document.createElement('div');
        toothItem.className = 'selected-tooth-item';
        toothItem.setAttribute('data-tooth-id', toothId);
        
        toothItem.innerHTML = `
            ${toothNames[toothId]} (Tooth ${toothId})
            <span class="remove-tooth" onclick="removeToothSelection('${toothId}')">&times;</span>
        `;
        
        selectedTeethList.appendChild(toothItem);
    }
    
    function removeToothFromList(toothId) {
        const toothItem = selectedTeethList.querySelector(`[data-tooth-id="${toothId}"]`);
        if (toothItem) {
            toothItem.remove();
        }
    }
    
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
}); 