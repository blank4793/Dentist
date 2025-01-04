$(document).ready(function() {
    // Check if user is logged in
    const user = JSON.parse(localStorage.getItem('user'));
    if (!user) {
        window.location.href = 'login.html';
        return;
    }

    // Load patient list
    function loadPatientList() {
        db.collection('patients')
            .orderBy('date', 'desc')
            .limit(10)
            .get()
            .then((querySnapshot) => {
                const tbody = $('#recentPatientsList');
                tbody.empty();

                querySnapshot.forEach((doc) => {
                    const patient = doc.data();
                    tbody.append(`
                        <tr>
                            <td>${patient.name}</td>
                            <td>${patient.date}</td>
                            <td>${patient.treatment}</td>
                            <td>${patient.status}</td>
                            <td>
                                <button onclick="editPatient('${doc.id}')">Edit</button>
                                <button onclick="viewPatient('${doc.id}')">View</button>
                            </td>
                        </tr>
                    `);
                });
            });
    }

    // Save patient form
    function savePatientForm(formData) {
        return db.collection('patients').add({
            ...formData,
            doctorId: user.uid,
            createdAt: firebase.firestore.FieldValue.serverTimestamp()
        });
    }

    // Edit patient
    window.editPatient = function(patientId) {
        // Load patient data
        db.collection('patients').doc(patientId).get()
            .then((doc) => {
                if (doc.exists) {
                    const patient = doc.data();
                    // Redirect to form with patient data
                    localStorage.setItem('editPatient', JSON.stringify({
                        id: doc.id,
                        ...patient
                    }));
                    window.location.href = 'index.html';
                }
            });
    };

    // View patient
    window.viewPatient = function(patientId) {
        // Similar to edit but in read-only mode
    };

    // Logout
    $('#logoutBtn').click(function() {
        auth.signOut().then(() => {
            localStorage.clear();
            window.location.href = 'login.html';
        });
    });
}); 