class DentalChart {
    constructor(containerId, selectedTeeth = [], readOnly = false) {
        this.container = document.getElementById(containerId);
        this.selectedTeeth = new Set(selectedTeeth);
        this.readOnly = readOnly;
        this.init();
    }

    init() {
        // Load the SVG content
        fetch('dental-chart.html')
            .then(response => response.text())
            .then(svgContent => {
                this.container.innerHTML = svgContent;
                this.setupEventListeners();
                this.highlightSelectedTeeth();
            });
    }

    setupEventListeners() {
        if (this.readOnly) return;

        const toothElements = this.container.querySelectorAll('#Spots polygon, #Spots path');
        toothElements.forEach(tooth => {
            tooth.addEventListener('click', (e) => {
                const toothId = e.target.getAttribute('data-key');
                this.toggleTooth(toothId);
            });
        });
    }

    toggleTooth(toothId) {
        const tooth = this.container.querySelector(`#Spots [data-key="${toothId}"]`);
        if (!tooth) return;

        if (this.selectedTeeth.has(toothId)) {
            this.selectedTeeth.delete(toothId);
            tooth.classList.remove('selected');
        } else {
            this.selectedTeeth.add(toothId);
            tooth.classList.add('selected');
        }

        // Update hidden input
        this.updateHiddenInput();
        // Trigger change event
        this.container.dispatchEvent(new CustomEvent('teethChange', {
            detail: Array.from(this.selectedTeeth)
        }));
    }

    highlightSelectedTeeth() {
        this.selectedTeeth.forEach(toothId => {
            const tooth = this.container.querySelector(`#Spots [data-key="${toothId}"]`);
            if (tooth) {
                tooth.classList.add('selected');
            }
        });
    }

    updateHiddenInput() {
        const input = this.container.querySelector('input[name="selected_teeth"]');
        if (input) {
            input.value = Array.from(this.selectedTeeth).join(',');
        }
    }

    getSelectedTeeth() {
        return Array.from(this.selectedTeeth);
    }
} 