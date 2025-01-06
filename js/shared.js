// Shared functions used across multiple pages
const formatCurrency = (amount) => {
    return '₹' + amount.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
};

function updateTreatmentsTable() {
    const tbody = document.getElementById('selectedTreatmentsList');
    if (!tbody) return;

    tbody.innerHTML = treatments.map((treatment, index) => `
        <tr>
            <td>${treatment.name}</td>
            <td><input type="number" value="${treatment.quantity}" min="1" onchange="updateQuantity(${index}, this.value)"></td>
            <td>${formatCurrency(treatment.pricePerUnit)}</td>
            <td>${formatCurrency(treatment.totalPrice)}</td>
            <td><button type="button" class="remove-btn" onclick="removeTreatment(${index})">Remove</button></td>
        </tr>
    `).join('');
}

function updateBillingTable() {
    const tbody = document.getElementById('billingList');
    if (!tbody) return;

    tbody.innerHTML = treatments.map(treatment => `
        <tr>
            <td>${treatment.name}</td>
            <td style="text-align: center;">${treatment.quantity}</td>
            <td style="text-align: right;">${formatCurrency(treatment.pricePerUnit)}</td>
            <td style="text-align: right;">${formatCurrency(treatment.totalPrice)}</td>
        </tr>
    `).join('');
    
    updateTotals();
}

function updateTotals() {
    const total = treatments.reduce((sum, t) => sum + t.totalPrice, 0);
    const discountType = $('#discountType').val();
    const discountValue = parseFloat($('#discountValue').val()) || 0;
    
    let netTotal = total;
    if (discountType === 'percentage') {
        netTotal = total * (1 - discountValue / 100);
    } else {
        netTotal = total - discountValue;
    }

    $('#totalAmount').text(formatCurrency(total));
    $('#netTotal').text(formatCurrency(netTotal));
} 