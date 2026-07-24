<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$pageTitle = 'Dashboard';
$activePage = 'dashboard';

$counts = [
    'teams' => (int) $pdo->query('SELECT COUNT(*) FROM team')->fetchColumn(),
    'players' => (int) $pdo->query('SELECT COUNT(*) FROM player')->fetchColumn(),
    'matches' => (int) $pdo->query('SELECT COUNT(*) FROM matches')->fetchColumn(),
    'awards' => (int) $pdo->query('SELECT COUNT(*) FROM match_award')->fetchColumn(),
];

$recentMatches = $pdo->query(
    'SELECT m.match_id, m.match_date,
            t1.team_name AS team1_name,
            t2.team_name AS team2_name,
            tw.team_name AS winner_name
     FROM matches m
     JOIN team t1 ON t1.team_id = m.team1_id
     JOIN team t2 ON t2.team_id = m.team2_id
     LEFT JOIN team tw ON tw.team_id = m.winner_team_id
     ORDER BY m.match_date DESC, m.match_id DESC
     LIMIT 6'
)->fetchAll();

$standings = $pdo->query(
    'SELECT t.team_name, pt.matches_played, pt.wins, pt.losses, pt.points
     FROM points_table pt
     JOIN team t ON t.team_id = pt.team_id
     ORDER BY pt.points DESC, pt.wins DESC, t.team_name'
)->fetchAll();

$topBatters = $pdo->query(
    'SELECT p.player_name, t.team_name, SUM(s.runs) AS total_runs
     FROM score s
     JOIN player p ON p.player_id = s.player_id
     LEFT JOIN team t ON t.team_id = p.team_id
     GROUP BY p.player_id, p.player_name, t.team_name
     ORDER BY total_runs DESC, p.player_name
     LIMIT 5'
)->fetchAll();

$topBowlers = $pdo->query(
    'SELECT p.player_name, t.team_name, SUM(s.wickets) AS total_wickets
     FROM score s
     JOIN player p ON p.player_id = s.player_id
     LEFT JOIN team t ON t.team_id = p.team_id
     GROUP BY p.player_id, p.player_name, t.team_name
     ORDER BY total_wickets DESC, p.player_name
     LIMIT 5'
)->fetchAll();

require __DIR__ . '/includes/header.php';
?>

<div class="stats-grid">
    <article class="stat-card">
        <span class="stat-label">Registered Teams</span>
        <strong class="stat-value"><?= $counts['teams'] ?></strong>
        <span class="stat-note">Tournament squads</span>
    </article>
    <article class="stat-card">
        <span class="stat-label">Total Players</span>
        <strong class="stat-value"><?= $counts['players'] ?></strong>
        <span class="stat-note">Across all teams</span>
    </article>
    <article class="stat-card">
        <span class="stat-label">Scheduled Matches</span>
        <strong class="stat-value"><?= $counts['matches'] ?></strong>
        <span class="stat-note">Completed and pending</span>
    </article>
    <article class="stat-card">
        <span class="stat-label">Match Awards</span>
        <strong class="stat-value"><?= $counts['awards'] ?></strong>
        <span class="stat-note">Recorded achievements</span>
    </article>
</div>

<div class="grid grid-equal">
    <section class="card card-accent">
        <div class="card-header">
            <div>
                <h2>Recent Matches</h2>
                <p>Latest fixtures and declared winners</p>
            </div>
            <a class="btn btn-secondary btn-sm" href="matches.php">Manage Matches</a>
        </div>

        <?php if ($recentMatches): ?>
            <div class="table-wrap">
                <table>
                    <thead>
                    <tr>
                        <th>Match</th>
                        <th>Date</th>
                        <th>Winner</th>
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
                                <?php if ($match['winner_name']): ?>
                                    <span class="badge badge-success"><?= h($match['winner_name']) ?></span>
                                <?php else: ?>
                                    <span class="badge badge-warning">Pending</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-state"><strong>No matches found</strong>Add your first match from the Matches page.</div>
        <?php endif; ?>
    </section>

    <section class="card card-accent">
        <div class="card-header">
            <div>
                <h2>Current Standings</h2>
                <p>Two points are awarded for each win</p>
            </div>
            <a class="btn btn-secondary btn-sm" href="points.php">View Points</a>
        </div>

        <?php if ($standings): ?>
            <div class="table-wrap">
                <table>
                    <thead>
                    <tr>
                        <th>#</th>
                        <th>Team</th>
                        <th>MP</th>
                        <th>W</th>
                        <th>L</th>
                        <th>Pts</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($standings as $index => $row): ?>
                        <tr>
                            <td><span class="rank"><?= $index + 1 ?></span></td>
                            <td><strong><?= h($row['team_name']) ?></strong></td>
                            <td><?= (int) $row['matches_played'] ?></td>
                            <td><?= (int) $row['wins'] ?></td>
                            <td><?= (int) $row['losses'] ?></td>
                            <td><span class="badge badge-dark"><?= (int) $row['points'] ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-state"><strong>No standings yet</strong>Use the Points Table page to calculate standings.</div>
        <?php endif; ?>
    </section>
</div>

<div class="grid grid-equal" style="margin-top: 22px;">
    <section class="card">
        <div class="card-header">
            <div>
                <h2>Top Run Scorers</h2>
                <p>Calculated from all score records</p>
            </div>
        </div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Player</th><th>Team</th><th>Runs</th></tr></thead>
                <tbody>
                <?php foreach ($topBatters as $row): ?>
                    <tr>
                        <td><strong><?= h($row['player_name']) ?></strong></td>
                        <td><?= h($row['team_name'] ?? 'Unassigned') ?></td>
                        <td><span class="score-number"><?= (int) $row['total_runs'] ?></span></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$topBatters): ?>
                    <tr><td colspan="3" class="empty-state">No score data available.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section class="card">
        <div class="card-header">
            <div>
                <h2>Top Wicket Takers</h2>
                <p>Calculated from all score records</p>
            </div>
        </div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Player</th><th>Team</th><th>Wickets</th></tr></thead>
                <tbody>
                <?php foreach ($topBowlers as $row): ?>
                    <tr>
                        <td><strong><?= h($row['player_name']) ?></strong></td>
                        <td><?= h($row['team_name'] ?? 'Unassigned') ?></td>
                        <td><span class="score-number"><?= (int) $row['total_wickets'] ?></span></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$topBowlers): ?>
                    <tr><td colspan="3" class="empty-state">No score data available.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>

<section class="card" style="margin-top: 22px;">
    <div class="card-header">
        <div>
            <h2>Quick Management</h2>
            <p>Open the most frequently used tournament modules</p>
        </div>
    </div>
    <div class="quick-links">
        <a class="quick-link" href="players.php"><strong>Add or edit players</strong><span>Manage team assignments and playing roles</span></a>
        <a class="quick-link" href="scores.php"><strong>Enter match scores</strong><span>Record runs and wickets for valid players</span></a>
        <a class="quick-link" href="awards.php"><strong>Assign match awards</strong><span>Select award recipients from participating teams</span></a>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
