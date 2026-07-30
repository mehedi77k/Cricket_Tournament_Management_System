<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

require_account_access();

$isSuperAdmin = is_super_admin();
$isAdmin = is_admin();

$pageTitle =
    $isSuperAdmin
        ? 'Account Management'
        : 'User Approvals';

$activePage = 'users';
$errors = [];

$form = [
    'full_name' => '',
    'email' => '',
    'phone' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $action = post_string('action');


    /* =====================================================
       CREATE ADMIN — SUPER ADMIN ONLY
       ===================================================== */

    if ($action === 'create_admin') {
        if (!$isSuperAdmin) {
            set_flash(
                'error',
                'Only the Super Admin can create Admin accounts.'
            );

            redirect('users.php');
        }

        $form['full_name'] =
            post_string('full_name');

        $form['email'] =
            normalize_email(
                post_string('email')
            );

        $form['phone'] =
            post_string('phone');

        $password =
            (string) (
                $_POST['password'] ?? ''
            );

        $confirmPassword =
            (string) (
                $_POST['confirm_password']
                ?? ''
            );

        if (
            strlen($form['full_name']) < 2 ||
            strlen($form['full_name']) > 100
        ) {
            $errors[] =
                'Full name must contain 2 to 100 characters.';
        }

        if (
            !filter_var(
                $form['email'],
                FILTER_VALIDATE_EMAIL
            )
        ) {
            $errors[] =
                'Enter a valid email address.';
        }

        if (
            strlen($form['phone']) > 30
        ) {
            $errors[] =
                'Phone number is too long.';
        }

        $passwordError =
            validate_password($password);

        if ($passwordError !== null) {
            $errors[] = $passwordError;
        }

        if (
            $password !==
            $confirmPassword
        ) {
            $errors[] =
                'Password confirmation does not match.';
        }

        if (!$errors) {
            try {
                $statement = $pdo->prepare(
                    "INSERT INTO users
                        (
                            full_name,
                            email,
                            password_hash,
                            role,
                            phone,
                            status,
                            approved_by,
                            approved_at
                        )
                     VALUES
                        (
                            :full_name,
                            :email,
                            :password_hash,
                            'admin',
                            :phone,
                            'active',
                            :approved_by,
                            NOW()
                        )"
                );

                $statement->execute([
                    'full_name' =>
                        $form['full_name'],

                    'email' =>
                        $form['email'],

                    'password_hash' =>
                        password_hash(
                            $password,
                            PASSWORD_DEFAULT
                        ),

                    'phone' =>
                        $form['phone'] !== ''
                            ? $form['phone']
                            : null,

                    'approved_by' =>
                        current_user_id(),
                ]);

                set_flash(
                    'success',
                    'Admin account created and activated successfully.'
                );

                redirect('users.php');

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
                        'An account already exists with this email.';
                } else {
                    $errors[] =
                        db_error_message(
                            $exception
                        );
                }
            }
        }
    }


    /* =====================================================
       APPROVE PENDING USER — ADMIN OR SUPER ADMIN
       ===================================================== */

    if ($action === 'approve_user') {
        $userId = post_int('user_id');

        $statement = $pdo->prepare(
            'SELECT
                user_id,
                full_name,
                role,
                status
             FROM users
             WHERE user_id = :user_id
             LIMIT 1'
        );

        $statement->execute([
            'user_id' => $userId,
        ]);

        $targetUser =
            $statement->fetch();

        if (!$targetUser) {
            set_flash(
                'error',
                'The selected account was not found.'
            );

            redirect('users.php');
        }

        if (
            $targetUser['role'] !== 'user' ||
            $targetUser['status'] !== 'pending'
        ) {
            set_flash(
                'error',
                'Only a pending User registration can be approved.'
            );

            redirect('users.php');
        }

        $update = $pdo->prepare(
            "UPDATE users
             SET
                status = 'active',
                approved_by = :approved_by,
                approved_at = NOW()
             WHERE user_id = :user_id
               AND role = 'user'
               AND status = 'pending'"
        );

        $update->execute([
            'approved_by' =>
                current_user_id(),

            'user_id' =>
                $userId,
        ]);

        set_flash(
            'success',
            'User registration approved. The User can now log in.'
        );

        redirect('users.php');
    }


    /* =====================================================
       SUPER ADMIN ACCOUNT CONTROL
       ===================================================== */

    if (
        in_array(
            $action,
            [
                'reject_user',
                'activate_account',
                'deactivate_account',
            ],
            true
        )
    ) {
        if (!$isSuperAdmin) {
            set_flash(
                'error',
                'Only the Super Admin can perform that account action.'
            );

            redirect('users.php');
        }

        $userId = post_int('user_id');

        $statement = $pdo->prepare(
            'SELECT
                user_id,
                full_name,
                role,
                status
             FROM users
             WHERE user_id = :user_id
             LIMIT 1'
        );

        $statement->execute([
            'user_id' => $userId,
        ]);

        $targetUser =
            $statement->fetch();

        if (!$targetUser) {
            set_flash(
                'error',
                'The selected account was not found.'
            );

            redirect('users.php');
        }

        if (
            $userId === current_user_id()
        ) {
            set_flash(
                'error',
                'You cannot change the status of your own Super Admin account.'
            );

            redirect('users.php');
        }

        if (
            $targetUser['role'] ===
            'super_admin'
        ) {
            set_flash(
                'error',
                'The Super Admin account is protected.'
            );

            redirect('users.php');
        }


        /* Reject pending User */

        if ($action === 'reject_user') {
            if (
                $targetUser['role'] !==
                'user' ||
                $targetUser['status'] !==
                'pending'
            ) {
                set_flash(
                    'error',
                    'Only a pending User registration can be rejected.'
                );

                redirect('users.php');
            }

            $update = $pdo->prepare(
                "UPDATE users
                 SET
                    status = 'rejected',
                    approved_by = :approved_by,
                    approved_at = NOW()
                 WHERE user_id = :user_id
                   AND role = 'user'
                   AND status = 'pending'"
            );

            $update->execute([
                'approved_by' =>
                    current_user_id(),

                'user_id' =>
                    $userId,
            ]);

            set_flash(
                'success',
                'User registration rejected.'
            );

            redirect('users.php');
        }


        /* Activate Admin or User */

        if (
            $action ===
            'activate_account'
        ) {
            $update = $pdo->prepare(
                "UPDATE users
                 SET
                    status = 'active',
                    approved_by = :approved_by,
                    approved_at = NOW()
                 WHERE user_id = :user_id
                   AND role IN ('admin', 'user')"
            );

            $update->execute([
                'approved_by' =>
                    current_user_id(),

                'user_id' =>
                    $userId,
            ]);

            set_flash(
                'success',
                role_label(
                    (string) $targetUser['role']
                ) .
                ' account activated.'
            );

            redirect('users.php');
        }


        /* Deactivate Admin or User */

        if (
            $action ===
            'deactivate_account'
        ) {
            $update = $pdo->prepare(
                "UPDATE users
                 SET status = 'inactive'
                 WHERE user_id = :user_id
                   AND role IN ('admin', 'user')"
            );

            $update->execute([
                'user_id' => $userId,
            ]);

            set_flash(
                'success',
                role_label(
                    (string) $targetUser['role']
                ) .
                ' account deactivated.'
            );

            redirect('users.php');
        }
    }
}


