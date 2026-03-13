<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

session_start();
require_once __DIR__ . '/../backend/db.php';
require_once __DIR__ . '/../backend/helpers.php';
require_once __DIR__ . '/../backend/auth.php';

try {
    // Verificar autentica��o
    if (!isset($_SESSION['user_id'])) {
        jsonResponse(401, ['error' => 'N�o autenticado']);
    }

    // Verificar se � admin
    if (($_SESSION['user_type'] ?? 'jogador') !== 'admin') {
        jsonResponse(403, ['error' => 'Acesso negado. Apenas administradores.']);
    }

    $pdo = db();
    $method = $_SERVER['REQUEST_METHOD'];

    // GET - Listar usu�rios pendentes
    if ($method === 'GET') {
        $stmt = $pdo->query('
            SELECT id, name, email, league, phone, created_at, approved, approved_at, approved_by
            FROM users 
            WHERE approved = 0
            ORDER BY created_at DESC
        ');
        $pendingUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);

        jsonResponse(200, ['users' => $pendingUsers]);
    }

    // PUT - Aprovar ou reprovar usu�rio
    if ($method === 'PUT') {
        requireMethod('PUT');
        $body = readJsonBody();

        $userId = (int)($body['user_id'] ?? 0);
        $action = $body['action'] ?? ''; // 'approve' ou 'reject'

        if (!$userId || !in_array($action, ['approve', 'reject'])) {
            jsonResponse(422, ['error' => 'Par�metros inv�lidos']);
        }

        // Buscar usu�rio
        $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            jsonResponse(404, ['error' => 'Usu�rio n�o encontrado']);
        }

        if ($action === 'approve') {
            // Aprovar usu�rio
            $stmt = $pdo->prepare('
                UPDATE users 
                SET approved = 1, approved_at = NOW(), approved_by = ?
                WHERE id = ?
            ');
            $stmt->execute([$_SESSION['user_id'], $userId]);

            jsonResponse(200, ['message' => 'Usu�rio aprovado com sucesso', 'user_id' => $userId]);
        } else if ($action === 'reject') {
            // Rejeitar/deletar usu�rio
            $stmt = $pdo->prepare('DELETE FROM users WHERE id = ?');
            $stmt->execute([$userId]);

            jsonResponse(200, ['message' => 'Usu�rio rejeitado e removido', 'user_id' => $userId]);
        }
    }

    jsonResponse(405, ['error' => 'M�todo n�o permitido']);

} catch (PDOException $e) {
    error_log('Erro SQL no user-approval.php: ' . $e->getMessage());
    jsonResponse(500, ['error' => 'Erro no banco de dados', 'details' => $e->getMessage()]);
} catch (Exception $e) {
    error_log('Erro no user-approval.php: ' . $e->getMessage());
    jsonResponse(500, ['error' => 'Erro interno do servidor', 'details' => $e->getMessage()]);
}

