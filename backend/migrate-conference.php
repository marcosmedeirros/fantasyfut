<?php
require_once __DIR__ . '/db.php';

$pdo = db();

function columnExists(PDO $pdo, string $table, string $column): bool {
    $stmt = $pdo->prepare("SHOW COLUMNS FROM `$table` LIKE ?");
    $stmt->execute([$column]);
    return (bool) $stmt->fetch();
}

try {
    if (!columnExists($pdo, 'teams', 'conference')) {
        $pdo->exec("ALTER TABLE teams ADD COLUMN conference ENUM('LESTE','OESTE') NULL AFTER league");
        $pdo->exec("ALTER TABLE teams ADD INDEX idx_team_conference (conference)");
        echo "[OK] Coluna 'conference' adicionada em 'teams'.\n";
    } else {
        echo "[SKIP] Coluna 'conference' j� existe em 'teams'.\n";
    }
    echo "Migra��o conclu�da.";
} catch (Throwable $e) {
    http_response_code(500);
    echo "Erro na migra��o: " . $e->getMessage();
}