/* =========================================================
   PENDING USER REGISTRATIONS
   ========================================================= */

$pendingUsers = $pdo->query(
    "SELECT
        user_id,
        full_name,
        email,
        phone,
        role,
        status,
        created_at
     FROM users
     WHERE role = 'user'
       AND status = 'pending'
     ORDER BY created_at ASC"
)->fetchAll();


/* =========================================================
   ALL ACCOUNTS — SUPER ADMIN ONLY
   ========================================================= */

$allUsers = [];

if ($isSuperAdmin) {
    $allUsers = $pdo->query(
        'SELECT
            u.user_id,
            u.full_name,
            u.email,
            u.phone,
            u.role,
            u.status,
            u.last_login_at,
            u.created_at,
            u.approved_at,
            reviewer.full_name
                AS approved_by_name
         FROM users u
         LEFT JOIN users reviewer
            ON reviewer.user_id =
                u.approved_by
         ORDER BY
            CASE u.role
                WHEN \'super_admin\' THEN 1
                WHEN \'admin\' THEN 2
                ELSE 3
            END,
            CASE u.status
                WHEN \'pending\' THEN 1
                WHEN \'active\' THEN 2
                WHEN \'inactive\' THEN 3
                ELSE 4
            END,
            u.created_at DESC'
    )->fetchAll();
}

require __DIR__ .
    '/includes/header.php';
?>

<?php if ($errors): ?>

    <div class="alert alert-error">

        <span>
            <?= h(implode(' ', $errors)) ?>
        </span>

        <button
            type="button"
            class="alert-close"
        >
            ×
        </button>

    </div>

<?php endif; ?>


