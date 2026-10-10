<?php
require __DIR__ . '/config.php';

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');

$action = $_REQUEST['action'] ?? '';
$pdo    = db();

function clean(string $s, int $max): string
{
    return mb_substr(trim($s), 0, $max);
}

function must_post(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        json_out(['error' => 'POST required'], 405);
    }
}

function user_session(PDO $pdo, string $token): array
{
    $st = $pdo->prepare('SELECT * FROM chat_sessions WHERE token = ?');
    $st->execute([$token]);
    $s = $st->fetch();
    if (!$s) {
        json_out(['error' => 'Session not found'], 404);
    }
    return $s;
}

function admin_session(PDO $pdo, int $id): array
{
    $st = $pdo->prepare('SELECT * FROM chat_sessions WHERE id = ?');
    $st->execute([$id]);
    $s = $st->fetch();
    if (!$s) {
        json_out(['error' => 'Session not found'], 404);
    }
    return $s;
}

try {
    switch ($action) {

        /* ===================== VISITOR ACTIONS ===================== */

        // Contact form submit: creates the chat and its first message
        case 'start':
            must_post();
            if (!empty($_POST['website'])) {            // honeypot: bots fill this hidden field
                json_out(['ok' => true]);
            }
            $first = clean($_POST['first_name'] ?? '', 60);
            $last  = clean($_POST['last_name'] ?? '', 60);
            $email = clean($_POST['email'] ?? '', 150);
            $msg   = clean($_POST['message'] ?? '', 2000);

            if ($first === '' || $last === '' || $msg === '') {
                json_out(['error' => 'Please fill in all fields.'], 422);
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                json_out(['error' => 'Please enter a valid email address.'], 422);
            }

            $token = bin2hex(random_bytes(16));
            $pdo->beginTransaction();
            $pdo->prepare(
                'INSERT INTO chat_sessions (token, first_name, last_name, email, user_last_seen, last_activity, created_at)
                 VALUES (?, ?, ?, ?, NOW(), NOW(), NOW())'
            )->execute([$token, $first, $last, $email]);
            $sid = (int) $pdo->lastInsertId();
            $pdo->prepare(
                "INSERT INTO chat_messages (session_id, sender, message, created_at) VALUES (?, 'user', ?, NOW())"
            )->execute([$sid, $msg]);
            $pdo->commit();

            json_out(['token' => $token]);

        // Visitor polls for new messages
        case 'user_fetch':
            $s     = user_session($pdo, $_GET['token'] ?? '');
            $after = (int) ($_GET['after'] ?? 0);

            $pdo->prepare('UPDATE chat_sessions SET user_last_seen = NOW() WHERE id = ?')->execute([$s['id']]);

            $st = $pdo->prepare(
                'SELECT id, sender, message, created_at FROM chat_messages
                 WHERE session_id = ? AND id > ? ORDER BY id ASC LIMIT 200'
            );
            $st->execute([$s['id'], $after]);
            json_out(['messages' => $st->fetchAll(), 'status' => $s['status']]);

        // Visitor sends a message
        case 'user_send':
            must_post();
            $s   = user_session($pdo, $_POST['token'] ?? '');
            $msg = clean($_POST['message'] ?? '', 2000);
            if ($msg === '') {
                json_out(['error' => 'Message is empty.'], 422);
            }
            if ($s['status'] !== 'open') {
                json_out(['error' => 'This chat is closed.'], 403);
            }
            $pdo->prepare(
                "INSERT INTO chat_messages (session_id, sender, message, created_at) VALUES (?, 'user', ?, NOW())"
            )->execute([$s['id'], $msg]);
            $pdo->prepare('UPDATE chat_sessions SET last_activity = NOW(), user_last_seen = NOW() WHERE id = ?')
                ->execute([$s['id']]);
            json_out(['ok' => true]);

        /* ====================== ADMIN ACTIONS ====================== */

        // List of conversations with unread counts
        case 'admin_sessions':
            require_admin_api();
            $rows = $pdo->query(
                "SELECT s.id, s.first_name, s.last_name, s.email, s.status, s.last_activity,
                        (s.user_last_seen IS NOT NULL AND s.user_last_seen > (NOW() - INTERVAL 10 SECOND)) AS online,
                        (SELECT COUNT(*) FROM chat_messages m
                          WHERE m.session_id = s.id AND m.sender = 'user' AND m.is_read = 0) AS unread,
                        (SELECT m2.message FROM chat_messages m2
                          WHERE m2.session_id = s.id ORDER BY m2.id DESC LIMIT 1) AS last_message
                 FROM chat_sessions s
                 ORDER BY s.last_activity DESC
                 LIMIT 100"
            )->fetchAll();
            json_out(['sessions' => $rows]);

        // Admin loads messages of one conversation (and marks the user's messages as read)
        case 'admin_fetch':
            require_admin_api();
            $s     = admin_session($pdo, (int) ($_GET['session_id'] ?? 0));
            $after = (int) ($_GET['after'] ?? 0);

            $st = $pdo->prepare(
                'SELECT id, sender, message, created_at FROM chat_messages
                 WHERE session_id = ? AND id > ? ORDER BY id ASC LIMIT 200'
            );
            $st->execute([$s['id'], $after]);
            $messages = $st->fetchAll();

            $pdo->prepare("UPDATE chat_messages SET is_read = 1 WHERE session_id = ? AND sender = 'user' AND is_read = 0")
                ->execute([$s['id']]);

            $online = $s['user_last_seen'] && (time() - strtotime($s['user_last_seen']) <= 10);
            json_out([
                'messages' => $messages,
                'status'   => $s['status'],
                'online'   => $online,
            ]);

        // Admin replies
        case 'admin_send':
            require_admin_api();
            check_csrf();
            must_post();
            $s   = admin_session($pdo, (int) ($_POST['session_id'] ?? 0));
            $msg = clean($_POST['message'] ?? '', 2000);
            if ($msg === '') {
                json_out(['error' => 'Message is empty.'], 422);
            }
            $pdo->prepare(
                "INSERT INTO chat_messages (session_id, sender, message, is_read, created_at) VALUES (?, 'admin', ?, 1, NOW())"
            )->execute([$s['id'], $msg]);
            $pdo->prepare('UPDATE chat_sessions SET last_activity = NOW() WHERE id = ?')->execute([$s['id']]);
            json_out(['ok' => true]);

        // Admin closes or reopens a conversation
        case 'admin_status':
            require_admin_api();
            check_csrf();
            must_post();
            $s      = admin_session($pdo, (int) ($_POST['session_id'] ?? 0));
            $status = ($_POST['status'] ?? '') === 'closed' ? 'closed' : 'open';
            $pdo->prepare('UPDATE chat_sessions SET status = ? WHERE id = ?')->execute([$status, $s['id']]);
            json_out(['ok' => true]);

        default:
            json_out(['error' => 'Unknown action'], 400);
    }
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log($e->getMessage());
    json_out(['error' => 'Server error'], 500);
}
