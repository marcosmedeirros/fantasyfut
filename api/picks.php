<?php
session_start();
header('Content-Type: application/json');

require_once dirname(__DIR__) . '/backend/auth.php';
require_once dirname(__DIR__) . '/backend/db.php';

// Verificar autentica��o
$user = getUserSession();
if (!$user) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'N�o autorizado']);
    exit;
}

$pdo = db();

// POST - Desabilitado: sistema gera picks automaticamente
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Edi��o manual de picks desabilitada. As picks s�o geradas automaticamente.']);
    exit;
}

// DELETE - Desabilitado
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Exclus�o manual de picks desabilitada. As picks s�o geridas automaticamente.']);
    exit;
}

// PUT - Desabilitado
if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Atualiza��o manual de picks desabilitada. As picks s�o geradas automaticamente.']);
    exit;
}

// GET - Listar picks
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $teamId = $_GET['team_id'] ?? null;

    if (!$teamId) {
        echo json_encode(['success' => false, 'error' => 'Team ID n�o informado']);
        exit;
    }

    $stmt = $pdo->prepare('
        SELECT p.*, 
               orig.city as original_team_city, orig.name as original_team_name,
               last_t.city as last_owner_city, last_t.name as last_owner_name
        FROM picks p
        LEFT JOIN teams orig ON p.original_team_id = orig.id
        LEFT JOIN teams last_t ON p.last_owner_team_id = last_t.id
        WHERE p.team_id = ?
        ORDER BY p.season_year, p.round
    ');
    $stmt->execute([$teamId]);
    $picks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'picks' => $picks]);
    exit;
}

echo json_encode(['success' => false, 'error' => 'M�todo n�o suportado']);

