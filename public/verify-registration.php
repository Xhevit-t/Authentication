<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/config.php';
require_once __DIR__ . '/../src/functions.php';
require_once __DIR__ . '/../src/db.php';

csrf_check();

if (!isset($_SESSION['reg_user_id'], $_SESSION['reg_email'])) {
    redirect('/register.php');
}

$user_id = (int)$_SESSION['reg_user_id'];
$email   = $_SESSION['reg_email'];

$errors = [];
$ok = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = post('code', '');

    if ($code === '') {
        $errors[] = 'Please enter the verification code.';
    } else {
        if (verify_code($user_id, $code, 'registration')) {
            db()->prepare("UPDATE users SET is_verified = 1 WHERE id = :id")
                ->execute([':id' => $user_id]);

            unset($_SESSION['reg_user_id'], $_SESSION['reg_email']);

            $ok = true;
        } else {
            $errors[] = 'Invalid or expired verification code.';
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Verify Email</title>
    <link rel="stylesheet" href="/styles.css?v=10">
</head>
<body>
<div class="page">
    <div class="card">
        <div class="card-inner">
            <h1>Verify Your Email</h1>

            <?php if ($ok): ?>
                <div class="success">
                    Email verified! You can now <a href="/login.php">log in</a>.
                </div>
            <?php else: ?>

                <?php if ($errors): ?>
                    <div class="error">
                        <?php foreach ($errors as $e) echo '<div>'.e($e).'</div>'; ?>
                    </div>
                <?php endif; ?>

                <p>We have sent a verification code to <strong><?php echo e($email); ?></strong>.</p>

                <form method="post" action="/verify-registration.php" novalidate>
                    <input type="hidden" name="_csrf" value="<?php echo e(csrf_token()); ?>">
                    <div class="form-row">
                        <div class="label">Verification Code</div>
                        <input class="input" type="text" name="code" placeholder="000000" required>
                    </div>
                    <button class="btn" type="submit">Verify</button>
                </form>

            <?php endif; ?>
        </div>
    </div>
</div>
</body>
</html>
