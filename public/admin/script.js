const form = document.getElementById('admin-login-form');
const idInput = document.getElementById('admin-id');
const passInput = document.getElementById('admin-password');
const idError = document.getElementById('id-error');
const passError = document.getElementById('pass-error');
const loginBtn = document.getElementById('login-btn');
const toggleBtn = document.getElementById('toggle-pass');
const forgotLink = document.getElementById('forgot-link');
const overlay = document.getElementById('admin-forgot-overlay');
const closeReset = document.getElementById('close-reset');
const cancelReset = document.getElementById('cancel-reset');
const forgotForm = document.getElementById('admin-forgot-form');
const sendReset = document.getElementById('send-reset');
const forgotEmail = document.getElementById('forgot-email');

const isEmail = (value) => /@/.test(value);
const isValidEmail = (value) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);
const isValidUsername = (value) => /^[A-Za-z0-9_.-]{3,}$/.test(value);

const isIdentifierValid = () => {
  if (!idInput) return true;
  const v = idInput.value.trim();
  if (!v) return false;
  if (isEmail(v) && !isValidEmail(v)) return false;
  if (!isEmail(v) && !isValidUsername(v)) return false;
  return true;
};
const validateIdentifier = () => {
  if (!idInput) return true;
  const v = idInput.value.trim();
  if (!v) { window.showToast && window.showToast('error', 'Please enter your username or email.'); return false; }
  if (isEmail(v) && !isValidEmail(v)) { window.showToast && window.showToast('error', 'Enter a valid email address.'); return false; }
  if (!isEmail(v) && !isValidUsername(v)) { window.showToast && window.showToast('error', 'Username must be at least 3 characters.'); return false; }
  return true;
};

const isPasswordValid = () => {
  if (!passInput) return true;
  const v = passInput.value;
  if (!v) return false;
  if (v.length < 8) return false;
  return true;
};
const validatePassword = () => {
  if (!passInput) return true;
  const v = passInput.value;
  if (!v) { window.showToast && window.showToast('error', 'Please enter your password.'); return false; }
  if (v.length < 8) { window.showToast && window.showToast('error', 'Password must be at least 8 characters.'); return false; }
  return true;
};

const updateButtonState = () => {
  const ok = isIdentifierValid() && isPasswordValid();
  if (loginBtn) loginBtn.disabled = !ok;
};

if (idInput) { idInput.addEventListener('input', updateButtonState); }
if (passInput) { passInput.addEventListener('input', updateButtonState); }

if (toggleBtn) {
  toggleBtn.addEventListener('click', () => {
    const visible = passInput.type === 'text';
    passInput.type = visible ? 'password' : 'text';
    toggleBtn.setAttribute('aria-pressed', (!visible).toString());
    const img = toggleBtn.querySelector('img');
    const base = (img && img.src && img.src.indexOf('/admin/') !== -1) ? 'admin/assets/' : 'assets/';
    if (img) { img.src = visible ? base + 'eye-off.svg' : base + 'eye-on.svg'; }
    toggleBtn.setAttribute('aria-label', visible ? 'Show password' : 'Hide password');
    passInput.focus();
  });
}

if (form) {
  form.addEventListener('submit', (e) => {
    const valid = validateIdentifier() && validatePassword();
    if (!valid) {
      e.preventDefault();
      return;
    }
  });
}

const openForgot = () => {
  overlay.classList.add('open');
  overlay.setAttribute('aria-hidden', 'false');
  document.body.style.overflow = 'hidden';
  if (forgotEmail) { forgotEmail.value = ''; setTimeout(() => forgotEmail.focus(), 50); }
};

const closeForgot = () => {
  overlay.classList.remove('open');
  overlay.setAttribute('aria-hidden', 'true');
  document.body.style.overflow = '';
};

if (forgotLink) {
  forgotLink.addEventListener('click', (e) => { e.preventDefault(); openForgot(); });
}
if (cancelReset) {
  cancelReset.addEventListener('click', (e) => { e.preventDefault(); closeForgot(); });
}
if (closeReset) {
  closeReset.addEventListener('click', (e) => { e.preventDefault(); closeForgot(); });
}
if (overlay) {
  overlay.addEventListener('click', (e) => { if (e.target === overlay) closeForgot(); });
}
if (forgotForm) {
  forgotForm.addEventListener('submit', (e) => {
    const value = (forgotEmail?.value || '').trim();
    if (!isValidEmail(value)) {
      e.preventDefault();
      if (window.showToast) window.showToast('error', 'Enter a valid email');
      return;
    }
    if (sendReset) {
      sendReset.disabled = true;
      sendReset.textContent = 'Sending...';
    }
  });
}

document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeForgot(); });
if (idInput && passInput) { updateButtonState(); }

// Toast component
(function () {
  const ensureContainer = () => {
    let c = document.getElementById('toast-container');
    if (!c) { c = document.createElement('div'); c.id = 'toast-container'; c.className = 'toast-container'; document.body.appendChild(c); }
    return c;
  };
  const icons = {
    success: '<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 12l2 2 4-4"/><circle cx="12" cy="12" r="9"/></svg>',
    error: '<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 8v6"/><circle cx="12" cy="16" r="1"/></svg>',
    warning: '<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 9v4"/><path d="M12 17h.01"/><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/></svg>'
  };
  window.showToast = function (type, message, duration = 5000) {
    const c = ensureContainer();
    const t = document.createElement('div');
    t.className = 'toast ' + (type || 'info');
    t.setAttribute('role', 'alert');
    t.innerHTML = `${icons[type] || ''}<div class="message"></div>`;
    t.querySelector('.message').textContent = String(message || '');
    c.appendChild(t);
    void t.offsetHeight; t.classList.add('show');
    const remove = () => { t.classList.remove('show'); setTimeout(() => t.remove(), 250); };
    setTimeout(remove, duration);
  };
  if (window.__messages) {
    const m = window.__messages;
    if (m.error) window.showToast('error', m.error);
    if (m.success) window.showToast('success', m.success);
    if (m.errors && typeof m.errors === 'object') { Object.values(m.errors).forEach(msg => window.showToast('error', msg)); }
  }
})();
