<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

require_login();

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');


/* =========================================================
   LOAD ALL CURRENT MATCH SCORES
   ========================================================= */

$matches = $pdo->query(
    'SELECT
        m.match_id,
        m.team1_score,
        m.team1_wickets,
        m.team1_overs,
        m.team2_score,
        m.team2_wickets,
        m.team2_overs,
        m.result_status,
        tw.team_name AS winner_name
     FROM matches m
     LEFT JOIN team tw
        ON tw.team_id = m.winner_team_id
     ORDER BY m.match_id DESC'
)->fetchAll();


/* =========================================================
   FORMAT JSON RESPONSE
   ========================================================= */

$response = [];

foreach ($matches as $match) {
    $resultStatus =
        (string) $match['result_status'];

    $resultText =
        'Pending';

    $resultClass =
        'badge-warning';

    if ($resultStatus === 'live') {
        $resultText =
            'Live';

        $resultClass =
            'badge-live';
    } elseif ($resultStatus === 'draw') {
        $resultText =
            'Draw';

        $resultClass =
            'badge-muted';
    } elseif (
        $resultStatus === 'completed' &&
        !empty($match['winner_name'])
    ) {
        $resultText =
            (string) $match['winner_name'] .
            ' won';

        $resultClass =
            'badge-success';
    }

    $response[] = [
        'match_id' =>
            (int) $match['match_id'],

        'score' =>
            format_match_score(
                $match
            ),

        'result_text' =>
            $resultText,

        'result_class' =>
            $resultClass,
    ];
}


/* =========================================================
   SEND JSON
   ========================================================= */

echo json_encode(
    [
        'success' => true,
        'matches' => $response,
    ],
    JSON_UNESCAPED_UNICODE |
    JSON_UNESCAPED_SLASHES
);