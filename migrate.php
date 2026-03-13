<?php
require_once __DIR__ . '/backend/db.php';
$pdo = db();

try {
    echo "?? Iniciando migra��o...\n\n";
    
    // 1. Adicionar novos campos
    echo "1?? Adicionando campos max_trades e edital...\n";
    $pdo->exec("ALTER TABLE league_settings ADD COLUMN IF NOT EXISTS max_trades INT NOT NULL DEFAULT 3 AFTER cap_max");
    $pdo->exec("ALTER TABLE league_settings ADD COLUMN IF NOT EXISTS edital TEXT NULL AFTER max_trades");
    echo "   ? Campos adicionados\n\n";
    
    // 2. Atualizar ENUM nas tabelas
    echo "2?? Atualizando ENUM nas tabelas...\n";
    
    $pdo->exec("ALTER TABLE leagues MODIFY COLUMN name ENUM('ELITE','NEXT','RISE','ROOKIE') NOT NULL");
    echo "   ? leagues atualizada\n";
    
    $pdo->exec("ALTER TABLE users MODIFY COLUMN league ENUM('ELITE','NEXT','RISE','ROOKIE') NOT NULL");
    echo "   ? users atualizada\n";
    
    $pdo->exec("ALTER TABLE divisions MODIFY COLUMN league ENUM('ELITE','NEXT','RISE','ROOKIE') NOT NULL");
    echo "   ? divisions atualizada\n";
    
    $pdo->exec("ALTER TABLE teams MODIFY COLUMN league ENUM('ELITE','NEXT','RISE','ROOKIE') NOT NULL");
    echo "   ? teams atualizada\n";
    
    $pdo->exec("ALTER TABLE drafts MODIFY COLUMN league ENUM('ELITE','NEXT','RISE','ROOKIE') NOT NULL");
    echo "   ? drafts atualizada\n";
    
    $pdo->exec("ALTER TABLE league_settings MODIFY COLUMN league ENUM('ELITE','NEXT','RISE','ROOKIE') NOT NULL");
    echo "   ? league_settings atualizada\n\n";
    
    // 3. Atualizar dados PRIME -> NEXT
    echo "3?? Migrando dados PRIME ? NEXT...\n";
    
    $pdo->exec("UPDATE leagues SET name = 'NEXT' WHERE name = 'PRIME'");
    $pdo->exec("UPDATE leagues SET description = 'Liga Next - Jogadores intermedi�rios avan�ados' WHERE name = 'NEXT'");
    echo "   ? leagues migrada\n";
    
    $pdo->exec("UPDATE users SET league = 'NEXT' WHERE league = 'PRIME'");
    echo "   ? users migrada\n";
    
    $pdo->exec("UPDATE divisions SET league = 'NEXT' WHERE league = 'PRIME'");
    echo "   ? divisions migrada\n";
    
    $pdo->exec("UPDATE teams SET league = 'NEXT' WHERE league = 'PRIME'");
    echo "   ? teams migrada\n";
    
    $pdo->exec("UPDATE drafts SET league = 'NEXT' WHERE league = 'PRIME'");
    echo "   ? drafts migrada\n";
    
    $pdo->exec("UPDATE league_settings SET league = 'NEXT' WHERE league = 'PRIME'");
    $pdo->exec("UPDATE league_settings SET max_trades = 3 WHERE max_trades = 0");
    echo "   ? league_settings migrada\n\n";
    
    // 4. Verifica��o
    echo "4?? Verificando resultado...\n";
    $stmt = $pdo->query('SELECT * FROM league_settings ORDER BY FIELD(league, "ELITE", "NEXT", "RISE", "ROOKIE")');
    $settings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\n?? Configura��es das Ligas:\n";
    echo "------------------------------------------------------------\n";
    foreach ($settings as $s) {
        echo "Liga: " . str_pad($s['league'], 10) . 
             " | CAP: " . $s['cap_min'] . "-" . $s['cap_max'] . 
             " | Max Trades: " . $s['max_trades'] . "\n";
    }
    echo "------------------------------------------------------------\n";
    
    echo "\n? MIGRA��O CONCLU�DA COM SUCESSO!\n";
    
} catch (Exception $e) {
    echo "\n? ERRO: " . $e->getMessage() . "\n";
    exit(1);
}

