<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/config.php';
require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/functions.php';

csrf_check();

$errors  = [];
$ok      = false;

$username = '';
$email    = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = post('username', '');
    $email    = post('email', '');
    $password = post('password', '');
    $confirm  = post('confirm', '');

    if ($username === '' || strlen($username) < 3) {
        $errors[] = 'Username must be at least 3 characters.';
    }

    if (!valid_email($email)) {
        $errors[] = 'Invalid email address.';
    }

    $passwordErrors = validate_password($password);
    $errors = array_merge($errors, $passwordErrors);

    if ($password !== $confirm) {
        $errors[] = 'Passwords do not match.';
    }

    if (!$errors) {
        $stmt = db()->prepare('SELECT id, is_verified, email FROM users WHERE username = :u OR email = :e LIMIT 1');
        $stmt->execute([':u' => $username, ':e' => $email]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            if ((int)$existing['is_verified'] === 1) {
                $errors[] = 'Username or email already exists.';
            } else {
                $user_id = (int)$existing['id'];
                $targetEmail = $existing['email'];

                $code = create_verification_code($user_id, 'registration');

                if (send_verification_email($targetEmail, $code, 'registration')) {
                    $_SESSION['reg_email']   = $targetEmail;
                    $_SESSION['reg_user_id'] = $user_id;

                    redirect('/verify-registration.php');
                } else {
                    $errors[] = 'Failed to send verification email.';
                }
            }
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);

            db()->prepare('
                INSERT INTO users (username, email, password_hash, is_verified)
                VALUES (:u, :e, :p, 0)
            ')->execute([
                ':u' => $username,
                ':e' => $email,
                ':p' => $hash,
            ]);

            $user_id = (int) db()->lastInsertId();

            $code = create_verification_code($user_id, 'registration');

            if (send_verification_email($email, $code, 'registration')) {
                $_SESSION['reg_email']   = $email;
                $_SESSION['reg_user_id'] = $user_id;

                redirect('/verify-registration.php');
            } else {
                $errors[] = 'Failed to send verification email.';
            }
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Create Account</title>
    <link rel="stylesheet" href="/styles.css?v=7">
</head>
<body>
<div class="page">
    <div class="card">
        <div class="card-inner">
            <h1>Create Your Account</h1>

            <?php if ($errors): ?>
                <div class="error">
                    <?php foreach ($errors as $e): ?>
                        <div><?php echo e($e); ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form method="post" action="/register.php" novalidate>
                <input type="hidden" name="_csrf" value="<?php echo e(csrf_token()); ?>">

                <div class="form-row">
                    <div class="label">Username</div>
                    <input
                            class="input"
                            type="text"
                            name="username"
                            autocomplete="username"
                            required
                            value="<?php echo e($username); ?>"
                    >
                </div>

                <div class="form-row">
                    <div class="label">Email</div>
                    <input
                            class="input"
                            type="email"
                            name="email"
                            autocomplete="email"
                            required
                            value="<?php echo e($email); ?>"
                    >
                </div>

                <div class="form-row">
                    <div class="label">Password</div>
                    <input
                            class="input"
                            type="password"
                            name="password"
                            autocomplete="new-password"
                            required
                    >
                    <small style="color:#6b7280;font-size:0.85rem;">
                        Password must be at least 8 characters and contain uppercase, lowercase,
                        number and special character (e.g. ! @ # $ %).
                    </small>
                </div>

                <div class="form-row">
                    <div class="label">Confirm Password</div>
                    <input
                            class="input"
                            type="password"
                            name="confirm"
                            autocomplete="new-password"
                            required
                    >
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
