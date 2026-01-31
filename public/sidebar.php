<?php
require_once __DIR__ . '/../src/security.php';
secure_session_start();
$uri = (string)($_SERVER['REQUEST_URI'] ?? '/');
function is_active_link(string $pattern, string $uri): bool
{
    return strpos($uri, $pattern) !== false;
}
?>
<div id="sidebarBackdrop" class="fixed inset-0 z-40 bg-black/40 hidden opacity-0 transition-opacity duration-300" aria-hidden="true" onclick="window.__toggleSidebar && window.__toggleSidebar(false)"></div>
<aside id="appSidebar" class="fixed inset-y-0 left-0 z-50 w-72 transform -translate-x-full lg:-translate-x-full bg-white transition-transform duration-300 ease-in-out border-r" role="navigation" aria-label="Sidebar">
    <div class="flex h-full flex-col">
        <div class="px-4 py-4 border-b">
            <div class="flex items-center gap-3">
                <img src="<?= htmlspecialchars(asset_url('images/logo.png'), ENT_QUOTES, 'UTF-8'); ?>" alt="Scholar logo" class="h-8 w-8 rounded-lg object-contain" />
                <div>
                    <div class="text-base font-semibold">Scholar</div>
                    <div class="text-xs text-gray-500">San Luis</div>
                </div>
                <button class="ml-auto rounded-md p-2 hover:bg-gray-100" aria-label="Close sidebar" onclick="window.__toggleSidebar && window.__toggleSidebar(false)">✕</button>
            </div>
        </div>

        <div class="px-4 py-4 border-b">
            <div class="flex items-center gap-3">
                <span class="inline-grid h-10 w-10 place-items-center rounded-full bg-[#293D82] text-white text-sm">
                    <?= strtoupper(substr($username ?? 'U', 0, 1)); ?>
                </span>
                <div class="min-w-0">
                    <div class="truncate text-sm font-medium"><?= htmlspecialchars($username ?? 'User', ENT_QUOTES, 'UTF-8'); ?></div>
                    <div class="truncate text-xs text-gray-500"><?= htmlspecialchars($role ?? 'Staff', ENT_QUOTES, 'UTF-8'); ?></div>
                </div>
            </div>
        </div>

        <nav class="flex-1 overflow-y-auto px-3 py-4">
            <ul class="space-y-1">
                <li>
                    <a href="<?= route_url('admin/menu-1') ?>" data-spa="true" class="flex items-center gap-3 px-3 py-2 rounded-2xl transition-transform duration-150 ease-out hover:translate-x-1 <?= is_active_link('/admin/menu-1', $uri) ? 'bg-[#1e88e5] text-white' : 'text-[#293D82] hover:bg-[#e3f2fd]' ?>">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M4 4h8v8H4V4zm0 12h8v4H4v-4zm12-12h4v8h-4V4zm0 12h4v4h-4v-4z" />
                        </svg>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="<?= route_url('admin/menu-2') ?>" data-spa="true" class="flex items-center gap-3 px-3 py-2 rounded-2xl transition-transform duration-150 ease-out hover:translate-x-1 <?= is_active_link('/admin/menu-2', $uri) ? 'bg-[#1e88e5] text-white' : 'text-[#293D82] hover:bg-[#e3f2fd]' ?>">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M4 5h12M4 9h12M4 13h8" />
                            <path d="M16 3h4v18H6a2 2 0 01-2-2V3" />
                        </svg>
                        <span>Applications</span>
                    </a>
                </li>
                <li>
                    <a href="<?= route_url('admin/menu-3') ?>" data-spa="true" class="flex items-center gap-3 px-3 py-2 rounded-2xl transition-transform duration-150 ease-out hover:translate-x-1 <?= is_active_link('/admin/menu-3', $uri) ? 'bg-[#1e88e5] text-white' : 'text-[#293D82] hover:bg-[#e3f2fd]' ?>">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="5" y="6" width="14" height="12" rx="3" />
                            <circle cx="8" cy="12" r="1.5" />
                            <path d="M11 12h6" />
                        </svg>
                        <span>Payout Checklist</span>
                    </a>
                </li>
                <!-- 
                <li>
                    <a href="<?= route_url('admin/menu-4') ?>" data-spa="true" class="flex items-center gap-3 px-3 py-2 rounded-2xl transition-transform duration-150 ease-out hover:translate-x-1 <?= is_active_link('/admin/menu-4', $uri) ? 'bg-[#1e88e5] text-white' : 'text-[#293D82] hover:bg-[#e3f2fd]' ?>">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M3 7h13l5 5v7a2 2 0 01-2 2H5a2 2 0 01-2-2V7z" />
                            <path d="M3 7l4-4h6l3 4" />
                        </svg>
                        <span>Documents</span>
                    </a>
                </li>
                -->
                <li>
                       <a href="<?= route_url('menu-5') ?>" data-spa="true" class="flex items-center gap-3 px-3 py-2 rounded-2xl transition-transform duration-150 ease-out hover:translate-x-1 <?= is_active_link('/menu-5', $uri) ? 'bg-[#1e88e5] text-white' : 'text-[#293D82] hover:bg-[#e3f2fd]' ?>">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="7" cy="17" r="2" />
                            <circle cx="17" cy="7" r="2" />
                            <path d="M9 15l6-6" />
                            <path d="M13 9h4v4" />
                        </svg>
                        <span>Announcements</span>
                    </a>
                </li>
                <!-- 
                <li>
                    <a href="<?= route_url('admin/menu-6') ?>" data-spa="true" class="flex items-center gap-3 px-3 py-2 rounded-2xl transition-transform duration-150 ease-out hover:translate-x-1 <?= is_active_link('/admin/menu-6', $uri) ? 'bg-[#1e88e5] text-white' : 'text-[#293D82] hover:bg-[#e3f2fd]' ?>">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 22a5 5 0 005-5H7a5 5 0 005 5z" />
                            <path d="M12 3a6 6 0 016 6" />
                            <path d="M12 3a6 6 0 00-6 6" />
                        </svg>
                        <span>Menu 6</span>
                    </a>
                </li>
                -->
                <li class="mt-3 border-t pt-3">
                    <a href="<?= route_url('admin/account/manager') ?>" data-spa="true" class="flex items-center gap-3 px-3 py-2 rounded-2xl transition-transform duration-150 ease-out hover:translate-x-1 <?= is_active_link('/admin/account/manager', $uri) ? 'bg-[#1e88e5] text-white' : 'text-[#293D82] hover:bg-[#e3f2fd]' ?>">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="8" r="4" />
                            <path d="M4 20a8 8 0 0116 0" />
                        </svg>
                        <span>Account Manager</span>
                    </a>
                </li>
            </ul>
        </nav>

        <div class="mt-auto px-4 py-4 border-t">
            <form method="POST" action="<?= route_url('logout') ?>">
                <?= csrf_input(); ?>
                <button type="submit" class="w-full flex items-center justify-center gap-2 rounded-xl border px-3 py-2 text-[#293D82] hover:bg-gray-50">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M10 12h8" />
                        <path d="M14 8l4 4-4 4" />
                        <path d="M6 4h6a2 2 0 012 2v2" />
                        <path d="M6 20h6a2 2 0 002-2v-2" />
                    </svg>
                    <span>Sign Out</span>
                </button>
            </form>
        </div>
    </div>