<?php if ($isSuperAdmin): ?>

    <div class="grid grid-2">

        <section class="card card-accent">

            <div class="card-header">

                <div>

                    <h2>Create Admin</h2>

                    <p>
                        Only the Super Admin can create Admin accounts
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
                    value="create_admin"
                >

                <div class="form-group full">

                    <label
                        class="required"
                        for="full_name"
                    >
                        Admin Full Name
                    </label>

                    <input
                        id="full_name"
                        name="full_name"
                        type="text"
                        value="<?= h($form['full_name']) ?>"
                        required
                    >

                </div>

                <div class="form-group">

                    <label
                        class="required"
                        for="email"
                    >
                        Admin Email
                    </label>

                    <input
                        id="email"
                        name="email"
                        type="email"
                        value="<?= h($form['email']) ?>"
                        required
                    >

                </div>

                <div class="form-group">

                    <label for="phone">
                        Phone
                    </label>

                    <input
                        id="phone"
                        name="phone"
                        type="text"
                        value="<?= h($form['phone']) ?>"
                    >

                </div>

                <div class="form-group">

                    <label
                        class="required"
                        for="password"
                    >
                        Password
                    </label>

                    <input
                        id="password"
                        name="password"
                        type="password"
                        required
                    >

                    <span class="help-text">
                        Minimum 8 characters with at least one letter and one number.
                    </span>

                </div>

                <div class="form-group">

                    <label
                        class="required"
                        for="confirm_password"
                    >
                        Confirm Password
                    </label>

                    <input
                        id="confirm_password"
                        name="confirm_password"
                        type="password"
                        required
                    >

                </div>

                <div class="form-actions">

                    <button
                        type="submit"
                        class="btn btn-primary"
                    >
                        Create Admin
                    </button>

                </div>

            </form>

        </section>

        <section class="card card-accent">

<?php else: ?>

    <section class="card card-accent">

<?php endif; ?>


        <div class="card-header">

            <div>

                <h2>
                    Pending User Registrations
                </h2>

                <p>
                    <?= count($pendingUsers) ?>
                    User account(s) waiting for approval
                </p>

            </div>

        </div>

        <?php if ($pendingUsers): ?>

            <div class="approval-list">

                <?php foreach (
                    $pendingUsers as $user
                ): ?>

                    <article class="approval-item">

                        <div>

                            <strong>
                                <?= h($user['full_name']) ?>
                            </strong>

                            <span>
                                <?= h($user['email']) ?>
                            </span>

                            <?php if (
                                !empty($user['phone'])
                            ): ?>

                                <small>
                                    Phone:
                                    <?= h($user['phone']) ?>
                                </small>

                            <?php endif; ?>

                            <small>

                                Registered

                                <?= h(
                                    format_date(
                                        substr(
                                            (string) $user['created_at'],
                                            0,
                                            10
                                        )
                                    )
                                ) ?>

                            </small>

                        </div>

                        <div class="table-actions">

                            <form
                                method="post"
                                class="inline-form"
                            >

                                <?= csrf_field() ?>

                                <input
                                    type="hidden"
                                    name="action"
                                    value="approve_user"
                                >

                                <input
                                    type="hidden"
                                    name="user_id"
                                    value="<?= (int) $user['user_id'] ?>"
                                >

                                <button
                                    type="submit"
                                    class="btn btn-primary btn-sm"
                                >
                                    Approve
                                </button>

                            </form>

                            <?php if ($isSuperAdmin): ?>

                                <form
                                    method="post"
                                    class="inline-form"
                                    data-confirm="Reject this User registration?"
                                >

                                    <?= csrf_field() ?>

                                    <input
                                        type="hidden"
                                        name="action"
                                        value="reject_user"
                                    >

                                    <input
                                        type="hidden"
                                        name="user_id"
                                        value="<?= (int) $user['user_id'] ?>"
                                    >

                                    <button
                                        type="submit"
                                        class="btn btn-danger btn-sm"
                                    >
                                        Reject
                                    </button>

                                </form>

                            <?php endif; ?>

                        </div>

                    </article>

                <?php endforeach; ?>

            </div>

        <?php else: ?>

            <div class="empty-state">

                <strong>
                    No pending User registration
                </strong>

                New public User registrations will appear here.

            </div>

        <?php endif; ?>


<?php if ($isSuperAdmin): ?>

        </section>

    </div>

<?php else: ?>

    </section>

<?php endif; ?>


