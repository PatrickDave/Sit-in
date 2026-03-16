document.addEventListener('DOMContentLoaded', () => {
  // Make the Home page visually empty when the user clicks "Home"
  const homeLinks = document.querySelectorAll('.nav-links .nav-link[href="index.html"]');

  homeLinks.forEach((link) => {
    link.addEventListener('click', (event) => {
      event.preventDefault(); // Stop navigation to keep us on the current page

      const main = document.querySelector('.main');
      if (main) {
        main.innerHTML = ''; // Clear all content inside the main section
      }
    });
  });

  // Optional nicety: keep the footer year up to date if present
  const yearEl = document.querySelector('.footer span[data-year]');
  if (yearEl) {
    yearEl.textContent = new Date().getFullYear();
  }

  // Registration Form Handler
  const registrationForm = document.getElementById('registration-form');
  if (registrationForm) {
    registrationForm.addEventListener('submit', handleRegistration);
  }

  // Login Form Handler
  const loginForm = document.getElementById('loginForm');
  if (loginForm) {
    loginForm.addEventListener('submit', handleLogin);
  }

  // Show/hide Profile link based on login status
  updateProfileNavLink();
});

// Handle Registration Form Submission
function handleRegistration(event) {
  event.preventDefault();

  // Get form data
  const studentId = document.getElementById('studentId')?.value.trim();
  const lastName = document.getElementById('lastName')?.value.trim();
  const firstName = document.getElementById('firstName')?.value.trim();
  const email = document.getElementById('email')?.value.trim();
  const yearLevel = document.getElementById('yearlevel')?.value.trim();
  const course = document.getElementById('course')?.value.trim();
  const address = document.getElementById('addreess')?.value.trim();
  const password = document.getElementById('password')?.value;
  const confirmPassword = document.getElementById('confirmPassword')?.value;

  // Validation
  const errors = [];

  if (!studentId) errors.push('ID Number is required');
  if (!lastName) errors.push('Last Name is required');
  if (!firstName) errors.push('First Name is required');
  if (!email) errors.push('Email is required');
  if (!yearLevel) errors.push('Year Level is required');
  if (!email.match(/^[^\s@]+@[^\s@]+\.[^\s@]+$/)) errors.push('Valid email is required');
  if (!course) errors.push('Course is required');
  if (!address) errors.push('Address is required');
  if (!password) errors.push('Password is required');
  if (password.length < 6) errors.push('Password must be at least 6 characters');
  if (password !== confirmPassword) errors.push('Passwords do not match');

  if (errors.length > 0) {
    showStatus('register', errors.join('<br>'), 'error');
    return;
  }

  // Send data to PHP API
  fetch('api/register.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      studentId,
      firstName,
      lastName,
      email,
      yearLevel: parseInt(yearLevel),
      course,
      address,
      password,
      confirmPassword
    })
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      showStatus('register', 'Registration successful! Redirecting to login...', 'success');
      setTimeout(() => {
        window.location.href = 'login.html';
      }, 2000);
    } else {
      showStatus('register', data.error || 'Registration failed', 'error');
    }
  })
  .catch(error => {
    console.error('Registration error:', error);
    showStatus('register', 'Registration failed. Please try again.', 'error');
  });
}

// Handle Login Form Submission
function handleLogin(event) {
  event.preventDefault();

  const email = document.getElementById('email')?.value.trim();
  const password = document.getElementById('password')?.value;

  // Validation
  if (!email || !password) {
    showStatus('login', 'Email and password are required', 'error');
    return;
  }

  // Send login request to PHP API
  fetch('api/login.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      email,
      password
    })
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      // Store user session data
      localStorage.setItem('currentUser', JSON.stringify({
        studentId: data.data.user.studentId,
        firstName: data.data.user.firstName,
        lastName: data.data.user.lastName,
        email: data.data.user.email,
        loginAt: new Date().toISOString(),
        lastActivityAt: Date.now()
        
      }));

      // Show success and redirect
      showStatus('login', 'Login successful! Redirecting...', 'success');
      setTimeout(() => {
        window.location.href = 'profile.html';
      }, 1500);
    } else {
      showStatus('login', data.error || 'Invalid email or password', 'error');
    }
  })
  .catch(error => {
    console.error('Login error:', error);
    showStatus('login', 'Login failed. Please try again.', 'error');
  });
}

