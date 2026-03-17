document.addEventListener('DOMContentLoaded', function() {
    
    // --- 1. HANDLE REGISTRATION ERROR POPUP (From URL) ---
    const urlParams = new URLSearchParams(window.location.search);
    
    if (urlParams.get('error') === 'exists') {
        alert("Error: Student ID or Email is already registered.");

        // Clear all form fields (including the new Middle Name)
        const registrationForm = document.getElementById('registration-form'); 
        if (registrationForm) {
            registrationForm.reset();
        }

        // Clean up the URL to prevent repeated alerts on refresh
        window.history.replaceState({}, document.title, window.location.pathname);
    }

    // --- 2. FORM VALIDATION (On Submit) ---
    const form = document.getElementById('registration-form');
    
    if (form) {
        form.addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirm = document.getElementById('confirmPassword').value;
            const yearLevel = document.getElementById('yearlevel').value;
            const course = document.getElementById('course').value;

            // Check if passwords match
            if (password !== confirm) {
                e.preventDefault();
                alert("Passwords do not match!");
                return; // Stop further checks
            }

            // Optional: Ensure dropdowns are actually selected
            if (yearLevel === "" || course === "") {
                e.preventDefault();
                alert("Please select your Year Level and Course.");
                return;
            }
        });
    }
});