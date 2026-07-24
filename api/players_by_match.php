<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

$matchId = filter_input(INPUT_GET, 'match_id', FILTER_VALIDATE_INT);

if (!$matchId) {
    http_response_code(400);
    echo json_encode(['error' => 'A valid match_id is required.']);
    exit;
}

$statement = $pdo->prepare(
    'SELECT p.player_id, p.player_name, p.role, t.team_name
     FROM matches m
     JOIN player p ON p.team_id IN (m.team1_id, m.team2_id)
     JOIN team t ON t.team_id = p.team_id
     WHERE m.match_id = :match_id
     ORDER BY t.team_name, p.player_name'
);
$statement->execute(['match_id' => $matchId]);

echo json_encode($statement->fetchAll(), JSON_UNESCAPED_UNICODE);
