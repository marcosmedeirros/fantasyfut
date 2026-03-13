<?php
require_once __DIR__ . '/backend/db.php';

$pdo = db();

try {
    echo "Iniciando migra��o para sistema de aprova��o de usu�rios...\n";
    
    // Verificar se a coluna j� existe
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'approved'");
    if ($stmt->rowCount() > 0) {
        echo "Coluna 'approved' j� existe.\n";
    } else {
        // Adicionar coluna approved (1 = aprovado, 0 = pendente)
        $pdo->exec("ALTER TABLE users ADD COLUMN approved TINYINT(1) DEFAULT 1 COMMENT 'Status de aprova��o do usu�rio (1=aprovado, 0=pendente)'");
        echo "? Coluna 'approved' adicionada � tabela users.\n";
    }
    
    // Verificar se a coluna approved_at existe
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'approved_at'");
    if ($stmt->rowCount() > 0) {
        echo "Coluna 'approved_at' j� existe.\n";
    } else {
        // Adicionar coluna approved_at
        $pdo->exec("ALTER TABLE users ADD COLUMN approved_at DATETIME NULL COMMENT 'Data de aprova��o do usu�rio'");
        echo "? Coluna 'approved_at' adicionada � tabela users.\n";
    }
    
    // Verificar se a coluna approved_by existe
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'approved_by'");
    if ($stmt->rowCount() > 0) {
        echo "Coluna 'approved_by' j� existe.\n";
    } else {
        // Adicionar coluna approved_by
        $pdo->exec("ALTER TABLE users ADD COLUMN approved_by INT NULL COMMENT 'ID do admin que aprovou'");
        echo "? Coluna 'approved_by' adicionada � tabela users.\n";
    }
    
    // Aprovar todos os usu�rios existentes
    $pdo->exec("UPDATE users SET approved = 1, approved_at = NOW() WHERE approved IS NULL OR approved = 0");
    echo "? Todos os usu�rios existentes foram aprovados automaticamente.\n";
    
    echo "\n=== Migra��o conclu�da com sucesso! ===\n";
    
} catch (Exception $e) {
    echo "ERRO: " . $e->getMessage() . "\n";
    exit(1);
}

