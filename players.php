<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

require_admin();

$pageTitle = 'Players';
$activePage = 'players';
$errors = [];
$roles = ['Batsman', 'Bowler', 'All-Rounder', 'Wicket Keeper'];

$editPlayer = [
    'player_id' => 0,
    'player_name' => '',
    'role' => 'All-Rounder',
    'team_id' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = post_string('action');

    if ($action === 'save') {
        $playerId = post_int('player_id');
        $playerName = post_string('player_name');
        $role = post_string('role');
        $teamId = post_int('team_id');

        $editPlayer = [
            'player_id' => $playerId,
            'player_name' => $playerName,
            'role' => $role,
            'team_id' => $teamId,
        ];

        if ($playerName === '') {
            $errors[] = 'Player name is required.';
        }
        if (!in_array($role, $roles, true)) {
            $errors[] = 'Select a valid player role.';
        }
        if ($teamId <= 0) {
            $errors[] = 'Select a team.';
        }

        if (!$errors) {
            try {
                if ($playerId > 0) {
                    $statement = $pdo->prepare(
                        'UPDATE player
                         SET player_name = :player_name, role = :role, team_id = :team_id
                         WHERE player_id = :player_id'
                    );
                    $statement->execute([
                        'player_name' => $playerName,
                        'role' => $role,
                        'team_id' => $teamId,
                        'player_id' => $playerId,
                    ]);
                    //set_flash('success', 'Player updated successfully.');
                } else {
                    $statement = $pdo->prepare(
                        'INSERT INTO player (player_name, role, team_id)
                         VALUES (:player_name, :role, :team_id)'
                    );
                    $statement->execute([
                        'player_name' => $playerName,
                        'role' => $role,
                        'team_id' => $teamId,
                    ]);
                    //set_flash('success', 'Player added successfully.');
                }
                redirect('players.php');
            } catch (PDOException $exception) {
                $errors[] = db_error_message($exception);
            }
        }
    }

    if ($action === 'delete') {
        $playerId = post_int('player_id');
        try {
            $statement = $pdo->prepare('DELETE FROM player WHERE player_id = :player_id');
            $statement->execute(['player_id' => $playerId]);
            //set_flash('success', 'Player deleted successfully.');
        } catch (PDOException $exception) {
            set_flash('error', db_error_message($exception));
        }
        redirect('players.php');
    }
}

if (isset($_GET['edit'])) {
    $playerId = (int) $_GET['edit'];
    $statement = $pdo->prepare(
        'SELECT player_id, player_name, role, team_id FROM player WHERE player_id = :player_id'
    );
    $statement->execute(['player_id' => $playerId]);
    $foundPlayer = $statement->fetch();
    if ($foundPlayer) {
        $editPlayer = $foundPlayer;
    }
}

$teams = $pdo->query('SELECT team_id, team_name FROM team ORDER BY team_name')->fetchAll();
$players = $pdo->query(
    'SELECT p.player_id, p.player_name, p.role, p.team_id, t.team_name
     FROM player p
     LEFT JOIN team t ON t.team_id = p.team_id
     ORDER BY t.team_name, p.player_name'
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
                <h2><?= (int) $editPlayer['player_id'] > 0 ? 'Edit Player' : 'Add New Player' ?></h2>
                <p>Assign each player to one tournament team</p>
            </div>
        </div>

        <?php if (!$teams): ?>
            <div class="alert alert-warning"><span>Create a team before adding players.</span></div>
        <?php endif; ?>

        <form method="post" class="form-grid">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="player_id" value="<?= (int) $editPlayer['player_id'] ?>">

            <div class="form-group full">
                <label class="required" for="player_name">Player Name</label>
                <input id="player_name" name="player_name" maxlength="100" required value="<?= h($editPlayer['player_name']) ?>" placeholder="Player's full name">
            </div>

            <div class="form-group">
                <label class="required" for="role">Playing Role</label>
                <select id="role" name="role" required>
                    <?php foreach ($roles as $role): ?>
                        <option value="<?= h($role) ?>" <?= $editPlayer['role'] === $role ? 'selected' : '' ?>><?= h($role) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label class="required" for="team_id">Team</label>
                <select id="team_id" name="team_id" required <?= !$teams ? 'disabled' : '' ?>>
                    <option value="">Select team</option>
                    <?php foreach ($teams as $team): ?>
                        <option value="<?= (int) $team['team_id'] ?>" <?= (int) $editPlayer['team_id'] === (int) $team['team_id'] ? 'selected' : '' ?>>
                            <?= h($team['team_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-actions">
                <button class="btn btn-primary" type="submit" <?= !$teams ? 'disabled' : '' ?>><?= (int) $editPlayer['player_id'] > 0 ? 'Update Player' : 'Add Player' ?></button>
                <?php if ((int) $editPlayer['player_id'] > 0): ?>
                    <a class="btn btn-light" href="players.php">Cancel</a>
                <?php endif; ?>
            </div>
        </form>
    </section>

    <section class="card">
        <div class="card-header">
            <div>
                <h2>Player Directory</h2>
                <p><?= count($players) ?> players currently registered</p>
            </div>
        </div>

        <div class="table-toolbar">
            <div class="search-box">
                <input type="search" placeholder="Search player, team or role..." data-table-search="playersTable">
            </div>
        </div>

        <div class="table-wrap">
            <table id="playersTable">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Player</th>
                    <th>Role</th>
                    <th>Team</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($players as $player): ?>
                    <tr>
                        <td>#<?= (int) $player['player_id'] ?></td>
                        <td>
                            <div class="player-cell">
                                <span class="avatar"><?= h(substr($player['player_name'], 0, 2)) ?></span>
                                <strong><?= h($player['player_name']) ?></strong>
                            </div>
                        </td>
                        <td><span class="badge badge-muted"><?= h($player['role']) ?></span></td>
                        <td><?= h($player['team_name'] ?? 'Unassigned') ?></td>
                        <td>
                            <div class="table-actions">
                                <a class="btn btn-secondary btn-sm" href="players.php?edit=<?= (int) $player['player_id'] ?>">Edit</a>
                                <form method="post" class="inline-form" data-confirm="Delete this player? Existing score records may also be deleted.">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="player_id" value="<?= (int) $player['player_id'] ?>">
                                    <button class="btn btn-danger btn-sm" type="submit">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$players): ?>
                    <tr><td colspan="5" class="empty-state"><strong>No players found</strong>Add a player using the form.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
