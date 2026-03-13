<?php
require_once __DIR__ . '/backend/db.php';

try {
    $pdo = db();
    
    echo "Iniciando migra��o para adicionar controle de ativa��o/desativa��o de trades...\n";
    
    // Adicionar coluna trades_enabled em league_settings
    $pdo->exec("
        ALTER TABLE league_settings 
        ADD COLUMN IF NOT EXISTS trades_enabled TINYINT(1) DEFAULT 1 COMMENT 'Se 1, trades est�o ativas na liga; se 0, desativadas'
    ");
    
    echo "? Coluna trades_enabled adicionada � tabela league_settings\n";
    echo "? Por padr�o, todas as ligas t�m trades ativas (valor 1)\n";
    echo "\nMigra��o conclu�da com sucesso!\n";
    
} catch (PDOException $e) {
    echo "? Erro na migra��o: " . $e->getMessage() . "\n";
    exit(1);
}

