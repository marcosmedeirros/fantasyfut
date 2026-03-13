<?php
/**
 * Migration: Sistema de Playoffs
 * Cria as tabelas necess�rias para o sistema de playoffs por confer�ncia
 */

require_once __DIR__ . '/backend/db.php';

$pdo = db();

echo "=== Iniciando migra��o do sistema de playoffs ===\n\n";

try {
    // 1. Criar tabela playoff_brackets
    echo "1. Criando tabela playoff_brackets...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS playoff_brackets (
            id INT AUTO_INCREMENT PRIMARY KEY,
            season_id INT NOT NULL,
            league VARCHAR(50) NOT NULL,
            team_id INT NOT NULL,
            conference ENUM('LESTE', 'OESTE') NOT NULL,
            seed TINYINT NOT NULL COMMENT 'Posi��o 1-8 na classifica��o',
            status ENUM('active', 'first_round', 'semifinalist', 'conference_finalist', 'runner_up', 'champion') DEFAULT 'active',
            points_earned INT DEFAULT 0 COMMENT 'Pontos de classifica��o: 1�=4, 2-4�=3, 5-8�=2',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            UNIQUE KEY unique_team_season (season_id, league, team_id),
            UNIQUE KEY unique_seed_conf (season_id, league, conference, seed),
            
            FOREIGN KEY (season_id) REFERENCES seasons(id) ON DELETE CASCADE,
            FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE,
            
            INDEX idx_bracket_season (season_id, league),
            INDEX idx_bracket_conference (conference)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "   ? Tabela playoff_brackets criada!\n\n";
    
    // 2. Criar tabela playoff_matches
    echo "2. Criando tabela playoff_matches...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS playoff_matches (
            id INT AUTO_INCREMENT PRIMARY KEY,
            season_id INT NOT NULL,
            league VARCHAR(50) NOT NULL,
            conference ENUM('LESTE', 'OESTE', 'FINALS') NOT NULL,
            round ENUM('first_round', 'semifinals', 'conference_finals', 'finals') NOT NULL,
            match_number TINYINT NOT NULL COMMENT 'N�mero da partida na rodada',
            team1_id INT NULL COMMENT 'Primeiro time (maior seed na 1� rodada)',
            team2_id INT NULL COMMENT 'Segundo time',
            winner_id INT NULL COMMENT 'Time vencedor',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            UNIQUE KEY unique_match (season_id, league, conference, round, match_number),
            
            FOREIGN KEY (season_id) REFERENCES seasons(id) ON DELETE CASCADE,
            FOREIGN KEY (team1_id) REFERENCES teams(id) ON DELETE SET NULL,
            FOREIGN KEY (team2_id) REFERENCES teams(id) ON DELETE SET NULL,
            FOREIGN KEY (winner_id) REFERENCES teams(id) ON DELETE SET NULL,
            
            INDEX idx_match_season (season_id, league),
            INDEX idx_match_round (round)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "   ? Tabela playoff_matches criada!\n\n";
    
    echo "=== Migra��o conclu�da com sucesso! ===\n";
    echo "\nSistema de Pontua��o:\n";
    echo "----------------------------\n";
    echo "CLASSIFICA��O:\n";
    echo "  1� lugar: +4 pontos\n";
    echo "  2�-4� lugar: +3 pontos\n";
    echo "  5�-8� lugar: +2 pontos\n";
    echo "\nPLAYOFFS:\n";
    echo "  Campe�o: +5 pontos\n";
    echo "  Vice-Campe�o: +2 pontos\n";
    echo "  Finalista Confer�ncia: +3 pontos\n";
    echo "  Semifinalista: +2 pontos\n";
    echo "  1� Rodada: +1 ponto\n";
    echo "\nPR�MIOS (+1 ponto cada):\n";
    echo "  MVP, DPOY, MIP, 6� Homem\n";
    
} catch (Exception $e) {
    echo "ERRO: " . $e->getMessage() . "\n";
    exit(1);
}

