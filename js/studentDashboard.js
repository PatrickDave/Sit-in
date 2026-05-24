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
    const reservationLaboratoryInput = document.getElementById('reservationLaboratory');
    const reservationPcNumberInput = document.getElementById('reservationPcNumber');
    const reservationEnabledSwitch = document.getElementById('reservationEnabledSwitch');
    const summaryTotalHours = document.getElementById('summaryTotalHours');
    const summarySessionCount = document.getElementById('summarySessionCount');
    const summaryAverageDuration = document.getElementById('summaryAverageDuration');
    const summaryLongestDuration = document.getElementById('summaryLongestDuration');
    const summaryTableBody = document.getElementById('summaryTableBody');
    const summaryEmptyState = document.getElementById('summaryEmptyState');
    const summarySessionBadge = document.getElementById('summarySessionBadge');
    const summaryReservationState = document.getElementById('summaryReservationState');

    const RESERVATION_LABS = ['524', '526', '530', '540', '544'];
    let reservationAvailabilityByLab = {};
    let maintenancePcSet = new Set();
    let reservationInputsDisabled = false;

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

    function setReservationInputsDisabled(disabled) {
        reservationInputsDisabled = disabled;
        const fields = [
            reservationLaboratoryInput,
            reservationDateInput,
            reservationTimeInInput,
            reservationTimeOutInput,
            reservationPcNumberInput,
            document.getElementById('reservationPurpose')
        ];

        fields.forEach((field) => {
            if (field) field.disabled = disabled;
        });

        if (submitReservationBtn) {
            submitReservationBtn.disabled = disabled;
        }

        if (reservationStatus) {
            if (disabled) {
                reservationStatus.textContent = 'Reservation is disabled. Turn on Reservation Access to continue.';
                reservationStatus.className = 'small text-secondary mb-0 mt-3';
            } else {
                reservationStatus.textContent = '';
                reservationStatus.className = 'small mb-0 mt-3';
            }
        }
    }

    function normalizePcSet(values) {
        return new Set((Array.isArray(values) ? values : []).map(String));
    }

    function renderLaboratoryOptions() {
        if (!reservationLaboratoryInput) return;

        const selectedLab = reservationLaboratoryInput.value;
        let options = '<option value="">Select laboratory</option>';

        RESERVATION_LABS.forEach((lab) => {
            const occupiedSet = normalizePcSet(reservationAvailabilityByLab[lab]);
            const unavailableSet = new Set([...occupiedSet, ...maintenancePcSet]);
            const availableCount = Math.max(0, 50 - unavailableSet.size);
            const label = `Lab ${lab} (${availableCount}/50 available)`;
            const disabledAttr = availableCount === 0 ? 'disabled' : '';
            const selectedAttr = selectedLab === lab ? 'selected' : '';
            options += `<option value="${lab}" ${disabledAttr} ${selectedAttr}>${label}</option>`;
        });

        reservationLaboratoryInput.innerHTML = options;
    }

    function renderPcOptionsForLab() {
        if (!reservationPcNumberInput) return;

        const selectedPc = reservationPcNumberInput.value;
        const selectedLab = reservationLaboratoryInput?.value || '';
        const occupiedSet = normalizePcSet(reservationAvailabilityByLab[selectedLab]);

        let options = '<option value="">Select PC</option>';
        for (let pc = 1; pc <= 50; pc += 1) {
            const pcKey = String(pc);
            const isMaintenance = maintenancePcSet.has(pcKey);
            const isOccupied = occupiedSet.has(pcKey);
            const isUnavailable = isMaintenance || isOccupied;

            let label = `PC-${pc}`;
            if (isMaintenance) {
                label += ' - Under Maintenance';
            } else if (isOccupied) {
                label += ' - Occupied';
            } else {
                label += ' - Available';
            }

            const disabledAttr = isUnavailable ? 'disabled' : '';
            const selectedAttr = (!isUnavailable && selectedPc === pcKey) ? 'selected' : '';
            options += `<option value="${pc}" ${disabledAttr} ${selectedAttr}>${label}</option>`;
        }

        reservationPcNumberInput.innerHTML = options;
    }

    function loadReservationAvailability() {
        const laboratory = reservationLaboratoryInput?.value?.trim() || RESERVATION_LABS[0];
        const reservationDate = reservationDateInput?.value?.trim() || '';
        const timeIn = reservationTimeInInput?.value?.trim() || '';
        const timeOut = reservationTimeOutInput?.value?.trim() || '';

        const params = new URLSearchParams({
            action: 'availability',
            laboratory,
            reservation_date: reservationDate,
            time_in: timeIn,
            time_out: timeOut
        });

        fetch(`../../api/reservations.php?${params.toString()}`)
            .then((response) => response.json())
            .then((data) => {
                if (!data.success) {
                    return;
                }

                reservationAvailabilityByLab = data.occupied_by_lab || {};
                maintenancePcSet = normalizePcSet(data.maintenance_pcs);

                renderLaboratoryOptions();
                renderPcOptionsForLab();
            })
            .catch((error) => {
                console.error('Reservation availability fetch error:', error);
            });
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

    function parseHistoryDateTime(dateValue, timeValue) {
        if (!dateValue || !timeValue) return null;

        const normalizedTime = String(timeValue).trim().toUpperCase();
        const match = normalizedTime.match(/^(\d{1,2}):(\d{2})(?::(\d{2}))?\s*(AM|PM)$/);
        if (!match) return null;

        let hours = Number(match[1]);
        const minutes = Number(match[2]);
        const meridiem = match[4];

        if (meridiem === 'PM' && hours !== 12) hours += 12;
        if (meridiem === 'AM' && hours === 12) hours = 0;

        const date = new Date(`${dateValue}T00:00:00`);
        if (Number.isNaN(date.getTime())) return null;

        date.setHours(hours, minutes, 0, 0);
        return date;
    }

    function formatDuration(totalMinutes) {
        const safeMinutes = Math.max(0, Number(totalMinutes) || 0);
        const hours = Math.floor(safeMinutes / 60);
        const minutes = safeMinutes % 60;

        if (hours > 0) {
            return `${hours}h ${String(minutes).padStart(2, '0')}m`;
        }

        return `${minutes}m`;
    }

    function computeSessionDuration(record) {
        const start = parseHistoryDateTime(record.date, record.time_in);
        const end = parseHistoryDateTime(record.date, record.time_out);
        if (!start || !end) return null;

        let diffMinutes = Math.round((end.getTime() - start.getTime()) / 60000);
        if (diffMinutes < 0) {
            diffMinutes += 24 * 60;
        }

        return diffMinutes >= 0 ? diffMinutes : null;
    }

    function updateReservationStateBadge(enabled) {
        if (!summaryReservationState) return;

        if (enabled) {
            summaryReservationState.textContent = 'Reservation Enabled';
            summaryReservationState.className = 'badge rounded-pill text-bg-primary';
            return;
        }

        summaryReservationState.textContent = 'Reservation Disabled';
        summaryReservationState.className = 'badge rounded-pill text-bg-secondary';
    }

    function renderSummary(records) {
        const summaryRecords = Array.isArray(records) ? records : [];
        const enrichedRecords = summaryRecords.map((record) => ({
            ...record,
            durationMinutes: computeSessionDuration(record)
        }));

        const totalMinutes = enrichedRecords.reduce((sum, record) => sum + (record.durationMinutes || 0), 0);
        const sessionCount = enrichedRecords.length;
        const averageMinutes = sessionCount > 0 ? Math.round(totalMinutes / sessionCount) : 0;
        const longestMinutes = enrichedRecords.reduce((max, record) => Math.max(max, record.durationMinutes || 0), 0);

        if (summaryTotalHours) summaryTotalHours.textContent = formatDuration(totalMinutes);
        if (summarySessionCount) summarySessionCount.textContent = String(sessionCount);
        if (summaryAverageDuration) summaryAverageDuration.textContent = formatDuration(averageMinutes);
        if (summaryLongestDuration) summaryLongestDuration.textContent = formatDuration(longestMinutes);
        if (summarySessionBadge) {
            summarySessionBadge.textContent = `${sessionCount} ${sessionCount === 1 ? 'Session' : 'Sessions'}`;
        }

        if (!summaryTableBody || !summaryEmptyState) return;

        if (sessionCount === 0) {
            summaryTableBody.innerHTML = '';
            summaryEmptyState.style.display = 'block';
            return;
        }

        summaryEmptyState.style.display = 'none';
        summaryTableBody.innerHTML = enrichedRecords.map((record) => `
            <tr>
                <td>${escapeHtml(record.date || '--')}</td>
                <td>${escapeHtml(record.time_in || '--')}</td>
                <td>${escapeHtml(record.time_out || '--')}</td>
                <td>${record.durationMinutes === null ? '--' : escapeHtml(formatDuration(record.durationMinutes))}</td>
                <td>${escapeHtml(record.pc_number ? `PC-${record.pc_number}` : '--')}</td>
                <td><span class="badge rounded-pill text-bg-success">${escapeHtml(record.status || 'Completed')}</span></td>
            </tr>
        `).join('');
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
                    renderSummary([]);
                    return;
                }
                const records = data.data || [];
                renderHistoryRows(records);
                renderSummary(records);
            })
            .catch(error => {
                console.error('Sit-in history fetch error:', error);
                renderHistoryRows([]);
                renderSummary([]);
            });
    }

    loadSitInHistory();
    loadMyReservations();
    populatePcOptions();
    loadReservationAvailability();

    if (reservationDateInput) {
        reservationDateInput.min = new Date().toISOString().split('T')[0];
    }

    if (reservationModalElement) {
        reservationModalElement.addEventListener('show.bs.modal', function() {
            if (reservationStatus) {
                reservationStatus.textContent = '';
                reservationStatus.className = 'small mb-0 mt-3';
            }
            loadReservationAvailability();
        });

        reservationModalElement.addEventListener('hidden.bs.modal', function() {
            if (reservationForm) reservationForm.reset();
            if (reservationDateInput) {
                reservationDateInput.min = new Date().toISOString().split('T')[0];
            }
            populatePcOptions();
            loadReservationAvailability();
            if (reservationEnabledSwitch) {
                reservationEnabledSwitch.checked = true;
            }
            setReservationInputsDisabled(false);
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
            if (reservationInputsDisabled) {
                if (reservationStatus) {
                    reservationStatus.textContent = 'Enable Reservation Access first.';
                    reservationStatus.className = 'small text-danger mb-0 mt-3';
                }
                return;
            }

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
                    loadReservationAvailability();
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

    if (reservationEnabledSwitch) {
        reservationEnabledSwitch.addEventListener('change', function() {
            setReservationInputsDisabled(!reservationEnabledSwitch.checked);
            updateReservationStateBadge(reservationEnabledSwitch.checked);
        });
    }

    if (reservationLaboratoryInput) {
        reservationLaboratoryInput.addEventListener('change', function() {
            renderPcOptionsForLab();
            loadReservationAvailability();
        });
    }

    [reservationDateInput, reservationTimeInInput, reservationTimeOutInput].forEach((input) => {
        if (!input) return;
        input.addEventListener('change', loadReservationAvailability);
    });

    updateReservationStateBadge(reservationEnabledSwitch ? reservationEnabledSwitch.checked : true);

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
