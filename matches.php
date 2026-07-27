<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

require_admin();

$pageTitle = 'Matches';
$activePage = 'matches';
$errors = [];

$editMatch = [
    'match_id' => 0,
    'team1_id' => '',
    'team2_id' => '',
    'match_date' => date('Y-m-d'),
    'team1_score' => '',
    'team2_score' => '',
    'result_status' => 'pending',
    'winner_team_id' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = post_string('action');

    if ($action === 'save') {
        $matchId = post_int('match_id');
        $team1Id = post_int('team1_id');
        $team2Id = post_int('team2_id');
        $matchDate = post_string('match_date');
        $team1Score = nullable_post_int('team1_score');
        $team2Score = nullable_post_int('team2_score');

        $winnerTeamId = null;
        $resultStatus = 'pending';

        $editMatch = [
            'match_id' => $matchId,
            'team1_id' => $team1Id,
            'team2_id' => $team2Id,
            'match_date' => $matchDate,
            'team1_score' => $team1Score ?? '',
            'team2_score' => $team2Score ?? '',
            'result_status' => $resultStatus,
            'winner_team_id' => '',
        ];

        if ($team1Id <= 0 || $team2Id <= 0) {
            $errors[] = 'Select both participating teams.';
        }

        if ($team1Id === $team2Id && $team1Id > 0) {
            $errors[] = 'A team cannot play against itself.';
        }

        if (!is_valid_date($matchDate)) {
            $errors[] = 'Enter a valid match date.';
        }

        if ($team1Score !== null && $team1Score < 0) {
            $errors[] = 'Team 1 score cannot be negative.';
        }

        if ($team2Score !== null && $team2Score < 0) {
            $errors[] = 'Team 2 score cannot be negative.';
        }

        if (($team1Score === null) !== ($team2Score === null)) {
            $errors[] = 'Enter both team scores, or leave both score fields empty.';
        }

        if (!$errors && $team1Score !== null && $team2Score !== null) {
            if ($team1Score > $team2Score) {
                $winnerTeamId = $team1Id;
                $resultStatus = 'completed';
            } elseif ($team2Score > $team1Score) {
                $winnerTeamId = $team2Id;
                $resultStatus = 'completed';
            } else {
                $winnerTeamId = null;
                $resultStatus = 'draw';
            }

            $editMatch['result_status'] = $resultStatus;
            $editMatch['winner_team_id'] = $winnerTeamId ?? '';
        }

        if (!$errors) {
            try {
                if ($matchId > 0) {
                    $statement = $pdo->prepare(
                        'UPDATE matches
                         SET team1_id = :team1_id,
                             team2_id = :team2_id,
                             match_date = :match_date,
                             team1_score = :team1_score,
                             team2_score = :team2_score,
                             result_status = :result_status,
                             winner_team_id = :winner_team_id
                         WHERE match_id = :match_id'
                    );

                    $statement->execute([
                        'team1_id' => $team1Id,
                        'team2_id' => $team2Id,
                        'match_date' => $matchDate,
                        'team1_score' => $team1Score,
                        'team2_score' => $team2Score,
                        'result_status' => $resultStatus,
                        'winner_team_id' => $winnerTeamId,
                        'match_id' => $matchId,
                    ]);

                    recalculate_points($pdo);
                    set_flash('success', 'Match updated. Winner and points were calculated automatically.');
                } else {
                    $statement = $pdo->prepare(
                        'INSERT INTO matches
                            (team1_id, team2_id, match_date, team1_score, team2_score, result_status, winner_team_id)
                         VALUES
                            (:team1_id, :team2_id, :match_date, :team1_score, :team2_score, :result_status, :winner_team_id)'
                    );

                    $statement->execute([
                        'team1_id' => $team1Id,
                        'team2_id' => $team2Id,
                        'match_date' => $matchDate,
                        'team1_score' => $team1Score,
                        'team2_score' => $team2Score,
                        'result_status' => $resultStatus,
                        'winner_team_id' => $winnerTeamId,
                    ]);

                    recalculate_points($pdo);
                    set_flash('success', 'Match added. Winner and points were calculated automatically.');
                }

                redirect('matches.php');
            } catch (PDOException $exception) {
                $errors[] = db_error_message($exception);
            }
        }
    }

    if ($action === 'delete') {
        $matchId = post_int('match_id');

        try {
            $statement = $pdo->prepare(
                'DELETE FROM matches WHERE match_id = :match_id'
            );

            $statement->execute([
                'match_id' => $matchId,
            ]);

            recalculate_points($pdo);
            set_flash('success', 'Match deleted and points recalculated.');
        } catch (PDOException $exception) {
            set_flash('error', db_error_message($exception));
        }

        redirect('matches.php');
    }
}