// Display status messages
function showStatus(formType, message, type) {
  let statusElement;

  if (formType === 'login') {
    statusElement = document.getElementById('loginStatus');
  } else if (formType === 'register') {
    statusElement = document.getElementById('registerStatus');
  }

  if (!statusElement) return;

  // Remove existing status messages
  const existingStatus = statusElement.querySelector('.status-message');
  if (existingStatus) {
    existingStatus.remove();
  }

  // Create new status message
  const div = document.createElement('div');
  div.className = `status-message status-message--${type}`;
  div.innerHTML = `
    <div class="status-content" style="
      padding: 1rem;
      border-radius: 0.5rem;
      margin-bottom: 1.5rem;
      ${type === 'error' ? 'background-color: #fee2e2; color: #991b1b; border: 1px solid #fca5a5;' : 'background-color: #dcfce7; color: #166534; border: 1px solid #86efac;'}
    ">
      ${message}
    </div>
  `;

  statusElement.insertAdjacentElement('beforeend', div);
}

// Check if user is logged in and update UI
function checkLoginStatus() {
  const currentUser = JSON.parse(localStorage.getItem('currentUser') || 'null');
  if (currentUser) {
    // User is logged in - you can update UI accordingly
    console.log('User logged in:', currentUser.firstName, currentUser.lastName);
  }
}

// Show/hide Profile link based on login status
function updateProfileNavLink() {
  const currentUser = JSON.parse(localStorage.getItem('currentUser') || 'null');
  const profileLink = document.getElementById('profileNavLink');

  if (profileLink) {
    if (currentUser) {
      profileLink.style.display = 'block';
    } else {
      profileLink.style.display = 'none';
    }
  }
}

// Logout function (you can call this from a logout button)
function logout() {
  // Call logout API to destroy session in database
  fetch('api/logout.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    }
  })
  .then(() => {
    localStorage.removeItem('currentUser');
    window.location.href = 'index.html';
  })
  .catch(() => {
    // Still clear local storage and redirect even if API fails
    localStorage.removeItem('currentUser');
    window.location.href = 'index.html';
  });
}

// Toggle password visibility
function togglePassword(inputId) {
  const inputField = document.getElementById(inputId);
  if (inputField) {
    inputField.type = inputField.type === 'password' ? 'text' : 'password';
  }
}

// ===== PROFILE MANAGEMENT FUNCTIONS =====

// Load and display user profile
function loadUserProfile() {
  const currentUser = JSON.parse(localStorage.getItem('currentUser') || 'null');

  if (!currentUser) {
    window.location.href = 'login.html';
    return;
  }

  // Fetch user profile from database API
  fetch('api/profile.php', {
    method: 'GET',
    headers: {
      'Content-Type': 'application/json'
    }
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      const user = data.data;

      // Display profile data
      const fullName = `${user.firstName} ${user.lastName}`;

      // View Mode
      document.getElementById('profileName').textContent = fullName;
      document.getElementById('profileEmail').textContent = user.email;
      document.getElementById('viewStudentId').textContent = user.studentId;
      document.getElementById('viewEmail').textContent = user.email;
      document.getElementById('viewYearLevel').textContent = user.yearLevel;
      document.getElementById('viewCourse').textContent = user.course;
      document.getElementById('viewAddress').textContent = user.address;

      // Format registration date
      const regDate = new Date(user.createdAt).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
      });
      document.getElementById('viewMemberSince').textContent = regDate;

      // Load profile photo if exists
      if (user.profilePhoto) {
        const photoUrl = user.profilePhoto.startsWith('http') ? user.profilePhoto : '/' + user.profilePhoto;
        document.getElementById('profilePhotoDisplay').style.display = 'block';
        document.getElementById('profilePhotoDisplay').src = photoUrl;
        document.getElementById('profilePhotoFallback').style.display = 'none';

        document.getElementById('profilePhotoEdit').style.display = 'block';
        document.getElementById('profilePhotoEdit').src = photoUrl;
        document.getElementById('profilePhotoFallbackEdit').style.display = 'none';
      } else {
        // Show fallback with initials
        const initials = `${user.firstName[0]}${user.lastName[0]}`.toUpperCase();
        document.getElementById('profilePhotoFallback').textContent = initials;
        document.getElementById('profilePhotoFallback').style.display = 'flex';
        document.getElementById('profilePhotoDisplay').style.display = 'none';

        document.getElementById('profilePhotoFallbackEdit').textContent = initials;
        document.getElementById('profilePhotoFallbackEdit').style.display = 'flex';
        document.getElementById('profilePhotoEdit').style.display = 'none';
      }

      // Populate edit form
      document.getElementById('editStudentId').value = user.studentId;
      document.getElementById('editFirstName').value = user.firstName;
      document.getElementById('editLastName').value = user.lastName;
      document.getElementById('editEmail').value = user.email;
      document.getElementById('editYearLevel').value = user.yearLevel;
      document.getElementById('editCourse').value = user.course;
      document.getElementById('editAddress').value = user.address;
    } else {
      console.error('Failed to load profile:', data.error);
    }
  })
  .catch(error => {
    console.error('Profile loading error:', error);
  });
}

