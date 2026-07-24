<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$pageTitle = 'Match Awards';
$activePage = 'awards';
$errors = [];

$editAward = [
    'match_award_id' => 0,
    'match_id' => '',
    'player_id' => '',
    'award_type_id' => '',
    'performance_value' => '',
    'performance_unit' => '',
    'remarks' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = post_string('action');

    if ($action === 'save') {
        $matchAwardId = post_int('match_award_id');
        $matchId = post_int('match_id');
        $playerId = post_int('player_id');
        $awardTypeId = post_int('award_type_id');
        $performanceValueRaw = post_string('performance_value');
        $performanceUnit = post_string('performance_unit');
        $remarks = post_string('remarks');
        $performanceValue = $performanceValueRaw === '' ? null : (float) $performanceValueRaw;

        $editAward = [
            'match_award_id' => $matchAwardId,
            'match_id' => $matchId,
            'player_id' => $playerId,
            'award_type_id' => $awardTypeId,
            'performance_value' => $performanceValueRaw,
            'performance_unit' => $performanceUnit,
            'remarks' => $remarks,
        ];

        if ($matchId <= 0) {
            $errors[] = 'Select a match.';
        }
        if ($playerId <= 0) {
            $errors[] = 'Select a player.';
        }
        if ($awardTypeId <= 0) {
            $errors[] = 'Select an award type.';
        }
        if ($performanceValue !== null && $performanceValue < 0) {
            $errors[] = 'Performance value cannot be negative.';
        }
        if ($matchId > 0 && $playerId > 0 && !player_belongs_to_match($pdo, $playerId, $matchId)) {
            $errors[] = 'The selected player does not belong to either team in this match.';
        }

        if (!$errors) {
            try {
                if ($matchAwardId > 0) {
                    $statement = $pdo->prepare(
                        'UPDATE match_award
                         SET match_id = :match_id,
                             player_id = :player_id,
                             award_type_id = :award_type_id,
                             performance_value = :performance_value,
                             performance_unit = :performance_unit,
                             remarks = :remarks
                         WHERE match_award_id = :match_award_id'
                    );
                    $statement->execute([
                        'match_id' => $matchId,
                        'player_id' => $playerId,
                        'award_type_id' => $awardTypeId,
                        'performance_value' => $performanceValue,
                        'performance_unit' => $performanceUnit !== '' ? $performanceUnit : null,
                        'remarks' => $remarks !== '' ? $remarks : null,
                        'match_award_id' => $matchAwardId,
                    ]);
                    set_flash('success', 'Match award updated successfully.');
                } else {
                    $statement = $pdo->prepare(
                        'INSERT INTO match_award
                            (match_id, player_id, award_type_id, performance_value, performance_unit, remarks)
                         VALUES
                            (:match_id, :player_id, :award_type_id, :performance_value, :performance_unit, :remarks)'
                    );
                    $statement->execute([
                        'match_id' => $matchId,
                        'player_id' => $playerId,
                        'award_type_id' => $awardTypeId,
                        'performance_value' => $performanceValue,
                        'performance_unit' => $performanceUnit !== '' ? $performanceUnit : null,
                        'remarks' => $remarks !== '' ? $remarks : null,
                    ]);
                    //set_flash('success', 'Match award added successfully.');
                }
                redirect('awards.php');
            } catch (PDOException $exception) {
                $errors[] = db_error_message($exception);
            }
        }
    }

    if ($action === 'delete') {
        $matchAwardId = post_int('match_award_id');
        try {
            $statement = $pdo->prepare('DELETE FROM match_award WHERE match_award_id = :match_award_id');
            $statement->execute(['match_award_id' => $matchAwardId]);
            //set_flash('success', 'Match award deleted successfully.');
        } catch (PDOException $exception) {
            set_flash('error', db_error_message($exception));
        }
        redirect('awards.php');
    }
}

if (isset($_GET['edit'])) {
    $matchAwardId = (int) $_GET['edit'];
    $statement = $pdo->prepare(
        'SELECT match_award_id, match_id, player_id, award_type_id,
                performance_value, performance_unit, remarks
         FROM match_award
         WHERE match_award_id = :match_award_id'
    );
    $statement->execute(['match_award_id' => $matchAwardId]);
    $foundAward = $statement->fetch();
    if ($foundAward) {
        $editAward = $foundAward;
    }
}

$matches = $pdo->query(
    'SELECT m.match_id, m.match_date, t1.team_name AS team1_name, t2.team_name AS team2_name
     FROM matches m
     JOIN team t1 ON t1.team_id = m.team1_id
     JOIN team t2 ON t2.team_id = m.team2_id
     ORDER BY m.match_date DESC, m.match_id DESC'
)->fetchAll();

$awardTypes = $pdo->query(
    'SELECT award_type_id, award_name, is_active
     FROM award_type
     ORDER BY is_active DESC, award_name'
)->fetchAll();

