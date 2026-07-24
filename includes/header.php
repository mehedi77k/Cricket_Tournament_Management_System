<?php
/** @var string $pageTitle */
/** @var string $activePage */

$flash = get_flash();
$pageTitle = $pageTitle ?? 'Cricket Tournament';
$activePage = $activePage ?? '';

$navigation = [
    'dashboard' => ['label' => 'Dashboard', 'href' => 'index.php', 'icon' => 'DB'],
    'teams' => ['label' => 'Teams', 'href' => 'teams.php', 'icon' => 'TM'],
    'players' => ['label' => 'Players', 'href' => 'players.php', 'icon' => 'PL'],
    'matches' => ['label' => 'Matches', 'href' => 'matches.php', 'icon' => 'MT'],
    'scores' => ['label' => 'Scores', 'href' => 'scores.php', 'icon' => 'SC'],
    'awards' => ['label' => 'Match Awards', 'href' => 'awards.php', 'icon' => 'AW'],
    'award-types' => ['label' => 'Award Types', 'href' => 'award_types.php', 'icon' => 'AT'],
    'points' => ['label' => 'Points Table', 'href' => 'points.php', 'icon' => 'PT'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Cricket Tournament Management System">
    <title><?= h($pageTitle) ?> | Cricket Tournament</title>
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

        <nav class="sidebar-nav" aria-label="Main navigation">
            <?php foreach ($navigation as $key => $item): ?>
                <a class="nav-link <?= $activePage === $key ? 'active' : '' ?>" href="<?= h($item['href']) ?>">
                    <span class="nav-icon"><?= h($item['icon']) ?></span>
                    <span><?= h($item['label']) ?></span>
                </a>
            <?php endforeach; ?>
        </nav>

       
    </aside>

    <main class="main-content">
        <header class="topbar">
            <button class="menu-button" id="menuButton" type="button" aria-label="Toggle menu">☰</button>
            <div>
                <p class="eyebrow">Tournament Administration</p>
                <h1><?= h($pageTitle) ?></h1>
            </div>
            
        </header>

        <section class="content-area">
            <?php if ($flash): ?>
                <div class="alert alert-<?= h($flash['type']) ?>" role="alert">
                    <span><?= h($flash['message']) ?></span>
                    <button type="button" class="alert-close" aria-label="Close">×</button>
                </div>
            <?php endif; ?>
