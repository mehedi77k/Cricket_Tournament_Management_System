<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

require_admin();

$pageTitle = 'Scores';
$activePage = 'scores';
$errors = [];

$editScore = [
    'score_id' => 0,
    'match_id' => '',
    'player_id' => '',
    'runs' => 0,
    'wickets' => 0,
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = post_string('action');

    if ($action === 'save') {
        $scoreId = post_int('score_id');
        $matchId = post_int('match_id');
        $playerId = post_int('player_id');
        $runs = post_int('runs');
        $wickets = post_int('wickets');

        $editScore = [
            'score_id' => $scoreId,
            'match_id' => $matchId,
            'player_id' => $playerId,
            'runs' => $runs,
            'wickets' => $wickets,
        ];

        if ($matchId <= 0) {
            $errors[] = 'Select a match.';
        }
        if ($playerId <= 0) {
            $errors[] = 'Select a player.';
        }
        if ($runs < 0 || $wickets < 0) {
            $errors[] = 'Runs and wickets cannot be negative.';
        }
        if ($matchId > 0 && $playerId > 0 && !player_belongs_to_match($pdo, $playerId, $matchId)) {
            $errors[] = 'The selected player does not belong to either team in this match.';
        }

        if (!$errors) {
            $duplicateStatement = $pdo->prepare(
                'SELECT score_id
                 FROM score
                 WHERE match_id = :match_id
                   AND player_id = :player_id
                   AND score_id <> :score_id
                 LIMIT 1'
            );
            $duplicateStatement->execute([
                'match_id' => $matchId,
                'player_id' => $playerId,
                'score_id' => $scoreId,
            ]);

            if ($duplicateStatement->fetch()) {
                $errors[] = 'This player already has a score record for the selected match.';
            }
        }

        if (!$errors) {
            try {
                if ($scoreId > 0) {
                    $statement = $pdo->prepare(
                        'UPDATE score
                         SET match_id = :match_id, player_id = :player_id, runs = :runs, wickets = :wickets
                         WHERE score_id = :score_id'
                    );
                    $statement->execute([
                        'match_id' => $matchId,
                        'player_id' => $playerId,
                        'runs' => $runs,
                        'wickets' => $wickets,
                        'score_id' => $scoreId,
                    ]);
                    //set_flash('success', 'Score updated successfully.');
                } else {
                    $statement = $pdo->prepare(
                        'INSERT INTO score (match_id, player_id, runs, wickets)
                         VALUES (:match_id, :player_id, :runs, :wickets)'
                    );
                    $statement->execute([
                        'match_id' => $matchId,
                        'player_id' => $playerId,
                        'runs' => $runs,
                        'wickets' => $wickets,
                    ]);
                    //set_flash('success', 'Score added successfully.');
                }
                redirect('scores.php');
            } catch (PDOException $exception) {
                $errors[] = db_error_message($exception);
            }
        }
    }

    if ($action === 'delete') {
        $scoreId = post_int('score_id');
        try {
            $statement = $pdo->prepare('DELETE FROM score WHERE score_id = :score_id');
            $statement->execute(['score_id' => $scoreId]);
            //set_flash('success', 'Score deleted successfully.');
        } catch (PDOException $exception) {
            set_flash('error', db_error_message($exception));
        }
        redirect('scores.php');
    }
}

if (isset($_GET['edit'])) {
    $scoreId = (int) $_GET['edit'];
    $statement = $pdo->prepare(
        'SELECT score_id, match_id, player_id, runs, wickets FROM score WHERE score_id = :score_id'
    );
    $statement->execute(['score_id' => $scoreId]);
    $foundScore = $statement->fetch();
    if ($foundScore) {
        $editScore = $foundScore;
    }
}

$filterMatchId = isset($_GET['match_id']) ? (int) $_GET['match_id'] : 0;
$matches = $pdo->query(
    'SELECT m.match_id, m.match_date, t1.team_name AS team1_name, t2.team_name AS team2_name
     FROM matches m
     JOIN team t1 ON t1.team_id = m.team1_id
     JOIN team t2 ON t2.team_id = m.team2_id
     ORDER BY m.match_date DESC, m.match_id DESC'
)->fetchAll();

$scoreSql =
    'SELECT s.score_id, s.match_id, s.player_id, s.runs, s.wickets,
            p.player_name, p.role, t.team_name,
            m.match_date, t1.team_name AS team1_name, t2.team_name AS team2_name
     FROM score s
     JOIN player p ON p.player_id = s.player_id
     LEFT JOIN team t ON t.team_id = p.team_id
     JOIN matches m ON m.match_id = s.match_id
     JOIN team t1 ON t1.team_id = m.team1_id
     JOIN team t2 ON t2.team_id = m.team2_id';

$params = [];
if ($filterMatchId > 0) {
    $scoreSql .= ' WHERE s.match_id = :match_id';
    $params['match_id'] = $filterMatchId;
}
$scoreSql .= ' ORDER BY m.match_date DESC, s.match_id DESC, s.runs DESC, s.wickets DESC';

