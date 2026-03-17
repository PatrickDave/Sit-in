/**
 * Student Dashboard Logic
 * Path: Sit-in/js/studentDashboard.js
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log("Dashboard JS Loaded. Fetching data...");

    // 1. FETCH USER DATA FROM API
    fetch('../../api/studentDashboard.php')
        .then(response => {
            if (!response.ok) {
                throw new Error('HTTP error! status: ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            if (data.error) {
                console.error("Auth Error:", data.error);
                window.location.href = 'login.html';
                return;
            }

            console.log("Data received successfully:", data);

            // --- 2. NAME FORMATTING (Including Middle Initial) ---
            let middleInitial = "";
            if (data.middle_name && data.middle_name.trim() !== "") {
                middleInitial = data.middle_name.charAt(0).toUpperCase() + ". ";
            }
            const fullName = `${data.first_name} ${middleInitial}${data.last_name}`;

            // Update Navbar & Profile Card
            if (document.getElementById('navStudentName')) {
                document.getElementById('navStudentName').textContent = fullName;
            }
            if (document.getElementById('cardName')) {
                document.getElementById('cardName').textContent = fullName;
            }
            if (document.getElementById('cardID')) {
                document.getElementById('cardID').textContent = 'ID: ' + data.student_id;
            }

            // --- 3. UPDATE INFO & SESSIONS ---
            if (document.getElementById('cardCourse')) {
                document.getElementById('cardCourse').textContent = data.course;
            }
            if (document.getElementById('cardYear')) {
                document.getElementById('cardYear').textContent = 'Year ' + data.year_level;
            }
            if (document.getElementById('cardEmail')) {
                document.getElementById('cardEmail').textContent = data.email;
            }
            if (document.getElementById('cardAddress')) {
                document.getElementById('cardAddress').textContent = data.address;
            }

            const sessionElement = document.getElementById('cardSessions');
            if (sessionElement) {
                sessionElement.textContent = data.sessions_remaining;
                if (parseInt(data.sessions_remaining) <= 5) {
                    sessionElement.classList.add('text-warning');
                }
            }

            // --- 4. HANDLE PROFILE PICTURE ---
            const profilePicPath = (data.profile_picture && data.profile_picture !== 'default.png') 
                ? '../../image/' + data.profile_picture 
                : '../../image/default.png';

            if (document.getElementById('profilePic')) {
                document.getElementById('profilePic').src = profilePicPath;
            }

            // --- 5. POPULATE EDIT PROFILE MODAL FIELDS ---
            // This ensures the modal has current data when opened
            if (document.getElementById('editFirstName')) {
                document.getElementById('editFirstName').value = data.first_name;
            }
            if (document.getElementById('editMiddleName')) {
                document.getElementById('editMiddleName').value = data.middle_name || "";
            }
            if (document.getElementById('editLastName')) {
                document.getElementById('editLastName').value = data.last_name;
            }
            if (document.getElementById('editEmail')) {
                document.getElementById('editEmail').value = data.email;
            }
            if (document.getElementById('editAddress')) {
                document.getElementById('editAddress').value = data.address;
            }
            if (document.getElementById('modalPreview')) {
                document.getElementById('modalPreview').src = profilePicPath;
            }
        })
        .catch(error => {
            console.error('Fetch Error:', error);
        });

    // --- 6. HANDLE UPDATE SUCCESS NOTIFICATION ---
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('update') === 'success') {
        const toastElement = document.getElementById('updateToast');
        if (toastElement) {
            const toast = new bootstrap.Toast(toastElement);
            toast.show();
        } else {
            alert("Profile updated successfully!");
        }
        // Clean URL to prevent repeated notifications
        window.history.replaceState({}, document.title, window.location.pathname);
    }

    // --- 7. LIVE IMAGE PREVIEW FOR MODAL ---
    const profileInput = document.getElementById('profileInput');
    if (profileInput) {
        profileInput.addEventListener('change', function(e) {
            if (e.target.files && e.target.files[0]) {
                let reader = new FileReader();
                reader.onload = function(event) {
                    const preview = document.getElementById('modalPreview');
                    if (preview) preview.src = event.target.result;
                }
                reader.readAsDataURL(e.target.files[0]);
            }
        });
    }
});