<?php
declare(strict_types=1);
require_once __DIR__ . '/../src/config.php';
require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/functions.php';

csrf_check();
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = post('username', '');
    $password = post('password', '');
    if ($username === '' || $password === '') {
        $errors[] = 'Please enter username and password.';
    } else {
        $stmt = db()->prepare('SELECT id, username, password_hash FROM users WHERE username = :u OR email = :u LIMIT 1');
        $stmt->execute([':u'=>$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user && password_verify($password, $user['password_hash'])) {
            if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
                $new = password_hash($password, PASSWORD_DEFAULT);
                db()->prepare('UPDATE users SET password_hash = :p WHERE id = :id')->execute([':p'=>$new, ':id'=>$user['id']]);
            }
            login_user($user);
            redirect('/dashboard.php');
        } else {
            $errors[] = 'Invalid credentials.';
        }
    }
}
function check_rate_limit(string $identifier, int $max_attempts = 5, int $window = 900): bool {
    $key = "login_attempt_" . md5($identifier);
    $attempts = $_SESSION[$key] ?? ['count' => 0, 'reset_at' => time()];

    if (time() > $attempts['reset_at'] + $window) {
        $attempts = ['count' => 0, 'reset_at' => time()];
    }

    if ($attempts['count'] >= $max_attempts) return false;

    $attempts['count']++;
    $_SESSION[$key] = $attempts;
    return true;
}
function get_remaining_attempts(string $identifier, int $max_attempts = 5): int {
    $key = "login_attempt_" . md5($identifier);
    $attempts = $_SESSION[$key] ?? ['count' => 0];
    return max(0, $max_attempts - $attempts['count']);
}

?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Login</title>
    <link rel="stylesheet" href="/styles.css?v=6">
</head>
<body>
<div class="page">
    <div class="card">
        <div class="card-inner">
            <h1>Login Into Your Account</h1>
            <?php if ($errors): ?>
                <div class="error"><?php foreach ($errors as $e) echo "<div>".e($e)."</div>"; ?></div>
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
