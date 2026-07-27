<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

require_guest();

$errors = [];
$email = '';
$flash = get_flash();

try {
    $adminCount = (int) $pdo
        ->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")
        ->fetchColumn();
} catch (PDOException) {
    exit('Users table not found. Create the users table first.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $email = normalize_email(post_string('email'));
    $password = (string) ($_POST['password'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Enter a valid email address.';
    }

    if ($password === '') {
        $errors[] = 'Password is required.';
    }

    if (!$errors) {
        $statement = $pdo->prepare(
            'SELECT
                user_id,
                full_name,
                email,
                password_hash,
                role,
                status
             FROM users
             WHERE email = :email
             LIMIT 1'
        );

        $statement->execute([
            'email' => $email,
        ]);

        $user = $statement->fetch();

        if (
            !$user ||
            !password_verify($password, $user['password_hash'])
        ) {
            $errors[] = 'Email or password is incorrect.';
        } elseif ($user['status'] !== 'active') {
            $errors[] = 'Your account is inactive.';
        } else {
            login_user($user);

            $updateStatement = $pdo->prepare(
                'UPDATE users
                 SET last_login_at = NOW()
                 WHERE user_id = :user_id'
            );

            $updateStatement->execute([
                'user_id' => (int) $user['user_id'],
            ]);

            //set_flash('success', 'Login successful.');

            redirect(dashboard_url());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Login | Cricket Tournament</title>

    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body class="auth-body">

<main class="auth-page">

    <section class="auth-card">

        <div class="auth-heading">
            <p class="eyebrow">Account Login</p>

            <h1>Login</h1>

            <p>
                Admin and User use the same login form.
            </p>
        </div>

        <?php if ($flash): ?>
            <div class="alert alert-<?= h($flash['type']) ?>">
                <?= h($flash['message']) ?>
            </div>
        <?php endif; ?>

        <?php if ($errors): ?>
            <div class="alert alert-error">

                <?php foreach ($errors as $error): ?>
                    <div><?= h($error) ?></div>
                <?php endforeach; ?>

            </div>
        <?php endif; ?>

        <form method="post" class="auth-form">

            <?= csrf_field() ?>

            <div class="form-group">
                <label for="email">Email</label>

                <input
                    type="email"
                    id="email"
                    name="email"
                    value="<?= h($email) ?>"
                    required
                >
            </div>

            <div class="form-group">
                <label for="password">Password</label>

                <input
                    type="password"
                    id="password"
                    name="password"
                    required
                >
            </div>

            <button
                type="submit"
                class="btn btn-primary auth-submit"
            >
                Login
            </button>

        </form>

        <div class="auth-links">

            <span>
                No account?
                <a href="register.php">Register as User</a>
            </span>

            <?php if ($adminCount === 0): ?>
                <span>
                    <a href="setup_admin.php">
                        Create First Admin
                    </a>
                </span>
            <?php endif; ?>

        </div>

    </section>

</main>

</body>
</html>