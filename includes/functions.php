<?php

declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function h(mixed $value): string
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
}

function redirect(string $location): never
{
    header('Location: ' . $location);
    exit;
}

function set_flash(
    string $type,
    string $message
): void {
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
        $_SESSION['csrf_token'] =
            bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return
        '<input type="hidden" name="csrf_token" value="' .
        h(csrf_token()) .
        '">';
}

function verify_csrf(): void
{
    $submittedToken =
        $_POST['csrf_token'] ?? '';

    $sessionToken =
        $_SESSION['csrf_token'] ?? '';

    if (
        !is_string($submittedToken) ||
        !is_string($sessionToken) ||
        $submittedToken === '' ||
        !hash_equals(
            $sessionToken,
            $submittedToken
        )
    ) {
        http_response_code(419);

        exit(
            'Invalid or expired request token. ' .
            'Refresh the page and try again.'
        );
    }
}

function post_string(string $key): string
{
    return trim(
        (string) ($_POST[$key] ?? '')
    );
}

function post_int(string $key): int
{
    return (int) ($_POST[$key] ?? 0);
}

function nullable_post_int(
    string $key
): ?int {
    $value = trim(
        (string) ($_POST[$key] ?? '')
    );

    return $value === ''
        ? null
        : (int) $value;
}

function nullable_post_string(
    string $key
): ?string {
    $value = trim(
        (string) ($_POST[$key] ?? '')
    );

    return $value === ''
        ? null
        : $value;
}

function is_valid_date(
    string $date
): bool {
    $dateObject =
        DateTime::createFromFormat(
            'Y-m-d',
            $date
        );

    return
        $dateObject !== false &&
        $dateObject->format('Y-m-d') === $date;
}

function format_date(
    ?string $date
): string {
    if (!$date) {
        return '—';
    }

    try {
        return
            (new DateTime($date))
                ->format('d M Y');
    } catch (Exception) {
        return $date;
    }
}

function is_valid_cricket_overs(
    ?string $overs
): bool {
    if (
        $overs === null ||
        $overs === ''
    ) {
        return true;
    }

    return preg_match(
        '/^\d+(?:\.[0-5])?$/',
        $overs
    ) === 1;
}

function normalize_cricket_overs(
    ?string $overs
): ?string {
    if (
        $overs === null ||
        $overs === ''
    ) {
        return null;
    }

    if (!is_valid_cricket_overs($overs)) {
        return null;
    }

    return str_contains($overs, '.')
        ? $overs
        : $overs . '.0';
}

function format_cricket_overs(
    mixed $overs
): string {
    if (
        $overs === null ||
        $overs === ''
    ) {
        return '0';
    }

    $formatted = number_format(
        (float) $overs,
        1,
        '.',
        ''
    );

    return str_ends_with(
        $formatted,
        '.0'
    )
        ? substr($formatted, 0, -2)
        : $formatted;
}

function format_innings_score(
    mixed $runs,
    mixed $wickets,
    mixed $overs
): string {
    $safeRuns =
        $runs === null ||
        $runs === ''
            ? 0
            : max(0, (int) $runs);

    $safeWickets =
        $wickets === null ||
        $wickets === ''
            ? 0
            : min(
                10,
                max(0, (int) $wickets)
            );

    return sprintf(
        '%d/%d (%s)',
        $safeRuns,
        $safeWickets,
        format_cricket_overs($overs)
    );
}

function format_match_score(
    array $match
): string {
    $scoreFields = [
        $match['team1_score'] ?? null,
        $match['team1_wickets'] ?? null,
        $match['team1_overs'] ?? null,
        $match['team2_score'] ?? null,
        $match['team2_wickets'] ?? null,
        $match['team2_overs'] ?? null,
    ];

    $hasAnyScore = array_filter(
        $scoreFields,
        static fn (mixed $value): bool =>
            $value !== null &&
            $value !== ''
    ) !== [];

    if (!$hasAnyScore) {
        return '0-0';
    }

    return
        format_innings_score(
            $match['team1_score'] ?? null,
            $match['team1_wickets'] ?? null,
            $match['team1_overs'] ?? null
        ) .
        ' - ' .
        format_innings_score(
            $match['team2_score'] ?? null,
            $match['team2_wickets'] ?? null,
            $match['team2_overs'] ?? null
        );
}

