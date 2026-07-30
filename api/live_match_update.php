<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

require_admin();

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');


/**
 * Send JSON response and stop execution.
 */
function json_response(array $data, int $status = 200): never
{
    http_response_code($status);

    echo json_encode(
        $data,
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES
    );

    exit;
}


/*
 * Only POST request is allowed.
 */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(
        [
            'success' => false,
            'message' => 'Only POST requests are allowed.',
        ],
        405
    );
}


/* =========================================================
   CSRF VALIDATION
   ========================================================= */

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
    json_response(
        [
            'success' => false,
            'message' =>
                'Invalid or expired request token. Refresh the page.',
        ],
        419
    );
}


/* =========================================================
   RECEIVE FORM VALUES
   ========================================================= */

$matchId =
    post_int('match_id');

$team1Id =
    post_int('team1_id');

$team2Id =
    post_int('team2_id');

$matchDate =
    post_string('match_date');

$matchState =
    post_string('match_state');

$team1Score =
    nullable_post_int(
        'team1_score'
    ) ?? 0;

$team1Wickets =
    nullable_post_int(
        'team1_wickets'
    ) ?? 0;

$team1OversInput =
    nullable_post_string(
        'team1_overs'
    );

$team2Score =
    nullable_post_int(
        'team2_score'
    ) ?? 0;

$team2Wickets =
    nullable_post_int(
        'team2_wickets'
    ) ?? 0;

$team2OversInput =
    nullable_post_string(
        'team2_overs'
    );


/* =========================================================
   SERVER-SIDE VALIDATION
   ========================================================= */

$errors = [];

if ($matchState !== 'live') {
    $errors[] =
        'Automatic saving works only when Match Status is Live.';
}

if (
    $team1Id <= 0 ||
    $team2Id <= 0
) {
    $errors[] =
        'Select both teams first.';
}

if (
    $team1Id === $team2Id &&
    $team1Id > 0
) {
    $errors[] =
        'A team cannot play against itself.';
}

if (!is_valid_date($matchDate)) {
    $errors[] =
        'Select a valid match date.';
}

if (
    $team1Score < 0 ||
    $team2Score < 0
) {
    $errors[] =
        'Runs cannot be negative.';
}

if (
    $team1Wickets < 0 ||
    $team1Wickets > 10 ||
    $team2Wickets < 0 ||
    $team2Wickets > 10
) {
    $errors[] =
        'Wickets must be between 0 and 10.';
}

if (
    !is_valid_cricket_overs(
        $team1OversInput
    )
) {
    $errors[] =
        'Team 1 overs must be written like 15, 15.2, or 19.5.';
}

if (
    !is_valid_cricket_overs(
        $team2OversInput
    )
) {
    $errors[] =
        'Team 2 overs must be written like 15, 15.2, or 19.5.';
}

if ($errors !== []) {
    json_response(
        [
            'success' => false,
            'message' =>
                implode(' ', $errors),
        ],
        422
    );
}


/* =========================================================
   NORMALIZE OVERS
   ========================================================= */

$team1Overs =
    normalize_cricket_overs(
        $team1OversInput
    ) ?? '0.0';

$team2Overs =
    normalize_cricket_overs(
        $team2OversInput
    ) ?? '0.0';

$created = false;


/* =========================================================
   INSERT OR UPDATE LIVE MATCH
   ========================================================= */

try {
    $pdo->beginTransaction();

    /*
     * Existing match update.
     */
    if ($matchId > 0) {
        $existsStatement =
            $pdo->prepare(
                'SELECT match_id
                 FROM matches
                 WHERE match_id = :match_id
                 LIMIT 1
                 FOR UPDATE'
            );

        $existsStatement->execute(
            [
                'match_id' => $matchId,
            ]
        );

        if (
            !$existsStatement->fetchColumn()
        ) {
            $pdo->rollBack();

            json_response(
                [
                    'success' => false,
                    'message' =>
                        'The selected match no longer exists.',
                ],
                404
            );
        }

        $statement =
            $pdo->prepare(
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
                     result_status = \'live\',
                     winner_team_id = NULL
                 WHERE match_id = :match_id'
            );

        $statement->execute(
            [
                'team1_id' =>
                    $team1Id,

                'team2_id' =>
                    $team2Id,

                'match_date' =>
                    $matchDate,

                'team1_score' =>
                    $team1Score,

                'team1_wickets' =>
                    $team1Wickets,

                'team1_overs' =>
                    $team1Overs,

                'team2_score' =>
                    $team2Score,

                'team2_wickets' =>
                    $team2Wickets,

                'team2_overs' =>
                    $team2Overs,

                'match_id' =>
                    $matchId,
            ]
        );
    } else {
        /*
         * New Live match automatically create.
         */
        $statement =
            $pdo->prepare(
                'INSERT INTO matches
                    (
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
                    )
                 VALUES
                    (
                        :team1_id,
                        :team2_id,
                        :match_date,
                        :team1_score,
                        :team1_wickets,
                        :team1_overs,
                        :team2_score,
                        :team2_wickets,
                        :team2_overs,
                        \'live\',
                        NULL
                    )'
            );

        $statement->execute(
            [
                'team1_id' =>
                    $team1Id,

                'team2_id' =>
                    $team2Id,

                'match_date' =>
                    $matchDate,

                'team1_score' =>
                    $team1Score,

                'team1_wickets' =>
                    $team1Wickets,

                'team1_overs' =>
                    $team1Overs,

                'team2_score' =>
                    $team2Score,

                'team2_wickets' =>
                    $team2Wickets,

                'team2_overs' =>
                    $team2Overs,
            ]
        );

        $matchId =
            (int) $pdo->lastInsertId();

        $created = true;
    }

    $pdo->commit();


    /* =====================================================
       BUILD FORMATTED SCORE
       ===================================================== */

    $match = [
        'team1_score' =>
            $team1Score,

        'team1_wickets' =>
            $team1Wickets,

        'team1_overs' =>
            $team1Overs,

        'team2_score' =>
            $team2Score,

        'team2_wickets' =>
            $team2Wickets,

        'team2_overs' =>
            $team2Overs,
    ];


    /* =====================================================
       SUCCESS RESPONSE
       ===================================================== */

    json_response(
        [
            'success' => true,

            'message' =>
                'Live score saved.',

            'created' =>
                $created,

            'match_id' =>
                $matchId,

            'score' =>
                format_match_score(
                    $match
                ),

            'result_text' =>
                'Live',

            'result_class' =>
                'badge-live',
        ]
    );
} catch (PDOException $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    json_response(
        [
            'success' => false,

            'message' =>
                db_error_message(
                    $exception
                ),
        ],
        500
    );
}