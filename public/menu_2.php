<?php
require_once __DIR__ . '/../src/security.php';
secure_session_start();
require_once __DIR__ . '/../src/auth.php';
enforce_auth_for_page(basename(__FILE__));
if (!isset($_SESSION['auth_user_id'])) {
    header('Location: ' . route_url(''));
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Menu 2</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="min-h-screen bg-[#f0f7ff]">
    <?php require __DIR__ . '/header.php'; ?>
    <?php require __DIR__ . '/sidebar.php'; ?>
    <div class="pt-14 lg:pl-16" id="appMain">
        <main id="app-content" class="max-w-7xl mx-auto px-4 py-6">
            <div class="rounded-2xl bg-white p-6 shadow-sm">
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
                            <option value="grade-asc">Grade ↑</option>
                            <option value="grade-desc">Grade ↓</option>
                            <option value="year-asc">Year ↑</option>
                            <option value="year-desc">Year ↓</option>
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
                        <button id="clearFilters" class="rounded-xl border px-3 py-2 text-sm text-[#293D82] hover:bg-[#e3f2fd]" aria-label="Clear filters">Clear Filters</button>
                    </div>
                    <div class="sm:col-span-1 flex items-center gap-2 justify-end">
                        <label class="flex items-center gap-2 text-sm text-[#293D82]"><span>Per page</span>
                            <select id="pageSize" class="rounded-xl border px-3 py-2 text-sm focus:ring-2 focus:ring-[#1e88e5]">
                                <option>5</option>
                                <option selected>10</option>
                                <option>20</option>
                            </select>
                        </label>
                        <button id="exportCsv" class="rounded-xl bg-[#1e88e5] px-3 py-2 text-white text-sm hover:bg-[#1976d2]" aria-label="Download CSV">Export CSV</button>
                    </div>
                </div>
                <div class="mt-2" id="chipsContainer">
                    <div id="chips" class="flex flex-wrap gap-2"></div>
                </div>
            </div>

            <div class="mt-6 rounded-2xl border bg-white p-6">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="border-b text-[#293D82]">
                            <tr class="text-left">
                                <th class="px-3 py-2 cursor-pointer select-none" data-sort-key="name">Name</th>
                                <th class="px-3 py-2 cursor-pointer select-none" data-sort-key="yearLevel">Year Level</th>
                                <th class="px-3 py-2 cursor-pointer select-none" data-sort-key="grade">Inputted Grade</th>
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
                    <button id="prevPage" class="rounded-xl border px-3 py-1 text-xs text-[#293D82] hover:bg-[#e3f2fd]" aria-label="Previous page">Prev</button>
                    <button id="nextPage" class="rounded-xl border px-3 py-1 text-xs text-[#293D82] hover:bg-[#e3f2fd]" aria-label="Next page">Next</button>
                </div>
            </div>

            <div id="docModal" class="fixed inset-0 z-50 hidden" role="dialog" aria-modal="true" aria-labelledby="docTitle">
                <div id="docOverlay" class="fixed inset-0 bg-black/40 opacity-0 transition-opacity duration-300"></div>
                <div class="flex min-h-screen items-center justify-center p-4">
                    <div class="relative w-full max-w-3xl scale-95 opacity-0 rounded-2xl bg-white shadow-lg transition-all duration-300" id="docPanel">
                        <div class="flex items-center justify-between border-b px-4 py-3">
                            <div>
                                <h3 id="docTitle" class="text-lg font-semibold text-[#212121]"></h3>
                                <div id="docGwa" class="text-sm text-[#293D82]"></div>
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

            <script data-page-script="true">
                (function() {
                    var students = (window.AppData && Array.isArray(window.AppData.applications)) ? window.AppData.applications.slice() : [{
                            name: 'Dela Cruz, Juan',
                            yearLevel: '1st Year',
                            grade: 1.25,
                            gwa: 1.5,
                            status: 'For Review'
                        },
                        {
                            name: 'Santos, Maria',
                            yearLevel: '2nd Year',
                            grade: 1.75,
                            gwa: 1.7,
                            status: 'Accepted'
                        },
                        {
                            name: 'Reyes, Pedro',
                            yearLevel: '3rd Year',
                            grade: 1.50,
                            gwa: 1.6,
                            status: 'For Review'
                        },
                        {
                            name: 'Garcia, Ana',
                            yearLevel: '4th Year',
                            grade: 1.90,
                            gwa: 1.8,
                            status: 'Accepted'
                        },
                        {
                            name: 'Lim, Carlo',
                            yearLevel: '1st Year',
                            grade: 1.30,
                            gwa: 1.4,
                            status: 'For Review'
                        },
                        {
                            name: 'Tan, Lea',
                            yearLevel: '2nd Year',
                            grade: 1.60,
                            gwa: 1.6,
                            status: 'For Review'
                        },
                        {
                            name: 'Torres, Miguel',
                            yearLevel: '3rd Year',
                            grade: 1.45,
                            gwa: 1.5,
                            status: 'Accepted'
                        },
                        {
                            name: 'Domingo, Iris',
                            yearLevel: '4th Year',
                            grade: 1.85,
                            gwa: 1.7,
                            status: 'For Review'
                        },
                        {
                            name: 'Navarro, Joel',
                            yearLevel: '2nd Year',
                            grade: 1.70,
                            gwa: 1.7,
                            status: 'Accepted'
                        },
                        {
                            name: 'Cruz, Liza',
                            yearLevel: '3rd Year',
                            grade: 1.40,
                            gwa: 1.5,
                            status: 'For Review'
                        },
                        {
                            name: 'Ramos, Noel',
                            yearLevel: '1st Year',
                            grade: 1.20,
                            gwa: 1.3,
                            status: 'Accepted'
                        }
                    ];
                    var searchEl = document.getElementById('searchInput');
                    var sortEl = document.getElementById('sortSelect');
                    var yearEl = document.getElementById('yearSelect');
                    var tbody = document.getElementById('tableBody');
                    var modal = document.getElementById('docModal');
                    var overlay = document.getElementById('docOverlay');
                    var panel = document.getElementById('docPanel');
                    var closeBtn = document.getElementById('docClose');
                    var titleEl = document.getElementById('docTitle');
                    var gwaEl = document.getElementById('docGwa');
                    var resultCount = document.getElementById('resultCount');
                    var pageInfo = document.getElementById('pageInfo');
                    var pageSizeEl = document.getElementById('pageSize');
                    var prevBtn = document.getElementById('prevPage');
                    var nextBtn = document.getElementById('nextPage');
                    var clearBtn = document.getElementById('clearFilters');
                    var exportBtn = document.getElementById('exportCsv');
                    var ths = Array.prototype.slice.call(document.querySelectorAll('th[data-sort-key]'));
                    var page = 1;
                    var timer = null;

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
                        var data = students.slice();
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
                            var idx = start + i;
                            var stClass = (s.status === 'Accepted') ? 'bg-green-600 text-white' : 'bg-amber-500 text-white';
                            var badgeClass = (s.status === 'Accepted') ? 'bg-green-100 text-green-700 border border-green-200' : 'bg-amber-100 text-amber-700 border border-amber-200';
                            return '<tr class="border-b hover:bg-gray-50">' +
                                '<td class="px-3 py-2 text-[#212121]">' + s.name + '</td>' +
                                '<td class="px-3 py-2 text-[#212121]">' + s.yearLevel + '</td>' +
                                '<td class="px-3 py-2 text-[#212121]">' + s.grade + '</td>' +
                                '<td class="px-3 py-2"><button class="rounded-xl bg-[#1e88e5] px-3 py-1 text-white text-xs hover:bg-[#1976d2] focus:ring-2 focus:ring-[#1e88e5]" data-idx="' + idx + '">View</button></td>' +
                                '<td class="px-3 py-2"><div class="flex items-center gap-2"><span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs ' + badgeClass + '">' + s.status + '</span><select class="rounded-md px-2 py-1 text-xs focus:ring-2 focus:ring-[#1e88e5] ' + stClass + '" data-status-idx="' + idx + '"><option' + (s.status === 'For Review' ? ' selected' : '') + '>For Review</option><option' + (s.status === 'Accepted' ? ' selected' : '') + '>Accepted</option></select></div></td>' +
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
                        gwaEl.textContent = 'GWA: ' + s.gwa;
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

                    function exportCsv() {
                        var rows = filtered().map(function(s) {
                            return [s.name, s.yearLevel, s.grade, s.status];
                        });
                        var header = ['Name', 'Year Level', 'Inputted Grade', 'Status'];
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
                            if (window.AppData) window.AppData.applications = students.slice();
                            render();
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