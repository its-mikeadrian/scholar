// Animation on scroll
document.addEventListener('DOMContentLoaded', function () {
    // Add fade-in animation to elements when they come into view
    const fadeElements = document.querySelectorAll('.fade-in');
    const fadeInOnScroll = function () {
        fadeElements.forEach(element => {
            const elementTop = element.getBoundingClientRect().top;
            const elementVisible = 150;
            if (elementTop < window.innerHeight - elementVisible) {
                element.classList.add('active');
            }
        });
    };
    fadeInOnScroll();
    window.addEventListener('scroll', fadeInOnScroll);

    // Custom smooth scroll function for 1 second
    function smoothScrollTo(targetY, duration = 1000) {
        const startY = window.scrollY;
        const changeY = targetY - startY;
        const startTime = performance.now();
        function animateScroll(currentTime) {
            const elapsed = currentTime - startTime;
            const progress = Math.min(elapsed / duration, 1);
            // easeInOutQuad
            const ease = progress < 0.5
                ? 2 * progress * progress
                : -1 + (4 - 2 * progress) * progress;
            window.scrollTo(0, startY + changeY * ease);
            if (elapsed < duration) {
                requestAnimationFrame(animateScroll);
            }
        }
        requestAnimationFrame(animateScroll);
    }

    // Smooth scroll for nav links (only when link points to an element on THIS page)
    document.querySelectorAll('.scroll-link').forEach(link => {
        link.addEventListener('click', function (e) {
            const href = this.getAttribute('href') || '';
            const hashIndex = href.indexOf('#');
            if (hashIndex === -1) {
                // No hash, allow normal navigation (e.g., external link or full URL)
                return;
            }
            const hash = href.slice(hashIndex + 1);
            if (!hash) return;
            const target = document.getElementById(hash);
            if (target) {
                e.preventDefault();
                // Offset for fixed header (80px)
                const headerOffset = 80;
                const targetY = target.getBoundingClientRect().top + window.scrollY - headerOffset;
                smoothScrollTo(targetY, 1000);
            }
            // If target not present, allow the browser to navigate (e.g., go to /students/home#anchor)
        });
    });

    // Smooth scroll for Home button
    const homeBtn = document.getElementById('homeBtn');
    if (homeBtn) {
        homeBtn.addEventListener('click', function (e) {
            const href = this.getAttribute('href') || '';
            if (href.startsWith('#')) {
                e.preventDefault();
                smoothScrollTo(0, 1000);
            }
            // otherwise allow normal navigation (e.g., when href points to students/home)
        });
    }

    // Apply button functionality
    const applyButtons = document.querySelectorAll('#heroApplyBtn, #ctaApplyBtn');
    applyButtons.forEach(button => {
        button.addEventListener('click', function () {
            const applicationModal = document.createElement('div');
            applicationModal.style.cssText = `
                        position: fixed;
                        top: 0;
                        left: 0;
                        width: 100%;
                        height: 100%;
                        background-color: rgba(0,0,0,0.8);
                        display: flex;
                        justify-content: center;
                        align-items: center;
                        z-index: 2000;
                    `;
            applicationModal.innerHTML = `
                        <div style="background-color: white; padding: 40px; border-radius: 15px; max-width: 500px; width: 90%; text-align: center; box-shadow: 0 10px 30px rgba(0,0,0,0.3); border-top: 8px solid #1e88e5;">
                            <div style="background-color: #1e88e5; color: white; width: 80px; height: 80px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; font-size: 2rem;">
                                <i class="fas fa-graduation-cap"></i>
                            </div>
                            <h2 style="color: #0d47a1; margin-bottom: 20px;">Application Form</h2>
                            <p style="margin-bottom: 30px; color: #555;">The Iskolar Nang Luis application portal will open in a new window. Please prepare all required documents before proceeding.</p>
                            <div style="display: flex; gap: 15px; justify-content: center; flex-wrap: wrap;">
                                <button id="proceedBtn" style="background-color: #ffeb3b; color: #212121; border: none; padding: 12px 30px; border-radius: 50px; font-weight: 600; cursor: pointer; transition: all 0.3s ease;">Proceed to Application</button>
                                <button id="closeModal" style="background-color: #e0e0e0; color: #212121; border: none; padding: 12px 30px; border-radius: 50px; font-weight: 600; cursor: pointer; transition: all 0.3s ease;">Close</button>
                            </div>
                        </div>
                    `;
            document.body.appendChild(applicationModal);
            document.getElementById('closeModal').addEventListener('click', function () {
                document.body.removeChild(applicationModal);
            });
            document.getElementById('proceedBtn').addEventListener('click', function () {
                alert('Wla kapang LInk boss, ere ay mag re redirect nalang sa application portal pag meron na. Thank you for your interest in Iskolar Nang Luis!');
                document.body.removeChild(applicationModal);
            });
            applicationModal.addEventListener('click', function (e) {
                if (e.target === applicationModal) {
                    document.body.removeChild(applicationModal);
                }
            });
        });
    });


    // Mobile nav toggle
    const navToggle = document.getElementById('navToggle');
    const mainNav = document.getElementById('mainNav');
    if (navToggle && mainNav) {
        navToggle.addEventListener('click', function () {
            const isOpen = mainNav.classList.toggle('open');
            navToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        });
        document.addEventListener('click', function (e) {
            if (!mainNav.contains(e.target) && !navToggle.contains(e.target)) {
                if (mainNav.classList.contains('open')) {
                    mainNav.classList.remove('open');
                    navToggle.setAttribute('aria-expanded', 'false');
                }
            }
        });
    }

    // Profile dropdown toggle
    const userProfileBtn = document.getElementById('userProfileBtn');
    const profileDropdown = document.getElementById('profileDropdown');
    const logoutBtn = document.getElementById('logoutBtn');

    if (userProfileBtn && profileDropdown) {
        userProfileBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            profileDropdown.classList.toggle('active');
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', function (e) {
            if (!userProfileBtn.contains(e.target) && !profileDropdown.contains(e.target)) {
                profileDropdown.classList.remove('active');
            }
        });

        // Close dropdown when clicking a menu item
        profileDropdown.querySelectorAll('a, button').forEach(item => {
            item.addEventListener('click', function () {
                profileDropdown.classList.remove('active');
            });
        });
    }

    // Logout functionality
    if (logoutBtn) {
        logoutBtn.addEventListener('click', function () {
            if (confirm('Are you sure you want to logout?')) {
                alert('Logged out successfully (demo).');
                // In a real application, you would redirect to login page
                // window.location.href = '/login';
            }
        });
    }

    // History modal functionality (opens a modal populated with application history)
    const historyLink = document.getElementById('History');
    if (historyLink) {
        historyLink.addEventListener('click', function (e) {
            e.preventDefault();
            let modal = document.getElementById('historyModal');
            if (!modal) {
                modal = document.createElement('div');
                modal.id = 'historyModal';
                modal.className = 'modal';
                modal.innerHTML = `
                    <div class="modal-content">
                        <div class="modal-header" style="display:flex;justify-content:space-between;align-items:center;">
                            <h2>Application History</h2>
                            <button class="close-btn" id="historyCloseBtn">&times;</button>
                        </div>
                        <div id="historyList"></div>
                    </div>
                `;
                document.body.appendChild(modal);

                // close when clicking outside content
                modal.addEventListener('click', function (ev) {
                    if (ev.target === modal) closeHistoryModal();
                });

                // close button handler
                modal.querySelector('#historyCloseBtn').addEventListener('click', closeHistoryModal);
            }
            populateHistory();
            modal.classList.add('active');
        });
    }

    function closeHistoryModal() {
        const modal = document.getElementById('historyModal');
        if (modal) modal.classList.remove('active');
    }

    function populateHistory() {
        const historyList = document.getElementById('historyList');
        if (!historyList) return;

        // Sample data; replace with an AJAX call to the server if you have real data
        const historyData = window._sampleHistoryData || [
            { date: 'June 3, 2025', name: 'Dela Cruz, Juan Cruz', status: 'Completed', level: '1st Year - 1st Semester' },
            { date: 'October 15, 2025', name: 'Dela Cruz, Juan Cruz', status: 'Completed', level: '1st Year - 2nd Semester' },
            { date: 'June 20, 2026', name: 'Dela Cruz, Juan Cruz', status: 'Completed', level: '2nd Year - 1st Semester' }
        ];

        if (historyData.length === 0) {
            historyList.innerHTML = '<div class="no-history" style="text-align:center;color:#999;padding:40px 20px;">No submitted forms yet.</div>';
        } else {
            historyList.innerHTML = historyData.map(item => `
                <div class="history-item" style="background:#f9f9f9;padding:15px;border-radius:8px;margin-bottom:12px;border-left:4px solid #667eea;display:flex;justify-content:space-between;align-items:flex-start;">
                    <div>
                        <div class="history-item-date" style="color:#667eea;font-weight:600;font-size:.9rem;margin-bottom:8px;"><i class="fas fa-calendar-alt"></i> ${item.date}</div>
                        <div class="history-item-name" style="font-weight:600;color:#333;margin-bottom:6px;">${item.name}</div>
                        <div class="history-item-details" style="color:#666;font-size:.9rem;margin-top:8px;line-height:1.5;"><strong>Academic Level:</strong> ${item.level}</div>
                    </div>
                    <div class="history-item-status" style="background:#e8f5e9;color:#2e7d32;padding:4px 8px;border-radius:4px;font-size:.85rem;white-space:nowrap;margin-left:12px;"><i class="fas fa-check-circle"></i> ${item.status}</div>
                </div>
            `).join('');
        }
    }

});

