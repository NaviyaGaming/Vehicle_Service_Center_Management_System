// ===== Auth State Check =====
const loggedInUser = localStorage.getItem('torquepoint_user');
const loginLink = document.getElementById('loginLink');

if (loggedInUser) {
    const userData = JSON.parse(loggedInUser);
    
    // 1. Change all "Get Started" links to go to services.html instead of login.html
    document.querySelectorAll('a[href="login.html"]').forEach(link => {
        if (link.id !== 'loginLink') {
            link.href = '../services/services.html';
        }
    });

    // 2. Change "Login" text to show the user's email
    loginLink.innerHTML = `<i class="fa-solid fa-user"></i> ${userData.email}`;
    loginLink.href = "#"; // Prevent it from going to login page if clicked
    
    // 3. Create a Logout button next to the email
    const logoutLink = document.createElement('a');
    logoutLink.href = "#";
    logoutLink.className = "nav-link";
    logoutLink.innerHTML = '<i class="fa-solid fa-arrow-right-from-bracket"></i> Logout';
    
    // When logout is clicked, clear the storage and reload
    logoutLink.addEventListener('click', (e) => {
        e.preventDefault();
        localStorage.removeItem('torquepoint_user');
        window.location.href = 'home.html';
    });

    // Insert the logout link right after the login link in the navbar
    loginLink.parentNode.insertBefore(logoutLink, loginLink.nextSibling);
}

// ===== Year =====
document.getElementById('year').textContent = new Date().getFullYear();

// ===== Navbar background on scroll =====
const navbar = document.getElementById('navbar');
window.addEventListener('scroll', () => {
  navbar.classList.toggle('scrolled', window.scrollY > 40);
});

// ===== Mobile nav toggle =====
const navToggle = document.getElementById('navToggle');
const navLinks = document.getElementById('navLinks');
navToggle.addEventListener('click', () => {
  navLinks.classList.toggle('open');
});
navLinks.querySelectorAll('a').forEach(a => {
  a.addEventListener('click', () => navLinks.classList.remove('open'));
});

// ===== Smooth scroll for Home / About / Contact =====
document.querySelectorAll('.nav-link[data-target]').forEach(link => {
  link.addEventListener('click', (e) => {
    const targetId = link.getAttribute('data-target');
    const targetEl = document.getElementById(targetId);
    if (targetEl) {
      e.preventDefault();
      targetEl.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
  });
});

// ===== Scroll reveal animation =====
const revealEls = document.querySelectorAll('.reveal');
const observer = new IntersectionObserver((entries) => {
  entries.forEach(entry => {
    if (entry.isIntersecting) {
      entry.target.classList.add('in');
      observer.unobserve(entry.target);
    }
  });
}, { threshold: 0.18 });
revealEls.forEach(el => observer.observe(el));