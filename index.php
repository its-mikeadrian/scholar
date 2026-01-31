<?php
declare(strict_types=1);

$script = $_SERVER['SCRIPT_NAME'] ?? '';
$base = rtrim(str_replace('\\', '/', dirname($script)), '/');
$target = $base . '/public/';
if (!empty($_SERVER['QUERY_STRING'])) {
    $target .= '?' . $_SERVER['QUERY_STRING'];
}

header('Location: ' . $target, true, 302);
exit;