$scoreStatement = $pdo->prepare($scoreSql);
$scoreStatement->execute($params);
$scores = $scoreStatement->fetchAll();

require __DIR__ . '/includes/header.php';
?>

<?php if ($errors): ?>
    <div class="alert alert-error">
        <span><?= h(implode(' ', $errors)) ?></span>
        <button type="button" class="alert-close">×</button>
    </div>
<?php endif; ?>

<div class="grid grid-2">
    <section class="card card-accent">
        <div class="card-header">
            <div>
                <h2><?= (int) $editScore['score_id'] > 0 ? 'Edit Player Score' : 'Add Player Score' ?></h2>
                <p>Only players from the selected match teams are available</p>
            </div>
        </div>

        <form method="post" class="form-grid">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="score_id" value="<?= (int) $editScore['score_id'] ?>">

            <div class="form-group full">
                <label class="required" for="score_match_id">Match</label>
                <select id="score_match_id" name="match_id" required data-player-match-select="score_player_id">
                    <option value="">Select match</option>
                    <?php foreach ($matches as $match): ?>
                        <option value="<?= (int) $match['match_id'] ?>" <?= (int) $editScore['match_id'] === (int) $match['match_id'] ? 'selected' : '' ?>>
                            #<?= (int) $match['match_id'] ?> · <?= h($match['team1_name']) ?> vs <?= h($match['team2_name']) ?> · <?= h(format_date($match['match_date'])) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group full">
                <label class="required" for="score_player_id">Player</label>
                <select id="score_player_id" name="player_id" required data-selected-player="<?= h($editScore['player_id']) ?>" disabled>
                    <option value="">Select player</option>
                </select>
            </div>

            <div class="form-group">
                <label class="required" for="runs">Runs</label>
                <input id="runs" name="runs" type="number" min="0" required value="<?= (int) $editScore['runs'] ?>">
            </div>

            <div class="form-group">
                <label class="required" for="wickets">Wickets</label>
                <input id="wickets" name="wickets" type="number" min="0" required value="<?= (int) $editScore['wickets'] ?>">
            </div>

            <div class="form-actions">
                <button class="btn btn-primary" type="submit"><?= (int) $editScore['score_id'] > 0 ? 'Update Score' : 'Add Score' ?></button>
                <?php if ((int) $editScore['score_id'] > 0): ?>
                    <a class="btn btn-light" href="scores.php">Cancel</a>
                <?php endif; ?>
            </div>
        </form>
    </section>

    <section class="card">
        <div class="card-header">
            <div>
                <h2>Score Records</h2>
                <p><?= count($scores) ?> rows shown</p>
            </div>
        </div>

        <div class="table-toolbar">
            <form method="get" class="filter-form">
                <div class="form-group">
                    <label for="filter_match_id">Filter by match</label>
                    <select id="filter_match_id" name="match_id">
                        <option value="">All matches</option>
                        <?php foreach ($matches as $match): ?>
                            <option value="<?= (int) $match['match_id'] ?>" <?= $filterMatchId === (int) $match['match_id'] ? 'selected' : '' ?>>
                                #<?= (int) $match['match_id'] ?> · <?= h($match['team1_name']) ?> vs <?= h($match['team2_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button class="btn btn-secondary" type="submit">Apply Filter</button>
                <?php if ($filterMatchId > 0): ?><a class="btn btn-light" href="scores.php">Clear</a><?php endif; ?>
            </form>
            <div class="search-box">
                <input type="search" placeholder="Search player or team..." data-table-search="scoresTable">
            </div>
        </div>

        <div class="table-wrap">
            <table id="scoresTable">
                <thead>
                <tr>
                    <th>Match</th>
                    <th>Player</th>
                    <th>Team</th>
                    <th>Runs</th>
                    <th>Wickets</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($scores as $score): ?>
                    <tr>
                        <td>
                            <strong>#<?= (int) $score['match_id'] ?></strong><br>
                            <span class="muted"><?= h($score['team1_name']) ?> vs <?= h($score['team2_name']) ?></span>
                        </td>
                        <td><strong><?= h($score['player_name']) ?></strong><br><span class="muted"><?= h($score['role']) ?></span></td>
                        <td><?= h($score['team_name'] ?? 'Unassigned') ?></td>
                        <td><span class="score-number"><?= (int) $score['runs'] ?></span></td>
                        <td><span class="score-number"><?= (int) $score['wickets'] ?></span></td>
                        <td>
                            <div class="table-actions">
                                <a class="btn btn-secondary btn-sm" href="scores.php?edit=<?= (int) $score['score_id'] ?>">Edit</a>
                                <form method="post" class="inline-form" data-confirm="Delete this score record?">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="score_id" value="<?= (int) $score['score_id'] ?>">
                                    <button class="btn btn-danger btn-sm" type="submit">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$scores): ?>
                    <tr><td colspan="6" class="empty-state"><strong>No score records found</strong>Add a score using the form.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