<?php if ($isSuperAdmin): ?>

    <section
        class="card"
        style="margin-top: 22px;"
    >

        <div class="card-header">

            <div>

                <h2>All Accounts</h2>

                <p>
                    Super Admin controls all Admin and User accounts
                </p>

            </div>

        </div>

        <div class="table-toolbar">

            <div class="search-box">

                <input
                    type="search"
                    placeholder="Search name, email, role or status..."
                    data-table-search="usersTable"
                >

            </div>

        </div>

        <div class="table-wrap">

            <table id="usersTable">

                <thead>

                <tr>
                    <th>Account</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Registered</th>
                    <th>Last Login</th>
                    <th>Reviewed By</th>
                    <th>Actions</th>
                </tr>

                </thead>

                <tbody>

                <?php foreach (
                    $allUsers as $user
                ): ?>

                    <tr>

                        <td>

                            <strong>
                                <?= h($user['full_name']) ?>
                            </strong>

                            <br>

                            <span class="muted">
                                <?= h($user['email']) ?>
                            </span>

                        </td>

                        <td>

                            <span class="badge badge-dark">

                                <?= h(
                                    role_label(
                                        (string) $user['role']
                                    )
                                ) ?>

                            </span>

                        </td>

                        <td>

                            <?php if (
                                $user['status'] === 'active'
                            ): ?>

                                <span class="badge badge-success">
                                    Active
                                </span>

                            <?php elseif (
                                $user['status'] === 'pending'
                            ): ?>

                                <span class="badge badge-warning">
                                    Pending
                                </span>

                            <?php elseif (
                                $user['status'] === 'rejected'
                            ): ?>

                                <span class="badge badge-danger">
                                    Rejected
                                </span>

                            <?php else: ?>

                                <span class="badge badge-muted">
                                    Inactive
                                </span>

                            <?php endif; ?>

                        </td>

                        <td>

                            <?= h(
                                format_date(
                                    substr(
                                        (string) $user['created_at'],
                                        0,
                                        10
                                    )
                                )
                            ) ?>

                        </td>

                        <td>

                            <?= $user['last_login_at']
                                ? h($user['last_login_at'])
                                : '—' ?>

                        </td>

                        <td>

                            <?= h(
                                $user['approved_by_name']
                                ?? '—'
                            ) ?>

                        </td>

                        <td>

                            <div class="table-actions">

                                <?php if (
                                    (int) $user['user_id'] ===
                                    current_user_id()
                                ): ?>

                                    <span class="muted">
                                        Current account
                                    </span>

                                <?php elseif (
                                    $user['role'] ===
                                    'super_admin'
                                ): ?>

                                    <span class="muted">
                                        Protected
                                    </span>

                                <?php elseif (
                                    $user['role'] === 'user' &&
                                    $user['status'] === 'pending'
                                ): ?>

                                    <form
                                        method="post"
                                        class="inline-form"
                                    >

                                        <?= csrf_field() ?>

                                        <input
                                            type="hidden"
                                            name="action"
                                            value="approve_user"
                                        >

                                        <input
                                            type="hidden"
                                            name="user_id"
                                            value="<?= (int) $user['user_id'] ?>"
                                        >

                                        <button
                                            type="submit"
                                            class="btn btn-primary btn-sm"
                                        >
                                            Approve
                                        </button>

                                    </form>

                                    <form
                                        method="post"
                                        class="inline-form"
                                        data-confirm="Reject this User registration?"
                                    >

                                        <?= csrf_field() ?>

                                        <input
                                            type="hidden"
                                            name="action"
                                            value="reject_user"
                                        >

                                        <input
                                            type="hidden"
                                            name="user_id"
                                            value="<?= (int) $user['user_id'] ?>"
                                        >

                                        <button
                                            type="submit"
                                            class="btn btn-danger btn-sm"
                                        >
                                            Reject
                                        </button>

                                    </form>

                                <?php elseif (
                                    $user['status'] === 'active'
                                ): ?>

                                    <form
                                        method="post"
                                        class="inline-form"
                                        data-confirm="Deactivate this account?"
                                    >

                                        <?= csrf_field() ?>

                                        <input
                                            type="hidden"
                                            name="action"
                                            value="deactivate_account"
                                        >

                                        <input
                                            type="hidden"
                                            name="user_id"
                                            value="<?= (int) $user['user_id'] ?>"
                                        >

                                        <button
                                            type="submit"
                                            class="btn btn-danger btn-sm"
                                        >
                                            Deactivate
                                        </button>

                                    </form>

                                <?php else: ?>

                                    <form
                                        method="post"
                                        class="inline-form"
                                    >

                                        <?= csrf_field() ?>

                                        <input
                                            type="hidden"
                                            name="action"
                                            value="activate_account"
                                        >

                                        <input
                                            type="hidden"
                                            name="user_id"
                                            value="<?= (int) $user['user_id'] ?>"
                                        >

                                        <button
                                            type="submit"
                                            class="btn btn-secondary btn-sm"
                                        >
                                            Activate
                                        </button>

                                    </form>

                                <?php endif; ?>

                            </div>

                        </td>

                    </tr>

                <?php endforeach; ?>

                </tbody>

            </table>

        </div>

    </section>

<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>