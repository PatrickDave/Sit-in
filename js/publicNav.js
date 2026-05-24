document.addEventListener('DOMContentLoaded', function () {
  const navLinks = document.querySelectorAll('.nav-link');
  const animatedSelectors = [
    '.about-hero',
    '.community-hero',
    '.community-hero-card',
    '.community-feature-grid'
  ];

  function replayEntranceAnimations() {
    animatedSelectors.forEach((selector) => {
      document.querySelectorAll(selector).forEach((element) => {
        element.style.animation = 'none';
        void element.offsetWidth;
        element.style.animation = '';
      });
    });
  }

  navLinks.forEach((link) => {
    link.addEventListener('click', function () {
      navLinks.forEach((item) => item.classList.remove('is-clicked'));
      link.classList.add('is-clicked');
    });
  });

  replayEntranceAnimations();

  window.addEventListener('pageshow', function () {
    replayEntranceAnimations();
  });
});
