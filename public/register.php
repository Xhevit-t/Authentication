<?php
declare(strict_types=1);
require_once __DIR__ . '/../src/config.php';
require_once __DIR__ . '/../src/functions.php';
require_once __DIR__ . '/../src/db.php';

csrf_check();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = post('username','');
    $email    = post('email','');
    $password = post('password','');
    $confirm  = post('confirm','');

    if ($username === '' || strlen($username) < 3) $errors[] = 'Username must be at least 3 characters.';
    if (!valid_email($email)) $errors[] = 'Invalid email.';
    if ($password === '' || strlen($password) < 8) $errors[] = 'Password must be at least 8 characters.';
    if ($password !== $confirm) $errors[] = 'Passwords do not match.';

    if (!$errors) {
        $stmt = db()->prepare("SELECT id FROM users WHERE username = :u OR email = :e LIMIT 1");
        $stmt->execute([':u'=>$username, ':e'=>$email]);
        if ($stmt->fetch()) {
            $errors[] = 'Username or email already exists.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            db()->prepare("
                INSERT INTO users (username, email, password_hash, is_verified)
                VALUES (:u, :e, :p, 0)
            ")->execute([':u'=>$username, ':e'=>$email, ':p'=>$hash]);

            $user_id = (int)db()->lastInsertId();
            $code = create_verification_code($user_id, 'registration');

            $_SESSION['reg_user_id'] = $user_id;
            $_SESSION['reg_email'] = $email;
            $_SESSION['reg_code'] = $code; // për ta shfaqur si “email”

            redirect('/verify-registration.php');
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Create Account</title>
    <link rel="stylesheet" href="/styles.css?v=8">
</head>
<body>
<div class="page">
    <div class="card">
        <div class="card-inner">
            <h1>Create Your Account</h1>
            <?php if ($errors): ?>
                <div class="error">
                    <?php foreach ($errors as $e) echo '<div>'.e($e).'</div>'; ?>
                </div>
            <?php endif; ?>
            <form method="post" action="/register.php" novalidate>
                <input type="hidden" name="_csrf" value="<?php echo e(csrf_token()); ?>">
                <div class="form-row">
                    <div class="label">Username</div>
                    <input class="input" type="text" name="username" autocomplete="username" required>
                </div>
                <div class="form-row">
                    <div class="label">Email</div>
                    <input class="input" type="email" name="email" autocomplete="email" required>
                </div>
                <div class="form-row">
                    <div class="label">Password</div>
                    <input class="input" type="password" name="password" autocomplete="new-password" required>
                </div>
                <div class="form-row">
                    <div class="label">Confirm Password</div>
                    <input class="input" type="password" name="confirm" autocomplete="new-password" required>
                </div>
                <button class="btn" type="submit">Create Account</button>
            </form>
            <div class="actions">
                Already registered? <a href="/login.php">Log in</a>
            </div>
        </div>
    </div>
</div>
</body>
</html>
