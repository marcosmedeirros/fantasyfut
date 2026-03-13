<?php
/**
 * Migra��o para criar as novas tabelas de hist�rico e pontos
 * Execute este arquivo via navegador para criar as tabelas
 */

require_once __DIR__ . '/backend/config.php';
require_once __DIR__ . '/backend/db.php';

$pdo = db();

echo "<h1>Migra��o: Novo Sistema de Hist�rico e Pontos</h1>";
echo "<pre>";

try {
    // 1. Criar tabela season_history
    echo "\n1. Criando tabela season_history...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS season_history (
            id INT AUTO_INCREMENT PRIMARY KEY,
            season_id INT NOT NULL,
            league ENUM('ELITE', 'NEXT', 'RISE', 'ROOKIE') NOT NULL,
            sprint_number INT NOT NULL,
            season_number INT NOT NULL,
            year INT NOT NULL,
            
            champion_team_id INT,
            runner_up_team_id INT,
            
            mvp_player VARCHAR(100),
            mvp_team_id INT,
            dpoy_player VARCHAR(100),
            dpoy_team_id INT,
            mip_player VARCHAR(100),
            mip_team_id INT,
            sixth_man_player VARCHAR(100),
            sixth_man_team_id INT,
            
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            
            UNIQUE KEY unique_season_history (season_id),
            INDEX idx_league_history (league)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "   ? Tabela season_history criada!\n";

    // 2. Criar tabela team_season_points
    echo "\n2. Criando tabela team_season_points...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS team_season_points (
            id INT AUTO_INCREMENT PRIMARY KEY,
            team_id INT NOT NULL,
            team_name VARCHAR(150) NOT NULL COMMENT 'Nome do time no momento do registro',
            league ENUM('ELITE', 'NEXT', 'RISE', 'ROOKIE') NOT NULL,
            season_id INT NOT NULL,
            sprint_number INT NOT NULL,
            season_number INT NOT NULL,
            points INT NOT NULL DEFAULT 0,
            
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            UNIQUE KEY unique_team_season_points (team_id, season_id),
            INDEX idx_league_points (league),
            INDEX idx_team_total (team_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "   ? Tabela team_season_points criada!\n";

    // 3. Adicionar foreign keys (ignorar erro se j� existir ou tabela n�o existir)
    echo "\n3. Adicionando foreign keys...\n";
    
    try {
        $pdo->exec("ALTER TABLE season_history 
            ADD CONSTRAINT fk_sh_season FOREIGN KEY (season_id) REFERENCES seasons(id) ON DELETE CASCADE");
        echo "   ? FK season_id adicionada em season_history\n";
    } catch (Exception $e) {
        echo "   ?? FK season_id j� existe ou n�o pode ser criada: " . $e->getMessage() . "\n";
    }
    
    try {
        $pdo->exec("ALTER TABLE team_season_points 
            ADD CONSTRAINT fk_tsp_team FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE");
        echo "   ? FK team_id adicionada em team_season_points\n";
    } catch (Exception $e) {
        echo "   ?? FK team_id j� existe ou n�o pode ser criada: " . $e->getMessage() . "\n";
    }
    
    try {
        $pdo->exec("ALTER TABLE team_season_points 
            ADD CONSTRAINT fk_tsp_season FOREIGN KEY (season_id) REFERENCES seasons(id) ON DELETE CASCADE");
        echo "   ? FK season_id adicionada em team_season_points\n";
    } catch (Exception $e) {
        echo "   ?? FK season_id j� existe ou n�o pode ser criada: " . $e->getMessage() . "\n";
    }

    echo "\n\n========================================\n";
    echo "MIGRA��O CONCLU�DA COM SUCESSO!\n";
    echo "========================================\n\n";
    
    echo "Estrutura do sistema:\n";
    echo "- season_history: Salva Campe�o, Vice, MVP, DPOY, MIP, 6� Homem (reseta com sprint)\n";
    echo "- team_season_points: Salva pontos manuais por time/temporada (N�O reseta)\n";
    echo "\n";
    echo "API dispon�vel em: /api/history-points.php\n";
    echo "Actions: get_history, save_history, get_ranking, save_season_points, etc.\n";

} catch (PDOException $e) {
    echo "\n? ERRO: " . $e->getMessage() . "\n";
}

echo "</pre>";

