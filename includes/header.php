<?php

/** @var string $pageTitle */
/** @var string $activePage */

require_login();

$flash = get_flash();

$pageTitle = $pageTitle ?? 'Cricket Tournament';
$activePage = $activePage ?? '';

if (is_admin()) {
    $navigation = [
        'dashboard' => [
            'label' => 'Dashboard',
            'href' => 'index.php',
            'icon' => 'DB',
        ],

        'teams' => [
            'label' => 'Teams',
            'href' => 'teams.php',
            'icon' => 'TM',
        ],

        'players' => [
            'label' => 'Players',
            'href' => 'players.php',
            'icon' => 'PL',
        ],

        'matches' => [
            'label' => 'Matches',
            'href' => 'matches.php',
            'icon' => 'MT',
        ],

        'scores' => [
            'label' => 'Scores',
            'href' => 'scores.php',
            'icon' => 'SC',
        ],

        'awards' => [
            'label' => 'Match Awards',
            'href' => 'awards.php',
            'icon' => 'AW',
        ],

        'award-types' => [
            'label' => 'Award Types',
            'href' => 'award_types.php',
            'icon' => 'AT',
        ],

        'points' => [
            'label' => 'Points Table',
            'href' => 'points.php',
            'icon' => 'PT',
        ],

        'profile' => [
            'label' => 'My Profile',
            'href' => 'profile.php',
            'icon' => 'ME',
        ],
    ];
} else {
    $navigation = [
        'user-dashboard' => [
            'label' => 'Dashboard',
            'href' => 'user_dashboard.php',
            'icon' => 'DB',
        ],

        'profile' => [
            'label' => 'My Profile',
            'href' => 'profile.php',
            'icon' => 'ME',
        ],
    ];
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

    <title>
        <?= h($pageTitle) ?> | Cricket Tournament
    </title>

    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>

<div class="app-shell">

    <aside class="sidebar" id="sidebar">

        <div class="brand">
            <div class="brand-mark">CT</div>

            <div>
                <strong>Cricket Tournament</strong>
                <span>Management System</span>
            </div>
        </div>

        <nav class="sidebar-nav">

            <?php foreach ($navigation as $key => $item): ?>

                <a
                    class="nav-link
                    <?= $activePage === $key ? 'active' : '' ?>"
                    href="<?= h($item['href']) ?>"
                >
                    <span class="nav-icon">
                        <?= h($item['icon']) ?>
                    </span>

                    <span>
                        <?= h($item['label']) ?>
                    </span>
                </a>

            <?php endforeach; ?>

        </nav>

        <div class="sidebar-footer">

            <div>
                <span class="status-dot"></span>

                <?= h(ucfirst(current_user_role())) ?>
                session active
            </div>

            <div class="sidebar-user-name">
                <?= h(current_user_name()) ?>
            </div>

        </div>

    </aside>

    <main class="main-content">

        <header class="topbar">

            <button
                class="menu-button"
                id="menuButton"
                type="button"
            >
                ☰
            </button>

            <div>
                <p class="eyebrow">
                    <?= is_admin()
                        ? 'Tournament Administration'
                        : 'Tournament User Portal' ?>
                </p>

                <h1><?= h($pageTitle) ?></h1>
            </div>

            <div class="topbar-account">

                <a class="account-chip" href="profile.php">

                    <span>
                        <strong>
                            <?= h(current_user_name()) ?>
                        </strong>

                        <small>
                            <?= h(ucfirst(current_user_role())) ?>
                        </small>
                    </span>

                </a>

                <form
                    method="post"
                    action="logout.php"
                    class="inline-form"
                >
                    <?= csrf_field() ?>

                    <button
                        type="submit"
                        class="btn btn-light btn-sm"
                    >
                        Logout
                    </button>
                </form>

            </div>

        </header>

        <section class="content-area">

            <?php if ($flash): ?>

                <div
                    class="alert alert-<?= h($flash['type']) ?>"
                >
                    <span>
                        <?= h($flash['message']) ?>
                    </span>
                </div>

            <?php endif; ?>