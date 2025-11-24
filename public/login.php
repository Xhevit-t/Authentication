<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/config.php';
require_once __DIR__ . '/../src/functions.php';
require_once __DIR__ . '/../src/db.php';

csrf_check();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_input = post('username', '');
    $password   = post('password', '');

    if ($user_input === '' || $password === '') {
        $errors[] = 'Please enter username/email and password.';
    } else {
        $stmt = db()->prepare("
            SELECT id, username, email, password_hash, is_verified
            FROM users
            WHERE username = :u OR email = :u
            LIMIT 1
        ");
        $stmt->execute([':u' => $user_input]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            if (!(int)$user['is_verified']) {
                $errors[] = 'Please verify your email before logging in.';
            } else {
                if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
                    $new = password_hash($password, PASSWORD_DEFAULT);
                    db()->prepare("UPDATE users SET password_hash = :p WHERE id = :id")
                        ->execute([':p' => $new, ':id' => $user['id']]);
                }

                $code = create_verification_code((int)$user['id'], 'login');
                send_verification_email($user['email'], $code, 'login');

                $_SESSION['login_user_id'] = (int)$user['id'];

                redirect('/verify-login.php');
            }
        } else {
            $errors[] = 'Invalid credentials.';
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Login</title>
    <link rel="stylesheet" href="/styles.css?v=10">
</head>
<body>
<div class="page">
    <div class="card">
        <div class="card-inner">
            <h1>Login Into Your Account</h1>

            <?php if ($errors): ?>
                <div class="error">
                    <?php foreach ($errors as $e) echo '<div>'.e($e).'</div>'; ?>
                </div>
            <?php endif; ?>

            <form method="post" action="/login.php" novalidate>
                <input type="hidden" name="_csrf" value="<?php echo e(csrf_token()); ?>">

                <div class="form-row">
                    <div class="label">Email or Username</div>
                    <input class="input" type="text" name="username" autocomplete="username" required>
                </div>

                <div class="form-row">
                    <div class="label">Password</div>
                    <input class="input" type="password" name="password" autocomplete="current-password" required>
                </div>

                <button class="btn" type="submit">Login</button>
            </form>

            <div class="actions">
                <a href="/register.php">Create Account</a>
            </div>
        </div>
    </div>
</div>
</body>
</html>