if (isset($_GET['edit'])) {
    $matchId = (int) $_GET['edit'];

    $statement = $pdo->prepare(
        'SELECT
            match_id,
            team1_id,
            team2_id,
            match_date,
            team1_score,
            team2_score,
            result_status,
            winner_team_id
         FROM matches
         WHERE match_id = :match_id
         LIMIT 1'
    );

    $statement->execute([
        'match_id' => $matchId,
    ]);

    $foundMatch = $statement->fetch();

    if ($foundMatch) {
        $editMatch = $foundMatch;
    }
}

$teams = $pdo->query(
    'SELECT team_id, team_name
     FROM team
     ORDER BY team_name'
)->fetchAll();

$matches = $pdo->query(
    'SELECT
        m.match_id,
        m.team1_id,
        m.team2_id,
        m.match_date,
        m.team1_score,
        m.team2_score,
        m.result_status,
        m.winner_team_id,
        t1.team_name AS team1_name,
        t2.team_name AS team2_name,
        tw.team_name AS winner_name,
        COUNT(DISTINCT s.score_id) AS score_count,
        COUNT(DISTINCT ma.match_award_id) AS award_count
     FROM matches m
     JOIN team t1 ON t1.team_id = m.team1_id
     JOIN team t2 ON t2.team_id = m.team2_id
     LEFT JOIN team tw ON tw.team_id = m.winner_team_id
     LEFT JOIN score s ON s.match_id = m.match_id
     LEFT JOIN match_award ma ON ma.match_id = m.match_id
     GROUP BY
        m.match_id,
        m.team1_id,
        m.team2_id,
        m.match_date,
        m.team1_score,
        m.team2_score,
        m.result_status,
        m.winner_team_id,
        t1.team_name,
        t2.team_name,
        tw.team_name
     ORDER BY m.match_date DESC, m.match_id DESC'
)->fetchAll();

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
                <h2><?= (int) $editMatch['match_id'] > 0 ? 'Edit Match' : 'Schedule Match' ?></h2>
                <p>Enter both team totals to calculate the winner automatically</p>
            </div>
        </div>

        <?php if (count($teams) < 2): ?>
            <div class="alert alert-warning">
                <span>Create at least two teams before scheduling a match.</span>
            </div>
        <?php endif; ?>

        <form method="post" class="form-grid">
            <?= csrf_field() ?>

            <input type="hidden" name="action" value="save">
            <input type="hidden" name="match_id" value="<?= (int) $editMatch['match_id'] ?>">

            <div class="form-group">
                <label class="required" for="team1_id">Team 1</label>

                <select id="team1_id" name="team1_id" required>
                    <option value="">Select first team</option>

                    <?php foreach ($teams as $team): ?>
                        <option
                            value="<?= (int) $team['team_id'] ?>"
                            <?= (int) $editMatch['team1_id'] === (int) $team['team_id'] ? 'selected' : '' ?>
                        >
                            <?= h($team['team_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label class="required" for="team2_id">Team 2</label>

                <select id="team2_id" name="team2_id" required>
                    <option value="">Select second team</option>

                    <?php foreach ($teams as $team): ?>
                        <option
                            value="<?= (int) $team['team_id'] ?>"
                            <?= (int) $editMatch['team2_id'] === (int) $team['team_id'] ? 'selected' : '' ?>
                        >
                            <?= h($team['team_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group full">
                <label class="required" for="match_date">Match Date</label>

                <input
                    id="match_date"
                    name="match_date"
                    type="date"
                    required
                    value="<?= h($editMatch['match_date']) ?>"
                >
            </div>

            <div class="form-group">
                <label for="team1_score">Team 1 Total Score</label>

                <input
                    id="team1_score"
                    name="team1_score"
                    type="number"
                    min="0"
                    placeholder="Example: 120"
                    value="<?= $editMatch['team1_score'] !== null ? h($editMatch['team1_score']) : '' ?>"
                >
            </div>

            <div class="form-group">
                <label for="team2_score">Team 2 Total Score</label>

                <input
                    id="team2_score"
                    name="team2_score"
                    type="number"
                    min="0"
                    placeholder="Example: 125"
                    value="<?= $editMatch['team2_score'] !== null ? h($editMatch['team2_score']) : '' ?>"
                >
            </div>

            <div class="form-group full">
                <div class="note-box">
                    Leave both scores empty while the match is pending.
                    After entering both scores, the higher-scoring team becomes the winner automatically.
                    Equal scores are recorded as a draw.
                </div>
            </div>

            <?php if ((int) $editMatch['match_id'] > 0): ?>
                <div class="form-group full">
                    <label>Current Result</label>

                    <?php if ($editMatch['result_status'] === 'completed'): ?>
                        <span class="badge badge-success">Completed</span>
                    <?php elseif ($editMatch['result_status'] === 'draw'): ?>
                        <span class="badge badge-muted">Draw</span>
                    <?php else: ?>
                        <span class="badge badge-warning">Pending</span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <div class="form-actions">
                <button
                    class="btn btn-primary"
                    type="submit"
                    <?= count($teams) < 2 ? 'disabled' : '' ?>
                >
                    <?= (int) $editMatch['match_id'] > 0 ? 'Update Match' : 'Add Match' ?>
                </button>

                <?php if ((int) $editMatch['match_id'] > 0): ?>
                    <a class="btn btn-light" href="matches.php">Cancel</a>
                <?php endif; ?>
            </div>
        </form>
    </section>

    <section class="card">
        <div class="card-header">
            <div>
                <h2>Match Schedule and Results</h2>
                <p><?= count($matches) ?> match records</p>
            </div>
        </div>

        <div class="table-toolbar">
            <div class="search-box">
                <input
                    type="search"
                    placeholder="Search team, date, score or winner..."
                    data-table-search="matchesTable"
                >
            </div>
        </div>

        <div class="table-wrap">
            <table id="matchesTable">
                <thead>
                <tr>
                    <th>Match</th>
                    <th>Date</th>
                    <th>Score</th>
                    <th>Result</th>
                    <th>Data</th>
                    <th>Actions</th>
                </tr>
                </thead>

                <tbody>
                <?php foreach ($matches as $match): ?>
                    <tr>
                        <td>
                            <strong>
                                #<?= (int) $match['match_id'] ?> · <?= h($match['team1_name']) ?>
                            </strong>
                            <br>
                            <span class="muted">vs <?= h($match['team2_name']) ?></span>
                        </td>

                        <td><?= h(format_date($match['match_date'])) ?></td>

                        <td>
                            <?php if ($match['team1_score'] !== null && $match['team2_score'] !== null): ?>
                                <strong>
                                    <?= h($match['team1_name']) ?>:
                                    <span class="score-number"><?= (int) $match['team1_score'] ?></span>
                                </strong>
                                <br>
                                <strong>
                                    <?= h($match['team2_name']) ?>:
                                    <span class="score-number"><?= (int) $match['team2_score'] ?></span>
                                </strong>
                            <?php else: ?>
                                <span class="muted">Score not entered</span>
                            <?php endif; ?>
                        </td>

                        <td>
                            <?php if ($match['result_status'] === 'completed' && $match['winner_name']): ?>
                                <span class="badge badge-success">
                                    <?= h($match['winner_name']) ?> won
                                </span>
                            <?php elseif ($match['result_status'] === 'draw'): ?>
                                <span class="badge badge-muted">Draw</span>
                            <?php else: ?>
                                <span class="badge badge-warning">Pending</span>
                            <?php endif; ?>
                        </td>

                        <td>
                            <span class="badge badge-muted">
                                <?= (int) $match['score_count'] ?> player scores
                            </span>

                            <span class="badge badge-muted">
                                <?= (int) $match['award_count'] ?> awards
                            </span>
                        </td>

                        <td>
                            <div class="table-actions">
                                <a
                                    class="btn btn-secondary btn-sm"
                                    href="matches.php?edit=<?= (int) $match['match_id'] ?>"
                                >
                                    Edit
                                </a>

                                <form
                                    method="post"
                                    class="inline-form"
                                    data-confirm="Delete this match? Its scores and awards will also be deleted."
                                >
                                    <?= csrf_field() ?>

                                    <input type="hidden" name="action" value="delete">
                                    <input
                                        type="hidden"
                                        name="match_id"
                                        value="<?= (int) $match['match_id'] ?>"
                                    >

                                    <button class="btn btn-danger btn-sm" type="submit">
                                        Delete
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>

                <?php if (!$matches): ?>
                    <tr>
                        <td colspan="6" class="empty-state">
                            <strong>No matches found</strong>
                            Schedule a match using the form.
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>