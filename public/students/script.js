const container = document.getElementById('container');
const registerBtn = document.getElementById('register');
const loginBtn = document.getElementById('login');
const registerInline = document.getElementById('register-inline');
const loginInline = document.getElementById('login-inline');
const resendBtn = document.getElementById('resend-otp');
const forgotLink = document.getElementById('forgot-link');
const overlay = document.getElementById('forgot-overlay');
const sendReset = document.getElementById('send-reset');
const cancelReset = document.getElementById('cancel-reset');
const closeReset = document.getElementById('close-reset');
const forgotEmail = document.getElementById('forgot-email');

registerBtn.addEventListener('click', () => {
    container.classList.add("active");
});

loginBtn.addEventListener('click', () => {
    container.classList.remove("active");
});

if (registerInline) {
    registerInline.addEventListener('click', (e) => {
        e.preventDefault();
        container.classList.add('active');
    });
}

if (loginInline) {
    loginInline.addEventListener('click', (e) => {
        e.preventDefault();
        container.classList.remove('active');
    });
}

if (resendBtn) {
    const cooldown = 30;
    const startCooldown = () => {
        let remaining = cooldown;
        resendBtn.disabled = true;
        resendBtn.classList.add('disabled');
        resendBtn.textContent = `Resend in ${remaining}s`;
        const timer = setInterval(() => {
            remaining -= 1;
            if (remaining <= 0) {
                clearInterval(timer);
                resendBtn.disabled = false;
                resendBtn.classList.remove('disabled');
                resendBtn.textContent = 'Resend';
            } else {
                resendBtn.textContent = `Resend in ${remaining}s`;
            }
        }, 1000);
    };
    resendBtn.addEventListener('click', () => {
        startCooldown();
    });
}

const openForgot = () => {
    if (!overlay) return;
    overlay.classList.add('open');
    overlay.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
    if (forgotEmail) {
        forgotEmail.value = '';
        setTimeout(() => forgotEmail.focus(), 50);
    }
};

const closeForgot = () => {
    if (!overlay) return;
    overlay.classList.remove('open');
    overlay.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
};

const isValidEmail = (value) => {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);
};

if (forgotLink) {
    forgotLink.addEventListener('click', (e) => {
        e.preventDefault();
        openForgot();
    });
}

if (cancelReset) {
    cancelReset.addEventListener('click', (e) => {
        e.preventDefault();
        closeForgot();
    });
}

if (closeReset) {
    closeReset.addEventListener('click', (e) => {
        e.preventDefault();
        closeForgot();
    });
}

if (overlay) {
    overlay.addEventListener('click', (e) => {
        if (e.target === overlay) {
            closeForgot();
        }
    });
}

if (sendReset) {
    sendReset.addEventListener('click', (e) => {
        e.preventDefault();
        if (!forgotEmail) return;
        const value = forgotEmail.value.trim();
        if (!isValidEmail(value)) {
            if (window.showToast) {
                window.showToast('error', 'Enter a valid email');
            }
            return;
        }
        sendReset.disabled = true;
        sendReset.textContent = 'Sending...';
        setTimeout(() => {
            sendReset.disabled = false;
            sendReset.textContent = 'Send Reset Link';
            closeForgot();
        }, 1200);
    });
}

document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        closeForgot();
    }
});

// Toast component
(function() {
    const ensureContainer = () => {
        let c = document.getElementById('toast-container');
        if (!c) {
            c = document.createElement('div');
            c.id = 'toast-container';
            c.className = 'toast-container';
            document.body.appendChild(c);
        }
        return c;
    };

    const icons = {
        success: '<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 12l2 2 4-4"/><circle cx="12" cy="12" r="9"/></svg>',
        error: '<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 8v6"/><circle cx="12" cy="16" r="1"/></svg>',
        warning: '<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 9v4"/><path d="M12 17h.01"/><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/></svg>'
    };

    window.showToast = function(type, message, duration = 5000) {
        const c = ensureContainer();
        const t = document.createElement('div');
        t.className = 'toast ' + (type || 'info');
        t.setAttribute('role', 'alert');
        t.innerHTML = `${icons[type] || ''}<div class="message"></div>`;
        t.querySelector('.message').textContent = String(message || '');
        c.appendChild(t);
        // Force reflow then show
        void t.offsetHeight;
        t.classList.add('show');
        const remove = () => {
            t.classList.remove('show');
            setTimeout(() => {
                t.remove();
            }, 250);
        };
        setTimeout(remove, duration);
    };

    if (window.__messages) {
        const m = window.__messages;
        if (m.error) window.showToast('error', m.error);
        if (m.success) window.showToast('success', m.success);
        if (m.warnings && Array.isArray(m.warnings)) {
            m.warnings.forEach(msg => window.showToast('warning', msg));
        }
        if (m.errors && typeof m.errors === 'object') {
            Object.values(m.errors).forEach(msg => window.showToast('error', msg));
        }
    }
})();
