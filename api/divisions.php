<?php
require_once __DIR__ . '/../backend/db.php';
require_once __DIR__ . '/../backend/helpers.php';

$pdo = db();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $stmt = $pdo->query('SELECT id, name, importance, champions FROM divisions ORDER BY importance DESC, id DESC');
    $divisions = $stmt->fetchAll();
    jsonResponse(200, ['divisions' => $divisions]);
}

if ($method === 'POST') {
    $body = readJsonBody();
    $name = trim($body['name'] ?? '');
    $importance = (int) ($body['importance'] ?? 0);
    $champions = trim($body['champions'] ?? '');

    if ($name === '') {
        jsonResponse(422, ['error' => 'Nome da divis�o � obrigat�rio.']);
    }

    $exists = $pdo->prepare('SELECT id FROM divisions WHERE name = ?');
    $exists->execute([$name]);
    if ($exists->fetch()) {
        jsonResponse(409, ['error' => 'Divis�o j� existe.']);
    }

    $stmt = $pdo->prepare('INSERT INTO divisions (name, importance, champions) VALUES (?, ?, ?)');
    $stmt->execute([$name, $importance, $champions]);

    jsonResponse(201, ['message' => 'Divis�o criada.', 'division_id' => $pdo->lastInsertId()]);
}

jsonResponse(405, ['error' => 'Method not allowed']);

