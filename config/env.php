<?php

use Dotenv\Dotenv;

if (!class_exists(Dotenv::class)) {
    $autoloadLocal = __DIR__ . '/vendor/autoload.php';
    $autoloadParent = __DIR__ . '/../vendor/autoload.php';
    if (file_exists($autoloadLocal)) {
        require_once $autoloadLocal;
    } elseif (file_exists($autoloadParent)) {
        require_once $autoloadParent;
    }
}

if (!function_exists('loadEnv')) {
    function loadEnv(): void
    {
        static $loaded = false;
        if ($loaded) {
            return;
        }
        $envPath = dirname(__DIR__);
        if (is_dir($envPath)) {
            try {
                $dotenv = Dotenv::createImmutable($envPath);
                $dotenv->safeLoad();
            } catch (Throwable $e) {
            }
        }
        $loaded = true;
    }
}

if (!function_exists('env_get')) {
    function env_get(string $key, $default = null)
    {
        loadEnv();
        if (array_key_exists($key, $_ENV)) {
            return $_ENV[$key];
        }
        $val = getenv($key);
        return $val !== false ? $val : $default;
    }
}

if (!function_exists('decrypt_secret')) {
    function decrypt_secret(?string $ciphertext, ?string $key = null, ?string $iv = null): ?string
    {
        if (!$ciphertext) {
            return null;
        }
        $key = $key ?? env_get('LOGIN_MASTER_KEY');
        $iv = $iv ?? env_get('SMTP_IV');
        if (!$key || !$iv) {
            return null;
        }
        $cipherRaw = base64_decode($ciphertext, true);
        $ivRaw = base64_decode($iv, true);
        if ($cipherRaw === false || $ivRaw === false) {
            return null;
        }
        $plain = openssl_decrypt($cipherRaw, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $ivRaw);
        return $plain !== false ? $plain : null;
    }
}

if (!function_exists('encrypt_secret')) {
    function encrypt_secret(string $plaintext, ?string $key = null, ?string $iv = null): ?string
    {
        $key = $key ?? env_get('LOGIN_MASTER_KEY');
        $iv = $iv ?? env_get('SMTP_IV');
        if (!$key || !$iv) {
            return null;
        }
        $ivRaw = base64_decode($iv, true);
        if ($ivRaw === false) {
            return null;
        }
        $cipherRaw = openssl_encrypt($plaintext, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $ivRaw);
        if ($cipherRaw === false) {
            return null;
        }
        return base64_encode($cipherRaw);
    }
}

if (!function_exists('smtp_password')) {
    /**
     * Get SMTP password: prefer encrypted value with OS key, fallback to plaintext env.
     */
    function smtp_password(): ?string
    {
        $enc = env_get('SMTP_PASSWORD_ENC');
        $pwd = decrypt_secret($enc);
        if ($pwd) {
            return $pwd;
        }
        $plain = env_get('SMTP_PASSWORD');
        return $plain ?: null;
    }
}
