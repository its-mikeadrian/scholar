<?php
require_once __DIR__ . '/../src/security.php';
secure_session_start();
require_once __DIR__ . '/../src/auth.php';
enforce_auth_for_page(basename(__FILE__));
if (!isset($_SESSION['auth_user_id'])) {
    header('Location: ' . route_url('admin'));
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Iskolar Nang Luis - EDUCATIONAL ASSISTANCE</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="min-h-screen bg-[#f0f7ff]">
    <?php require __DIR__ . '/header.php'; ?>
    <?php require __DIR__ . '/sidebar.php'; ?>
    <div class="pt-14 lg:pl-16" id="appMain">
        <main id="app-content" class="max-w-7xl mx-auto px-4 py-6">
            <div class="rounded-2xl bg-white p-6 shadow-sm border border-slate-100">
                <div class="flex items-center justify-between">
                    <h2 class="text-xl font-semibold text-[#212121]">Applications</h2>
                </div>
                <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-3">
                    <label class="sm:col-span-1 flex items-center gap-2 rounded-xl border px-3 py-2 focus-within:ring-2 focus-within:ring-[#1e88e5]" aria-label="Search applications">
                        <svg class="h-5 w-5 text-[#293D82]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="11" cy="11" r="7" />
                            <path d="M21 21l-4.3-4.3" />
                        </svg>
                        <input id="searchInput" type="text" placeholder="Search" class="w-full outline-none text-sm" />
                    </label>
                    <label class="sm:col-span-1 block">
                        <span class="mb-1 block text-xs text-[#293D82]">Sort</span>
                        <select id="sortSelect" class="w-full rounded-xl border px-3 py-2 text-sm focus:ring-2 focus:ring-[#1e88e5]">
                            <option value="name-asc">Name A-Z</option>
                            <option value="name-desc">Name Z-A</option>
                            <option value="yearLevel-asc">Year ↑</option>
                            <option value="yearLevel-desc">Year ↓</option>
                        </select>
                    </label>
                    <label class="sm:col-span-1 block">
                        <span class="mb-1 block text-xs text-[#293D82]">Year Level</span>
                        <select id="yearSelect" class="w-full rounded-xl border px-3 py-2 text-sm focus:ring-2 focus:ring-[#1e88e5]">
                            <option value="">All</option>
                            <option>1st Year</option>
                            <option>2nd Year</option>
                            <option>3rd Year</option>
                            <option>4th Year</option>
                        </select>
                    </label>
                </div>
                <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-3">
                    <div class="sm:col-span-1 flex items-center gap-2 text-sm text-[#293D82]" aria-live="polite">
                        <span id="resultCount">0 results</span>
                    </div>
                    <div class="sm:col-span-1">
                        <button id="clearFilters" class="rounded-xl border px-3 py-2 text-sm text-[#293D82] hover:bg-[#e3f2fd] focus:ring-2 focus:ring-[#1e88e5]" aria-label="Clear filters">Clear Filters</button>
                    </div>
                    <div class="sm:col-span-1 flex items-center gap-2 justify-end">
                        <label class="flex items-center gap-2 text-sm text-[#293D82]"><span>Per page</span>
                            <select id="pageSize" class="rounded-xl border px-3 py-2 text-sm focus:ring-2 focus:ring-[#1e88e5]">
                                <option>5</option>
                                <option selected>10</option>
                                <option>20</option>
                            </select>
                        </label>
                        <button id="exportCsv" class="rounded-xl bg-[#1e88e5] px-3 py-2 text-white text-sm hover:bg-[#1976d2] focus:ring-2 focus:ring-[#1e88e5]" aria-label="Download CSV">Export CSV</button>
                        <button id="openArchive" class="rounded-xl bg-[#1e88e5] px-3 py-2 text-white text-sm hover:bg-[#1976d2] focus:ring-2 focus:ring-[#1e88e5]" aria-label="Open archive">Archive</button>
                    </div>
                </div>
                <div class="mt-2 hidden" id="chipsContainer">
                    <div id="chips" class="flex flex-wrap gap-2"></div>
                </div>
            </div>

            <div class="mt-6 rounded-2xl border border-slate-100 bg-white p-6 shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="border-b text-[#293D82]">
                            <tr class="text-left">
                                <th class="px-3 py-2 cursor-pointer select-none" data-sort-key="name">Name</th>
                                <th class="px-3 py-2 cursor-pointer select-none" data-sort-key="yearLevel">Year Level</th>
                                <th class="px-3 py-2">Documents</th>
                                <th class="px-3 py-2">Status</th>
                            </tr>
                        </thead>
                        <tbody id="tableBody"></tbody>
                    </table>
                </div>
            </div>
            <div class="mt-4 flex items-center justify-between">
                <div class="text-xs text-[#293D82]" id="pageInfo"></div>
                <div class="flex items-center gap-2">
                    <button id="prevPage" class="rounded-xl border px-3 py-1 text-xs text-[#293D82] hover:bg-[#e3f2fd] focus:ring-2 focus:ring-[#1e88e5]" aria-label="Previous page">Prev</button>
                    <button id="nextPage" class="rounded-xl border px-3 py-1 text-xs text-[#293D82] hover:bg-[#e3f2fd] focus:ring-2 focus:ring-[#1e88e5]" aria-label="Next page">Next</button>
                </div>
            </div>

            <div id="docModal" class="fixed inset-0 z-50 hidden" role="dialog" aria-modal="true" aria-labelledby="docTitle">
                <div id="docOverlay" class="fixed inset-0 bg-black/40 opacity-0 transition-opacity duration-300"></div>
                <div class="flex min-h-screen items-center justify-center p-4">
                    <div class="relative w-full max-w-3xl scale-95 opacity-0 rounded-2xl bg-white shadow-lg transition-all duration-300" id="docPanel">
                        <div class="flex items-center justify-between border-b px-4 py-3">
                            <div>
                                <h3 id="docTitle" class="text-lg font-semibold text-[#212121]"></h3>
                            </div>
                            <button id="docClose" class="rounded-xl p-2 hover:bg-gray-100 focus:ring-2 focus:ring-[#1e88e5]" aria-label="Close">✕</button>
                        </div>
                        <div class="p-4">
                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div class="rounded-xl border p-3">
                                    <div class="mb-2 text-sm font-medium text-[#212121]">Certificate of Registration (COR)</div>
                                    <div class="aspect-video w-full rounded-md bg-gray-100"></div>
                                </div>
                                <div class="rounded-xl border p-3">
                                    <div class="mb-2 text-sm font-medium text-[#212121]">Report of Grades</div>
                                    <div class="aspect-video w-full rounded-md bg-gray-100"></div>
                                </div>
                                <div class="rounded-xl border p-3">
                                    <div class="mb-2 text-sm font-medium text-[#212121]">Barangay Indigency</div>
                                    <div class="aspect-video w-full rounded-md bg-gray-100"></div>
                                </div>
                                <div class="rounded-xl border p-3">
                                    <div class="mb-2 text-sm font-medium text-[#212121]">Voter's Certification</div>
                                    <div class="aspect-video w-full rounded-md bg-gray-100"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div id="archiveModal" class="fixed inset-0 z-50 hidden" role="dialog" aria-modal="true" aria-labelledby="archiveTitle">
                <div id="archiveOverlay" class="fixed inset-0 bg-black/40 opacity-0 transition-opacity duration-300"></div>
                <div class="flex min-h-screen items-center justify-center p-4">
                    <div class="relative w-full max-w-5xl scale-95 opacity-0 rounded-2xl bg-white shadow-lg transition-all duration-300" id="archivePanel">
                        <div class="flex items-center justify-between border-b px-4 py-3">
                            <h3 id="archiveTitle" class="text-lg font-semibold text-[#212121]">Archive</h3>
                            <button id="archiveClose" class="rounded-xl p-2 hover:bg-gray-100 focus:ring-2 focus:ring-[#1e88e5]" aria-label="Close">✕</button>
                        </div>
                        <div class="p-4">
                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-4">
                                <label class="sm:col-span-1 flex items-center gap-2 rounded-xl border px-3 py-2 focus-within:ring-2 focus-within:ring-[#1e88e5]" aria-label="Search archive">
                                    <svg class="h-5 w-5 text-[#293D82]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <circle cx="11" cy="11" r="7" />
                                        <path d="M21 21l-4.3-4.3" />
                                    </svg>
                                    <input id="archiveSearch" type="text" placeholder="Search" class="w-full outline-none text-sm" />
                                </label>
                                <label class="sm:col-span-1 block">
                                    <span class="mb-1 block text-xs text-[#293D82]">Year Level</span>
                                    <select id="archiveYear" class="w-full rounded-xl border px-3 py-2 text-sm focus:ring-2 focus:ring-[#1e88e5]">
                                        <option value="">All</option>
                                        <option>1st Year</option>
                                        <option>2nd Year</option>
                                        <option>3rd Year</option>
                                        <option>4th Year</option>
                                    </select>
                                </label>
                                <label class="sm:col-span-1 block">
                                    <span class="mb-1 block text-xs text-[#293D82]">Status</span>
                                    <select id="archiveStatus" class="w-full rounded-xl border px-3 py-2 text-sm focus:ring-2 focus:ring-[#1e88e5]">
                                        <option value="">All</option>
                                        <option>Accepted</option>
                                        <option>Rejected</option>
                                    </select>
                                </label>
                                <label class="sm:col-span-1 block">
                                    <span class="mb-1 block text-xs text-[#293D82]">Date</span>
                                    <input id="archiveDate" type="date" class="w-full rounded-xl border px-3 py-2 text-sm focus:ring-2 focus:ring-[#1e88e5]" />
                                </label>
                            </div>

                            <div class="mt-3 flex items-center justify-between">
                                <div class="text-sm text-[#293D82]" id="archiveResultCount">0 results</div>
                                <label class="flex items-center gap-2 text-sm text-[#293D82]"><span>Per page</span>
                                    <select id="archivePageSize" class="rounded-xl border px-3 py-2 text-sm focus:ring-2 focus:ring-[#1e88e5]">
                                        <option>5</option>
                                        <option selected>10</option>
                                        <option>20</option>
                                    </select>
                                </label>
                            </div>

                            <div class="mt-4 overflow-x-auto">
                                <table class="min-w-full text-sm">
                                    <thead class="border-b text-[#293D82]">
                                        <tr class="text-left">
                                            <th class="px-3 py-2">Name</th>
                                            <th class="px-3 py-2">Year Level</th>
                                            <th class="px-3 py-2">Date</th>
                                            <th class="px-3 py-2">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody id="archiveTableBody"></tbody>
                                </table>
                            </div>

                            <div class="mt-4 flex items-center justify-between">
                                <div class="text-xs text-[#293D82]" id="archivePageInfo"></div>
                                <div class="flex items-center gap-2">
                                    <button id="archivePrev" class="rounded-xl border px-3 py-1 text-xs text-[#293D82] hover:bg-[#e3f2fd] focus:ring-2 focus:ring-[#1e88e5]" aria-label="Previous page">Prev</button>
                                    <button id="archiveNext" class="rounded-xl border px-3 py-1 text-xs text-[#293D82] hover:bg-[#e3f2fd] focus:ring-2 focus:ring-[#1e88e5]" aria-label="Next page">Next</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <script data-page-script="true">
                (function() {
                    var students = (window.AppData && Array.isArray(window.AppData.applications)) ? window.AppData.applications.slice() : [{
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
                    students.forEach(function(s, idx) {
                        if (s && typeof s === 'object' && s._rowIndex == null) s._rowIndex = idx;
                    });

                    var ACTIVE_STATUSES = {
                        'For Review': true,
                        'Incomplete': true
                    };
                    var ARCHIVE_STATUSES = {
                        'Accepted': true,
                        'Rejected': true
                    };

                    students.forEach(function(s) {
                        if (s && typeof s === 'object' && ARCHIVE_STATUSES[s.status] && !s.archivedDate) {
                            s.archivedDate = (new Date()).toISOString().slice(0, 10);
                        }
                    });

                    function getActiveBase() {
                        return students.filter(function(s) {
                            return !!ACTIVE_STATUSES[s.status];
                        });
                    }

                    function getArchiveBase() {
                        return students.filter(function(s) {
                            return !!ARCHIVE_STATUSES[s.status];
                        });
                    }
                    var searchEl = document.getElementById('searchInput');
                    var sortEl = document.getElementById('sortSelect');
                    var yearEl = document.getElementById('yearSelect');
                    var tbody = document.getElementById('tableBody');
                    var modal = document.getElementById('docModal');
                    var overlay = document.getElementById('docOverlay');
                    var panel = document.getElementById('docPanel');
                    var closeBtn = document.getElementById('docClose');
                    var titleEl = document.getElementById('docTitle');
                    var resultCount = document.getElementById('resultCount');
                    var pageInfo = document.getElementById('pageInfo');
                    var pageSizeEl = document.getElementById('pageSize');
                    var prevBtn = document.getElementById('prevPage');
                    var nextBtn = document.getElementById('nextPage');
                    var clearBtn = document.getElementById('clearFilters');
                    var exportBtn = document.getElementById('exportCsv');
                    var openArchiveBtn = document.getElementById('openArchive');
                    var ths = Array.prototype.slice.call(document.querySelectorAll('th[data-sort-key]'));
                    var page = 1;
                    var timer = null;

                    var archiveModal = document.getElementById('archiveModal');
                    var archiveOverlay = document.getElementById('archiveOverlay');
                    var archivePanel = document.getElementById('archivePanel');
                    var archiveClose = document.getElementById('archiveClose');
                    var archiveSearchEl = document.getElementById('archiveSearch');
                    var archiveYearEl = document.getElementById('archiveYear');
                    var archiveStatusEl = document.getElementById('archiveStatus');
                    var archiveDateEl = document.getElementById('archiveDate');
                    var archiveResultCountEl = document.getElementById('archiveResultCount');
                    var archivePageInfoEl = document.getElementById('archivePageInfo');
                    var archivePageSizeEl = document.getElementById('archivePageSize');
                    var archivePrevBtn = document.getElementById('archivePrev');
                    var archiveNextBtn = document.getElementById('archiveNext');
                    var archiveTbody = document.getElementById('archiveTableBody');
                    var archivePage = 1;
                    var archiveTimer = null;

                    function todayDateValue() {
                        var d = new Date();
                        var y = String(d.getFullYear());
                        var m = String(d.getMonth() + 1).padStart(2, '0');
                        var day = String(d.getDate()).padStart(2, '0');
                        return y + '-' + m + '-' + day;
                    }

                    function getSort() {
                        var v = sortEl.value || 'name-asc';
                        var p = v.split('-');
                        return {
                            key: p[0],
                            dir: p[1]
                        };
                    }

                    function applySort(data) {
                        var s = getSort();
                        data.sort(function(a, b) {
                            var av = a[s.key];
                            var bv = b[s.key];
                            if (s.key === 'name' || s.key === 'yearLevel') {
                                av = String(av).toLowerCase();
                                bv = String(bv).toLowerCase();
                            }
                            if (av < bv) return s.dir === 'asc' ? -1 : 1;
                            if (av > bv) return s.dir === 'asc' ? 1 : -1;
                            return 0;
                        });
                    }

                    function filtered() {
                        var q = (searchEl.value || '').toLowerCase();
                        var y = yearEl.value || '';
                        var data = getActiveBase();
                        if (q) data = data.filter(function(s) {
                            return s.name.toLowerCase().indexOf(q) !== -1;
                        });
                        if (y) data = data.filter(function(s) {
                            return s.yearLevel === y;
                        });
                        applySort(data);
                        return data;
                    }

                    function render() {
                        var per = parseInt(pageSizeEl.value || '10', 10);
                        var data = filtered();
                        var total = data.length;
                        var maxPage = Math.max(1, Math.ceil(total / per));
                        if (page > maxPage) page = maxPage;
                        var start = (page - 1) * per;
                        var end = Math.min(start + per, total);
                        var slice = data.slice(start, end);
                        resultCount.textContent = String(total) + ' results';
                        pageInfo.textContent = 'Showing ' + (total === 0 ? 0 : start + 1) + '–' + end + ' of ' + total + ' • Page ' + page + ' of ' + maxPage;
                        prevBtn.disabled = page <= 1;
                        nextBtn.disabled = page >= maxPage;
                        var html = slice.map(function(s, i) {
                            var rowIdx = s._rowIndex;
                            var stClassMap = {
                                'Accepted': 'bg-green-600 text-white',
                                'For Review': 'bg-amber-500 text-white',
                                'Incomplete': 'bg-gray-500 text-white',
                                'Rejected': 'bg-red-600 text-white'
                            };
                            var badgeClassMap = {
                                'Accepted': 'bg-green-100 text-green-700 border border-green-200',
                                'For Review': 'bg-amber-100 text-amber-700 border border-amber-200',
                                'Incomplete': 'bg-gray-100 text-gray-700 border border-gray-200',
                                'Rejected': 'bg-red-100 text-red-700 border border-red-200'
                            };
                            var stClass = stClassMap[s.status] || 'bg-gray-500 text-white';
                            var badgeClass = badgeClassMap[s.status] || 'bg-gray-100 text-gray-700 border border-gray-200';
                            return '<tr class="border-b hover:bg-gray-50">' +
                                '<td class="px-3 py-2 text-[#212121]">' + s.name + '</td>' +
                                '<td class="px-3 py-2 text-[#212121]">' + s.yearLevel + '</td>' +
                                '<td class="px-3 py-2"><button class="rounded-xl bg-[#1e88e5] px-3 py-1 text-white text-xs hover:bg-[#1976d2] focus:ring-2 focus:ring-[#1e88e5]" data-idx="' + rowIdx + '">View</button></td>' +
                                '<td class="px-3 py-2"><div class="flex items-center gap-2"><span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs ' + badgeClass + '">' + s.status + '</span><select class="rounded-md px-2 py-1 text-xs focus:ring-2 focus:ring-[#1e88e5] ' + stClass + '" data-status-idx="' + rowIdx + '"><option' + (s.status === 'For Review' ? ' selected' : '') + '>For Review</option><option' + (s.status === 'Accepted' ? ' selected' : '') + '>Accepted</option><option' + (s.status === 'Incomplete' ? ' selected' : '') + '>Incomplete</option><option' + (s.status === 'Rejected' ? ' selected' : '') + '>Rejected</option></select></div></td>' +
                                '</tr>';
                        }).join('');
                        tbody.innerHTML = html;
                        ths.forEach(function(th) {
                            th.classList.remove('underline');
                        });
                        var s = getSort();
                        var activeTh = document.querySelector('th[data-sort-key="' + s.key + '"]');
                        if (activeTh) activeTh.classList.add('underline');
                    }

                    function renderChips() {
                        var q = (searchEl.value || '').trim();
                        var y = yearEl.value || '';
                        var html = '';
                        if (q) {
                            html += '<button type="button" class="inline-flex items-center gap-2 rounded-full bg-[#e3f2fd] px-3 py-1 text-xs text-[#293D82] hover:bg-[#d7e9fb] focus:ring-2 focus:ring-[#1e88e5]" data-clear="search"><span>Search: ' + q.replace(/[<>&]/g, function(c) {
                                return {
                                    '<': '&lt;',
                                    '>': '&gt;',
                                    '&': '&amp;'
                                } [c];
                            }) + '</span><span aria-hidden="true">✕</span></button>';
                        }
                        if (y) {
                            html += '<button type="button" class="inline-flex items-center gap-2 rounded-full bg-[#e3f2fd] px-3 py-1 text-xs text-[#293D82] hover:bg-[#d7e9fb] focus:ring-2 focus:ring-[#1e88e5]" data-clear="year"><span>Year: ' + y + '</span><span aria-hidden="true">✕</span></button>';
                        }
                        var chipsEl = document.getElementById('chips');
                        var cont = document.getElementById('chipsContainer');
                        if (chipsEl && cont) {
                            chipsEl.innerHTML = html;
                            cont.classList.toggle('hidden', html === '');
                        }
                    }

                    function openModal(s) {
                        titleEl.textContent = s.name;
                        modal.classList.remove('hidden');
                        requestAnimationFrame(function() {
                            overlay.classList.remove('opacity-0');
                            overlay.classList.add('opacity-100');
                            panel.classList.remove('opacity-0');
                            panel.classList.remove('scale-95');
                            panel.classList.add('opacity-100');
                            panel.classList.add('scale-100');
                            closeBtn.focus();
                        });
                        document.addEventListener('keydown', onKeyDown);
                    }

                    function closeModal() {
                        overlay.classList.add('opacity-0');
                        overlay.classList.remove('opacity-100');
                        panel.classList.add('opacity-0');
                        panel.classList.add('scale-95');
                        panel.classList.remove('opacity-100');
                        panel.classList.remove('scale-100');
                        setTimeout(function() {
                            modal.classList.add('hidden');
                        }, 300);
                        document.removeEventListener('keydown', onKeyDown);
                    }

                    function onKeyDown(e) {
                        if (e.key === 'Escape') {
                            closeModal();
                        }
                    }

                    function openArchive() {
                        if (!archiveModal) return;
                        archivePage = 1;
                        renderArchive();
                        archiveModal.classList.remove('hidden');
                        requestAnimationFrame(function() {
                            if (archiveOverlay) {
                                archiveOverlay.classList.remove('opacity-0');
                                archiveOverlay.classList.add('opacity-100');
                            }
                            if (archivePanel) {
                                archivePanel.classList.remove('opacity-0');
                                archivePanel.classList.remove('scale-95');
                                archivePanel.classList.add('opacity-100');
                                archivePanel.classList.add('scale-100');
                            }
                            if (archiveClose) archiveClose.focus();
                        });
                        document.addEventListener('keydown', onArchiveKeyDown);
                    }

                    function closeArchive() {
                        if (!archiveModal) return;
                        if (archiveOverlay) {
                            archiveOverlay.classList.add('opacity-0');
                            archiveOverlay.classList.remove('opacity-100');
                        }
                        if (archivePanel) {
                            archivePanel.classList.add('opacity-0');
                            archivePanel.classList.add('scale-95');
                            archivePanel.classList.remove('opacity-100');
                            archivePanel.classList.remove('scale-100');
                        }
                        setTimeout(function() {
                            archiveModal.classList.add('hidden');
                        }, 300);
                        document.removeEventListener('keydown', onArchiveKeyDown);
                    }

                    function onArchiveKeyDown(e) {
                        if (e.key === 'Escape') closeArchive();
                    }

                    function archiveFiltered() {
                        var q = (archiveSearchEl && archiveSearchEl.value ? archiveSearchEl.value : '').toLowerCase();
                        var y = archiveYearEl ? (archiveYearEl.value || '') : '';
                        var st = archiveStatusEl ? (archiveStatusEl.value || '') : '';
                        var dt = archiveDateEl ? (archiveDateEl.value || '') : '';
                        var data = getArchiveBase();
                        if (q) data = data.filter(function(s) {
                            return s.name.toLowerCase().indexOf(q) !== -1;
                        });
                        if (y) data = data.filter(function(s) {
                            return s.yearLevel === y;
                        });
                        if (st) data = data.filter(function(s) {
                            return s.status === st;
                        });
                        if (dt) data = data.filter(function(s) {
                            return (s.archivedDate || '') === dt;
                        });
                        data.sort(function(a, b) {
                            var av = String(a.name || '').toLowerCase();
                            var bv = String(b.name || '').toLowerCase();
                            if (av < bv) return -1;
                            if (av > bv) return 1;
                            return 0;
                        });
                        return data;
                    }

                    function renderArchive() {
                        if (!archiveModal || !archiveTbody) return;
                        var per = parseInt((archivePageSizeEl && archivePageSizeEl.value) ? archivePageSizeEl.value : '10', 10);
                        var data = archiveFiltered();
                        var total = data.length;
                        var maxPage = Math.max(1, Math.ceil(total / per));
                        if (archivePage > maxPage) archivePage = maxPage;
                        var start = (archivePage - 1) * per;
                        var end = Math.min(start + per, total);
                        var slice = data.slice(start, end);
                        if (archiveResultCountEl) archiveResultCountEl.textContent = String(total) + ' results';
                        if (archivePageInfoEl) archivePageInfoEl.textContent = 'Showing ' + (total === 0 ? 0 : start + 1) + '–' + end + ' of ' + total + ' • Page ' + archivePage + ' of ' + maxPage;
                        if (archivePrevBtn) archivePrevBtn.disabled = archivePage <= 1;
                        if (archiveNextBtn) archiveNextBtn.disabled = archivePage >= maxPage;
                        var badgeClassMap = {
                            'Accepted': 'bg-green-100 text-green-700 border border-green-200',
                            'Rejected': 'bg-red-100 text-red-700 border border-red-200'
                        };
                        archiveTbody.innerHTML = slice.map(function(s) {
                            var badgeClass = badgeClassMap[s.status] || 'bg-gray-100 text-gray-700 border border-gray-200';
                            return '<tr class="border-b hover:bg-gray-50">' +
                                '<td class="px-3 py-2 text-[#212121]">' + s.name + '</td>' +
                                '<td class="px-3 py-2 text-[#212121]">' + s.yearLevel + '</td>' +
                                '<td class="px-3 py-2 text-[#212121]">' + (s.archivedDate || '') + '</td>' +
                                '<td class="px-3 py-2"><span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs ' + badgeClass + '">' + s.status + '</span></td>' +
                                '</tr>';
                        }).join('');
                    }

                    function exportCsv() {
                        var rows = filtered().map(function(s) {
                            return [s.name, s.yearLevel, s.status];
                        });
                        var header = ['Name', 'Year Level', 'Status'];
                        var csv = [header].concat(rows).map(function(r) {
                            return r.map(function(c) {
                                var v = String(c);
                                if (v.indexOf(',') !== -1) return '"' + v.replace(/"/g, '""') + '"';
                                return v;
                            }).join(',');
                        }).join('\n');
                        var blob = new Blob([csv], {
                            type: 'text/csv;charset=utf-8;'
                        });
                        var url = URL.createObjectURL(blob);
                        var a = document.createElement('a');
                        a.href = url;
                        a.download = 'applications.csv';
                        a.click();
                        setTimeout(function() {
                            URL.revokeObjectURL(url);
                        }, 500);
                    }
                    searchEl.addEventListener('input', function() {
                        if (timer) clearTimeout(timer);
                        timer = setTimeout(function() {
                            page = 1;
                            render();
                            renderChips();
                        }, 200);
                    });
                    sortEl.addEventListener('change', function() {
                        page = 1;
                        render();
                    });
                    yearEl.addEventListener('change', function() {
                        page = 1;
                        render();
                        renderChips();
                    });
                    pageSizeEl.addEventListener('change', function() {
                        page = 1;
                        render();
                    });
                    prevBtn.addEventListener('click', function() {
                        if (page > 1) {
                            page -= 1;
                            render();
                        }
                    });
                    nextBtn.addEventListener('click', function() {
                        page += 1;
                        render();
                    });
                    clearBtn.addEventListener('click', function() {
                        searchEl.value = '';
                        yearEl.value = '';
                        sortEl.value = 'name-asc';
                        page = 1;
                        render();
                        renderChips();
                    });
                    exportBtn.addEventListener('click', exportCsv);
                    if (openArchiveBtn) openArchiveBtn.addEventListener('click', openArchive);
                    if (archiveClose) archiveClose.addEventListener('click', closeArchive);
                    if (archivePrevBtn) archivePrevBtn.addEventListener('click', function() {
                        if (archivePage > 1) {
                            archivePage -= 1;
                            renderArchive();
                        }
                    });
                    if (archiveNextBtn) archiveNextBtn.addEventListener('click', function() {
                        archivePage += 1;
                        renderArchive();
                    });
                    if (archivePageSizeEl) archivePageSizeEl.addEventListener('change', function() {
                        archivePage = 1;
                        renderArchive();
                    });
                    if (archiveYearEl) archiveYearEl.addEventListener('change', function() {
                        archivePage = 1;
                        renderArchive();
                    });
                    if (archiveStatusEl) archiveStatusEl.addEventListener('change', function() {
                        archivePage = 1;
                        renderArchive();
                    });
                    if (archiveDateEl) archiveDateEl.addEventListener('change', function() {
                        archivePage = 1;
                        renderArchive();
                    });
                    if (archiveSearchEl) archiveSearchEl.addEventListener('input', function() {
                        if (archiveTimer) clearTimeout(archiveTimer);
                        archiveTimer = setTimeout(function() {
                            archivePage = 1;
                            renderArchive();
                        }, 200);
                    });
                    document.addEventListener('click', function(e) {
                        var v = e.target.closest('button[data-idx]');
                        if (v) {
                            var s = students[parseInt(v.getAttribute('data-idx'), 10)];
                            openModal(s);
                            return;
                        }
                        var c = e.target.closest('#docClose');
                        if (c) {
                            closeModal();
                            return;
                        }
                        if (modal && !panel.contains(e.target) && !e.target.closest('#docPanel') && !e.target.closest('button[data-idx]')) {
                            if (!modal.classList.contains('hidden')) closeModal();
                        }
                        var ac = e.target.closest('#archiveClose');
                        if (ac) {
                            closeArchive();
                            return;
                        }
                        if (archiveModal && !archiveModal.classList.contains('hidden') && archivePanel && !archivePanel.contains(e.target) && !e.target.closest('#archivePanel') && !e.target.closest('#openArchive')) {
                            closeArchive();
                        }
                        var chip = e.target.closest('button[data-clear]');
                        if (chip) {
                            var which = chip.getAttribute('data-clear');
                            if (which === 'search') searchEl.value = '';
                            if (which === 'year') yearEl.value = '';
                            page = 1;
                            render();
                            renderChips();
                            return;
                        }
                        var th = e.target.closest('th[data-sort-key]');
                        if (th) {
                            var srt = getSort();
                            var key = th.getAttribute('data-sort-key');
                            if (srt.key === key) sortEl.value = key + '-' + (srt.dir === 'asc' ? 'desc' : 'asc');
                            else sortEl.value = key + '-asc';
                            page = 1;
                            render();
                        }
                    });
                    document.addEventListener('change', function(e) {
                        var sel = e.target.closest('select[data-status-idx]');
                        if (sel) {
                            var i = parseInt(sel.getAttribute('data-status-idx'), 10);
                            students[i].status = sel.value;
                            if (ARCHIVE_STATUSES[sel.value]) {
                                if (!students[i].archivedDate) students[i].archivedDate = todayDateValue();
                            }
                            if (window.AppData) window.AppData.applications = students.slice();
                            render();
                            if (archiveModal && !archiveModal.classList.contains('hidden')) renderArchive();
                        }
                    });
                    render();
                    renderChips();
                })();
            </script>
        </main>
    </div>
</body>

</html>
