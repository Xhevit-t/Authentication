<?php
declare(strict_types=1);

function redirect(string $path): never {
    header('Location: ' . $path);
    exit;
}

function post(string $k, ?string $d = null): ?string {
    return isset($_POST[$k]) ? trim((string)$_POST[$k]) : $d;
}

function e(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function valid_email(string $email): bool {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function csrf_token(): string {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_check(): void {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $t = $_POST['_csrf'] ?? '';
        if (!hash_equals($_SESSION['csrf'] ?? '', $t)) {
            http_response_code(419);
            exit('CSRF validation failed.');
        }
    }
}

function generate_verification_code(): string {
    return str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
}

function create_verification_code(int $user_id, string $type): string {
    require_once __DIR__ . '/db.php';

    $code = generate_verification_code();
    $expires_at = date('Y-m-d H:i:s', time() + 900);

    db()->prepare("
        INSERT INTO verification_codes (user_id, code, type, expires_at)
        VALUES (:uid, :code, :type, :exp)
    ")->execute([
        ':uid' => $user_id,
        ':code' => $code,
        ':type' => $type,
        ':exp' => $expires_at
    ]);

    return $code;
}

function verify_code(int $user_id, string $code, string $type): bool {
    require_once __DIR__ . '/db.php';

    $stmt = db()->prepare("
        SELECT id FROM verification_codes
        WHERE user_id = :uid AND code = :code AND type = :type AND expires_at > datetime('now')
        LIMIT 1
    ");
    $stmt->execute([
        ':uid' => $user_id,
        ':code' => $code,
        ':type' => $type
    ]);

    if ($stmt->fetch()) {
        db()->prepare("DELETE FROM verification_codes WHERE user_id = :uid AND type = :type")
            ->execute([':uid' => $user_id, ':type' => $type]);
        return true;
    }
    return false;
}

function login_user(array $u): void {
    session_regenerate_id(true);
    $_SESSION['uid'] = $u['id'];
    $_SESSION['username'] = $u['username'];
}

function current_user_id(): ?int {
    return isset($_SESSION['uid']) ? (int)$_SESSION['uid'] : null;
}

function require_auth(): void {
    if (!current_user_id()) {
        redirect('/login.php');
    }
}

function logout_user(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time()-42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}