// Toggle between view and edit mode
function toggleEditMode() {
  const viewMode = document.getElementById('viewMode');
  const editMode = document.getElementById('editMode');

  viewMode.style.display = viewMode.style.display === 'none' ? 'block' : 'none';
  editMode.style.display = editMode.style.display === 'none' ? 'block' : 'none';

  // Clear any previous status messages
  document.getElementById('editStatus').innerHTML = '';
}

// Save profile changes
function saveProfile(event) {
  event.preventDefault();

  const firstName = document.getElementById('editFirstName').value.trim();
  const lastName = document.getElementById('editLastName').value.trim();
  const email = document.getElementById('editEmail').value.trim();
  const yearlevel = document.getElementById('editYearLevel').value;
  const course = document.getElementById('editCourse').value;
  const address = document.getElementById('editAddress').value.trim();

  // Validation
  const errors = [];
  if (!firstName) errors.push('First Name is required');
  if (!lastName) errors.push('Last Name is required');
  if (!email) errors.push('Email is required');
  if (!email.match(/^[^\s@]+@[^\s@]+\.[^\s@]+$/)) errors.push('Valid email is required');
  if (!yearlevel) errors.push('Year Level is required');
  if (!course) errors.push('Course is required');
  if (!address) errors.push('Address is required');

  if (errors.length > 0) {
    showProfileStatus(errors.join('<br>'), 'error');
    return;
  }

    // Get current user from localStorage for session info
  const currentUser = JSON.parse(localStorage.getItem('currentUser'));
  
  if (!currentUser) {
    showProfileStatus('Session expired. Please login again.', 'error');
    window.location.href = 'login.html';
    return;
  }

  // Send update to PHP API
  fetch('api/update-profile.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      firstName,
      lastName,
      email,
      yearLevel: parseInt(yearlevel),
      course,
      address
    })
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      // Update localStorage session
      const updatedCurrentUser = {
        ...currentUser,
        firstName,
        lastName,
        email
      };
      localStorage.setItem('currentUser', JSON.stringify(updatedCurrentUser));
      
      showProfileStatus('Profile updated successfully!', 'success');
      setTimeout(() => {
        loadUserProfile();
        toggleEditMode();
      }, 1500);
    } else {
      showProfileStatus(data.error || 'Update failed', 'error');
    }
  })
  .catch(error => {
    console.error('Update error:', error);
    showProfileStatus('Update failed. Please try again.', 'error');
  });
}


