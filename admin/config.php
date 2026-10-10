<?php
// ---------- Database settings: change these ----------
const DB_HOST = 'localhost';
const DB_NAME = 'torquepoint_db';
const DB_USER = 'root';
const DB_PASS = '';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $pdo = new PDO(
            'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]
        );
    }
    return $pdo;
}

function json_out(array $data, int $code = 200): void
{
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * IMPORTANT: connect this to YOUR admin login.
 * Change the session key below to whatever your admin site sets after login
 * (for example $_SESSION['admin_id'] or $_SESSION['user_role'] === 'admin').
 */
function is_admin(): bool
{
    static $cached = null;
    if ($cached !== null) {
        return $cached;
    }
    $cached = false;

    if (!empty($_SESSION['user_email'])) {
        // Change 'isAdmin' to 'is_admin' to match your database and dashboard.php
        $st = db()->prepare('SELECT is_admin FROM users WHERE email = ? LIMIT 1');
        $st->execute([$_SESSION['user_email']]);
        $cached = ((int) $st->fetchColumn() === 1);
    }
    return $cached;
}

function require_admin_api(): void
{
    if (!is_admin()) {
        json_out(['error' => 'Unauthorized'], 403);
    }
}

function check_csrf(): void
{
    $sent = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $sent)) {
        json_out(['error' => 'Invalid CSRF token'], 403);
    }
}
