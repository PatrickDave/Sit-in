/**
 * Student Dashboard Logic
 * Path: Sit-in/js/studentDashboard.js
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log("Dashboard JS Loaded. Fetching data...");

    function escapeHtml(value) {
        return String(value ?? '')
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }

    // --- A. SIT-IN HISTORY RENDER HELPERS ---
    const historyTableBody = document.getElementById('historyTableBody');
    const historyEmptyState = document.getElementById('historyEmptyState');
    const successfulHistoryCount = document.getElementById('successfulHistoryCount');
    const feedbackModalElement = document.getElementById('feedbackModal');
    const feedbackModal = feedbackModalElement ? new bootstrap.Modal(feedbackModalElement) : null;
    const feedbackSitInIdInput = document.getElementById('feedbackSitInId');
    const feedbackMessageInput = document.getElementById('feedbackMessage');
    const feedbackStatus = document.getElementById('feedbackStatus');
    const submitFeedbackBtn = document.getElementById('submitFeedbackBtn');

    window.openFeedbackModal = function(sitInId) {
        if (!feedbackModal || !feedbackSitInIdInput || !feedbackMessageInput || !feedbackStatus) return;
        feedbackSitInIdInput.value = sitInId;
        feedbackMessageInput.value = '';
        feedbackStatus.textContent = '';
        feedbackModal.show();
    };

    function renderHistoryRows(records) {
        if (!historyTableBody || !historyEmptyState || !successfulHistoryCount) return;

        const historyRecords = records || [];
        successfulHistoryCount.textContent = `${historyRecords.length} Records`;

        if (historyRecords.length === 0) {
            historyTableBody.innerHTML = '';
            historyEmptyState.style.display = 'block';
            return;
        }

        historyEmptyState.style.display = 'none';
        historyTableBody.innerHTML = historyRecords.map((record) => {
            const idNumber = record.id_number || '--';
            const name = record.name || '--';
            const purpose = record.purpose || '--';
            const laboratory = record.laboratory || '--';
            const timeIn = record.time_in || '--';
            const timeOut = record.time_out || '--';
            const date = record.date || '--';
            const sitInId = record.sit_in_id || 0;
            const feedbackSubmitted = Number(record.feedback_submitted || 0) === 1;
            const feedbackAction = feedbackSubmitted
                ? '<button type="button" class="btn btn-secondary btn-sm" disabled>Submitted</button>'
                : `<button type="button" class="btn btn-success btn-sm feedback-action-btn" data-sit-in-id="${sitInId}">Feedback</button>`;

            return `
                <tr>
                    <td>${idNumber}</td>
                    <td>${name}</td>
                    <td>${purpose}</td>
                    <td>${laboratory}</td>
                    <td>${timeIn}</td>
                    <td>${timeOut}</td>
                    <td>${date}</td>
                    <td>${feedbackAction}</td>
                </tr>
            `;
        }).join('');
    }

    function loadSitInHistory() {
        fetch('../../api/student_sitin_history.php')
            .then(response => {
                if (!response.ok) {
                    throw new Error('HTTP error! status: ' + response.status);
                }
                return response.json();
            })
            .then(data => {
                if (!data.success) {
                    console.error('History Error:', data.message || 'Unable to load sit-in history');
                    renderHistoryRows([]);
                    return;
                }
                renderHistoryRows(data.data || []);
            })
            .catch(error => {
                console.error('Sit-in history fetch error:', error);
                renderHistoryRows([]);
            });
    }

    loadSitInHistory();

    function renderAnnouncements(records) {
        const container = document.getElementById('studentAnnouncementList');
        const empty = document.getElementById('studentAnnouncementEmpty');
        if (!container || !empty) return;

        if (!records || records.length === 0) {
            container.innerHTML = '';
            empty.style.display = 'block';
            return;
        }

        empty.style.display = 'none';
        container.innerHTML = records.map((row) => `
            <div class="announcement-item">
                <div class="announcement-head">
                    <span class="fw-bold">CCS Admin</span>
                    <span>${escapeHtml(row.created_at || '')}</span>
                </div>
                <p class="announcement-msg">${escapeHtml(row.message || '')}</p>
            </div>
        `).join('');
    }

    function loadAnnouncements() {
        fetch('../../api/admin_announcements.php?action=list&limit=6')
            .then(response => {
                if (!response.ok) {
                    throw new Error('HTTP error! status: ' + response.status);
                }
                return response.json();
            })
            .then(data => {
                if (!data.success) {
                    renderAnnouncements([]);
                    return;
                }
                renderAnnouncements(data.data || []);
            })
            .catch(error => {
                console.error('Announcement fetch error:', error);
                renderAnnouncements([]);
            });
    }

    loadAnnouncements();

    if (historyTableBody) {
        historyTableBody.addEventListener('click', function(e) {
            const btn = e.target.closest('.feedback-action-btn');
            if (!btn) return;

            const sitInId = Number(btn.getAttribute('data-sit-in-id') || 0);
            if (!sitInId) {
                alert('Unable to open feedback form for this row.');
                return;
            }
            window.openFeedbackModal(sitInId);
        });
    }

    if (submitFeedbackBtn) {
        submitFeedbackBtn.addEventListener('click', function() {
            if (!feedbackSitInIdInput || !feedbackMessageInput || !feedbackStatus) return;

            const sitInId = feedbackSitInIdInput.value.trim();
            const message = feedbackMessageInput.value.trim();
            if (!sitInId || !message) {
                feedbackStatus.textContent = 'Please enter your feedback message.';
                feedbackStatus.className = 'small text-danger mb-0';
                return;
            }

            const formData = new FormData();
            formData.append('sit_in_id', sitInId);
            formData.append('message', message);

            submitFeedbackBtn.disabled = true;
            fetch('../../api/student_feedback_submit.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (!data.success) {
                        feedbackStatus.textContent = data.message || 'Failed to submit feedback.';
                        feedbackStatus.className = 'small text-danger mb-0';
                        return;
                    }

                    feedbackStatus.textContent = 'Feedback submitted successfully.';
                    feedbackStatus.className = 'small text-success mb-0';
                    setTimeout(() => {
                        if (feedbackModal) feedbackModal.hide();
                        loadSitInHistory();
                    }, 600);
                })
                .catch(error => {
                    console.error('Feedback submit error:', error);
                    feedbackStatus.textContent = 'Error submitting feedback.';
                    feedbackStatus.className = 'small text-danger mb-0';
                })
                .finally(() => {
                    submitFeedbackBtn.disabled = false;
                });
        });
    }

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
