/<?php
require_once __DIR__ . '/../src/security.php';
secure_session_start();
require_once __DIR__ . '/../src/auth.php';
enforce_auth_for_page(basename(__FILE__));
if (!isset($_SESSION['auth_user_id'])) {
    header('Location: ' . route_url('admin'));
    exit;
}
// Fetch approved applications for payout checklist
require_once __DIR__ . '/../src/db.php';
$approved = [];
try {
    $pdo = get_db_connection();
    $stmt = $pdo->query("SELECT id, first_name, middle_name, last_name, academic_level, semester FROM scholarship_applications WHERE status = 'approved' ORDER BY submission_date DESC");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $name = trim(($row['last_name'] ?? '') . ', ' . ($row['first_name'] ?? ''));
        $approved[] = [
            'id' => $row['id'],
            'name' => $name,
            'yearLevel' => $row['academic_level'] ?? '',
            'semester' => $row['semester'] ?? '',
            'paid' => false
        ];
    }
} catch (Exception $e) {
    error_log('Error fetching approved applications: ' . $e->getMessage());
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
                    <h2 class="text-xl font-semibold text-[#212121]">Payout Checklist</h2>
                </div>
                <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-4">
                    <label class="sm:col-span-1 flex items-center gap-2 rounded-xl border px-3 py-2 focus-within:ring-2 focus-within:ring-[#1e88e5]" aria-label="Search Payouts">
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
                            <option value="semester-asc">Semester ↑</option>
                            <option value="semester-desc">Semester ↓</option>
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
                    <label class="sm:col-span-1 block">
                        <span class="mb-1 block text-xs text-[#293D82]">Semester</span>
                        <select id="semesterSelect" class="w-full rounded-xl border px-3 py-2 text-sm focus:ring-2 focus:ring-[#1e88e5]">
                            <option value="">All</option>
                            <option>1st Sem</option>
                            <option>2nd Sem</option>
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
                                <th class="px-3 py-2 cursor-pointer select-none" data-sort-key="semester">Semester</th>
                                <th class="px-3 py-2">Paid</th>
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

            <div id="confirmModal" class="fixed inset-0 z-50 hidden" role="dialog" aria-modal="true" aria-labelledby="confirmTitle">
                <div id="confirmOverlay" class="fixed inset-0 bg-black/40 opacity-0 transition-opacity duration-300"></div>
                <div class="flex min-h-screen items-center justify-center p-4">
                    <div class="relative w-full max-w-md scale-95 opacity-0 rounded-2xl bg-white shadow-lg transition-all duration-300" id="confirmPanel">
                        <div class="flex items-center justify-between border-b px-4 py-3">
                            <h3 id="confirmTitle" class="text-lg font-semibold text-[#212121]">Confirm Payment</h3>
                            <button id="confirmClose" class="rounded-xl p-2 hover:bg-gray-100 focus:ring-2 focus:ring-[#1e88e5]" aria-label="Close">✕</button>
                        </div>
                        <div class="p-6">
                            <p class="text-sm text-[#293D82]">Confirm payment status change?</p>
                            <div class="mt-4 flex justify-end gap-2">
                                <button id="confirmCancel" class="rounded-xl border px-3 py-2 text-sm text-[#293D82] hover:bg-gray-100 focus:ring-2 focus:ring-[#1e88e5]">Cancel</button>
                                <button id="confirmOk" class="rounded-xl bg-[#1e88e5] px-3 py-2 text-sm text-white hover:bg-[#1976d2] focus:ring-2 focus:ring-[#1e88e5]">Confirm</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div id="detailsModal" class="fixed inset-0 z-50 hidden" role="dialog" aria-modal="true" aria-labelledby="detailsTitle">
                <div id="detailsOverlay" class="fixed inset-0 bg-black/40 opacity-0 transition-opacity duration-300"></div>
                <div class="flex min-h-screen items-center justify-center p-4">
                    <div class="relative w-full max-w-2xl scale-95 opacity-0 rounded-2xl bg-white shadow-lg transition-all duration-300" id="detailsPanel">
                        <div class="flex items-center justify-between border-b px-4 py-3">
                            <h3 id="detailsTitle" class="text-lg font-semibold text-[#212121]">Application Details</h3>
                            <button id="detailsClose" class="rounded-xl p-2 hover:bg-gray-100 focus:ring-2 focus:ring-[#1e88e5]" aria-label="Close">✕</button>
                        </div>
                        <div class="p-4" id="detailsContent"></div>
                    </div>
                </div>
            </div>

            <script data-page-script="true">
                // inject server-provided approved checklist into AppData (use DB data only)
                window.AppData = window.AppData || {};
                window.AppData.checklist = <?php echo json_encode($approved); ?>;
                    (function() {
                    var items = (window.AppData && Array.isArray(window.AppData.checklist)) ? window.AppData.checklist.slice() : [];
                    items = items.map(function(s) {
                        if (s && typeof s === 'object' && !s.semester) {
                            s.semester = '1st Sem';
                        }
                        return s;
                    });
                    var searchEl = document.getElementById('searchInput');
                    var sortEl = document.getElementById('sortSelect');
                    var yearEl = document.getElementById('yearSelect');
                    var semesterEl = document.getElementById('semesterSelect');
                    var tbody = document.getElementById('tableBody');
                    var resultCount = document.getElementById('resultCount');
                    var pageInfo = document.getElementById('pageInfo');
                    var pageSizeEl = document.getElementById('pageSize');
                    var prevBtn = document.getElementById('prevPage');
                    var nextBtn = document.getElementById('nextPage');
                    var clearBtn = document.getElementById('clearFilters');
                    var exportBtn = document.getElementById('exportCsv');
                    var ths = Array.prototype.slice.call(document.querySelectorAll('th[data-sort-key]'));
                    var chipsEl = document.getElementById('chips');
                    var chipsCont = document.getElementById('chipsContainer');

                    var confirmModal = document.getElementById('confirmModal');
                    var confirmOverlay = document.getElementById('confirmOverlay');
                    var confirmPanel = document.getElementById('confirmPanel');
                    var confirmClose = document.getElementById('confirmClose');
                    var confirmCancel = document.getElementById('confirmCancel');
                    var confirmOk = document.getElementById('confirmOk');
                    var pendingPaidIdx = null;

                    // Details modal elements
                    var detailsModal = document.getElementById('detailsModal');
                    var detailsOverlay = document.getElementById('detailsOverlay');
                    var detailsPanel = document.getElementById('detailsPanel');
                    var detailsClose = document.getElementById('detailsClose');
                    var detailsContent = document.getElementById('detailsContent');

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

                    // Escape HTML for safe insertion into details modal
                    function escapeHtml(str) {
                        if (str == null) return '';
                        return String(str).replace(/[&<>"]+/g, function(m) {
                            return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[m] || m;
                        });
                    }

                    function applySort(data) {
                        var s = getSort();
                        data.sort(function(a, b) {
                            var av = a[s.key],
                                bv = b[s.key];
                            if (s.key !== 'paid') {
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
                        var sem = semesterEl ? (semesterEl.value || '') : '';
                        var data = items.slice();
                        if (q) data = data.filter(function(s) {
                            return s.name.toLowerCase().indexOf(q) !== -1;
                        });
                        if (y) data = data.filter(function(s) {
                            return s.yearLevel === y;
                        });
                        if (sem) data = data.filter(function(s) {
                            return s.semester === sem;
                        });
                        applySort(data);
                        return data;
                    }

                    function renderChips() {
                        var q = (searchEl.value || '').trim();
                        var y = yearEl.value || '';
                        var sem = semesterEl ? (semesterEl.value || '') : '';
                        var html = '';
                        if (q) html += '<button type="button" class="inline-flex items-center gap-2 rounded-full bg-[#e3f2fd] px-3 py-1 text-xs text-[#293D82] hover:bg-[#d7e9fb] focus:ring-2 focus:ring-[#1e88e5]" data-clear="search"><span>Search: ' + q.replace(/[<>&]/g, function(c) {
                            return {
                                '<': '&lt;',
                                '>': '&gt;',
                                '&': '&amp;'
                            } [c];
                        }) + '</span><span aria-hidden="true">✕</span></button>';
                        if (y) html += '<button type="button" class="inline-flex items-center gap-2 rounded-full bg-[#e3f2fd] px-3 py-1 text-xs text-[#293D82] hover:bg-[#d7e9fb] focus:ring-2 focus:ring-[#1e88e5]" data-clear="year"><span>Year: ' + y + '</span><span aria-hidden="true">✕</span></button>';
                        if (sem) html += '<button type="button" class="inline-flex items-center gap-2 rounded-full bg-[#e3f2fd] px-3 py-1 text-xs text-[#293D82] hover:bg-[#d7e9fb] focus:ring-2 focus:ring-[#1e88e5]" data-clear="semester"><span>Semester: ' + sem + '</span><span aria-hidden="true">✕</span></button>';
                        if (chipsEl && chipsCont) {
                            chipsEl.innerHTML = html;
                            chipsCont.classList.toggle('hidden', html === '');
                        }
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
                            var paidClass = s.paid ? 'bg-green-100 text-green-700 border border-green-200' : 'bg-gray-100 text-gray-700 border border-gray-200';
                                return '<tr data-idx="' + idx + '" class="border-b hover:bg-gray-50 cursor-pointer">' +
                                    '<td class="px-3 py-2 text-[#212121]">' + escapeHtml(s.name) + '</td>' +
                                    '<td class="px-3 py-2 text-[#212121]">' + escapeHtml(s.yearLevel) + '</td>' +
                                    '<td class="px-3 py-2 text-[#212121]">' + escapeHtml(s.semester || '') + '</td>' +
                                    '<td class="px-3 py-2"><div class="flex items-center gap-2"><span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs ' + paidClass + '">' + (s.paid ? 'Paid' : 'Unpaid') + '</span><input type="checkbox" aria-label="Mark paid" class="h-4 w-4 rounded border-gray-300 text-[#1e88e5] focus:ring-[#1e88e5]" data-paid-idx="' + idx + '" ' + (s.paid ? 'checked' : '') + '></div></td>' +
                                    '</tr>';
                        }).join('');
                        tbody.innerHTML = html;
                        ths.forEach(function(th) {
                            th.classList.remove('underline');
                        });
                        var srt = getSort();
                        var activeTh = document.querySelector('th[data-sort-key="' + srt.key + '"]');
                        if (activeTh) activeTh.classList.add('underline');
                    }

                    function openConfirm(idx) {
                        pendingPaidIdx = idx;
                        confirmModal.classList.remove('hidden');
                        requestAnimationFrame(function() {
                            confirmOverlay.classList.remove('opacity-0');
                            confirmOverlay.classList.add('opacity-100');
                            confirmPanel.classList.remove('opacity-0');
                            confirmPanel.classList.remove('scale-95');
                            confirmPanel.classList.add('opacity-100');
                            confirmPanel.classList.add('scale-100');
                            confirmOk.focus();
                        });
                        document.addEventListener('keydown', onKeyDownConfirm);
                    }

                    function closeConfirm() {
                        confirmOverlay.classList.add('opacity-0');
                        confirmOverlay.classList.remove('opacity-100');
                        confirmPanel.classList.add('opacity-0');
                        confirmPanel.classList.add('scale-95');
                        confirmPanel.classList.remove('opacity-100');
                        confirmPanel.classList.remove('scale-100');
                        setTimeout(function() {
                            confirmModal.classList.add('hidden');
                        }, 300);
                        document.removeEventListener('keydown', onKeyDownConfirm);
                    }

                    function onKeyDownConfirm(e) {
                        if (e.key === 'Escape') closeConfirm();
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
                    if (semesterEl) semesterEl.addEventListener('change', function() {
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
                        if (semesterEl) semesterEl.value = '';
                        sortEl.value = 'name-asc';
                        page = 1;
                        render();
                        renderChips();
                    });
                    exportBtn.addEventListener('click', function() {
                        try {
                            var rows = filtered().map(function(s) {
                                return [s.name, s.yearLevel, (s.semester || ''), (s.paid ? 'Paid' : 'Unpaid')];
                            });
                            var header = ['Name', 'Year Level', 'Semester', 'Paid'];
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
                            a.download = 'checklist.csv';
                            a.click();
                            setTimeout(function() {
                                URL.revokeObjectURL(url);
                            }, 500);
                        } catch (e) {
                            window.showToast && window.showToast('error', 'Export failed');
                        }
                    });
                    document.addEventListener('click', function(e) {
                        // If the click was on a table row (but not on interactive controls), open details
                        var tr = e.target.closest('tr[data-idx]');
                        if (tr && !e.target.closest('button, input, select, a')) {
                            var ridx = parseInt(tr.getAttribute('data-idx'), 10);
                            if (!isNaN(ridx)) {
                                // items corresponds to window.AppData.checklist
                                var item = (items && items[ridx]) ? items[ridx] : null;
                                if (item) {
                                    openDetails(ridx);
                                    return;
                                }
                            }
                        }

                        var c2 = e.target.closest('#confirmClose');
                        if (c2) {
                            closeConfirm();
                            return;
                        }
                        if (confirmModal && !confirmPanel.contains(e.target) && !e.target.closest('#confirmPanel')) {
                            if (!confirmModal.classList.contains('hidden')) closeConfirm();
                        }
                        var chip = e.target.closest('button[data-clear]');
                        if (chip) {
                            var which = chip.getAttribute('data-clear');
                            if (which === 'search') searchEl.value = '';
                            if (which === 'year') yearEl.value = '';
                            if (which === 'semester' && semesterEl) semesterEl.value = '';
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
                        var cb = e.target.closest('input[type="checkbox"][data-paid-idx]');
                        if (cb) {
                            e.preventDefault();
                            var i = parseInt(cb.getAttribute('data-paid-idx'), 10);
                            if (!items[i].paid) {
                                openConfirm(i);
                            }
                        }
                    });
                    confirmOk.addEventListener('click', function() {
                        if (pendingPaidIdx !== null) {
                            items[pendingPaidIdx].paid = true;
                            if (window.AppData) window.AppData.checklist = items.slice();
                            pendingPaidIdx = null;
                            closeConfirm();
                            render();
                        }
                    });
                    confirmCancel.addEventListener('click', function() {
                        pendingPaidIdx = null;
                        closeConfirm();
                    });

                    function openDetails(idx) {
                        var item = (items && items[idx]) ? items[idx] : null;
                        if (!item) return;
                        var html = '<div class="space-y-3 text-sm text-[#293D82]">';
                        html += '<div><strong>Name:</strong> ' + escapeHtml(item.name || '') + '</div>';
                        html += '<div><strong>Year Level:</strong> ' + escapeHtml(item.yearLevel || '') + '</div>';
                        html += '<div><strong>Semester:</strong> ' + escapeHtml(item.semester || '') + '</div>';
                        html += '<div><strong>Paid:</strong> ' + (item.paid ? 'Yes' : 'No') + '</div>';
                        html += '<div><strong>Application ID:</strong> ' + escapeHtml(String(item.id || '')) + '</div>';
                        html += '</div>';
                        if (detailsContent) detailsContent.innerHTML = html;
                        if (!detailsModal) return;
                        detailsModal.classList.remove('hidden');
                        requestAnimationFrame(function() {
                            if (detailsOverlay) {
                                detailsOverlay.classList.remove('opacity-0');
                                detailsOverlay.classList.add('opacity-100');
                            }
                            if (detailsPanel) {
                                detailsPanel.classList.remove('opacity-0');
                                detailsPanel.classList.remove('scale-95');
                                detailsPanel.classList.add('opacity-100');
                                detailsPanel.classList.add('scale-100');
                                detailsClose && detailsClose.focus();
                            }
                        });
                        document.addEventListener('keydown', onDetailsKeyDown);
                    }

                    function closeDetails() {
                        if (!detailsModal) return;
                        if (detailsOverlay) {
                            detailsOverlay.classList.add('opacity-0');
                            detailsOverlay.classList.remove('opacity-100');
                        }
                        if (detailsPanel) {
                            detailsPanel.classList.add('opacity-0');
                            detailsPanel.classList.add('scale-95');
                            detailsPanel.classList.remove('opacity-100');
                            detailsPanel.classList.remove('scale-100');
                        }
                        setTimeout(function() { detailsModal.classList.add('hidden'); }, 300);
                        document.removeEventListener('keydown', onDetailsKeyDown);
                    }

                    function onDetailsKeyDown(e) {
                        if (e.key === 'Escape') closeDetails();
                    }
                    if (detailsClose) detailsClose.addEventListener('click', closeDetails);

                    render();
                    renderChips();
                })();
            </script>
        </main>
    </div>
</body>

</html>
