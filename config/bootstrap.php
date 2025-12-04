<?php

declare(strict_types=1);

use Dotenv\Dotenv;

// Composer autoload
require_once __DIR__ . '/../vendor/autoload.php';

// Load environment variables if present
$envRoot = dirname(__DIR__);
if (is_file($envRoot . '/.env')) {
    $dotenv = Dotenv::createImmutable($envRoot);
    $dotenv->safeLoad();
}


// Helper functions for URL building when app runs in a subdirectory
if (!function_exists('app_base_path')) {
    function app_base_path(): string
    {
        // Prefer deriving base path from the URL path and anchor it to /public
        // so direct access to nested scripts still produces a stable base.
        $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
        if ($scriptName !== '') {
            $publicPos = strpos($scriptName, '/public/');
            if ($publicPos !== false) {
                $base = substr($scriptName, 0, $publicPos + strlen('/public'));
                return rtrim($base, '/');
            }
        }

        // Compute relative to document root as a secondary strategy
        $docRoot = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? ''), '/');
        $scriptFilename = str_replace('\\', '/', $_SERVER['SCRIPT_FILENAME'] ?? '');
        if ($docRoot !== '' && $scriptFilename !== '' && strpos($scriptFilename, $docRoot) === 0) {
            $rel = substr(dirname($scriptFilename), strlen($docRoot));
            $rel = '/' . trim(str_replace('\\', '/', $rel), '/');
            return $rel === '/' ? '' : $rel;
        }

        // Last fallback: directory of the script name
        $script = $_SERVER['SCRIPT_NAME'] ?? '';
        $dir = str_replace('\\', '/', dirname($script));
        $dir = rtrim($dir, '/');
        return $dir === '/' ? '' : $dir;
    }
}

if (!function_exists('asset_url')) {
    function asset_url(string $path): string
    {
        return app_base_path() . '/' . ltrim($path, '/');
    }
}

if (!function_exists('route_url')) {
    function route_url(string $path): string
    {
        return app_base_path() . '/' . ltrim($path, '/');
    }
}
