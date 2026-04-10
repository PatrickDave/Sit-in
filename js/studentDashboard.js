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
    const reservationModalElement = document.getElementById('reservationModal');
    const reservationModal = reservationModalElement ? new bootstrap.Modal(reservationModalElement) : null;
    const reservationTableBody = document.getElementById('reservationTableBody');
    const reservationEmptyState = document.getElementById('reservationEmptyState');
    const reservationForm = document.getElementById('reservationForm');
    const reservationStatus = document.getElementById('reservationStatus');
    const submitReservationBtn = document.getElementById('submitReservationBtn');
    const reservationDateInput = document.getElementById('reservationDate');
    const reservationTimeInInput = document.getElementById('reservationTimeIn');
    const reservationTimeOutInput = document.getElementById('reservationTimeOut');
    const reservationPcNumberInput = document.getElementById('reservationPcNumber');

    function reservationBadge(status) {
        const normalized = String(status || 'pending').toLowerCase();
        if (normalized === 'approved') {
            return '<span class="badge rounded-pill text-bg-success">Approved</span>';
        }
        if (normalized === 'denied') {
            return '<span class="badge rounded-pill text-bg-danger">Denied</span>';
        }
        return '<span class="badge rounded-pill text-bg-warning">Pending</span>';
    }

    function renderReservationRows(records) {
        if (!reservationTableBody || !reservationEmptyState) return;

        if (!records || records.length === 0) {
            reservationTableBody.innerHTML = '';
            reservationEmptyState.style.display = 'block';
            return;
        }

        reservationEmptyState.style.display = 'none';
        reservationTableBody.innerHTML = records.map((record) => `
            <tr>
                <td>${escapeHtml(record.student_id || '--')}</td>
                <td>${escapeHtml(record.laboratory || '--')}</td>
                <td>PC-${escapeHtml(record.pc_number || '--')}</td>
                <td>${escapeHtml(record.reservation_date || '--')}</td>
                <td>${escapeHtml(record.time_in || '--')}</td>
                <td>${escapeHtml(record.time_out || '--')}</td>
                <td>${escapeHtml(record.purpose || '--')}</td>
                <td>${reservationBadge(record.status)}</td>
            </tr>
        `).join('');
    }

    function populatePcOptions() {
        if (!reservationPcNumberInput) return;
        let options = '<option value="">Select PC</option>';
        for (let pc = 1; pc <= 50; pc += 1) {
            options += `<option value="${pc}">PC-${pc}</option>`;
        }
        reservationPcNumberInput.innerHTML = options;
    }

    function toMinutes(timeValue) {
        if (!timeValue || !timeValue.includes(':')) return null;
        const [hours, minutes] = timeValue.split(':').map(Number);
        if (Number.isNaN(hours) || Number.isNaN(minutes)) return null;
        return (hours * 60) + minutes;
    }

    function loadMyReservations() {
        fetch('../../api/reservations.php?action=my')
            .then(response => {
                if (!response.ok) {
                    throw new Error('HTTP error! status: ' + response.status);
                }
                return response.json();
            })
            .then(data => {
                if (!data.success) {
                    renderReservationRows([]);
                    return;
                }
                renderReservationRows(data.data || []);
            })
            .catch(error => {
                console.error('Reservation fetch error:', error);
                renderReservationRows([]);
            });
    }

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
    loadMyReservations();
    populatePcOptions();

    if (reservationDateInput) {
        reservationDateInput.min = new Date().toISOString().split('T')[0];
    }

    if (reservationModalElement) {
        reservationModalElement.addEventListener('show.bs.modal', function() {
            if (reservationStatus) {
                reservationStatus.textContent = '';
                reservationStatus.className = 'small mb-0 mt-3';
            }
        });

        reservationModalElement.addEventListener('hidden.bs.modal', function() {
            if (reservationForm) reservationForm.reset();
            if (reservationDateInput) {
                reservationDateInput.min = new Date().toISOString().split('T')[0];
            }
            populatePcOptions();
            if (reservationStatus) {
                reservationStatus.textContent = '';
                reservationStatus.className = 'small mb-0 mt-3';
            }
        });
    }

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

    if (submitReservationBtn) {
        submitReservationBtn.addEventListener('click', function() {
            const laboratory = document.getElementById('reservationLaboratory')?.value.trim() || '';
            const reservationDate = document.getElementById('reservationDate')?.value.trim() || '';
            const timeIn = reservationTimeInInput?.value.trim() || '';
            const timeOut = reservationTimeOutInput?.value.trim() || '';
            const pcNumber = reservationPcNumberInput?.value.trim() || '';
            const purpose = document.getElementById('reservationPurpose')?.value.trim() || '';

            if (!laboratory || !reservationDate || !timeIn || !timeOut || !pcNumber || !purpose) {
                if (reservationStatus) {
                    reservationStatus.textContent = 'Please complete all reservation fields.';
                    reservationStatus.className = 'small text-danger mb-0 mt-3';
                }
                return;
            }

            const timeInMinutes = toMinutes(timeIn);
            const timeOutMinutes = toMinutes(timeOut);
            if (timeInMinutes === null || timeOutMinutes === null || timeOutMinutes <= timeInMinutes) {
                if (reservationStatus) {
                    reservationStatus.textContent = 'Time-out must be later than Time-in.';
                    reservationStatus.className = 'small text-danger mb-0 mt-3';
                }
                return;
            }

            const duration = timeOutMinutes - timeInMinutes;
            if (duration < 30 || duration > 120) {
                if (reservationStatus) {
                    reservationStatus.textContent = 'Reservation duration must be between 30 minutes and 2 hours.';
                    reservationStatus.className = 'small text-danger mb-0 mt-3';
                }
                return;
            }

            const formData = new FormData();
            formData.append('action', 'create');
            formData.append('laboratory', laboratory);
            formData.append('reservation_date', reservationDate);
            formData.append('time_in', timeIn);
            formData.append('time_out', timeOut);
            formData.append('pc_number', pcNumber);
            formData.append('purpose', purpose);

            submitReservationBtn.disabled = true;
            fetch('../../api/reservations.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (!data.success) {
                        if (reservationStatus) {
                            reservationStatus.textContent = data.message || 'Failed to submit reservation.';
                            reservationStatus.className = 'small text-danger mb-0 mt-3';
                        }
                        return;
                    }

                    if (reservationStatus) {
                        reservationStatus.textContent = data.message || 'Reservation submitted successfully.';
                        reservationStatus.className = 'small text-success mb-0 mt-3';
                    }

                    if (reservationForm) reservationForm.reset();
                    if (reservationDateInput) {
                        reservationDateInput.min = new Date().toISOString().split('T')[0];
                    }
                    loadMyReservations();
                    setTimeout(() => {
                        if (reservationModal) reservationModal.hide();
                        if (reservationStatus) reservationStatus.textContent = '';
                    }, 700);
                })
                .catch(error => {
                    console.error('Reservation submit error:', error);
                    if (reservationStatus) {
                        reservationStatus.textContent = 'Error submitting reservation.';
                        reservationStatus.className = 'small text-danger mb-0 mt-3';
                    }
                })
                .finally(() => {
                    submitReservationBtn.disabled = false;
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
