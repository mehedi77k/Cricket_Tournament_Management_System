<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed.');
}

verify_csrf();

logout_user();

set_flash('success', 'You have been logged out.');

redirect('login.php');