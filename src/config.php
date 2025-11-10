<?php
declare(strict_types=1);

ini_set('session.cookie_httponly', '1');
ini_set('session.use_strict_mode', '1');
ini_set('session.cookie_samesite', 'Lax');
//ini_set('session.cookie_secure', '1');
session_name('ib_auth_sess');
session_start();

define('DB_PATH', __DIR__ . '/../data/app.sqlite');
$dir = dirname(DB_PATH);
if (!is_dir($dir)) { mkdir($dir, 0755, true); }
