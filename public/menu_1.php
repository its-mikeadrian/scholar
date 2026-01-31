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
                    <div>
                        <h2 class="text-xl font-semibold text-[#212121]">Dashboard Analytics</h2>
                        <div class="mt-1 text-xs text-[#293D82]">Overview of applications and payouts</div>
                    </div>
                </div>
                <div class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-3 lg:grid-cols-5">
                    <div class="rounded-2xl border border-slate-100 bg-[#f8fbff] p-4">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <div class="text-xs text-[#293D82]">Applications</div>
                                <div id="metricTotalApps" class="mt-1 text-2xl font-semibold text-[#212121]">0</div>
                            </div>
                            <div class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-[#e3f2fd] text-[#1e88e5]">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M7 3h10a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2z" />
                                    <path d="M8 7h8" />
                                    <path d="M8 11h8" />
                                    <path d="M8 15h5" />
                                </svg>
                            </div>
                        </div>
                    </div>
                    <div class="rounded-2xl border border-slate-100 bg-[#f8fbff] p-4">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <div class="text-xs text-[#293D82]">Accepted</div>
                                <div id="metricAccepted" class="mt-1 text-2xl font-semibold text-[#212121]">0</div>
                            </div>
                            <div class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-[#e3f2fd] text-[#1e88e5]">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M20 6L9 17l-5-5" />
                                </svg>
                            </div>
                        </div>
                    </div>
                    <div class="rounded-2xl border border-slate-100 bg-[#f8fbff] p-4">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <div class="text-xs text-[#293D82]">For Review</div>
                                <div id="metricForReview" class="mt-1 text-2xl font-semibold text-[#212121]">0</div>
                            </div>
                            <div class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-[#e3f2fd] text-[#1e88e5]">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M12 8v4" />
                                    <path d="M12 16h.01" />
                                    <circle cx="12" cy="12" r="9" />
                                </svg>
                            </div>
                        </div>
                    </div>
                    <div class="rounded-2xl border border-slate-100 bg-[#f8fbff] p-4">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <div class="text-xs text-[#293D82]">Paid</div>
                                <div id="metricPaid" class="mt-1 text-2xl font-semibold text-[#212121]">0</div>
                            </div>
                            <div class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-[#e3f2fd] text-[#1e88e5]">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="9" />
                                    <path d="M9 12l2 2 4-4" />
                                </svg>
                            </div>
                        </div>
                    </div>
                    <div class="rounded-2xl border border-slate-100 bg-[#f8fbff] p-4">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <div class="text-xs text-[#293D82]">Unpaid</div>
                                <div id="metricUnpaid" class="mt-1 text-2xl font-semibold text-[#212121]">0</div>
                            </div>
                            <div class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-[#e3f2fd] text-[#1e88e5]">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M12 9v4" />
                                    <path d="M12 17h.01" />
                                    <circle cx="12" cy="12" r="9" />
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-6 rounded-2xl border border-slate-100 bg-white p-6 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-sm font-medium text-[#212121]">Year Level Distribution</div>
                        <div class="mt-1 text-xs text-[#293D82]">Counts by year level</div>
                    </div>
                </div>
                <div class="mt-4 space-y-3">
                    <div class="flex items-center gap-3">
                        <div class="w-24 text-xs text-[#293D82]">1st Year</div>
                        <div class="flex-1 h-3 rounded-full bg-[#e3f2fd]">
                            <div id="barY1" class="h-3 rounded-full bg-[#1e88e5]" style="width:0%"></div>
                        </div>
                        <div id="valY1" class="w-10 text-xs text-right text-[#212121]">0</div>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="w-24 text-xs text-[#293D82]">2nd Year</div>
                        <div class="flex-1 h-3 rounded-full bg-[#e3f2fd]">
                            <div id="barY2" class="h-3 rounded-full bg-[#1e88e5]" style="width:0%"></div>
                        </div>
                        <div id="valY2" class="w-10 text-xs text-right text-[#212121]">0</div>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="w-24 text-xs text-[#293D82]">3rd Year</div>
                        <div class="flex-1 h-3 rounded-full bg-[#e3f2fd]">
                            <div id="barY3" class="h-3 rounded-full bg-[#1e88e5]" style="width:0%"></div>
                        </div>
                        <div id="valY3" class="w-10 text-xs text-right text-[#212121]">0</div>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="w-24 text-xs text-[#293D82]">4th Year</div>
                        <div class="flex-1 h-3 rounded-full bg-[#e3f2fd]">
                            <div id="barY4" class="h-3 rounded-full bg-[#1e88e5]" style="width:0%"></div>
                        </div>
                        <div id="valY4" class="w-10 text-xs text-right text-[#212121]">0</div>
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
                        var paid = checklist.filter(function(i) {
                            return !!i.paid;
                        }).length;
                        var unpaid = checklist.length - paid;
                        text('metricTotalApps', totalApps);
                        text('metricAccepted', accepted);
                        text('metricForReview', forReview);
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
                    }
                    render();
                })();
            </script>
        </main>
    </div>
</body>

</html>
