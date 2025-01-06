<div class="form-group">
    <label>Dental Chart</label>
    <div class="dental-chart-container">
        <div id="editDentalChart"></div>
        <input type="hidden" name="selected_teeth" value="<?php echo htmlspecialchars($patient['selected_teeth'] ?? ''); ?>">
    </div>
</div>

<script src="dental-chart-component.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Get selected teeth from PHP
        const selectedTeeth = <?php echo json_encode(explode(',', $patient['selected_teeth'] ?? '')); ?>;
        
        // Initialize dental chart in edit mode
        const dentalChart = new DentalChart('editDentalChart', selectedTeeth, false);
        
        // Optional: Listen for changes
        document.getElementById('editDentalChart').addEventListener('teethChange', function(e) {
            console.log('Selected teeth changed:', e.detail);
        });
    });
</script> 