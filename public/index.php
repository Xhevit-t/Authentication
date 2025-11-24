<?php
declare(strict_types=1);
require_once __DIR__ . '/../src/config.php';
require_once __DIR__ . '/../src/functions.php';

if (current_user_id()) {
    redirect('/dashboard.php');
} else {
    redirect('/login.php');
}