$awards = $pdo->query(
    'SELECT ma.match_award_id, ma.match_id, ma.player_id, ma.award_type_id,
            ma.performance_value, ma.performance_unit, ma.remarks, ma.created_at,
            p.player_name, p.role, t.team_name, at.award_name,
            m.match_date, t1.team_name AS team1_name, t2.team_name AS team2_name
     FROM match_award ma
     JOIN matches m ON m.match_id = ma.match_id
     JOIN team t1 ON t1.team_id = m.team1_id
     JOIN team t2 ON t2.team_id = m.team2_id
     JOIN player p ON p.player_id = ma.player_id
     LEFT JOIN team t ON t.team_id = p.team_id
     JOIN award_type at ON at.award_type_id = ma.award_type_id
     ORDER BY m.match_date DESC, ma.match_id DESC, at.award_name'
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
                <h2><?= (int) $editAward['match_award_id'] > 0 ? 'Edit Match Award' : 'Assign Match Award' ?></h2>
                <p>One award type can be assigned once per match</p>
            </div>
        </div>

        <form method="post" class="form-grid">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="match_award_id" value="<?= (int) $editAward['match_award_id'] ?>">

            <div class="form-group full">
                <label class="required" for="award_match_id">Match</label>
                <select id="award_match_id" name="match_id" required data-player-match-select="award_player_id">
                    <option value="">Select match</option>
                    <?php foreach ($matches as $match): ?>
                        <option value="<?= (int) $match['match_id'] ?>" <?= (int) $editAward['match_id'] === (int) $match['match_id'] ? 'selected' : '' ?>>
                            #<?= (int) $match['match_id'] ?> · <?= h($match['team1_name']) ?> vs <?= h($match['team2_name']) ?> · <?= h(format_date($match['match_date'])) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group full">
                <label class="required" for="award_player_id">Award Recipient</label>
                <select id="award_player_id" name="player_id" required data-selected-player="<?= h($editAward['player_id']) ?>" disabled>
                    <option value="">Select player</option>
                </select>
            </div>

            <div class="form-group full">
                <label class="required" for="award_type_id">Award Type</label>
                <select id="award_type_id" name="award_type_id" required>
                    <option value="">Select award type</option>
                    <?php foreach ($awardTypes as $awardType): ?>
                        <?php
                        $isCurrentInactive = (int) $editAward['award_type_id'] === (int) $awardType['award_type_id'];
                        if ((int) $awardType['is_active'] !== 1 && !$isCurrentInactive) {
                            continue;
                        }
                        ?>
                        <option value="<?= (int) $awardType['award_type_id'] ?>" <?= (int) $editAward['award_type_id'] === (int) $awardType['award_type_id'] ? 'selected' : '' ?>>
                            <?= h($awardType['award_name']) ?><?= (int) $awardType['is_active'] === 1 ? '' : ' (Inactive)' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="performance_value">Performance Value</label>
                <input id="performance_value" name="performance_value" type="number" min="0" step="0.01" value="<?= h($editAward['performance_value']) ?>" placeholder="Example: 142.50">
            </div>

            <div class="form-group">
                <label for="performance_unit">Unit</label>
                <input id="performance_unit" name="performance_unit" maxlength="30" value="<?= h($editAward['performance_unit']) ?>" placeholder="Example: km/h">
            </div>

            <div class="form-group full">
                <label for="remarks">Remarks</label>
                <textarea id="remarks" name="remarks" maxlength="255" placeholder="Short explanation of the award decision"><?= h($editAward['remarks']) ?></textarea>
            </div>

            <div class="form-actions">
                <button class="btn btn-primary" type="submit"><?= (int) $editAward['match_award_id'] > 0 ? 'Update Award' : 'Assign Award' ?></button>
                <?php if ((int) $editAward['match_award_id'] > 0): ?>
                    <a class="btn btn-light" href="awards.php">Cancel</a>
                <?php endif; ?>
            </div>
        </form>
    </section>

    <section class="card">
        <div class="card-header">
            <div>
                <h2>Award History</h2>
                <p><?= count($awards) ?> awards recorded</p>
            </div>
        </div>

        <div class="table-toolbar">
            <div class="search-box">
                <input type="search" placeholder="Search match, player or award..." data-table-search="awardsTable">
            </div>
        </div>

        <div class="table-wrap">
            <table id="awardsTable">
                <thead>
                <tr>
                    <th>Match</th>
                    <th>Award</th>
                    <th>Player</th>
                    <th>Performance</th>
                    <th>Remarks</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($awards as $award): ?>
                    <tr>
                        <td>
                            <strong>#<?= (int) $award['match_id'] ?></strong><br>
                            <span class="muted"><?= h($award['team1_name']) ?> vs <?= h($award['team2_name']) ?></span>
                        </td>
                        <td><span class="badge badge-success"><?= h($award['award_name']) ?></span></td>
                        <td>
                            <strong><?= h($award['player_name']) ?></strong><br>
                            <span class="muted"><?= h($award['team_name'] ?? 'Unassigned') ?> · <?= h($award['role']) ?></span>
                        </td>
                        <td>
                            <?php if ($award['performance_value'] !== null): ?>
                                <span class="score-number"><?= h($award['performance_value']) ?></span>
                                <?= h($award['performance_unit'] ?? '') ?>
                            <?php else: ?>
                                —
                            <?php endif; ?>
                        </td>
                        <td><?= h($award['remarks'] ?: '—') ?></td>
                        <td>
                            <div class="table-actions">
                                <a class="btn btn-secondary btn-sm" href="awards.php?edit=<?= (int) $award['match_award_id'] ?>">Edit</a>
                                <form method="post" class="inline-form" data-confirm="Delete this match award?">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="match_award_id" value="<?= (int) $award['match_award_id'] ?>">
                                    <button class="btn btn-danger btn-sm" type="submit">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$awards): ?>
                    <tr><td colspan="6" class="empty-state"><strong>No match awards found</strong>Assign an award using the form.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
