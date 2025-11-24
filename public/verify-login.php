<?php
declare(strict_types=1);
require_once __DIR__ . '/../src/config.php';
require_once __DIR__ . '/../src/functions.php';
require_once __DIR__ . '/../src/db.php';

csrf_check();

if (!isset($_SESSION['login_user_id'], $_SESSION['login_code'], $_SESSION['login_username'], $_SESSION['login_email'])) {
    redirect('/login.php');
}

$user_id = (int)$_SESSION['login_user_id'];
$username = $_SESSION['login_username'];
$email = $_SESSION['login_email'];
$shown_code = $_SESSION['login_code'];

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = post('code','');
    if ($code === '') {
        $errors[] = 'Please enter the 2FA code.';
    } else {
        if (verify_code($user_id, $code, 'login')) {
            $stmt = db()->prepare("SELECT id, username FROM users WHERE id = :id LIMIT 1");
            $stmt->execute([':id'=>$user_id]);
            $user = $stmt->fetch();

            if ($user) {
                login_user($user);
                unset($_SESSION['login_user_id'], $_SESSION['login_code'], $_SESSION['login_username'], $_SESSION['login_email']);
                redirect('/dashboard.php');
            } else {
                $errors[] = 'User not found.';
            }
        } else {
            $errors[] = 'Invalid or expired 2FA code.';
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Two-Factor Authentication</title>
    <link rel="stylesheet" href="/styles.css?v=8">
</head>
<body>
<div class="page">
    <div class="card">
        <div class="card-inner">
            <h1>Enter 2FA Code</h1>
            <?php if ($errors): ?>
                <div class="error">
                    <?php foreach ($errors as $e) echo '<div>'.e($e).'</div>'; ?>
                </div>
            <?php endif; ?>
            <p>We have “sent” a 2FA code to: <strong><?php echo e($email); ?></strong></p>
            <p><em>For lab testing, your code is: <?php echo e($shown_code); ?></em></p>
            <form method="post" action="/verify-login.php" novalidate>
                <input type="hidden" name="_csrf" value="<?php echo e(csrf_token()); ?>">
                <div class="form-row">
                    <div class="label">2FA Code</div>
                    <input class="input" type="text" name="code" placeholder="000000" required>
                </div>
                <button class="btn" type="submit">Verify</button>
            </form>
        </div>
    </div>
</div>
</body>
</html>
