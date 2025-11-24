<?php
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'secure'   => false,
    'httponly' => true,
    'samesite' => 'Lax',
]);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('DB_PATH', __DIR__ . '/../data/app.sqlite');

define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_USER', 'zini.xhevit@gmail.com');
define('SMTP_PASS', 'zaic titk zomb lear');
define('SMTP_PORT', 587);
