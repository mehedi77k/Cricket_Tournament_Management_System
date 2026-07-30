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
    'team1_score' => null,
    'team1_wickets' => null,
    'team1_overs' => null,
    'team2_score' => null,
    'team2_wickets' => null,
    'team2_overs' => null,
    'result_status' => 'pending',
    'winner_team_id' => null,
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = post_string('action');

    if ($action === 'save') {
        $matchId = post_int('match_id');
        $team1Id = post_int('team1_id');
        $team2Id = post_int('team2_id');
        $matchDate = post_string('match_date');
        $matchState = post_string('match_state');

        $team1Score = nullable_post_int('team1_score');
        $team1Wickets = nullable_post_int('team1_wickets');
        $team1OversInput = nullable_post_string('team1_overs');
        $team2Score = nullable_post_int('team2_score');
        $team2Wickets = nullable_post_int('team2_wickets');
        $team2OversInput = nullable_post_string('team2_overs');

        $winnerTeamId = null;
        $resultStatus = 'pending';

        if (!in_array($matchState, ['pending', 'live', 'completed'], true)) {
            $matchState = 'pending';
            $errors[] = 'Select a valid match status.';
        }

        if ($team1Id <= 0 || $team2Id <= 0) {
            $errors[] = 'Select both participating teams.';
        }

        if ($team1Id === $team2Id && $team1Id > 0) {
            $errors[] = 'A team cannot play against itself.';
        }

        if (!is_valid_date($matchDate)) {
            $errors[] = 'Enter a valid match date.';
        }

        foreach ([
            'Team 1 runs' => $team1Score,
            'Team 2 runs' => $team2Score,
        ] as $label => $value) {
            if ($value !== null && $value < 0) {
                $errors[] = $label . ' cannot be negative.';
            }
        }

        foreach ([
            'Team 1 wickets' => $team1Wickets,
            'Team 2 wickets' => $team2Wickets,
        ] as $label => $value) {
            if ($value !== null && ($value < 0 || $value > 10)) {
                $errors[] = $label . ' must be between 0 and 10.';
            }
        }

        if (!is_valid_cricket_overs($team1OversInput)) {
            $errors[] = 'Team 1 overs must use cricket format, such as 19.4 or 20.';
        }

        if (!is_valid_cricket_overs($team2OversInput)) {
            $errors[] = 'Team 2 overs must use cricket format, such as 19.4 or 20.';
        }

        if ($matchState === 'completed' && ($team1Score === null || $team2Score === null)) {
            $errors[] = 'Both team runs are required before completing the match.';
        }

        if ($matchState === 'pending') {
            $team1Score = null;
            $team1Wickets = null;
            $team1Overs = null;
            $team2Score = null;
            $team2Wickets = null;
            $team2Overs = null;
            $resultStatus = 'pending';
        } else {
            $team1Score ??= 0;
            $team1Wickets ??= 0;
            $team1Overs = normalize_cricket_overs($team1OversInput) ?? '0.0';
            $team2Score ??= 0;
            $team2Wickets ??= 0;
            $team2Overs = normalize_cricket_overs($team2OversInput) ?? '0.0';

            if ($matchState === 'live') {
                $resultStatus = 'live';
            } elseif (!$errors) {
                if ($team1Score > $team2Score) {
                    $winnerTeamId = $team1Id;
                    $resultStatus = 'completed';
                } elseif ($team2Score > $team1Score) {
                    $winnerTeamId = $team2Id;
                    $resultStatus = 'completed';
                } else {
                    $resultStatus = 'draw';
                }
            }
        }

        $editMatch = [
            'match_id' => $matchId,
            'team1_id' => $team1Id,
            'team2_id' => $team2Id,
            'match_date' => $matchDate,
            'team1_score' => $team1Score,
            'team1_wickets' => $team1Wickets,
            'team1_overs' => $team1Overs ?? null,
            'team2_score' => $team2Score,
            'team2_wickets' => $team2Wickets,
            'team2_overs' => $team2Overs ?? null,
            'result_status' => $resultStatus,
            'winner_team_id' => $winnerTeamId,
        ];

        if (!$errors) {
            try {
                $parameters = [
                    'team1_id' => $team1Id,
                    'team2_id' => $team2Id,
                    'match_date' => $matchDate,
                    'team1_score' => $team1Score,
                    'team1_wickets' => $team1Wickets,
                    'team1_overs' => $team1Overs,
                    'team2_score' => $team2Score,
                    'team2_wickets' => $team2Wickets,
                    'team2_overs' => $team2Overs,
                    'result_status' => $resultStatus,
                    'winner_team_id' => $winnerTeamId,
                ];

                if ($matchId > 0) {
                    $statement = $pdo->prepare(
                        'UPDATE matches
                         SET team1_id = :team1_id,
                             team2_id = :team2_id,
                             match_date = :match_date,
                             team1_score = :team1_score,
                             team1_wickets = :team1_wickets,
                             team1_overs = :team1_overs,
                             team2_score = :team2_score,
                             team2_wickets = :team2_wickets,
                             team2_overs = :team2_overs,
                             result_status = :result_status,
                             winner_team_id = :winner_team_id
                         WHERE match_id = :match_id'
                    );
                    $parameters['match_id'] = $matchId;
                    $statement->execute($parameters);
                    $message = 'Match updated successfully.';
                } else {
                    $statement = $pdo->prepare(
                        'INSERT INTO matches
                            (team1_id, team2_id, match_date,
                             team1_score, team1_wickets, team1_overs,
                             team2_score, team2_wickets, team2_overs,
                             result_status, winner_team_id)
                         VALUES
                            (:team1_id, :team2_id, :match_date,
                             :team1_score, :team1_wickets, :team1_overs,
                             :team2_score, :team2_wickets, :team2_overs,
                             :result_status, :winner_team_id)'
                    );
                    $statement->execute($parameters);
                    $message = 'Match added successfully.';
                }

                recalculate_points($pdo);

                if ($resultStatus === 'completed' && $winnerTeamId !== null) {
                    $message .= ' The winner and points were calculated automatically.';
                } elseif ($resultStatus === 'draw') {
                    $message .= ' The match was recorded as a draw and points were recalculated.';
                } elseif ($resultStatus === 'live') {
                    $message .= ' The live score is now visible on the dashboards.';
                }

                //set_flash('success', $message);
                redirect('matches.php');
            } catch (PDOException $exception) {
                $errors[] = db_error_message($exception);
            }
        }
    }

    if ($action === 'delete') {
        $matchId = post_int('match_id');

        try {
            $statement = $pdo->prepare('DELETE FROM matches WHERE match_id = :match_id');
            $statement->execute(['match_id' => $matchId]);
            recalculate_points($pdo);
            //set_flash('success', 'Match deleted and points recalculated.');
        } catch (PDOException $exception) {
            //set_flash('error', db_error_message($exception));
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
            team1_wickets,
            team1_overs,
            team2_score,
            team2_wickets,
            team2_overs,
            result_status,
            winner_team_id
         FROM matches
         WHERE match_id = :match_id
         LIMIT 1'
    );
    $statement->execute(['match_id' => $matchId]);
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
        m.team1_wickets,
        m.team1_overs,
        m.team2_score,
        m.team2_wickets,
        m.team2_overs,
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
        m.team1_wickets,
        m.team1_overs,
        m.team2_score,
        m.team2_wickets,
        m.team2_overs,
        m.result_status,
        m.winner_team_id,
        t1.team_name,
        t2.team_name,
        tw.team_name
     ORDER BY m.match_date DESC, m.match_id DESC'
)->fetchAll();

