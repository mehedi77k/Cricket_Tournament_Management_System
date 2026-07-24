<?php

declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function h(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function redirect(string $location): never
{
    header('Location: ' . $location);
    exit;
}

function set_flash(string $type, string $message): void
{
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message,
    ];
}

function get_flash(): ?array
{
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);

    return $flash;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . h(csrf_token()) . '">';
}

function verify_csrf(): void
{
    $submittedToken = $_POST['csrf_token'] ?? '';
    $sessionToken = $_SESSION['csrf_token'] ?? '';

    if (!is_string($submittedToken) || !is_string($sessionToken) || !hash_equals($sessionToken, $submittedToken)) {
        http_response_code(419);
        exit('Invalid or expired request token. Refresh the page and try again.');
    }
}

function post_string(string $key): string
{
    return trim((string) ($_POST[$key] ?? ''));
}

function post_int(string $key): int
{
    return (int) ($_POST[$key] ?? 0);
}

function nullable_post_int(string $key): ?int
{
    $value = trim((string) ($_POST[$key] ?? ''));
    return $value === '' ? null : (int) $value;
}

function is_valid_date(string $date): bool
{
    $dateObject = DateTime::createFromFormat('Y-m-d', $date);
    return $dateObject !== false && $dateObject->format('Y-m-d') === $date;
}

function format_date(?string $date): string
{
    if (!$date) {
        return '—';
    }

    try {
        return (new DateTime($date))->format('d M Y');
    } catch (Exception) {
        return $date;
    }
}

function recalculate_points(PDO $pdo): void
{
    $teams = $pdo->query('SELECT team_id FROM team ORDER BY team_id')->fetchAll();
    $standings = [];

    foreach ($teams as $team) {
        $teamId = (int) $team['team_id'];
        $standings[$teamId] = [
            'matches_played' => 0,
            'wins' => 0,
            'losses' => 0,
            'points' => 0,
        ];
    }

    $completedMatches = $pdo->query(
        'SELECT team1_id, team2_id, winner_team_id
         FROM matches
         WHERE winner_team_id IS NOT NULL'
    )->fetchAll();

    foreach ($completedMatches as $match) {
        $team1Id = (int) $match['team1_id'];
        $team2Id = (int) $match['team2_id'];
        $winnerId = (int) $match['winner_team_id'];

        if (!isset($standings[$team1Id], $standings[$team2Id])) {
            continue;
        }

        if ($winnerId !== $team1Id && $winnerId !== $team2Id) {
            continue;
        }

        $loserId = $winnerId === $team1Id ? $team2Id : $team1Id;

        $standings[$team1Id]['matches_played']++;
        $standings[$team2Id]['matches_played']++;
        $standings[$winnerId]['wins']++;
        $standings[$winnerId]['points'] += 2;
        $standings[$loserId]['losses']++;
    }

    $statement = $pdo->prepare(
        'INSERT INTO points_table (team_id, matches_played, wins, losses, points)
         VALUES (:team_id, :matches_played, :wins, :losses, :points)
         ON DUPLICATE KEY UPDATE
             matches_played = VALUES(matches_played),
             wins = VALUES(wins),
             losses = VALUES(losses),
             points = VALUES(points)'
    );

    $pdo->beginTransaction();

    try {
        foreach ($standings as $teamId => $data) {
            $statement->execute([
                'team_id' => $teamId,
                'matches_played' => $data['matches_played'],
                'wins' => $data['wins'],
                'losses' => $data['losses'],
                'points' => $data['points'],
            ]);
        }

        if ($standings === []) {
            $pdo->exec('DELETE FROM points_table');
        } else {
            $placeholders = implode(',', array_fill(0, count($standings), '?'));
            $deleteStatement = $pdo->prepare(
                "DELETE FROM points_table WHERE team_id NOT IN ({$placeholders})"
            );
            $deleteStatement->execute(array_keys($standings));
        }

        $pdo->commit();
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $exception;
    }
}

function player_belongs_to_match(PDO $pdo, int $playerId, int $matchId): bool
{
    $statement = $pdo->prepare(
        'SELECT COUNT(*)
         FROM player p
         JOIN matches m ON m.match_id = :match_id
         WHERE p.player_id = :player_id
           AND p.team_id IN (m.team1_id, m.team2_id)'
    );
    $statement->execute([
        'player_id' => $playerId,
        'match_id' => $matchId,
    ]);

    return (int) $statement->fetchColumn() > 0;
}

function db_error_message(PDOException $exception): string
{
    $errorCode = (int) ($exception->errorInfo[1] ?? 0);

    return match ($errorCode) {
        1062 => 'This record already exists or violates a unique rule.',
        1451 => 'This record cannot be deleted because other data still depends on it.',
        1452 => 'A selected related record does not exist.',
        default => 'Database operation failed: ' . $exception->getMessage(),
    };
}