function recalculate_points(
    PDO $pdo
): void {
    $teams = $pdo
        ->query(
            'SELECT team_id
             FROM team
             ORDER BY team_id'
        )
        ->fetchAll();

    $standings = [];

    foreach ($teams as $team) {
        $teamId =
            (int) $team['team_id'];

        $standings[$teamId] = [
            'matches_played' => 0,
            'wins' => 0,
            'losses' => 0,
            'draws' => 0,
            'points' => 0,
        ];
    }

    $finishedMatches = $pdo
        ->query(
            "SELECT
                team1_id,
                team2_id,
                winner_team_id,
                result_status
             FROM matches
             WHERE result_status
                IN ('completed', 'draw')"
        )
        ->fetchAll();

    foreach (
        $finishedMatches as $match
    ) {
        $team1Id =
            (int) $match['team1_id'];

        $team2Id =
            (int) $match['team2_id'];

        $winnerId =
            $match['winner_team_id'] !== null
                ? (int) $match['winner_team_id']
                : null;

        $resultStatus =
            (string) $match['result_status'];

        if (
            !isset(
                $standings[$team1Id],
                $standings[$team2Id]
            )
        ) {
            continue;
        }

        if ($resultStatus === 'draw') {
            $standings[$team1Id]
                ['matches_played']++;

            $standings[$team2Id]
                ['matches_played']++;

            $standings[$team1Id]
                ['draws']++;

            $standings[$team2Id]
                ['draws']++;

            $standings[$team1Id]
                ['points']++;

            $standings[$team2Id]
                ['points']++;

            continue;
        }

        if (
            $winnerId !== $team1Id &&
            $winnerId !== $team2Id
        ) {
            continue;
        }

        $loserId =
            $winnerId === $team1Id
                ? $team2Id
                : $team1Id;

        $standings[$team1Id]
            ['matches_played']++;

        $standings[$team2Id]
            ['matches_played']++;

        $standings[$winnerId]
            ['wins']++;

        $standings[$winnerId]
            ['points'] += 2;

        $standings[$loserId]
            ['losses']++;
    }

    $statement = $pdo->prepare(
        'INSERT INTO points_table
            (
                team_id,
                matches_played,
                wins,
                losses,
                draws,
                points
            )
         VALUES
            (
                :team_id,
                :matches_played,
                :wins,
                :losses,
                :draws,
                :points
            )
         ON DUPLICATE KEY UPDATE
            matches_played =
                VALUES(matches_played),
            wins = VALUES(wins),
            losses = VALUES(losses),
            draws = VALUES(draws),
            points = VALUES(points)'
    );

    $pdo->beginTransaction();

    try {
        foreach (
            $standings as
            $teamId => $data
        ) {
            $statement->execute([
                'team_id' =>
                    $teamId,

                'matches_played' =>
                    $data['matches_played'],

                'wins' =>
                    $data['wins'],

                'losses' =>
                    $data['losses'],

                'draws' =>
                    $data['draws'],

                'points' =>
                    $data['points'],
            ]);
        }

        if ($standings === []) {
            $pdo->exec(
                'DELETE FROM points_table'
            );
        } else {
            $placeholders = implode(
                ',',
                array_fill(
                    0,
                    count($standings),
                    '?'
                )
            );

            $deleteStatement =
                $pdo->prepare(
                    "DELETE FROM points_table
                     WHERE team_id
                     NOT IN ({$placeholders})"
                );

            $deleteStatement->execute(
                array_keys($standings)
            );
        }

        $pdo->commit();
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        throw $exception;
    }
}

function player_belongs_to_match(
    PDO $pdo,
    int $playerId,
    int $matchId
): bool {
    $statement = $pdo->prepare(
        'SELECT COUNT(*)
         FROM player p
         JOIN matches m
            ON m.match_id = :match_id
         WHERE p.player_id = :player_id
           AND p.team_id IN
                (
                    m.team1_id,
                    m.team2_id
                )'
    );

    $statement->execute([
        'player_id' => $playerId,
        'match_id' => $matchId,
    ]);

    return
        (int) $statement
            ->fetchColumn() > 0;
}

function db_error_message(
    PDOException $exception
): string {
    $errorCode =
        (int) (
            $exception->errorInfo[1] ?? 0
        );

    return match ($errorCode) {
        1062 =>
            'This record already exists or violates a unique rule.',

        1451 =>
            'This record cannot be deleted because other data still depends on it.',

        1452 =>
            'A selected related record does not exist.',

        default =>
            'Database operation failed. Please try again or contact the administrator.',
    };
}


/* =========================================================
   AUTHENTICATION AND ROLE HELPERS
   ========================================================= */

function normalize_email(
    string $email
): string {
    return strtolower(
        trim($email)
    );
}

function is_logged_in(): bool
{
    return isset(
        $_SESSION['auth_user']['user_id']
    );
}

