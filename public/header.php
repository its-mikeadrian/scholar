<?php
require_once __DIR__ . '/../src/security.php';
require_once __DIR__ . '/../src/db.php';
secure_session_start();
$uid = (int)($_SESSION['auth_user_id'] ?? 0);
$username = 'User';
$role = $_SESSION['auth_role'] ?? 'student';
if ($uid > 0) {
    $stmt = $conn->prepare('SELECT username, email, role FROM users WHERE id = ? LIMIT 1');
    if ($stmt) {
        $stmt->bind_param('i', $uid);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res ? $res->fetch_assoc() : null;
        $stmt->close();
        if ($row) {
            $username = (string)$row['username'];
            $role = (string)$row['role'];
            $email = (string)$row['email'];
            $_SESSION['auth_role'] = $role;
        }
    }
}
// Page-level messages for toasts
$page_error = $_SESSION['error'] ?? null;
$page_success = $_SESSION['success'] ?? null;
$page_info = $_SESSION['info'] ?? null;
unset($_SESSION['error'], $_SESSION['success'], $_SESSION['info']);
?>
<header class="fixed top-0 left-0 right-0 z-40 bg-white border-b">
    <div class="flex h-14 items-center justify-between px-4">
        <button id="sidebarToggleBtn" class="inline-flex items-center justify-center rounded-md p-2 text-[#293D82] hover:bg-[#e3f2fd]" aria-label="Toggle sidebar" aria-expanded="false" onclick="window.__toggleSidebar && window.__toggleSidebar('toggle')">
            <svg id="iconHamburger" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M4 7h16" />
                <path d="M4 12h16" />
                <path d="M4 17h16" />
            </svg>
        </button>
        <div class="flex items-center gap-4">
            <span class="inline-flex items-center gap-2 text-sm text-[#293D82]"><span class="h-2 w-2 rounded-full bg-green-500"></span>System Online</span>
            <div class="relative">
                <button id="profileBtn" class="inline-grid h-8 w-8 place-items-center rounded-full bg-[#293D82] text-white" aria-haspopup="true" aria-expanded="false" onclick="toggleProfileMenu()">
                    <?= strtoupper(substr($username, 0, 1)); ?>
                </button>
                <div id="profileMenu" class="absolute right-0 mt-2 hidden w-56 rounded-md border bg-white shadow-lg" role="menu" aria-label="Profile menu">
                    <div class="px-3 py-2 text-xs text-gray-500">Role: <?= htmlspecialchars($role, ENT_QUOTES, 'UTF-8'); ?></div>
                    <a href="#" class="block px-3 py-2 hover:bg-gray-100" onclick="openAccountSettingsModal(); toggleProfileMenu(); return false;">Personal settings</a>

                    <form method="POST" action="<?= route_url('logout') ?>" class="px-3 py-2">
                        <?= csrf_input(); ?>
                        <button type="submit" class="w-full text-left rounded-md px-2 py-1 hover:bg-gray-100">Logout</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</header>
<?php require_once __DIR__ . '/user_settings_modals.php'; ?>
<style>
    @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap');

    :root {
        --deep: #212121;
        --olive: #1e88e5;
        --clay: #f0f7ff
    }

    body {
        font-family: 'Poppins', ui-sans-serif, system-ui, -apple-system, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, 'Noto Sans', 'Apple Color Emoji', 'Segoe UI Emoji'
    }

    body {
        color: var(--deep);
        background-color: var(--clay);
    }

    .toast-container {
        position: fixed;
        top: 20%;
        left: 50%;
        transform: translateX(-50%);
        display: flex;
        flex-direction: column;
        gap: 8px;
        z-index: 3000;
        pointer-events: none;
    }

    .toast {
        min-width: 280px;
        max-width: 520px;
        display: flex;
        align-items: center;
        gap: 10px;
        background: #fff;
        color: var(--deep);
        border-radius: 10px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, .18);
        padding: 12px 14px;
        border-left: 4px solid var(--olive);
        opacity: 0;
        transform: translateY(-8px);
        transition: opacity .25s ease, transform .25s ease;
    }

    .toast.show {
        opacity: 1;
        transform: translateY(0);
    }

    .toast .icon {
        width: 18px;
        height: 18px;
    }

    .toast .message {
        font-size: 13px;
        line-height: 1.4;
    }

    .toast.success {
        border-left-color: #2e7d32;
    }

    .toast.error {
        border-left-color: #c62828;
    }

    .toast.warning {
        border-left-color: #ed6c02;
    }
