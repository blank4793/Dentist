<!-- Add this where you want to display the dental chart -->
<div class="dental-chart-container">
    <div id="patientDentalChart"></div>
</div>

<script src="dental-chart-component.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Get selected teeth from PHP
        const selectedTeeth = <?php echo json_encode(explode(',', $patient['selected_teeth'] ?? '')); ?>;
        
        // Initialize dental chart in read-only mode
        const dentalChart = new DentalChart('patientDentalChart', selectedTeeth, true);
    });
</script> 