<?php
declare(strict_types=1);
function redirect(string $path): never { header('Location: ' . $path); exit; }
function post(string $k, ?string $d=null): ?string { return isset($_POST[$k]) ? trim((string)$_POST[$k]) : $d; }
function e(string $s): string { return htmlspecialchars($s, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8'); }
function valid_email(string $email): bool { return filter_var($email, FILTER_VALIDATE_EMAIL) !== false; }
function csrf_token(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function csrf_check(): void {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $t = $_POST['_csrf'] ?? '';
        if (!hash_equals($_SESSION['csrf'] ?? '', $t)) { http_response_code(419); echo 'CSRF validation failed.'; exit; }
    }
}
function login_user(array $u): void {
     session_regenerate_id(true);
    $_SESSION['uid']=$u['id']; $_SESSION['uname']=$u['username'];
}
function current_user_id(): ?int { return isset($_SESSION['uid']) ? (int)$_SESSION['uid'] : null; }
function require_auth(): void { if (!current_user_id()) redirect('/login.php'); }
