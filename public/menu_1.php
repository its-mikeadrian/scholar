<?php
require_once __DIR__ . '/../src/security.php';
secure_session_start();
require_once __DIR__ . '/../src/auth.php';
enforce_auth_for_page(basename(__FILE__));
if (!isset($_SESSION['auth_user_id'])) {
    header('Location: ' . route_url('admin'));
    exit;
}
// Load metrics from database
require_once __DIR__ . '/../src/db.php';
$totalApps = 0;
$accepted = 0;
$forReview = 0;
$paid = 0;
$unpaid = 0;
$y1 = $y2 = $y3 = $y4 = 0;
try {
    $pdo = get_db_connection();
    $totalApps = (int) $pdo->query("SELECT COUNT(*) FROM scholarship_applications")->fetchColumn();
    $accepted = (int) $pdo->query("SELECT COUNT(*) FROM scholarship_applications WHERE status = 'approved'")->fetchColumn();
    $forReview = (int) $pdo->query("SELECT COUNT(*) FROM scholarship_applications WHERE status = 'pending'")->fetchColumn();
    // paid field is not present in DB; default to 0 and treat unpaid = approved
    $paid = 0;
    $unpaid = max(0, $accepted - $paid);
    $levels = $pdo->query("SELECT academic_level, COUNT(*) AS c FROM scholarship_applications GROUP BY academic_level")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($levels as $lv) {
        $lvl = trim((string)($lv['academic_level'] ?? ''));
        $count = (int)($lv['c'] ?? 0);
        if (stripos($lvl, '1') !== false) $y1 = $count;
        else if (stripos($lvl, '2') !== false) $y2 = $count;
        else if (stripos($lvl, '3') !== false) $y3 = $count;
        else if (stripos($lvl, '4') !== false) $y4 = $count;
    }
} catch (Exception $e) {
    error_log('Error loading dashboard metrics: ' . $e->getMessage());
}

// compute bar widths
$maxY = max(1, $y1, $y2, $y3, $y4);
$w1 = ($y1 * 100) / $maxY;
$w2 = ($y2 * 100) / $maxY;
$w3 = ($y3 * 100) / $maxY;
$w4 = ($y4 * 100) / $maxY;
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
                                <div id="metricTotalApps" class="mt-1 text-2xl font-semibold text-[#212121]"><?php echo htmlspecialchars((string)$totalApps, ENT_QUOTES, 'UTF-8'); ?></div>
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
                                <div id="metricAccepted" class="mt-1 text-2xl font-semibold text-[#212121]"><?php echo htmlspecialchars((string)$accepted, ENT_QUOTES, 'UTF-8'); ?></div>
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
                                <div id="metricForReview" class="mt-1 text-2xl font-semibold text-[#212121]"><?php echo htmlspecialchars((string)$forReview, ENT_QUOTES, 'UTF-8'); ?></div>
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
                                <div id="metricPaid" class="mt-1 text-2xl font-semibold text-[#212121]"><?php echo htmlspecialchars((string)$paid, ENT_QUOTES, 'UTF-8'); ?></div>
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
                                <div id="metricUnpaid" class="mt-1 text-2xl font-semibold text-[#212121]"><?php echo htmlspecialchars((string)$unpaid, ENT_QUOTES, 'UTF-8'); ?></div>
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
                            <div id="barY1" class="h-3 rounded-full bg-[#1e88e5]" style="width:<?php echo htmlspecialchars((string)$w1, ENT_QUOTES, 'UTF-8'); ?>%"></div>
                        </div>
                        <div id="valY1" class="w-10 text-xs text-right text-[#212121]"><?php echo htmlspecialchars((string)$y1, ENT_QUOTES, 'UTF-8'); ?></div>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="w-24 text-xs text-[#293D82]">2nd Year</div>
                        <div class="flex-1 h-3 rounded-full bg-[#e3f2fd]">
                            <div id="barY2" class="h-3 rounded-full bg-[#1e88e5]" style="width:<?php echo htmlspecialchars((string)$w2, ENT_QUOTES, 'UTF-8'); ?>%"></div>
                        </div>
                        <div id="valY2" class="w-10 text-xs text-right text-[#212121]"><?php echo htmlspecialchars((string)$y2, ENT_QUOTES, 'UTF-8'); ?></div>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="w-24 text-xs text-[#293D82]">3rd Year</div>
                        <div class="flex-1 h-3 rounded-full bg-[#e3f2fd]">
                            <div id="barY3" class="h-3 rounded-full bg-[#1e88e5]" style="width:<?php echo htmlspecialchars((string)$w3, ENT_QUOTES, 'UTF-8'); ?>%"></div>
                        </div>
                        <div id="valY3" class="w-10 text-xs text-right text-[#212121]"><?php echo htmlspecialchars((string)$y3, ENT_QUOTES, 'UTF-8'); ?></div>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="w-24 text-xs text-[#293D82]">4th Year</div>
                        <div class="flex-1 h-3 rounded-full bg-[#e3f2fd]">
                            <div id="barY4" class="h-3 rounded-full bg-[#1e88e5]" style="width:<?php echo htmlspecialchars((string)$w4, ENT_QUOTES, 'UTF-8'); ?>%"></div>
                        </div>
                        <div id="valY4" class="w-10 text-xs text-right text-[#212121]"><?php echo htmlspecialchars((string)$y4, ENT_QUOTES, 'UTF-8'); ?></div>
                    </div>
                </div>
            </div>

            <script data-page-script="true">
                (function(){ /* metrics are rendered server-side */ })();
            </script>
        </main>
    </div>
</body>

</html>