$formState = in_array($editMatch['result_status'], ['completed', 'draw'], true)
    ? 'completed'
    : (string) $editMatch['result_status'];

require __DIR__ . '/includes/header.php';
?>

<?php if ($errors): ?>
    <div class="alert alert-error">
        <span><?= h(implode(' ', $errors)) ?></span>
        <button type="button" class="alert-close">×</button>
    </div>
<?php endif; ?>

<div class="grid grid-2 matches-layout">
    <section class="card card-accent">
        <div class="card-header">
            <div>
                <h2><?= (int) $editMatch['match_id'] > 0 ? 'Edit Match' : 'Schedule Match' ?></h2>
                <p>Use Pending, Live, or Completed status to control score calculation</p>
            </div>
        </div>

        <?php if (count($teams) < 2): ?>
            <div class="alert alert-warning">
                <span>Create at least two teams before scheduling a match.</span>
            </div>
        <?php endif; ?>

        <form method="post" class="form-grid" id="matchForm">
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

            <div class="form-group">
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
                <label class="required" for="match_state">Match Status</label>
                <select id="match_state" name="match_state" required>
                    <option value="pending" <?= $formState === 'pending' ? 'selected' : '' ?>>Pending / Scheduled</option>
                    <option value="live" <?= $formState === 'live' ? 'selected' : '' ?>>Live / In Progress</option>
                    <option value="completed" <?= $formState === 'completed' ? 'selected' : '' ?>>Completed</option>
                </select>
            </div>

            <div class="innings-grid full" id="inningsFields">
                <fieldset class="innings-card">
                    <legend>Team 1 Innings</legend>

                    <div class="form-group">
                        <label for="team1_score">Runs</label>
                        <input
                            id="team1_score"
                            name="team1_score"
                            type="number"
                            min="0"
                            placeholder="166"
                            value="<?= $editMatch['team1_score'] !== null ? h($editMatch['team1_score']) : '' ?>"
                        >
                    </div>

                    <div class="form-group">
                        <label for="team1_wickets">Wickets</label>
                        <input
                            id="team1_wickets"
                            name="team1_wickets"
                            type="number"
                            min="0"
                            max="10"
                            placeholder="7"
                            value="<?= $editMatch['team1_wickets'] !== null ? h($editMatch['team1_wickets']) : '' ?>"
                        >
                    </div>

                    <div class="form-group">
                        <label for="team1_overs">Overs</label>
                        <input
                            id="team1_overs"
                            name="team1_overs"
                            type="text"
                            inputmode="decimal"
                            placeholder="20 or 19.4"
                            value="<?= $editMatch['team1_overs'] !== null ? h(format_cricket_overs($editMatch['team1_overs'])) : '' ?>"
                        >
                    </div>
                </fieldset>

                <fieldset class="innings-card">
                    <legend>Team 2 Innings</legend>

                    <div class="form-group">
                        <label for="team2_score">Runs</label>
                        <input
                            id="team2_score"
                            name="team2_score"
                            type="number"
                            min="0"
                            placeholder="150"
                            value="<?= $editMatch['team2_score'] !== null ? h($editMatch['team2_score']) : '' ?>"
                        >
                    </div>

                    <div class="form-group">
                        <label for="team2_wickets">Wickets</label>
                        <input
                            id="team2_wickets"
                            name="team2_wickets"
                            type="number"
                            min="0"
                            max="10"
                            placeholder="8"
                            value="<?= $editMatch['team2_wickets'] !== null ? h($editMatch['team2_wickets']) : '' ?>"
                        >
                    </div>

                    <div class="form-group">
                        <label for="team2_overs">Overs</label>
                        <input
                            id="team2_overs"
                            name="team2_overs"
                            type="text"
                            inputmode="decimal"
                            placeholder="20 or 19.4"
                            value="<?= $editMatch['team2_overs'] !== null ? h(format_cricket_overs($editMatch['team2_overs'])) : '' ?>"
                        >
                    </div>
                </fieldset>
            </div>

            <div class="form-group full">
                <small
                    id="liveSaveStatus"
                    class="live-save-status"
                    aria-live="polite"
                    hidden
                ></small>
            </div>

            <?php if ((int) $editMatch['match_id'] > 0): ?>
                <div class="form-group full">
                    <label>Current Result</label>
                    <div>
                        <?php if ($editMatch['result_status'] === 'completed'): ?>
                            <span class="badge badge-success">Completed</span>
                        <?php elseif ($editMatch['result_status'] === 'draw'): ?>
                            <span class="badge badge-muted">Draw</span>
                        <?php elseif ($editMatch['result_status'] === 'live'): ?>
                            <span class="badge badge-live">Live</span>
                        <?php else: ?>
                            <span class="badge badge-warning">Pending</span>
                        <?php endif; ?>
                    </div>
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
                            <strong>#<?= (int) $match['match_id'] ?> · <?= h($match['team1_name']) ?></strong>
                            <br>
                            <span class="muted">vs <?= h($match['team2_name']) ?></span>
                        </td>

                        <td><?= h(format_date($match['match_date'])) ?></td>

                        <td>
                            <strong
                                class="match-score-line"
                                data-live-score-id="<?= (int) $match['match_id'] ?>"
                            ><?= h(format_match_score($match)) ?></strong>
                        </td>

                        <td>
                            <?php if ($match['result_status'] === 'completed' && $match['winner_name']): ?>
                                <span class="badge badge-success"><?= h($match['winner_name']) ?> won</span>
                            <?php elseif ($match['result_status'] === 'draw'): ?>
                                <span class="badge badge-muted">Draw</span>
                            <?php elseif ($match['result_status'] === 'live'): ?>
                                <span class="badge badge-live">Live</span>
                            <?php else: ?>
                                <span class="badge badge-warning">Pending</span>
                            <?php endif; ?>
                        </td>

                        <td>
                            <span class="badge badge-muted"><?= (int) $match['score_count'] ?> player scores</span>
                            <span class="badge badge-muted"><?= (int) $match['award_count'] ?> awards</span>
                        </td>

                        <td>
                            <div class="table-actions">
                                <a class="btn btn-secondary btn-sm" href="matches.php?edit=<?= (int) $match['match_id'] ?>">
                                    Edit
                                </a>

                                <form
                                    method="post"
                                    class="inline-form"
                                    data-confirm="Delete this match? Its scores and awards will also be deleted."
                                >
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="match_id" value="<?= (int) $match['match_id'] ?>">
                                    <button class="btn btn-danger btn-sm" type="submit">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>

                <?php if (!$matches): ?>
                    <tr>
                        <td colspan="6" class="empty-state">
                            <strong>No matches found</strong>
                            Add the first match using the form.
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>