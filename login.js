$(document).ready(function() {
    // Get Firebase auth instance
    const auth = firebase.auth();

    $('#loginForm').submit(function(e) {
        e.preventDefault();
        
        const username = $('#username').val();
        const password = $('#password').val();

        // Sign in with Firebase
        auth.signInWithEmailAndPassword(username, password)
            .then((userCredential) => {
                // Store user info in localStorage
                localStorage.setItem('user', JSON.stringify({
                    uid: userCredential.user.uid,
                    email: userCredential.user.email
                }));
                
                // Redirect to dashboard
                window.location.href = 'dashboard.html';
            })
            .catch((error) => {
                alert('Login failed: ' + error.message);
            });
    });
}); 