</aside>
<aside id="miniSidebar" class="fixed top-14 bottom-0 left-0 z-30 hidden lg:flex w-16 flex-col items-center gap-4 border-r bg-white py-4">
    <div class="relative group">
        <button class="inline-grid h-10 w-10 place-items-center rounded-full bg-[#293D82] text-white" aria-label="Expand sidebar" onclick="window.__toggleSidebar && window.__toggleSidebar(true)">
            <?= strtoupper(substr($username ?? 'U', 0, 1)); ?>
        </button>
        <span class="pointer-events-none absolute left-16 top-1/2 -translate-y-1/2 rounded-md bg-[#1e88e5] px-2 py-1 text-xs text-white shadow-lg opacity-0 transition-opacity duration-200 group-hover:opacity-100">Open Menu</span>
    </div>
    <nav class="flex-1">
        <ul class="flex flex-col items-center gap-3">
            <li>
                <div class="relative group">
                    <a href="<?= route_url('admin/menu-1') ?>" data-spa="true" class="inline-grid h-10 w-10 place-items-center rounded-xl text-[#293D82] hover:bg-[#e3f2fd]"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M4 4h8v8H4V4zm0 12h8v4H4v-4zm12-12h4v8h-4V4zm0 12h4v4h-4v-4z" />
                        </svg></a>
                    <span class="pointer-events-none absolute left-16 top-1/2 -translate-y-1/2 rounded-md bg-[#1e88e5] px-2 py-1 text-xs text-white shadow-lg opacity-0 transition-opacity duration-200 group-hover:opacity-100">Dashboard</span>
                </div>
            </li>
            <li>
                <div class="relative group">
                    <a href="<?= route_url('admin/menu-2') ?>" data-spa="true" class="inline-grid h-10 w-10 place-items-center rounded-xl text-[#293D82] hover:bg-[#e3f2fd]"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M4 5h12" />
                            <path d="M4 9h12" />
                            <path d="M4 13h8" />
                            <path d="M16 3h4v18H6a2 2 0 01-2-2V3" />
                        </svg></a>
                    <span class="pointer-events-none absolute left-16 top-1/2 -translate-y-1/2 rounded-md bg-[#1e88e5] px-2 py-1 text-xs text-white shadow-lg opacity-0 transition-opacity duration-200 group-hover:opacity-100">Applications</span>
                </div>
            </li>
            <li>
                <div class="relative group">
                    <a href="<?= route_url('admin/menu-3') ?>" data-spa="true" class="inline-grid h-10 w-10 place-items-center rounded-xl text-[#293D82] hover:bg-[#e3f2fd]"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="5" y="6" width="14" height="12" rx="3" />
                            <circle cx="8" cy="12" r="1.5" />
                            <path d="M11 12h6" />
                        </svg></a>
                    <span class="pointer-events-none absolute left-16 top-1/2 -translate-y-1/2 rounded-md bg-[#1e88e5] px-2 py-1 text-xs text-white shadow-lg opacity-0 transition-opacity duration-200 group-hover:opacity-100">Payout Checklist</span>
                </div>
            </li>
            <!-- 
            <li>
                <div class="relative group">
                    <a href="<?= route_url('admin/menu-4') ?>" data-spa="true" class="inline-grid h-10 w-10 place-items-center rounded-xl text-[#293D82] hover:bg-[#e3f2fd]"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M3 7h13l5 5v7a2 2 0 01-2 2H5a2 2 0 01-2-2V7z" />
                            <path d="M3 7l4-4h6l3 4" />
                        </svg></a>
                    <span class="pointer-events-none absolute left-16 top-1/2 -translate-y-1/2 rounded-md bg-[#1e88e5] px-2 py-1 text-xs text-white shadow-lg opacity-0 transition-opacity duration-200 group-hover:opacity-100">Documents</span>
                </div>
            </li>
            -->
            <li>
                <div class="relative group">
                    <a href="<?= route_url('menu-5') ?>" data-spa="true" class="inline-grid h-10 w-10 place-items-center rounded-xl text-[#293D82] hover:bg-[#e3f2fd]"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="7" cy="17" r="2" />
                            <circle cx="17" cy="7" r="2" />
                            <path d="M9 15l6-6" />
                            <path d="M13 9h4v4" />
                        </svg></a>
                    <span class="pointer-events-none absolute left-16 top-1/2 -translate-y-1/2 rounded-md bg-[#1e88e5] px-2 py-1 text-xs text-white shadow-lg opacity-0 transition-opacity duration-200 group-hover:opacity-100">Announcement</span>
                </div>
            </li>
            <!-- 
            <li>
                <div class="relative group">
                    <a href="<?= route_url('admin/menu-6') ?>" data-spa="true" class="inline-grid h-10 w-10 place-items-center rounded-xl text-[#293D82] hover:bg-[#e3f2fd]"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 22a5 5 0 005-5H7a5 5 0 005 5z" />
                            <path d="M12 3a6 6 0 016 6" />
                            <path d="M12 3a6 6 0 00-6 6" />
                        </svg></a>
                    <span class="pointer-events-none absolute left-16 top-1/2 -translate-y-1/2 rounded-md bg-[#1e88e5] px-2 py-1 text-xs text-white shadow-lg opacity-0 transition-opacity duration-200 group-hover:opacity-100">Menu 6</span>
                </div>
            </li>
            -->
        </ul>
    </nav>
    <div class="relative group">
        <a href="<?= route_url('admin/account/manager') ?>" data-spa="true" class="inline-grid h-10 w-10 place-items-center rounded-xl text-[#293D82] hover:bg-[#e3f2fd]"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="8" r="4" />
                <path d="M4 20a8 8 0 0116 0" />
            </svg></a>
        <span class="pointer-events-none absolute left-16 top-1/2 -translate-y-1/2 rounded-md bg-[#1e88e5] px-2 py-1 text-xs text-white shadow-lg opacity-0 transition-opacity duration-200 group-hover:opacity-100">Account</span>
    </div>
    <div class="relative group mt-auto">
        <form method="POST" action="<?= route_url('logout') ?>">
            <?= csrf_input(); ?>
            <button type="submit" class="inline-grid h-10 w-10 place-items-center rounded-xl text-[#293D82] hover:bg-[#e3f2fd]" aria-label="Sign Out">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M10 12h8" />
                    <path d="M14 8l4 4-4 4" />
                    <path d="M6 4h6a2 2 0 012 2v2" />
                    <path d="M6 20h6a2 2 0 002-2v-2" />
                </svg>
            </button>
        </form>
        <span class="pointer-events-none absolute left-16 top-1/2 -translate-y-1/2 rounded-md bg-[#1e88e5] px-2 py-1 text-xs text-white shadow-lg opacity-0 transition-opacity duration-200 group-hover:opacity-100">Sign Out</span>
    </div>
</aside>