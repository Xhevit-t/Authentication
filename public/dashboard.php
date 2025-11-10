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
    <link rel="stylesheet" href="/styles.css">
</head>
<body>
<div class="container">
    <h1>Welcome, <?php echo e($_SESSION['uname']); ?>!</h1>
    <p>You are logged in.</p>
    <p><a href="/logout.php">Log out</a></p>
</div>
</body>
</html>
