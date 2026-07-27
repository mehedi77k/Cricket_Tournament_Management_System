<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

require_login();

if (is_admin()) {
    redirect('index.php');
}

$pageTitle = 'User Dashboard';
$activePage = 'user-dashboard';

$counts = [
    'teams' => (int) $pdo->query('SELECT COUNT(*) FROM team')->fetchColumn(),
    'players' => (int) $pdo->query('SELECT COUNT(*) FROM player')->fetchColumn(),
    'matches' => (int) $pdo->query('SELECT COUNT(*) FROM matches')->fetchColumn(),
    'awards' => (int) $pdo->query('SELECT COUNT(*) FROM match_award')->fetchColumn(),
];

$recentMatches = $pdo->query(
    'SELECT
        m.match_id,
        m.match_date,
        m.team1_score,
        m.team2_score,
        m.result_status,
        t1.team_name AS team1_name,
        t2.team_name AS team2_name,
        tw.team_name AS winner_name
     FROM matches m
     JOIN team t1 ON t1.team_id = m.team1_id
     JOIN team t2 ON t2.team_id = m.team2_id
     LEFT JOIN team tw ON tw.team_id = m.winner_team_id
     ORDER BY m.match_date DESC, m.match_id DESC
     LIMIT 8'
)->fetchAll();

$standings = $pdo->query(
    'SELECT
        t.team_name,
        t.captain_name,
        COALESCE(pt.matches_played, 0) AS matches_played,
        COALESCE(pt.wins, 0) AS wins,
        COALESCE(pt.losses, 0) AS losses,
        COALESCE(pt.draws, 0) AS draws,
        COALESCE(pt.points, 0) AS points
     FROM team t
     LEFT JOIN points_table pt ON pt.team_id = t.team_id
     ORDER BY
        points DESC,
        wins DESC,
        draws DESC,
        t.team_name'
)->fetchAll();

require __DIR__ . '/includes/header.php';
?>

<div class="stats-grid">
    <article class="stat-card">
        <span class="stat-label">Teams</span>
        <strong class="stat-value"><?= $counts['teams'] ?></strong>
    </article>

    <article class="stat-card">
        <span class="stat-label">Players</span>
        <strong class="stat-value"><?= $counts['players'] ?></strong>
    </article>

    <article class="stat-card">
        <span class="stat-label">Matches</span>
        <strong class="stat-value"><?= $counts['matches'] ?></strong>
    </article>

    <article class="stat-card">
        <span class="stat-label">Awards</span>
        <strong class="stat-value"><?= $counts['awards'] ?></strong>
    </article>
</div>

<section class="card card-accent">
    <div class="card-header">
        <div>
            <h2>Recent Match Results</h2>
            <p>Team totals and automatically calculated winners</p>
        </div>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
            <tr>
                <th>Match</th>
                <th>Date</th>
                <th>Score</th>
                <th>Result</th>
            </tr>
            </thead>

            <tbody>
            <?php foreach ($recentMatches as $match): ?>
                <tr>
                    <td>
                        <strong><?= h($match['team1_name']) ?></strong>
                        <span class="muted"> vs </span>
                        <strong><?= h($match['team2_name']) ?></strong>
                    </td>

                    <td><?= h(format_date($match['match_date'])) ?></td>

                    <td>
                        <?php if ($match['team1_score'] !== null && $match['team2_score'] !== null): ?>
                            <strong>
                                <?= h($match['team1_name']) ?> <?= (int) $match['team1_score'] ?>
                                -
                                <?= (int) $match['team2_score'] ?> <?= h($match['team2_name']) ?>
                            </strong>
                        <?php else: ?>
                            <span class="muted">Score not entered</span>
                        <?php endif; ?>
                    </td>

                    <td>
                        <?php if ($match['result_status'] === 'completed' && $match['winner_name']): ?>
                            <span class="badge badge-success"><?= h($match['winner_name']) ?> won</span>
                        <?php elseif ($match['result_status'] === 'draw'): ?>
                            <span class="badge badge-muted">Draw</span>
                        <?php else: ?>
                            <span class="badge badge-warning">Pending</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>

            <?php if (!$recentMatches): ?>
                <tr>
                    <td colspan="4" class="empty-state">
                        <strong>No matches found</strong>
                    </td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<section class="card" style="margin-top: 22px;">
    <div class="card-header">
        <div>
            <h2>Points Table</h2>
            <p>Win = 2 points, Draw = 1 point, Loss = 0 points</p>
        </div>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
            <tr>
                <th>#</th>
                <th>Team</th>
                <th>Captain</th>
                <th>MP</th>
                <th>W</th>
                <th>L</th>
                <th>D</th>
                <th>Points</th>
            </tr>
            </thead>

            <tbody>
            <?php foreach ($standings as $index => $row): ?>
                <tr>
                    <td><?= $index + 1 ?></td>
                    <td><strong><?= h($row['team_name']) ?></strong></td>
                    <td><?= h($row['captain_name']) ?></td>
                    <td><?= (int) $row['matches_played'] ?></td>
                    <td><?= (int) $row['wins'] ?></td>
                    <td><?= (int) $row['losses'] ?></td>
                    <td><?= (int) $row['draws'] ?></td>
                    <td><span class="badge badge-dark"><?= (int) $row['points'] ?></span></td>
                </tr>
            <?php endforeach; ?>

            <?php if (!$standings): ?>
                <tr>
                    <td colspan="8">No team information found.</td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>