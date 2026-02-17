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
});

