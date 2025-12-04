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
    <title>Menu 1</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="min-h-screen bg-[#f0f7ff]">
    <?php require __DIR__ . '/header.php'; ?>
    <?php require __DIR__ . '/sidebar.php'; ?>
    <div class="pt-14 lg:pl-16" id="appMain">
        <main id="app-content" class="max-w-7xl mx-auto px-4 py-6">
            <div class="rounded-2xl bg-white p-6 shadow-sm">
                <div class="flex items-center justify-between">
                    <h2 class="text-xl font-semibold text-[#212121]">Dashboard Analytics</h2>
                </div>
                <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-3 lg:grid-cols-6">
                    <div class="rounded-2xl border p-4">
                        <div class="text-xs text-[#293D82]">Applications</div>
                        <div id="metricTotalApps" class="mt-1 text-2xl font-semibold">0</div>
                    </div>
                    <div class="rounded-2xl border p-4">
                        <div class="text-xs text-[#293D82]">Accepted</div>
                        <div id="metricAccepted" class="mt-1 text-2xl font-semibold">0</div>
                    </div>
                    <div class="rounded-2xl border p-4">
                        <div class="text-xs text-[#293D82]">For Review</div>
                        <div id="metricForReview" class="mt-1 text-2xl font-semibold">0</div>
                    </div>
                    <div class="rounded-2xl border p-4">
                        <div class="text-xs text-[#293D82]">Avg GWA</div>
                        <div id="metricAvgGwa" class="mt-1 text-2xl font-semibold">0.00</div>
                    </div>
                    <div class="rounded-2xl border p-4">
                        <div class="text-xs text-[#293D82]">Paid</div>
                        <div id="metricPaid" class="mt-1 text-2xl font-semibold">0</div>
                    </div>
                    <div class="rounded-2xl border p-4">
                        <div class="text-xs text-[#293D82]">Unpaid</div>
                        <div id="metricUnpaid" class="mt-1 text-2xl font-semibold">0</div>
                    </div>
                </div>

                <div class="mt-6 grid grid-cols-1 gap-4 lg:grid-cols-2">
                    <div class="rounded-2xl border p-6">
                        <div class="mb-2 text-sm font-medium text-[#212121]">Year Level Distribution</div>
                        <div class="space-y-3">
                            <div class="flex items-center gap-3">
                                <div class="w-24 text-xs text-[#293D82]">1st Year</div>
                                <div class="flex-1 h-3 rounded-full bg-[#e3f2fd]">
                                    <div id="barY1" class="h-3 rounded-full bg-[#1e88e5]" style="width:0%"></div>
                                </div>
                                <div id="valY1" class="w-10 text-xs text-right">0</div>
                            </div>
                            <div class="flex items-center gap-3">
                                <div class="w-24 text-xs text-[#293D82]">2nd Year</div>
                                <div class="flex-1 h-3 rounded-full bg-[#e3f2fd]">
                                    <div id="barY2" class="h-3 rounded-full bg-[#1e88e5]" style="width:0%"></div>
                                </div>
                                <div id="valY2" class="w-10 text-xs text-right">0</div>
                            </div>
                            <div class="flex items-center gap-3">
                                <div class="w-24 text-xs text-[#293D82]">3rd Year</div>
                                <div class="flex-1 h-3 rounded-full bg-[#e3f2fd]">
                                    <div id="barY3" class="h-3 rounded-full bg-[#1e88e5]" style="width:0%"></div>
                                </div>
                                <div id="valY3" class="w-10 text-xs text-right">0</div>
                            </div>
                            <div class="flex items-center gap-3">
                                <div class="w-24 text-xs text-[#293D82]">4th Year</div>
                                <div class="flex-1 h-3 rounded-full bg-[#e3f2fd]">
                                    <div id="barY4" class="h-3 rounded-full bg-[#1e88e5]" style="width:0%"></div>
                                </div>
                                <div id="valY4" class="w-10 text-xs text-right">0</div>
                            </div>
                        </div>
                    </div>
                    <div class="rounded-2xl border p-6">
                        <div class="mb-2 text-sm font-medium text-[#212121]">Top 5 by Grade</div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-sm">
                                <thead class="border-b text-[#293D82]">
                                    <tr class="text-left">
                                        <th class="px-3 py-2">Name</th>
                                        <th class="px-3 py-2">Year Level</th>
                                        <th class="px-3 py-2">Grade</th>
                                        <th class="px-3 py-2">Status</th>
                                    </tr>
                                </thead>
                                <tbody id="topTable"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <script data-page-script="true">
                (function() {
                    function text(id, v) {
                        var el = document.getElementById(id);
                        if (el) el.textContent = String(v);
                    }

                    function setWidth(id, pct) {
                        var el = document.getElementById(id);
                        if (el) el.style.width = String(Math.max(0, Math.min(100, pct))) + '%';
                    }

                    function setVal(id, v) {
                        var el = document.getElementById(id);
                        if (el) el.textContent = String(v);
                    }

                    function byYearLabel(y) {
                        if (y === '1st Year') return 'Y1';
                        if (y === '2nd Year') return 'Y2';
                        if (y === '3rd Year') return 'Y3';
                        if (y === '4th Year') return 'Y4';
                        return '';
                    }

                    function render() {
                        var apps = (window.AppData && Array.isArray(window.AppData.applications)) ? window.AppData.applications : [];
                        var checklist = (window.AppData && Array.isArray(window.AppData.checklist)) ? window.AppData.checklist : [];
                        var totalApps = apps.length;
                        var accepted = apps.filter(function(a) {
                            return a.status === 'Accepted';
                        }).length;
                        var forReview = apps.filter(function(a) {
                            return a.status === 'For Review';
                        }).length;
                        var avgGwa = 0;
                        if (apps.length > 0) {
                            var sum = apps.reduce(function(acc, a) {
                                return acc + (a.gwa || 0);
                            }, 0);
                            avgGwa = (sum / apps.length);
                        }
                        var paid = checklist.filter(function(i) {
                            return !!i.paid;
                        }).length;
                        var unpaid = checklist.length - paid;
                        text('metricTotalApps', totalApps);
                        text('metricAccepted', accepted);
                        text('metricForReview', forReview);
                        text('metricAvgGwa', avgGwa.toFixed(2));
                        text('metricPaid', paid);
                        text('metricUnpaid', unpaid);
                        var y1 = apps.filter(function(a) {
                            return a.yearLevel === '1st Year';
                        }).length;
                        var y2 = apps.filter(function(a) {
                            return a.yearLevel === '2nd Year';
                        }).length;
                        var y3 = apps.filter(function(a) {
                            return a.yearLevel === '3rd Year';
                        }).length;
                        var y4 = apps.filter(function(a) {
                            return a.yearLevel === '4th Year';
                        }).length;
                        var maxY = Math.max(1, y1, y2, y3, y4);
                        setWidth('barY1', y1 * 100 / maxY);
                        setVal('valY1', y1);
                        setWidth('barY2', y2 * 100 / maxY);
                        setVal('valY2', y2);
                        setWidth('barY3', y3 * 100 / maxY);
                        setVal('valY3', y3);
                        setWidth('barY4', y4 * 100 / maxY);
                        setVal('valY4', y4);
                        var sorted = apps.slice().sort(function(a, b) {
                            return (a.grade || 0) < (b.grade || 0) ? -1 : ((a.grade || 0) > (b.grade || 0) ? 1 : 0);
                        });
                        var top = sorted.slice(0, 5);
                        var rows = top.map(function(s) {
                            return '<tr class="border-b hover:bg-gray-50"><td class="px-3 py-2 text-[#212121]">' + s.name + '</td><td class="px-3 py-2 text-[#212121]">' + s.yearLevel + '</td><td class="px-3 py-2 text-[#212121]">' + (s.grade || '') + '</td><td class="px-3 py-2 text-[#212121]">' + (s.status || '') + '</td></tr>';
                        }).join('');
                        var tt = document.getElementById('topTable');
                        if (tt) tt.innerHTML = rows;
                    }
                    render();
                })();
            </script>
        </main>
    </div>
</body>

</html>