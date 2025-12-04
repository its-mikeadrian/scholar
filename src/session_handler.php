<?php

/**
 * Encrypted DB-backed session handler for SteelSync
 */
require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/db.php';

class DbSessionHandler implements SessionHandlerInterface
{
    private $pdo;
    private $key;
    private $cookieName;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $rawKey = env_get('STEELSYNC_SESSION_KEY', env_get('APP_KEY', 'dev-session-key'));
        $this->key = hash('sha256', $rawKey, true); // derive 32-byte key
        $this->cookieName = env_get('SESSION_NAME', 'STEELSYNCSESSID');
    }

    public function open($savePath, $sessionName): bool
    {
        // Nothing required
        return true;
    }

    public function close(): bool
    {
        return true;
    }

    public function read($id): string
    {
        try {
            $stmt = $this->pdo->prepare('SELECT `data`, `expires_at` FROM `sessions` WHERE `id` = ? LIMIT 1');
            $stmt->execute([$id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row) {
                return '';
            }
            if (isset($row['expires_at']) && strtotime($row['expires_at']) <= time()) {
                // expired, clean it up
                $this->destroy($id);
                return '';
            }
            $json = $row['data'];
            if (!$json) return '';
            $payload = json_decode($json, true);
            if (!$payload || !isset($payload['iv'], $payload['tag'], $payload['ct'])) {
                return '';
            }
            $iv = base64_decode($payload['iv']);
            $tag = base64_decode($payload['tag']);
            $ct = base64_decode($payload['ct']);
            $plain = openssl_decrypt($ct, 'aes-256-gcm', $this->key, OPENSSL_RAW_DATA, $iv, $tag);
            if ($plain === false) {
                return '';
            }
            return $plain;
        } catch (Throwable $e) {
            // Fail safe: return empty session
            return '';
        }
    }

    public function write($id, $data): bool
    {
        try {
            $iv = random_bytes(12);
            $tag = '';
            $ct = openssl_encrypt($data, 'aes-256-gcm', $this->key, OPENSSL_RAW_DATA, $iv, $tag);
            if ($ct === false) {
                return false;
            }
            $payload = json_encode([
                'v' => 1,
                'iv' => base64_encode($iv),
                'tag' => base64_encode($tag),
                'ct'  => base64_encode($ct),
            ]);
            $expires = date('Y-m-d H:i:s', time() + $this->currentTtl());
            $now = date('Y-m-d H:i:s');
            $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : null;
            $ua = isset($_SERVER['HTTP_USER_AGENT']) ? substr($_SERVER['HTTP_USER_AGENT'], 0, 255) : null;

            $uid = isset($_SESSION['auth_user_id']) ? (int)$_SESSION['auth_user_id'] : null;
            $stmt = $this->pdo->prepare('INSERT INTO `sessions` (`id`, `user_id`, `data`, `expires_at`, `last_activity`, `ip_address`, `user_agent`) VALUES (?, ?, ?, ?, ?, ?, ?) 
                ON DUPLICATE KEY UPDATE `user_id` = VALUES(`user_id`), `data` = VALUES(`data`), `expires_at` = VALUES(`expires_at`), `last_activity` = VALUES(`last_activity`), `ip_address` = VALUES(`ip_address`), `user_agent` = VALUES(`user_agent`)');
            return $stmt->execute([$id, $uid, $payload, $expires, $now, $ip, $ua]);
        } catch (Throwable $e) {
            return false;
        }
    }

    private function currentTtl(): int
    {
        try {
            $role = isset($_SESSION['auth_role']) ? $_SESSION['auth_role'] : null;
            if (is_string($role) && strtolower($role) === 'student') {
                return (int) env_get('STUDENT_SESSION_LIFETIME_SECONDS', '604800');
            }
            $adminTtl = env_get('ADMIN_SESSION_LIFETIME_SECONDS');
            return (int) ($adminTtl !== null ? $adminTtl : env_get('SESSION_LIFETIME_SECONDS', '1800'));
        } catch (Throwable $e) {
            return (int) env_get('SESSION_LIFETIME_SECONDS', '1800');
        }
    }

    public function destroy($id): bool
    {
        try {
            $stmt = $this->pdo->prepare('DELETE FROM `sessions` WHERE `id` = ?');
            $stmt->execute([$id]);
        } catch (Throwable $e) {
            // ignore
        }
        // Also remove the cookie on destroy to avoid stale client state
        if (isset($_COOKIE[$this->cookieName])) {
            $params = session_get_cookie_params();
            setcookie($this->cookieName, '', time() - 3600, $params['path'] ?? '/', $params['domain'] ?? '', $params['secure'] ?? false, $params['httponly'] ?? true);
        }
        return true;
    }

    public function gc($max_lifetime): int|false
    {
        try {
            $stmt = $this->pdo->prepare('DELETE FROM `sessions` WHERE `expires_at` < NOW()');
            $stmt->execute();
            return $stmt->rowCount();
        } catch (Throwable $e) {
            return false;
        }
    }
}
