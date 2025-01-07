document.addEventListener('DOMContentLoaded', function() {
    // Initialize both signature pads
    function initializeSignaturePad(canvasId) {
        const canvas = document.getElementById(canvasId);
        if (!canvas) {
            console.error(`Canvas element ${canvasId} not found`);
            return null;
        }

        // Set canvas dimensions
        const ratio = Math.max(window.devicePixelRatio || 1, 1);
        canvas.width = canvas.offsetWidth * ratio;
        canvas.height = canvas.offsetHeight * ratio;
        canvas.getContext("2d").scale(ratio, ratio);

        return new SignaturePad(canvas, {
            backgroundColor: 'rgb(255, 255, 255)',
            penColor: 'rgb(0, 0, 0)',
            minWidth: 0.5,
            maxWidth: 2.5,
            throttle: 16,
            velocityFilterWeight: 0.7
        });
    }

    // Initialize both signature pads
    const patientSignaturePad = initializeSignaturePad('patientSignaturePad');
    const doctorSignaturePad = initializeSignaturePad('doctorSignaturePad');

    // Clear signature buttons
    document.getElementById('clearPatientSignature')?.addEventListener('click', function() {
        patientSignaturePad?.clear();
    });

    document.getElementById('clearDoctorSignature')?.addEventListener('click', function() {
        doctorSignaturePad?.clear();
    });

    // Handle form submission
    const form = document.getElementById('patientForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            if (patientSignaturePad?.isEmpty()) {
                alert('Please provide patient signature');
                e.preventDefault();
                return false;
            }
            if (doctorSignaturePad?.isEmpty()) {
                alert('Please provide doctor signature');
                e.preventDefault();
                return false;
            }

            // Add both signatures to form
            document.getElementById('patientSignatureData').value = patientSignaturePad.toDataURL();
            document.getElementById('doctorSignatureData').value = doctorSignaturePad.toDataURL();
        });
    }

    // Handle window resize for both canvases
    window.onresize = function() {
        if (patientSignaturePad) {
            const patientData = patientSignaturePad.toData();
            const patientCanvas = document.getElementById('patientSignaturePad');
            const ratio = Math.max(window.devicePixelRatio || 1, 1);
            patientCanvas.width = patientCanvas.offsetWidth * ratio;
            patientCanvas.height = patientCanvas.offsetHeight * ratio;
            patientCanvas.getContext("2d").scale(ratio, ratio);
            patientSignaturePad.fromData(patientData);
        }

        if (doctorSignaturePad) {
            const doctorData = doctorSignaturePad.toData();
            const doctorCanvas = document.getElementById('doctorSignaturePad');
            const ratio = Math.max(window.devicePixelRatio || 1, 1);
            doctorCanvas.width = doctorCanvas.offsetWidth * ratio;
            doctorCanvas.height = doctorCanvas.offsetHeight * ratio;
            doctorCanvas.getContext("2d").scale(ratio, ratio);
            doctorSignaturePad.fromData(doctorData);
        }
    };
}); 