// Toast Notification System
function showToast(message, type = 'success') {
    const container = document.getElementById('toast-container') || createToastContainer();
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;

    let icon = '';
    if (type === 'success') icon = '<i class="fas fa-check-circle"></i>';
    else if (type === 'error') icon = '<i class="fas fa-exclamation-circle"></i>';
    else if (type === 'warning') icon = '<i class="fas fa-exclamation-triangle"></i>';

    toast.innerHTML = `
        <div class="toast-icon">${icon}</div>
        <div class="toast-message">${message}</div>
        <div class="toast-close">&times;</div>
    `;

    container.appendChild(toast);

    // Remove toast after 5 seconds
    const timeout = setTimeout(() => {
        toast.style.animation = 'toastSlideOut 0.3s forwards';
        setTimeout(() => toast.remove(), 300);
    }, 5000);

    // Close button click
    toast.querySelector('.toast-close').addEventListener('click', () => {
        clearTimeout(timeout);
        toast.style.animation = 'toastSlideOut 0.3s forwards';
        setTimeout(() => toast.remove(), 300);
    });
}

function createToastContainer() {
    const container = document.createElement('div');
    container.id = 'toast-container';
    container.className = 'toast-container';
    document.body.appendChild(container);
    return container;
}

// Profile Modal Logic
document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('profileModal');
    const openBtn = document.getElementById('openProfileModal');
    const closeBtn = document.querySelector('.close-modal');
    const form = modal ? modal.querySelector('form') : null;
    const fileInput = modal ? modal.querySelector('input[type="file"]') : null;
    const previewImg = modal ? modal.querySelector('.profile-preview-img') : null;
    const placeholder = modal ? modal.querySelector('.profile-placeholder') : null;

    if (modal && openBtn) {
        openBtn.addEventListener('click', function (e) {
            e.preventDefault();
            modal.style.display = 'block';
            document.body.style.overflow = 'hidden';
        });

        if (closeBtn) {
            closeBtn.addEventListener('click', function () {
                modal.style.display = 'none';
                document.body.style.overflow = '';
            });
        }

        window.addEventListener('click', function (e) {
            if (e.target == modal) {
                modal.style.display = 'none';
                document.body.style.overflow = '';
            }
        });

        // Image Preview
        if (fileInput) {
            fileInput.addEventListener('change', function (e) {
                const file = this.files[0];
                if (file) {
                    // Validate file type
                    const validTypes = ['image/jpeg', 'image/png'];
                    if (!validTypes.includes(file.type)) {
                        showToast('Invalid file type. Only JPG and PNG allowed.', 'error');
                        this.value = ''; // Clear input
                        return;
                    }
                    // Validate file size (2MB)
                    if (file.size > 2 * 1024 * 1024) {
                        showToast('File too large. Max size is 2MB.', 'error');
                        this.value = ''; // Clear input
                        return;
                    }

                    const reader = new FileReader();
                    reader.onload = function (e) {
                        if (previewImg) {
                            previewImg.src = e.target.result;
                        } else if (placeholder) {
                            // Replace placeholder with image
                            const img = document.createElement('img');
                            img.src = e.target.result;
                            img.className = 'profile-preview-img';
                            img.alt = 'Profile Preview';
                            placeholder.parentNode.replaceChild(img, placeholder);

                            // Update variable reference
                            // previewImg = img; // cannot reassign const, but element is in DOM
                        }
                    }
                    reader.readAsDataURL(file);
                }
            });
        }

        // Form Validation
        if (form) {
            form.addEventListener('submit', function (e) {
                let isValid = true;
                const requiredFields = form.querySelectorAll('[required]');

                requiredFields.forEach(field => {
                    if (!field.value.trim()) {
                        isValid = false;
                        field.style.borderColor = '#f44336';
                    } else {
                        field.style.borderColor = '#e5e7eb';
                    }
                });

                if (!isValid) {
                    e.preventDefault();
                    showToast('Please fill in all required fields.', 'error');
                }
            });
        }
    }
});