// Handle photo upload
function setupPhotoUpload() {
  const photoInput = document.getElementById('photoInput');

  photoInput.addEventListener('change', (event) => {
    const file = event.target.files[0];

    if (!file) return;

    // Validate file size (5MB max)
    if (file.size > 5 * 1024 * 1024) {
      showProfileStatus('File size must be less than 5MB', 'error');
      return;
    }

    // Validate file type
    const validTypes = ['image/jpeg', 'image/png', 'image/gif'];
    if (!validTypes.includes(file.type)) {
      showProfileStatus('Only JPG, PNG, and GIF files are allowed', 'error');
      return;
    }

    // Convert to base64
    const reader = new FileReader();
    reader.onload = (e) => {
      const base64String = e.target.result;

      // Update both preview images
      document.getElementById('profilePhotoDisplay').src = base64String;
      document.getElementById('profilePhotoDisplay').style.display = 'block';
      document.getElementById('profilePhotoFallback').style.display = 'none';

      document.getElementById('profilePhotoEdit').src = base64String;
      document.getElementById('profilePhotoEdit').style.display = 'block';
      document.getElementById('profilePhotoFallbackEdit').style.display = 'none';

      // Store temporarily (will be saved when profile is saved)
      showProfileStatus('Photo selected. Click "Save Changes" to confirm.', 'success');
    };

    reader.readAsDataURL(file);
  });
}

// Show status message
function showProfileStatus(message, type) {
  const statusElement = document.getElementById('editStatus');
  statusElement.innerHTML = `
    <div class="status-message status-message--${type}">
      <div class="status-content" style="
        padding: 1rem;
        border-radius: 0.5rem;
        ${type === 'error' ? 'background-color: #fee2e2; color: #991b1b; border: 1px solid #fca5a5;' : 'background-color: #dcfce7; color: #166534; border: 1px solid #86efac;'}
      ">
        ${message}
      </div>
    </div>
  `;
}

// ===== SESSION TIMER FUNCTIONS =====

const SESSION_DURATION = 60 * 60 * 1000; // 60 minutes in milliseconds
let sessionTimer;

// Initialize session timer
function initSessionTimer() {
  const currentUser = JSON.parse(localStorage.getItem('currentUser') || 'null');

  if (!currentUser) return;

  // Set initial login time if not set
  if (!currentUser.lastActivityAt) {
    currentUser.lastActivityAt = Date.now();
    localStorage.setItem('currentUser', JSON.stringify(currentUser));
  }

  // Update timer display every second
  updateSessionTimer();
  sessionTimer = setInterval(updateSessionTimer, 1000);
}

// Update session timer display
function updateSessionTimer() {
  const currentUser = JSON.parse(localStorage.getItem('currentUser') || 'null');

  if (!currentUser || !currentUser.lastActivityAt) return;

  const now = Date.now();
  const elapsedTime = now - currentUser.lastActivityAt;
  const remainingTime = Math.max(0, SESSION_DURATION - elapsedTime);

  // Calculate hours, minutes, seconds
  const hours = Math.floor(remainingTime / (1000 * 60 * 60));
  const minutes = Math.floor((remainingTime % (1000 * 60 * 60)) / (1000 * 60));
  const seconds = Math.floor((remainingTime % (1000 * 60)) / 1000);

  // Update display
  const timeElement = document.getElementById('sessionTime');
  if (timeElement) {
    timeElement.textContent = `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
  }

  // Check if session expired
  if (remainingTime <= 0) {
    handleSessionExpiry();
  }
  // Warn at 5 minutes remaining
  else if (remainingTime <= 5 * 60 * 1000 && remainingTime > 4 * 60 * 1000) {
    if (timeElement && !timeElement.dataset.warningShown) {
      timeElement.dataset.warningShown = 'true';
      alert('Your session is expiring in 5 minutes. Please save your work.');
    }
  }
}

// Handle session expiry
function handleSessionExpiry() {
  clearInterval(sessionTimer);
  localStorage.removeItem('currentUser');
  alert('Your session has expired. Please log in again.');
  window.location.href = 'login.html';
}

// Track user activity to reset inactivity timer
function setupActivityTracking() {
}

// Update last activity timestamp
function updateActivityTimestamp() {
  const currentUser = JSON.parse(localStorage.getItem('currentUser') || 'null');

  if (!currentUser) return;

  currentUser.lastActivityAt = Date.now();
  localStorage.setItem('currentUser', JSON.stringify(currentUser));
}

