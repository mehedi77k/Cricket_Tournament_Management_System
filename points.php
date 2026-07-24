<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$pageTitle = 'Points Table';
$activePage = 'points';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    if (post_string('action') === 'recalculate') {
        try {
            recalculate_points($pdo);
            
        } catch (PDOException $exception) {
            set_flash('error', db_error_message($exception));
        }
        redirect('points.php');
    }
}

$standings = $pdo->query(
    'SELECT t.team_id, t.team_name, t.captain_name,
            COALESCE(pt.matches_played, 0) AS matches_played,
            COALESCE(pt.wins, 0) AS wins,
            COALESCE(pt.losses, 0) AS losses,
            COALESCE(pt.points, 0) AS points
     FROM team t
     LEFT JOIN points_table pt ON pt.team_id = t.team_id
     ORDER BY points DESC, wins DESC, losses ASC, t.team_name'
)->fetchAll();

$completedMatchCount = (int) $pdo->query(
    'SELECT COUNT(*) FROM matches WHERE winner_team_id IS NOT NULL'
)->fetchColumn();
$pendingMatchCount = (int) $pdo->query(
    'SELECT COUNT(*) FROM matches WHERE winner_team_id IS NULL'
)->fetchColumn();

require __DIR__ . '/includes/header.php';
?>

<div class="stats-grid">
    <article class="stat-card">
        <span class="stat-label">Teams Ranked</span>
        <strong class="stat-value"><?= count($standings) ?></strong>
        <span class="stat-note">All registered teams</span>
    </article>
    <article class="stat-card">
        <span class="stat-label">Completed Matches</span>
        <strong class="stat-value"><?= $completedMatchCount ?></strong>
        <span class="stat-note">Matches with a winner</span>
    </article>
    <article class="stat-card">
        <span class="stat-label">Pending Matches</span>
        <strong class="stat-value"><?= $pendingMatchCount ?></strong>
        <span class="stat-note">Not included in standings</span>
    </article>
    <article class="stat-card">
        <span class="stat-label">Win Points</span>
        <strong class="stat-value">2</strong>
        <span class="stat-note">Points awarded per win</span>
    </article>
</div>

<section class="card card-accent">
    <div class="card-header">
        <div>
            <h2>Tournament Standings</h2>
            <p>Calculated using the winner stored in each completed match</p>
        </div>
        <form method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="recalculate">
            <button class="btn btn-primary" type="submit">Recalculate Points</button>
        </form>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
            <tr>
                <th>Position</th>
                <th>Team</th>
                <th>Captain</th>
                <th>Matches</th>
                <th>Wins</th>
                <th>Losses</th>
                <th>Points</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($standings as $index => $row): ?>
                <tr>
                    <td><span class="rank"><?= $index + 1 ?></span></td>
                    <td>
                        <div class="team-cell">
                            <span class="avatar"><?= h(substr($row['team_name'], 0, 2)) ?></span>
                            <strong><?= h($row['team_name']) ?></strong>
                        </div>
                    </td>
                    <td><?= h($row['captain_name']) ?></td>
                    <td><?= (int) $row['matches_played'] ?></td>
                    <td><span class="badge badge-success"><?= (int) $row['wins'] ?></span></td>
                    <td><?= (int) $row['losses'] ?></td>
                    <td><span class="badge badge-dark"><?= (int) $row['points'] ?></span></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$standings): ?>
                <tr><td colspan="7" class="empty-state"><strong>No teams found</strong>Add teams before calculating standings.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