function current_user(): ?array
{
    $user =
        $_SESSION['auth_user'] ?? null;

    return is_array($user)
        ? $user
        : null;
}

function current_user_id(): int
{
    return (int) (
        $_SESSION['auth_user']['user_id']
        ?? 0
    );
}

function current_user_name(): string
{
    return (string) (
        $_SESSION['auth_user']['full_name']
        ?? ''
    );
}

function current_user_role(): string
{
    return (string) (
        $_SESSION['auth_user']['role']
        ?? 'guest'
    );
}

function role_label(
    ?string $role = null
): string {
    $resolvedRole =
        $role ?? current_user_role();

    return match ($resolvedRole) {
        'super_admin' => 'Super Admin',
        'admin' => 'Admin',
        'user' => 'User',
        default => 'Guest',
    };
}

function is_super_admin(): bool
{
    return current_user_role()
        === 'super_admin';
}

function is_admin(): bool
{
    return current_user_role()
        === 'admin';
}

function is_user(): bool
{
    return current_user_role()
        === 'user';
}

function can_manage_accounts(): bool
{
    return is_super_admin();
}

function can_manage_tournament(): bool
{
    return is_admin();
}

function can_approve_users(): bool
{
    return
        is_super_admin() ||
        is_admin();
}

function dashboard_url(): string
{
    return match (current_user_role()) {
        'super_admin' => 'users.php',
        'admin' => 'index.php',
        'user' => 'user_dashboard.php',
        default => 'login.php',
    };
}

function login_user(
    array $user
): void {
    session_regenerate_id(true);

    $_SESSION['auth_user'] = [
        'user_id' =>
            (int) $user['user_id'],

        'full_name' =>
            (string) $user['full_name'],

        'email' =>
            (string) $user['email'],

        'role' =>
            (string) $user['role'],
    ];

    unset($_SESSION['csrf_token']);
}

function logout_user(): void
{
    unset(
        $_SESSION['auth_user'],
        $_SESSION['csrf_token']
    );

    session_regenerate_id(true);
}

function require_guest(): void
{
    if (is_logged_in()) {
        redirect(dashboard_url());
    }
}

function require_login(): void
{
    if (!is_logged_in()) {
        set_flash(
            'error',
            'Please log in to continue.'
        );

        redirect('login.php');
    }

    global $pdo;

    if (
        $pdo instanceof PDO &&
        !refresh_session_user($pdo)
    ) {
        set_flash(
            'error',
            'Your session is no longer active. Please log in again.'
        );

        redirect('login.php');
    }
}

function require_super_admin(): void
{
    require_login();

    if (!is_super_admin()) {
        set_flash(
            'error',
            'Super Admin access is required for that page.'
        );

        redirect(dashboard_url());
    }
}

function require_admin(): void
{
    require_login();

    if (!is_admin()) {
        set_flash(
            'error',
            'Admin access is required for that page.'
        );

        redirect(dashboard_url());
    }
}

function require_account_access(): void
{
    require_login();

    if (!can_approve_users()) {
        set_flash(
            'error',
            'Account access is required for that page.'
        );

        redirect('user_dashboard.php');
    }
}

function require_user(): void
{
    require_login();

    if (!is_user()) {
        redirect(dashboard_url());
    }
}

function refresh_session_user(
    PDO $pdo
): bool {
    if (!is_logged_in()) {
        return false;
    }

    $statement = $pdo->prepare(
        'SELECT
            user_id,
            full_name,
            email,
            role,
            status
         FROM users
         WHERE user_id = :user_id
         LIMIT 1'
    );

    $statement->execute([
        'user_id' =>
            current_user_id(),
    ]);

    $user = $statement->fetch();

    if (
        !$user ||
        $user['status'] !== 'active'
    ) {
        logout_user();

        return false;
    }

    if (
        !in_array(
            $user['role'],
            [
                'super_admin',
                'admin',
                'user',
            ],
            true
        )
    ) {
        logout_user();

        return false;
    }

    $_SESSION['auth_user'] = [
        'user_id' =>
            (int) $user['user_id'],

        'full_name' =>
            (string) $user['full_name'],

        'email' =>
            (string) $user['email'],

        'role' =>
            (string) $user['role'],
    ];

    return true;
}

function validate_password(
    string $password
): ?string {
    if (strlen($password) < 8) {
        return
            'Password must contain at least 8 characters.';
    }

    if (
        !preg_match(
            '/[A-Za-z]/',
            $password
        ) ||
        !preg_match(
            '/\d/',
            $password
        )
    ) {
        return
            'Password must contain at least one letter and one number.';
    }

    return null;
}