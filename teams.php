<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$pageTitle = 'Teams';
$activePage = 'teams';
$errors = [];

$editTeam = [
    'team_id' => 0,
    'team_name' => '',
    'captain_name' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = post_string('action');

    if ($action === 'save') {
        $teamId = post_int('team_id');
        $teamName = post_string('team_name');
        $captainName = post_string('captain_name');

        $editTeam = [
            'team_id' => $teamId,
            'team_name' => $teamName,
            'captain_name' => $captainName,
        ];

        if ($teamName === '') {
            $errors[] = 'Team name is required.';
        }
        if ($captainName === '') {
            $errors[] = 'Captain name is required.';
        }

        if (!$errors) {
            try {
                if ($teamId > 0) {
                    $statement = $pdo->prepare(
                        'UPDATE team SET team_name = :team_name, captain_name = :captain_name WHERE team_id = :team_id'
                    );
                    $statement->execute([
                        'team_name' => $teamName,
                        'captain_name' => $captainName,
                        'team_id' => $teamId,
                    ]);
                    //set_flash('success', 'Team updated successfully.');
                } else {
                    $pdo->beginTransaction();
                    $statement = $pdo->prepare(
                        'INSERT INTO team (team_name, captain_name) VALUES (:team_name, :captain_name)'
                    );
                    $statement->execute([
                        'team_name' => $teamName,
                        'captain_name' => $captainName,
                    ]);
                    $newTeamId = (int) $pdo->lastInsertId();
                    $pointsStatement = $pdo->prepare(
                        'INSERT INTO points_table (team_id, matches_played, wins, losses, points) VALUES (:team_id, 0, 0, 0, 0)'
                    );
                    $pointsStatement->execute(['team_id' => $newTeamId]);
                    $pdo->commit();
                    //set_flash('success', 'Team added successfully.');
                }
                redirect('teams.php');
            } catch (PDOException $exception) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $errors[] = db_error_message($exception);
            }
        }
    }

    if ($action === 'delete') {
        $teamId = post_int('team_id');
        try {
            $statement = $pdo->prepare('DELETE FROM team WHERE team_id = :team_id');
            $statement->execute(['team_id' => $teamId]);
            //set_flash('success', 'Team deleted successfully.');
        } catch (PDOException $exception) {
            set_flash('error', db_error_message($exception));
        }
        redirect('teams.php');
    }
}

if (isset($_GET['edit'])) {
    $teamId = (int) $_GET['edit'];
    $statement = $pdo->prepare('SELECT team_id, team_name, captain_name FROM team WHERE team_id = :team_id');
    $statement->execute(['team_id' => $teamId]);
    $foundTeam = $statement->fetch();
    if ($foundTeam) {
        $editTeam = $foundTeam;
    }
}

$teams = $pdo->query(
    'SELECT t.team_id, t.team_name, t.captain_name, COUNT(p.player_id) AS player_count
     FROM team t
     LEFT JOIN player p ON p.team_id = t.team_id
     GROUP BY t.team_id, t.team_name, t.captain_name
     ORDER BY t.team_name'
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
                <h2><?= (int) $editTeam['team_id'] > 0 ? 'Edit Team' : 'Add New Team' ?></h2>
                <p>Enter the official team and captain names</p>
            </div>
        </div>

        <form method="post" class="form-grid">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="team_id" value="<?= (int) $editTeam['team_id'] ?>">

            <div class="form-group full">
                <label class="required" for="team_name">Team Name</label>
                <input id="team_name" name="team_name" maxlength="100" required value="<?= h($editTeam['team_name']) ?>" placeholder="Example: Storm Breakers">
            </div>

            <div class="form-group full">
                <label class="required" for="captain_name">Captain Name</label>
                <input id="captain_name" name="captain_name" maxlength="100" required value="<?= h($editTeam['captain_name']) ?>" placeholder="Captain's full name">
            </div>

            <div class="form-actions">
                <button class="btn btn-primary" type="submit"><?= (int) $editTeam['team_id'] > 0 ? 'Update Team' : 'Add Team' ?></button>
                <?php if ((int) $editTeam['team_id'] > 0): ?>
                    <a class="btn btn-light" href="teams.php">Cancel</a>
                <?php endif; ?>
            </div>
        </form>
    </section>

    <section class="card">
        <div class="card-header">
            <div>
                <h2>Registered Teams</h2>
                <p><?= count($teams) ?> teams currently available</p>
            </div>
        </div>

        <div class="table-toolbar">
            <div class="search-box">
                <input type="search" placeholder="Search team or captain..." data-table-search="teamsTable">
            </div>
        </div>

        <div class="table-wrap">
            <table id="teamsTable">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Team</th>
                    <th>Captain</th>
                    <th>Players</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($teams as $team): ?>
                    <tr>
                        <td>#<?= (int) $team['team_id'] ?></td>
                        <td>
                            <div class="team-cell">
                                <span class="avatar"><?= h(substr($team['team_name'], 0, 2)) ?></span>
                                <strong><?= h($team['team_name']) ?></strong>
                            </div>
                        </td>
                        <td><?= h($team['captain_name']) ?></td>
                        <td><span class="badge badge-success"><?= (int) $team['player_count'] ?> players</span></td>
                        <td>
                            <div class="table-actions">
                                <a class="btn btn-secondary btn-sm" href="teams.php?edit=<?= (int) $team['team_id'] ?>">Edit</a>
                                <form method="post" class="inline-form" data-confirm="Delete this team? Teams used in matches cannot be deleted.">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="team_id" value="<?= (int) $team['team_id'] ?>">
                                    <button class="btn btn-danger btn-sm" type="submit">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$teams): ?>
                    <tr><td colspan="5" class="empty-state"><strong>No teams found</strong>Add a team using the form.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
