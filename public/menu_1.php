<?php
require_once __DIR__ . '/../src/security.php';
secure_session_start();
require_once __DIR__ . '/../src/auth.php';
require_once __DIR__ . '/../src/db.php';
enforce_auth_for_page(basename(__FILE__));
if (!isset($_SESSION['auth_user_id'])) {
    header('Location: ' . route_url('admin'));
    exit;
}

$announcement_stats = ['total' => 0, 'this_month' => 0, 'this_week' => 0, 'today' => 0];
$dashboard = [
    'totalApps' => 0,
    'accepted' => 0,
    'forReview' => 0,
    'paid' => 0,
    'unpaid' => 0,
    'year' => [
        '1st Year' => 0,
        '2nd Year' => 0,
        '3rd Year' => 0,
        '4th Year' => 0,
    ],
];
try {
    $pdo = get_db_connection();
    $announcement_stats['total'] = $pdo->query("SELECT COUNT(*) as count FROM announcements")->fetch(PDO::FETCH_ASSOC)['count'];
    $announcement_stats['this_month'] = $pdo->query("
        SELECT COUNT(*) as count FROM announcements 
        WHERE YEAR(created_at) = YEAR(NOW()) AND MONTH(created_at) = MONTH(NOW())
    ")->fetch(PDO::FETCH_ASSOC)['count'];
    $announcement_stats['this_week'] = $pdo->query("
        SELECT COUNT(*) as count FROM announcements 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    ")->fetch(PDO::FETCH_ASSOC)['count'];
    $announcement_stats['today'] = $pdo->query("
        SELECT COUNT(*) as count FROM announcements 
        WHERE DATE(created_at) = DATE(NOW())
    ")->fetch(PDO::FETCH_ASSOC)['count'];

    $dashboard['totalApps'] = (int)$pdo->query("SELECT COUNT(*) as c FROM scholarship_applications")->fetch(PDO::FETCH_ASSOC)['c'];
    $dashboard['accepted'] = (int)$pdo->query("SELECT COUNT(*) as c FROM scholarship_applications WHERE status = 'approved'")->fetch(PDO::FETCH_ASSOC)['c'];
    $dashboard['forReview'] = (int)$pdo->query("SELECT COUNT(*) as c FROM scholarship_applications WHERE status = 'pending'")->fetch(PDO::FETCH_ASSOC)['c'];
    $dashboard['paid'] = (int)$pdo->query("SELECT COUNT(*) as c FROM scholarship_applications WHERE status = 'approved' AND is_paid = 1")->fetch(PDO::FETCH_ASSOC)['c'];
    $dashboard['unpaid'] = (int)$pdo->query("SELECT COUNT(*) as c FROM scholarship_applications WHERE status = 'approved' AND (is_paid = 0 OR is_paid IS NULL)")->fetch(PDO::FETCH_ASSOC)['c'];
    $stmt = $pdo->query("SELECT academic_level, COUNT(*) as c FROM scholarship_applications GROUP BY academic_level");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $lvl = (string)($row['academic_level'] ?? '');
        if ($lvl !== '' && array_key_exists($lvl, $dashboard['year'])) {
            $dashboard['year'][$lvl] += (int)($row['c'] ?? 0);
        }
    }

    try {
        $dashboard['totalApps'] += (int)$pdo->query("SELECT COUNT(*) as c FROM scholarship_renewals")->fetch(PDO::FETCH_ASSOC)['c'];
        $dashboard['accepted'] += (int)$pdo->query("SELECT COUNT(*) as c FROM scholarship_renewals WHERE status = 'approved'")->fetch(PDO::FETCH_ASSOC)['c'];
        $dashboard['forReview'] += (int)$pdo->query("SELECT COUNT(*) as c FROM scholarship_renewals WHERE status = 'pending'")->fetch(PDO::FETCH_ASSOC)['c'];
        $dashboard['paid'] += (int)$pdo->query("SELECT COUNT(*) as c FROM scholarship_renewals WHERE status = 'approved' AND is_paid = 1")->fetch(PDO::FETCH_ASSOC)['c'];
        $dashboard['unpaid'] += (int)$pdo->query("SELECT COUNT(*) as c FROM scholarship_renewals WHERE status = 'approved' AND (is_paid = 0 OR is_paid IS NULL)")->fetch(PDO::FETCH_ASSOC)['c'];
        $stmt = $pdo->query("SELECT academic_level, COUNT(*) as c FROM scholarship_renewals GROUP BY academic_level");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $lvl = (string)($row['academic_level'] ?? '');
            if ($lvl !== '' && array_key_exists($lvl, $dashboard['year'])) {
                $dashboard['year'][$lvl] += (int)($row['c'] ?? 0);
            }
        }
    } catch (Exception $e) {
    }
} catch (Exception $e) {
    error_log('Error fetching announcements: ' . $e->getMessage());
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
                <div class="mt-5 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-5">
                    <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-lg p-3 border border-blue-200">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <div class="text-xs text-gray-600 font-medium">Applications</div>
                                <div id="metricTotalApps" class="text-2xl font-bold text-blue-600 mt-0">0</div>
                            </div>
                            <svg class="w-8 h-8 text-blue-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M7 3h10a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2z" />
                                <path d="M8 7h8" />
                                <path d="M8 11h8" />
                                <path d="M8 15h5" />
                            </svg>
                        </div>
                    </div>
                    <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-lg p-3 border border-green-200">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <div class="text-xs text-gray-600 font-medium">Accepted</div>
                                <div id="metricAccepted" class="text-2xl font-bold text-green-600 mt-0">0</div>
                            </div>
                            <svg class="w-8 h-8 text-green-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M20 6L9 17l-5-5" />
                            </svg>
                        </div>
                    </div>
                    <div class="bg-gradient-to-br from-purple-50 to-purple-100 rounded-lg p-3 border border-purple-200">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <div class="text-xs text-gray-600 font-medium">For Review</div>
                                <div id="metricForReview" class="text-2xl font-bold text-purple-600 mt-0">0</div>
                            </div>
                            <svg class="w-8 h-8 text-purple-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M12 8v4" />
                                <path d="M12 16h.01" />
                                <circle cx="12" cy="12" r="9" />
                            </svg>
                        </div>
                    </div>
                    <div class="bg-gradient-to-br from-orange-50 to-orange-100 rounded-lg p-3 border border-orange-200">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <div class="text-xs text-gray-600 font-medium">Paid</div>
                                <div id="metricPaid" class="text-2xl font-bold text-orange-600 mt-0">0</div>
                            </div>
                            <svg class="w-8 h-8 text-orange-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="9" />
                                <path d="M9 12l2 2 4-4" />
                            </svg>
                        </div>
                    </div>
                    <div class="bg-gradient-to-br from-rose-50 to-rose-100 rounded-lg p-3 border border-rose-200">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <div class="text-xs text-gray-600 font-medium">Unpaid</div>
                                <div id="metricUnpaid" class="text-2xl font-bold text-rose-600 mt-0">0</div>
                            </div>
                            <svg class="w-8 h-8 text-rose-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M12 9v4" />
                                <path d="M12 17h.01" />
                                <circle cx="12" cy="12" r="9" />
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-6 rounded-2xl border border-slate-100 bg-white p-6 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-sm font-semibold text-gray-800">Announcement Statistics</div>
                        <div class="mt-1 text-xs text-[#293D82]">Summary of announcements</div>
                    </div>
                </div>
                <div class="mt-4 grid grid-cols-2 gap-3">
                    <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-lg p-3 border border-blue-200">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs text-gray-600 font-medium">Total</p>
                                <p class="text-2xl font-bold text-blue-600 mt-0"><?php echo $announcement_stats['total']; ?></p>
                            </div>
                            <svg class="w-8 h-8 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-lg p-3 border border-green-200">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs text-gray-600 font-medium">This Month</p>
                                <p class="text-2xl font-bold text-green-600 mt-0"><?php echo $announcement_stats['this_month']; ?></p>
                            </div>
                            <svg class="w-8 h-8 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="bg-gradient-to-br from-purple-50 to-purple-100 rounded-lg p-3 border border-purple-200">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs text-gray-600 font-medium">This Week</p>
                                <p class="text-2xl font-bold text-purple-600 mt-0"><?php echo $announcement_stats['this_week']; ?></p>
                            </div>
                            <svg class="w-8 h-8 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 012 12V7a2 2 0 012-2z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="bg-gradient-to-br from-orange-50 to-orange-100 rounded-lg p-3 border border-orange-200">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs text-gray-600 font-medium">Today</p>
                                <p class="text-2xl font-bold text-orange-600 mt-0"><?php echo $announcement_stats['today']; ?></p>
                            </div>
                            <svg class="w-8 h-8 text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
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
                    window.AppData = window.AppData || {};
                    window.AppData.dashboard = <?php echo json_encode($dashboard, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;

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
                        var dash = (window.AppData && window.AppData.dashboard) ? window.AppData.dashboard : null;
                        var totalApps = dash ? (dash.totalApps || 0) : 0;
                        var accepted = dash ? (dash.accepted || 0) : 0;
                        var forReview = dash ? (dash.forReview || 0) : 0;
                        var paid = dash ? (dash.paid || 0) : 0;
                        var unpaid = dash ? (dash.unpaid || 0) : 0;
                        text('metricTotalApps', totalApps);
                        text('metricAccepted', accepted);
                        text('metricForReview', forReview);
                        text('metricPaid', paid);
                        text('metricUnpaid', unpaid);
                        var y = dash && dash.year ? dash.year : {};
                        var y1 = y['1st Year'] || 0;
                        var y2 = y['2nd Year'] || 0;
                        var y3 = y['3rd Year'] || 0;
                        var y4 = y['4th Year'] || 0;
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