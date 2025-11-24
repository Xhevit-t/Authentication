<?php
declare(strict_types=1);
require_once __DIR__ . '/../src/config.php';
require_once __DIR__ . '/../src/functions.php';

logout_user();
redirect('/login.php');
?>