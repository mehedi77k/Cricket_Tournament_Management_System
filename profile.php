<?php

declare(strict_types=1);

require_once __DIR__ .
    '/includes/bootstrap.php';

require_login();

$pageTitle = 'My Profile';
$activePage = 'profile';
$errors = [];

$statement = $pdo->prepare(
    'SELECT
        user_id,
        full_name,
        email,
        role,
        phone,
        bio,
        status,
        last_login_at,
        created_at
     FROM users
     WHERE user_id = :user_id
     LIMIT 1'
);

$statement->execute([
    'user_id' =>
        current_user_id(),
]);

$profile =
    $statement->fetch();

if (!$profile) {
    logout_user();

    set_flash(
        'error',
        'Account not found.'
    );

    redirect('login.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $action =
        post_string('action');


    /* =====================================================
       UPDATE PROFILE
       ===================================================== */

    if (
        $action ===
        'update_profile'
    ) {
        $fullName =
            post_string('full_name');

        $email =
            normalize_email(
                post_string('email')
            );

        $phone =
            post_string('phone');

        $bio =
            post_string('bio');

        if (
            strlen($fullName) < 2 ||
            strlen($fullName) > 100
        ) {
            $errors[] =
                'Name must contain 2 to 100 characters.';
        }

        if (
            !filter_var(
                $email,
                FILTER_VALIDATE_EMAIL
            )
        ) {
            $errors[] =
                'Enter a valid email address.';
        }

        if (strlen($phone) > 30) {
            $errors[] =
                'Phone number is too long.';
        }

        if (strlen($bio) > 500) {
            $errors[] =
                'Bio cannot exceed 500 characters.';
        }

        if (!$errors) {
            try {
                $updateStatement =
                    $pdo->prepare(
                        'UPDATE users
                         SET
                            full_name = :full_name,
                            email = :email,
                            phone = :phone,
                            bio = :bio
                         WHERE user_id = :user_id'
                    );

                $updateStatement->execute([
                    'full_name' =>
                        $fullName,

                    'email' =>
                        $email,

                    'phone' =>
                        $phone !== ''
                            ? $phone
                            : null,

                    'bio' =>
                        $bio !== ''
                            ? $bio
                            : null,

                    'user_id' =>
                        current_user_id(),
                ]);

                refresh_session_user($pdo);

                set_flash(
                    'success',
                    'Profile updated successfully.'
                );

                redirect('profile.php');

            } catch (
                PDOException $exception
            ) {
                if (
                    (int) (
                        $exception->errorInfo[1]
                        ?? 0
                    ) === 1062
                ) {
                    $errors[] =
                        'Another account already uses this email.';
                } else {
                    $errors[] =
                        db_error_message(
                            $exception
                        );
                }
            }
        }

        $profile['full_name'] =
            $fullName;

        $profile['email'] =
            $email;

        $profile['phone'] =
            $phone;

        $profile['bio'] =
            $bio;
    }


    /* =====================================================
       CHANGE PASSWORD
       ===================================================== */

    if (
        $action ===
        'change_password'
    ) {
        $currentPassword =
            (string) (
                $_POST['current_password']
                ?? ''
            );

        $newPassword =
            (string) (
                $_POST['new_password']
                ?? ''
            );

        $confirmPassword =
            (string) (
                $_POST['confirm_password']
                ?? ''
            );

        $passwordStatement =
            $pdo->prepare(
                'SELECT password_hash
                 FROM users
                 WHERE user_id = :user_id'
            );

        $passwordStatement->execute([
            'user_id' =>
                current_user_id(),
        ]);

        $passwordHash =
            (string) $passwordStatement
                ->fetchColumn();

        if (
            !password_verify(
                $currentPassword,
                $passwordHash
            )
        ) {
            $errors[] =
                'Current password is incorrect.';
        }

        $passwordError =
            validate_password(
                $newPassword
            );

        if ($passwordError !== null) {
            $errors[] =
                $passwordError;
        }

        if (
            $newPassword !==
            $confirmPassword
        ) {
            $errors[] =
                'New password confirmation does not match.';
        }

        if (!$errors) {
            $updatePassword =
                $pdo->prepare(
                    'UPDATE users
                     SET password_hash = :password_hash
                     WHERE user_id = :user_id'
                );

            $updatePassword->execute([
                'password_hash' =>
                    password_hash(
                        $newPassword,
                        PASSWORD_DEFAULT
                    ),

                'user_id' =>
                    current_user_id(),
            ]);

            set_flash(
                'success',
                'Password changed successfully.'
            );

            redirect('profile.php');
        }
    }
}

require __DIR__ .
    '/includes/header.php';
?>

<div class="grid grid-equal">

    <section class="card card-accent">

        <div class="card-header">

            <div>

                <h2>
                    Profile Information
                </h2>

                <p>
                    Update your personal details
                </p>

            </div>

            <span class="badge badge-dark">

                <?= h(
                    role_label(
                        (string) $profile['role']
                    )
                ) ?>

            </span>

        </div>

        <?php if ($errors): ?>

            <div class="alert alert-error">

                <?php foreach (
                    $errors as $error
                ): ?>

                    <div>
                        <?= h($error) ?>
                    </div>

                <?php endforeach; ?>

            </div>

        <?php endif; ?>

        <form
            method="post"
            class="form-grid"
        >

            <?= csrf_field() ?>

            <input
                type="hidden"
                name="action"
                value="update_profile"
            >

            <div class="form-group full">

                <label for="full_name">
                    Full Name
                </label>

                <input
                    type="text"
                    id="full_name"
                    name="full_name"
                    value="<?= h($profile['full_name']) ?>"
                    required
                >

            </div>

            <div class="form-group">

                <label for="email">
                    Email
                </label>

                <input
                    type="email"
                    id="email"
                    name="email"
                    value="<?= h($profile['email']) ?>"
                    required
                >

            </div>

            <div class="form-group">

                <label for="phone">
                    Phone
                </label>

                <input
                    type="text"
                    id="phone"
                    name="phone"
                    value="<?= h($profile['phone'] ?? '') ?>"
                >

            </div>

            <div class="form-group full">

                <label for="bio">
                    Bio
                </label>

                <textarea
                    id="bio"
                    name="bio"
                    maxlength="500"
                ><?= h($profile['bio'] ?? '') ?></textarea>

            </div>

            <div class="form-actions">

                <button
                    type="submit"
                    class="btn btn-primary"
                >
                    Save Profile
                </button>

            </div>

        </form>

    </section>

    <section class="card">

        <div class="card-header">

            <div>

                <h2>
                    Change Password
                </h2>

                <p>
                    Enter your current and new password
                </p>

            </div>

        </div>

        <form
            method="post"
            class="form-grid"
        >

            <?= csrf_field() ?>

            <input
                type="hidden"
                name="action"
                value="change_password"
            >

            <div class="form-group full">

                <label for="current_password">
                    Current Password
                </label>

                <input
                    type="password"
                    id="current_password"
                    name="current_password"
                    required
                >

            </div>

            <div class="form-group">

                <label for="new_password">
                    New Password
                </label>

                <input
                    type="password"
                    id="new_password"
                    name="new_password"
                    required
                >

            </div>

            <div class="form-group">

                <label for="confirm_password">
                    Confirm New Password
                </label>

                <input
                    type="password"
                    id="confirm_password"
                    name="confirm_password"
                    required
                >

            </div>

            <div class="form-actions">

                <button
                    type="submit"
                    class="btn btn-secondary"
                >
                    Change Password
                </button>

            </div>

        </form>

    </section>

</div>

<?php require __DIR__ . '/includes/footer.php'; ?>