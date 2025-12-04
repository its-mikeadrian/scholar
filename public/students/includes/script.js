// Animation on scroll
        document.addEventListener('DOMContentLoaded', function() {
            // Add fade-in animation to elements when they come into view
            const fadeElements = document.querySelectorAll('.fade-in');
            const fadeInOnScroll = function() {
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

            // Smooth scroll for nav links
            document.querySelectorAll('.scroll-link').forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    const targetId = this.getAttribute('href').replace('#', '');
                    const target = document.getElementById(targetId);
                    if (target) {
                        // Offset for fixed header (80px)
                        const headerOffset = 80;
                        const targetY = target.getBoundingClientRect().top + window.scrollY - headerOffset;
                        smoothScrollTo(targetY, 1000);
                    }
                });
            });

            // Smooth scroll for Home button
            const homeBtn = document.getElementById('homeBtn');
            if (homeBtn) {
                homeBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    smoothScrollTo(0, 1000);
                });
            }

            // Apply button functionality
            const applyButtons = document.querySelectorAll('#heroApplyBtn, #ctaApplyBtn');
            applyButtons.forEach(button => {
                button.addEventListener('click', function() {
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
                    document.getElementById('closeModal').addEventListener('click', function() {
                        document.body.removeChild(applicationModal);
                    });
                    document.getElementById('proceedBtn').addEventListener('click', function() {
                        alert('Wla kapang LInk boss, ere ay mag re redirect nalang sa application portal pag meron na. Thank you for your interest in Iskolar Nang Luis!');
                        document.body.removeChild(applicationModal);
                    });
                    applicationModal.addEventListener('click', function(e) {
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
                navToggle.addEventListener('click', function() {
                    const isOpen = mainNav.classList.toggle('open');
                    navToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
                });
                document.addEventListener('click', function(e) {
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
                userProfileBtn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    profileDropdown.classList.toggle('active');
                });

                // Close dropdown when clicking outside
                document.addEventListener('click', function(e) {
                    if (!userProfileBtn.contains(e.target) && !profileDropdown.contains(e.target)) {
                        profileDropdown.classList.remove('active');
                    }
                });

                // Close dropdown when clicking a menu item
                profileDropdown.querySelectorAll('a, button').forEach(item => {
                    item.addEventListener('click', function() {
                        profileDropdown.classList.remove('active');
                    });
                });
            }

            // Logout functionality
            if (logoutBtn) {
                logoutBtn.addEventListener('click', function() {
                    if (confirm('Are you sure you want to logout?')) {
                        alert('Logged out successfully (demo).');
                        // In a real application, you would redirect to login page
                        // window.location.href = '/login';
                    }
                });
            }

            
        });