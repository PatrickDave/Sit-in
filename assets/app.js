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
});

// Handle Registration Form Submission
function handleRegistration(event) {
  event.preventDefault();

  // Get form data
  const studentId = document.getElementById('studentId')?.value.trim();
  const lastName = document.getElementById('lastName')?.value.trim();
  const firstName = document.getElementById('firstName')?.value.trim();
  const middleName = document.getElementById('middleName')?.value.trim();
  const email = document.getElementById('email')?.value.trim();
  const course = document.getElementById('course')?.value.trim();
  const address = document.getElementById('addreess')?.value.trim();
  const password = document.getElementById('password')?.value;
  const confirmPassword = document.getElementById('confirmPassword')?.value;

  // Validation
  const errors = [];

  if (!studentId) errors.push('ID Number is required');
  if (!lastName) errors.push('Last Name is required');
  if (!firstName) errors.push('First Name is required');
  if (!middleName) errors.push('Middle Name is required');
  if (!email) errors.push('Email is required');
  if (!email.match(/^[^\s@]+@[^\s@]+\.[^\s@]+$/)) errors.push('Valid email is required');
  if (!course) errors.push('Course is required');
  if (!address) errors.push('Address is required');
  if (!password) errors.push('Password is required');
  if (password.length < 6) errors.push('Password must be at least 6 characters');
  if (password !== confirmPassword) errors.push('Passwords do not match');

  // Check if student ID already exists
  const users = JSON.parse(localStorage.getItem('sitInUsers') || '[]');
  if (users.some(user => user.studentId === studentId)) {
    errors.push('Student ID already registered');
  }

  if (errors.length > 0) {
    showStatus('register', errors.join('<br>'), 'error');
    return;
  }

  // Create user object
  const newUser = {
    studentId,
    lastName,
    firstName,
    middleName,
    email,
    course,
    address,
    password, // In production, this should be hashed
    registeredAt: new Date().toISOString()
  };

  // Store user
  users.push(newUser);
  localStorage.setItem('sitInUsers', JSON.stringify(users));

  // Show success message and redirect
  showStatus('register', 'Registration successful! Redirecting to login...', 'success');
  setTimeout(() => {
    window.location.href = 'login.html';
  }, 2000);
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

  // Get registered user
  const users = JSON.parse(localStorage.getItem('sitInUsers') || '[]');
  const user = users.find(u => u.email === email && u.password === password);

  if (!user) {
    showStatus('login', 'Invalid email or password', 'error');
    return;
  }

  // Create session
  localStorage.setItem('currentUser', JSON.stringify({
    studentId: user.studentId,
    firstName: user.firstName,
    lastName: user.lastName,
    email: user.email,
    loginAt: new Date().toISOString()
  }));

  // Show success and redirect
  showStatus('login', 'Login successful! Redirecting...', 'success');
  setTimeout(() => {
    window.location.href = 'index.html';
  }, 1500);
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

// Logout function (you can call this from a logout button)
function logout() {
  localStorage.removeItem('currentUser');
  window.location.href = 'index.html';
}
