// ===== Counter animations =====
function animateCounter(el, target) {
    const isFloat = target % 1 !== 0;
    const duration = 1800;
    const startTime = performance.now() + 400;
    
    function update(now) {
        if (now < startTime) {
            requestAnimationFrame(update);
            return;
        }
        const progress = Math.min((now - startTime) / duration, 1);
        const eased = 1 - Math.pow(1 - progress, 3);
        const value = eased * target;
        el.textContent = isFloat ? value.toFixed(1) : Math.floor(value);
        if (progress < 1) requestAnimationFrame(update);
        else el.textContent = isFloat ? target.toFixed(1) : target;
    }
    requestAnimationFrame(update);
}

document.querySelectorAll('[data-counter]').forEach(el => {
    const target = parseFloat(el.dataset.counter);
    animateCounter(el, target);
});

// ===== Toast notifications =====
function showToast(message, type = 'success') {
    const container = document.getElementById('toast-container');
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    const icon = type === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation';
    toast.innerHTML = `<i class="fa-solid ${icon}"></i> ${message}`;
    container.appendChild(toast);
    setTimeout(() => toast.remove(), 3500);
}

// ===== Password toggle =====
const passwordToggle = document.getElementById('password-toggle');
const passwordInput = document.getElementById('password');

passwordToggle.addEventListener('click', () => {
    const isPassword = passwordInput.type === 'password';
    passwordInput.type = isPassword ? 'text' : 'password';
    passwordToggle.querySelector('i').className = isPassword ? 'fa-regular fa-eye-slash' : 'fa-regular fa-eye';
});

// ===== Form submission =====
const form = document.getElementById('login-form');
const signInBtn = document.getElementById('sign-in-btn');
const emailInput = document.getElementById('email');

form.addEventListener('submit', (e) => {
    e.preventDefault();
    signInBtn.classList.add('loading');
    
    setTimeout(() => {
        signInBtn.classList.remove('loading');
        showToast(`Welcome back! Redirecting to dashboard…`, 'success');
        
        // Save user email to localStorage
        const userEmail = emailInput.value;
        localStorage.setItem('torquepoint_user', JSON.stringify({ email: userEmail }));
        
        // Simulate redirect
        setTimeout(() => {
            window.location.href = '../services/services.php';
        }, 1200);
    }, 1600);
});

// ===== Google Sign-In =====
function handleCredentialResponse(response) {
    const token = response.credential;
    showToast('Google authentication successful...', 'success');
    
    // Send the token to our PHP backend to verify and get the email
    fetch('google-auth.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ token: token })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showToast('Login successful! Redirecting...', 'success');
            setTimeout(() => {
                window.location.href = '../services/services.php'; // Or dashboard.php
            }, 1000);
        } else {
            showToast('Authentication failed: ' + data.message, 'error');
        }
    })
    .catch(error => {
        showToast('Network error. Is XAMPP running?', 'error');
        console.error("Error:", error);
    });
}

// Initialize Google Identity Services when loaded
window.addEventListener('load', () => {
    const checkGoogle = setInterval(() => {
        if (window.google && window.google.accounts && window.google.accounts.id) {
            clearInterval(checkGoogle);
            
            try {
                google.accounts.id.initialize({
                    client_id: '355473925120-mo8r0a90q5d6rdcc6of507vlpja8bhvl.apps.googleusercontent.com',
                    callback: handleCredentialResponse,
                    auto_select: false,
                    cancel_on_tap_outside: true
                });
                
                const googleBtn = document.getElementById('google-btn');
                if (googleBtn) {
                    googleBtn.addEventListener('click', (e) => {
                        e.preventDefault();
                        // This triggers the Google One Tap / Popup
                        google.accounts.id.prompt((notification) => {
                            // If Google blocks the popup (common when testing on local files), this fallback triggers
                            if (notification.isNotDisplayed() || notification.isSkippedMoment()) {
                                console.log("Google popup was blocked. Reason: ", notification.getNotDisplayedReason());
                                showToast("Google popup blocked. Simulating Google login for testing...", 'success');
                                
                                // Fallback: Simulate the Google login so you can continue building your project
                                handleCredentialResponse({ credential: "dummy_google_token_for_testing" });
                            }
                        });
                    });
                }
            } catch (error) {
                console.error("Google Init Error:", error);
            }
        }
    }, 200);
});