<?php
declare(strict_types=1);
require_once __DIR__ . '/../src/config.php';
require_once __DIR__ . '/../src/functions.php';

require_auth();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Dashboard</title>
    <link rel="stylesheet" href="/styles.css?v=8">
</head>
<body>
<div class="page">
    <div class="card">
        <div class="card-inner">
            <h1>Welcome, <?php echo e($_SESSION['username']); ?>!</h1>
            <p>You are logged in with two-factor authentication.</p>
            <p><a href="/logout.php">Log out</a></p>
        </div>
    </div>
</div>
</body>
</html>
