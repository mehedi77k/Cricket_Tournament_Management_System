<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

require_guest();

$adminCount = (int) $pdo
    ->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")
    ->fetchColumn();

if ($adminCount > 0) {
    set_flash('warning', 'The first Admin has already been created.');
    redirect('login.php');
}

$errors = [];

$form = [
    'full_name' => '',
    'email' => '',
    'phone' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $form['full_name'] = post_string('full_name');
    $form['email'] = normalize_email(post_string('email'));
    $form['phone'] = post_string('phone');

    $password = (string) ($_POST['password'] ?? '');
    $confirmPassword = (string) ($_POST['confirm_password'] ?? '');

    if (strlen($form['full_name']) < 2) {
        $errors[] = 'Admin name must contain at least 2 characters.';
    }

    if (!filter_var($form['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Enter a valid email address.';
    }

    $passwordError = validate_password($password);

    if ($passwordError !== null) {
        $errors[] = $passwordError;
    }

    if ($password !== $confirmPassword) {
        $errors[] = 'Password confirmation does not match.';
    }

    if (!$errors) {
        try {
            $statement = $pdo->prepare(
                "INSERT INTO users
                    (full_name, email, password_hash, role, phone, status)
                 VALUES
                    (:full_name, :email, :password_hash, 'admin', :phone, 'active')"
            );

            $statement->execute([
                'full_name' => $form['full_name'],
                'email' => $form['email'],
                'password_hash' => password_hash(
                    $password,
                    PASSWORD_DEFAULT
                ),
                'phone' => $form['phone'] !== ''
                    ? $form['phone']
                    : null,
            ]);

            set_flash(
                'success',
                'Admin account created successfully. Please log in.'
            );

            redirect('login.php');
        } catch (PDOException $exception) {
            if ((int) ($exception->errorInfo[1] ?? 0) === 1062) {
                $errors[] = 'This email address is already registered.';
            } else {
                $errors[] = db_error_message($exception);
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Create Admin | Cricket Tournament</title>

    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body class="auth-body">

<main class="auth-page">

    <section class="auth-card auth-card-wide">

        <div class="auth-heading">
            <p class="eyebrow">One-time setup</p>

            <h1>Create First Admin</h1>

            <p>
                This page will stop working after the first Admin is created.
            </p>
        </div>

        <?php if ($errors): ?>
            <div class="alert alert-error">

                <?php foreach ($errors as $error): ?>
                    <div><?= h($error) ?></div>
                <?php endforeach; ?>

            </div>
        <?php endif; ?>

        <form method="post" class="form-grid">

            <?= csrf_field() ?>

            <div class="form-group full">
                <label for="full_name">Admin Full Name</label>

                <input
                    type="text"
                    id="full_name"
                    name="full_name"
                    value="<?= h($form['full_name']) ?>"
                    required
                >
            </div>

            <div class="form-group">
                <label for="email">Admin Email</label>

                <input
                    type="email"
                    id="email"
                    name="email"
                    value="<?= h($form['email']) ?>"
                    required
                >
            </div>

            <div class="form-group">
                <label for="phone">Phone</label>

                <input
                    type="text"
                    id="phone"
                    name="phone"
                    value="<?= h($form['phone']) ?>"
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

                <span class="help-text">
                    Minimum 8 characters with a letter and number.
                </span>
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>

                <input
                    type="password"
                    id="confirm_password"
                    name="confirm_password"
                    required
                >
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    Create Admin
                </button>

                <a href="login.php" class="btn btn-light">
                    Login
                </a>
            </div>

        </form>

    </section>

</main>

</body>
</html>