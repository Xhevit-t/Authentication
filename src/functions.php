<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

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

function validate_password(string $pw): array {
    $errors = [];

    if (strlen($pw) < 8) {
        $errors[] = 'Password must be at least 8 characters.';
    }
    if (!preg_match('/[A-Z]/', $pw)) {
        $errors[] = 'Password must contain at least one uppercase letter.';
    }
    if (!preg_match('/[a-z]/', $pw)) {
        $errors[] = 'Password must contain at least one lowercase letter.';
    }
    if (!preg_match('/[0-9]/', $pw)) {
        $errors[] = 'Password must contain at least one number.';
    }
    if (!preg_match('/[^A-Za-z0-9]/', $pw)) {
        $errors[] = 'Password must contain at least one special character (e.g. ! @ # $ %).';
    }

    return $errors;
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
    $expires_at = date('Y-m-d H:i:s', time() + 900); // 15 min

    db()->prepare("
        INSERT INTO verification_codes (user_id, code, type, expires_at)
        VALUES (:uid, :code, :type, :exp)
    ")->execute([
        ':uid'  => $user_id,
        ':code' => $code,
        ':type' => $type,
        ':exp'  => $expires_at,
    ]);

    return $code;
}
function verify_code(int $user_id, string $code, string $type): bool {
    require_once __DIR__ . '/db.php';

    $stmt = db()->prepare("
        SELECT id FROM verification_codes
        WHERE user_id = :uid
          AND code    = :code
          AND type    = :type
          AND expires_at > datetime('now')
        LIMIT 1
    ");
    $stmt->execute([
        ':uid'  => $user_id,
        ':code' => $code,
        ':type' => $type,
    ]);

    if ($stmt->fetch()) {
        db()->prepare("DELETE FROM verification_codes WHERE user_id = :uid AND type = :type")
            ->execute([':uid' => $user_id, ':type' => $type]);
        return true;
    }

    return false;
}

function send_verification_email(string $email, string $code, string $type = 'registration'): bool {
    $mail = new PHPMailer(true);

    $subject = ($type === 'login')
        ? 'Your 2FA Login Code'
        : 'Email Verification Code';

    $body = "
        <h2>Your authentication code</h2>
        <p style='font-size:20px;font-weight:bold;'>$code</p>
        <p>This code expires in 15 minutes.</p>
    ";

    try {
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = SMTP_PORT;

        $mail->setFrom(SMTP_USER, 'Auth System');
        $mail->addAddress($email);

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;

        return $mail->send();
    } catch (Exception $e) {
        error_log('Mail error: ' . $mail->ErrorInfo);
        return false;
    }
}

function login_user(array $u): void {
    session_regenerate_id(true);
    $_SESSION['uid']      = $u['id'];
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
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}
