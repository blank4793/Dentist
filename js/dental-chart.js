$(document).ready(function() {
    const selectedTeeth = new Set();
    const selectedTeethList = document.getElementById('selectedTeethList');
    const selectedTeethInput = document.getElementById('selectedTeethInput');

    // Get all tooth elements
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
            console.log('Updating selected teeth:', Array.from(selectedTeeth));
        });
    });

    // Add tooth to the list display
    function addToothToList(toothId) {
        // Check if tooth is already in list
        if (document.querySelector(`.selected-tooth-item[data-tooth-id="${toothId}"]`)) {
            return; // Skip if already exists
        }

        const toothItem = document.createElement('div');
        toothItem.className = 'selected-tooth-item';
        toothItem.setAttribute('data-tooth-id', toothId);
        
        const toothName = toothNames[toothId] || `Tooth ${toothId}`;
        toothItem.innerHTML = `
            ${toothName} (Tooth ${toothId})
            <span class="remove-tooth" onclick="removeToothSelection('${toothId}')">&times;</span>
        `;
        
        selectedTeethList.appendChild(toothItem);
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
        selectedTeethInput.value = Array.from(selectedTeeth).join(',');
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

    // Add some CSS styles for selected teeth
    $('<style>')
        .text(`
            .selected { fill: #007bff !important; }
            .selected-tooth-item {
                padding: 5px;
                margin: 5px 0;
                background: #f8f9fa;
                border-radius: 4px;
                display: flex;
                justify-content: space-between;
                align-items: center;
            }
            .remove-tooth {
                cursor: pointer;
                color: #dc3545;
                font-weight: bold;
                padding: 0 5px;
            }
            .remove-tooth:hover {
                color: #bd2130;
            }
        `)
        .appendTo('head');
}); 