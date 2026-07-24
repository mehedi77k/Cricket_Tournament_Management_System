<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$pageTitle = 'Award Types';
$activePage = 'award-types';
$errors = [];

$editAwardType = [
    'award_type_id' => 0,
    'award_name' => '',
    'description' => '',
    'is_active' => 1,
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = post_string('action');

    if ($action === 'save') {
        $awardTypeId = post_int('award_type_id');
        $awardName = post_string('award_name');
        $description = post_string('description');
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        $editAwardType = [
            'award_type_id' => $awardTypeId,
            'award_name' => $awardName,
            'description' => $description,
            'is_active' => $isActive,
        ];

        if ($awardName === '') {
            $errors[] = 'Award name is required.';
        }

        if (!$errors) {
            try {
                if ($awardTypeId > 0) {
                    $statement = $pdo->prepare(
                        'UPDATE award_type
                         SET award_name = :award_name, description = :description, is_active = :is_active
                         WHERE award_type_id = :award_type_id'
                    );
                    $statement->execute([
                        'award_name' => $awardName,
                        'description' => $description !== '' ? $description : null,
                        'is_active' => $isActive,
                        'award_type_id' => $awardTypeId,
                    ]);
                    //set_flash('success', 'Award type updated successfully.');
                } else {
                    $statement = $pdo->prepare(
                        'INSERT INTO award_type (award_name, description, is_active)
                         VALUES (:award_name, :description, :is_active)'
                    );
                    $statement->execute([
                        'award_name' => $awardName,
                        'description' => $description !== '' ? $description : null,
                        'is_active' => $isActive,
                    ]);
                    //set_flash('success', 'Award type added successfully.');
                }
                redirect('award_types.php');
            } catch (PDOException $exception) {
                $errors[] = db_error_message($exception);
            }
        }
    }

    if ($action === 'delete') {
        $awardTypeId = post_int('award_type_id');
        try {
            $statement = $pdo->prepare('DELETE FROM award_type WHERE award_type_id = :award_type_id');
            $statement->execute(['award_type_id' => $awardTypeId]);
            //set_flash('success', 'Award type deleted successfully.');
        } catch (PDOException $exception) {
            set_flash('error', db_error_message($exception));
        }
        redirect('award_types.php');
    }
}

if (isset($_GET['edit'])) {
    $awardTypeId = (int) $_GET['edit'];
    $statement = $pdo->prepare(
        'SELECT award_type_id, award_name, description, is_active
         FROM award_type WHERE award_type_id = :award_type_id'
    );
    $statement->execute(['award_type_id' => $awardTypeId]);
    $foundAwardType = $statement->fetch();
    if ($foundAwardType) {
        $editAwardType = $foundAwardType;
    }
}

$awardTypes = $pdo->query(
    'SELECT at.award_type_id, at.award_name, at.description, at.is_active,
            COUNT(ma.match_award_id) AS usage_count
     FROM award_type at
     LEFT JOIN match_award ma ON ma.award_type_id = at.award_type_id
     GROUP BY at.award_type_id, at.award_name, at.description, at.is_active
     ORDER BY at.is_active DESC, at.award_name'
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
                <h2><?= (int) $editAwardType['award_type_id'] > 0 ? 'Edit Award Type' : 'Add Award Type' ?></h2>
                <p>Create reusable award categories</p>
            </div>
        </div>

        <form method="post" class="form-grid">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="award_type_id" value="<?= (int) $editAwardType['award_type_id'] ?>">

            <div class="form-group full">
                <label class="required" for="award_name">Award Name</label>
                <input id="award_name" name="award_name" maxlength="100" required value="<?= h($editAwardType['award_name']) ?>" placeholder="Example: Best Fielder">
            </div>

            <div class="form-group full">
                <label for="description">Description</label>
                <textarea id="description" name="description" maxlength="255" placeholder="What does this award represent?"><?= h($editAwardType['description']) ?></textarea>
            </div>

            <div class="form-group full">
                <label>
                    <input type="checkbox" name="is_active" value="1" style="width:auto;height:auto;margin-right:8px;" <?= (int) $editAwardType['is_active'] === 1 ? 'checked' : '' ?>>
                    Active and available for new match awards
                </label>
            </div>

            <div class="form-actions">
                <button class="btn btn-primary" type="submit"><?= (int) $editAwardType['award_type_id'] > 0 ? 'Update Award Type' : 'Add Award Type' ?></button>
                <?php if ((int) $editAwardType['award_type_id'] > 0): ?>
                    <a class="btn btn-light" href="award_types.php">Cancel</a>
                <?php endif; ?>
            </div>
        </form>
    </section>

    <section class="card">
        <div class="card-header">
            <div>
                <h2>Available Award Types</h2>
                <p><?= count($awardTypes) ?> award categories</p>
            </div>
        </div>

        <div class="table-toolbar">
            <div class="search-box">
                <input type="search" placeholder="Search award type..." data-table-search="awardTypesTable">
            </div>
        </div>

        <div class="table-wrap">
            <table id="awardTypesTable">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Award</th>
                    <th>Description</th>
                    <th>Status</th>
                    <th>Used</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($awardTypes as $awardType): ?>
                    <tr>
                        <td>#<?= (int) $awardType['award_type_id'] ?></td>
                        <td><strong><?= h($awardType['award_name']) ?></strong></td>
                        <td><?= h($awardType['description'] ?: '—') ?></td>
                        <td>
                            <span class="badge <?= (int) $awardType['is_active'] === 1 ? 'badge-success' : 'badge-muted' ?>">
                                <?= (int) $awardType['is_active'] === 1 ? 'Active' : 'Inactive' ?>
                            </span>
                        </td>
                        <td><?= (int) $awardType['usage_count'] ?></td>
                        <td>
                            <div class="table-actions">
                                <a class="btn btn-secondary btn-sm" href="award_types.php?edit=<?= (int) $awardType['award_type_id'] ?>">Edit</a>
                                <form method="post" class="inline-form" data-confirm="Delete this award type? Used award types cannot be deleted.">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="award_type_id" value="<?= (int) $awardType['award_type_id'] ?>">
                                    <button class="btn btn-danger btn-sm" type="submit">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$awardTypes): ?>
                    <tr><td colspan="6" class="empty-state"><strong>No award types found</strong>Add an award type using the form.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