</style>
<script>
    (function() {
        window.AppData = window.AppData || {};
        window.AppData.applications = window.AppData.applications || [{
                name: 'Dela Cruz, Juan',
                yearLevel: '1st Year',
                status: 'For Review'
            },
            {
                name: 'Santos, Maria',
                yearLevel: '2nd Year',
                status: 'Accepted'
            },
            {
                name: 'Reyes, Pedro',
                yearLevel: '3rd Year',
                status: 'For Review'
            },
            {
                name: 'Garcia, Ana',
                yearLevel: '4th Year',
                status: 'Accepted'
            },
            {
                name: 'Lim, Carlo',
                yearLevel: '1st Year',
                status: 'For Review'
            },
            {
                name: 'Tan, Lea',
                yearLevel: '2nd Year',
                status: 'For Review'
            },
            {
                name: 'Torres, Miguel',
                yearLevel: '3rd Year',
                status: 'Accepted'
            },
            {
                name: 'Domingo, Iris',
                yearLevel: '4th Year',
                status: 'For Review'
            },
            {
                name: 'Navarro, Joel',
                yearLevel: '2nd Year',
                status: 'Accepted'
            },
            {
                name: 'Cruz, Liza',
                yearLevel: '3rd Year',
                status: 'For Review'
            },
            {
                name: 'Ramos, Noel',
                yearLevel: '1st Year',
                status: 'Accepted'
            }
        ];
        window.AppData.checklist = window.AppData.checklist || [{
                name: 'Dela Cruz, Juan',
                yearLevel: '1st Year',
                paid: false
            },
            {
                name: 'Santos, Maria',
                yearLevel: '2nd Year',
                paid: true
            },
            {
                name: 'Reyes, Pedro',
                yearLevel: '3rd Year',
                paid: false
            },
            {
                name: 'Garcia, Ana',
                yearLevel: '4th Year',
                paid: true
            },
            {
                name: 'Lim, Carlo',
                yearLevel: '1st Year',
                paid: false
            },
            {
                name: 'Tan, Lea',
                yearLevel: '2nd Year',
                paid: false
            },
            {
                name: 'Torres, Miguel',
                yearLevel: '3rd Year',
                paid: true
            },
            {
                name: 'Domingo, Iris',
                yearLevel: '4th Year',
                paid: false
            },
            {
                name: 'Navarro, Joel',
                yearLevel: '2nd Year',
                paid: true
            },
            {
                name: 'Cruz, Liza',
                yearLevel: '3rd Year',
                paid: false
            },
            {
                name: 'Ramos, Noel',
                yearLevel: '1st Year',
                paid: true
            }
        ];
        var c = document.getElementById('toast-container');
        if (!c) {
            c = document.createElement('div');
            c.id = 'toast-container';
            c.className = 'toast-container';
            document.body.appendChild(c);
        }
        window.showToast = window.showToast || function(type, message, duration) {
            var t = document.createElement('div');
            t.className = 'toast ' + (type || 'info');
            t.setAttribute('role', 'alert');
            var icon = '';
            if (type === 'success') icon = '<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 12l2 2 4-4"/><circle cx="12" cy="12" r="9"/></svg>';
            else if (type === 'error') icon = '<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 8v6"/><circle cx="12" cy="16" r="1"/></svg>';
            else if (type === 'warning') icon = '<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 9v4"/><path d="M12 17h.01"/><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/></svg>';
            t.innerHTML = icon + '<div class="message"></div>';
            t.querySelector('.message').textContent = String(message || '');
            c.appendChild(t);
            void t.offsetHeight;
            t.classList.add('show');
            setTimeout(function() {
                t.classList.remove('show');
                setTimeout(function() {
                    t.remove();
                }, 250);
            }, duration || 5000);
        };
    })();

    function toggleProfileMenu() {
        var m = document.getElementById('profileMenu');
        var b = document.getElementById('profileBtn');
        if (!m || !b) return;
        var hidden = m.classList.contains('hidden');
        if (hidden) m.classList.remove('hidden');
        else m.classList.add('hidden');
        b.setAttribute('aria-expanded', hidden ? 'true' : 'false');
    }
    document.addEventListener('click', function(e) {
        var m = document.getElementById('profileMenu');
        var b = document.getElementById('profileBtn');
        if (!m || !b) return;
        if (!m.contains(e.target) && !b.contains(e.target)) {
            m.classList.add('hidden');
            b.setAttribute('aria-expanded', 'false');
        }
    });
    window.__toggleSidebar = function(open) {
        var s = document.getElementById('appSidebar');
        var d = document.getElementById('sidebarBackdrop');
        var b = document.getElementById('sidebarToggleBtn');
        var ms = document.getElementById('miniSidebar');
        var m = document.getElementById('appMain');
        if (!s || !d) return;
        var isLg = window.matchMedia('(min-width: 1024px)').matches;
        var isOpen = isLg ? !s.classList.contains('lg:-translate-x-full') : !s.classList.contains('-translate-x-full');
        var shouldOpen = (open === 'toggle') ? !isOpen : !!open;
        if (shouldOpen) {
            s.classList.remove('-translate-x-full');
            s.classList.remove('lg:-translate-x-full');
            s.classList.add('lg:translate-x-0');
            s.classList.add('translate-x-0');
            if (isLg) {
                d.classList.add('hidden');
                if (m) {
                    m.classList.remove('lg:pl-16');
                    m.classList.add('lg:pl-72');
                }
            } else {
                d.classList.remove('hidden');
                d.classList.remove('opacity-0');
                d.classList.add('opacity-100');
            }
            if (b) {
                b.setAttribute('aria-expanded', 'true');
                b.classList.add('invisible');
                b.classList.add('pointer-events-none');
            }
            if (ms && isLg) {
                ms.classList.add('hidden');
                ms.classList.add('lg:hidden');
            }
        } else {
            s.classList.add('-translate-x-full');
            s.classList.add('lg:-translate-x-full');
            s.classList.remove('lg:translate-x-0');
            s.classList.remove('translate-x-0');
            if (isLg) {
                d.classList.add('hidden');
                if (m) {
                    m.classList.remove('lg:pl-72');
                    m.classList.add('lg:pl-16');
                }
            } else {
                d.classList.add('opacity-0');
                d.classList.remove('opacity-100');
                setTimeout(function() {
                    d.classList.add('hidden');
                }, 300);
            }
            if (b) {
                b.setAttribute('aria-expanded', 'false');
                b.classList.remove('invisible');
                b.classList.remove('pointer-events-none');
            }
            if (ms && isLg) {
                ms.classList.remove('hidden');
                ms.classList.remove('lg:hidden');
            }
        }
    };
    // TOTP removed
    window.appOnReady = function(fn) {
        if (typeof fn !== 'function') return;
        document.addEventListener('app:content:ready', function() {
            try {
                fn();
            } catch (e) {}
        });
        var c = document.getElementById('app-content');
        if (c) {
            try {
                fn();
            } catch (e) {}
        }
    };

    function initSpa() {
        var content = document.getElementById('app-content');
        if (!content) return;

        function setActive(url) {
            var links = document.querySelectorAll('#appSidebar a[data-spa="true"]');
            links.forEach(function(a) {
                var href = a.getAttribute('href');
                var active = href && url.indexOf(href) !== -1;
                a.classList.toggle('bg-[#1e88e5]', active);
                a.classList.toggle('text-white', active);
                a.classList.toggle('text-[#293D82]', !active);
                if (!active) a.classList.remove('bg-[#1e88e5]');
            });
        }

        function fetchContent(url, push) {
            var loader = document.createElement('div');
            loader.className = 'animate-pulse';
            content.innerHTML = '<div class="p-6"><div class="h-6 w-1/3 bg-gray-200 mb-3"></div><div class="h-4 w-2/3 bg-gray-200"></div></div>';
            fetch(url, {
                    credentials: 'same-origin'
                })
                .then(function(res) {
                    return res.text();
                })
                .then(function(html) {
                    var doc = new DOMParser().parseFromString(html, 'text/html');
                    var next = doc.getElementById('app-content');
                    if (next) {
                        content.innerHTML = next.innerHTML;
                        document.querySelectorAll('script[data-spa-script="content"]').forEach(function(old) {
                            if (old.parentNode) old.parentNode.removeChild(old);
                        });
                        document.querySelectorAll('script[data-page-script="true"]').forEach(function(oldPageScript) {
                            if (oldPageScript.parentNode) oldPageScript.parentNode.removeChild(oldPageScript);
                        });
                        var scripts = doc.querySelectorAll('#app-content script, script[data-page-script="true"]');
                        scripts.forEach(function(s) {
                            var ns = document.createElement('script');
                            ns.setAttribute('data-spa-script', 'content');
                            var src = s.getAttribute('src');
                            if (src) {
                                ns.src = src;
                            } else {
                                ns.text = s.textContent || '';
                            }
                            document.body.appendChild(ns);
                        });
                        try {
                            document.dispatchEvent(new CustomEvent('app:content:ready', {
                                detail: {
                                    url: url
                                }
                            }));
                        } catch (e) {}
                        if (push) history.pushState({
                            url: url
                        }, '', url);
                        setActive(url);
                        window.scrollTo({
                            top: 0,
                            behavior: 'smooth'
                        });
                    } else {
                        window.location.href = url;
                    }
                })
                .catch(function() {
                    window.location.href = url;
                });
        }
        document.body.addEventListener('click', function(e) {
            var a = e.target.closest('a[data-spa="true"]');
            if (!a) return;
            var url = a.getAttribute('href');
            if (!url) return;
            e.preventDefault();
            fetchContent(url, true);
        });
        window.addEventListener('popstate', function(e) {
            var url = (e.state && e.state.url) || window.location.href;
            fetchContent(url, false);
        });
        setActive(window.location.href);
    }
    document.addEventListener('DOMContentLoaded', initSpa);
    (function() {
        var m = {
            error: <?php echo json_encode($page_error); ?>,
            success: <?php echo json_encode($page_success); ?>,
            info: <?php echo json_encode($page_info); ?>
        };
        if (m.error) window.showToast && window.showToast('error', m.error);
        if (m.success) window.showToast && window.showToast('success', m.success);
        if (m.info) window.showToast && window.showToast('warning', m.info);
    })();
</script>
