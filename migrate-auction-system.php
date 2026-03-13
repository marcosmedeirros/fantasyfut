<?php
/**
 * Migration: Sistema de Leil�o Free Agency
 * Cria as tabelas necess�rias para o sistema de leil�o
 */

require_once __DIR__ . '/backend/db.php';

$pdo = db();

echo "=== Iniciando migra��o do sistema de leil�o ===\n\n";

try {
    // 1. Criar tabela free_agency_auctions
    echo "1. Criando tabela free_agency_auctions...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS free_agency_auctions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            player_id INT NOT NULL COMMENT 'Jogador sendo leiloado',
            league VARCHAR(50) NOT NULL,
            status ENUM('active', 'finished', 'cancelled') DEFAULT 'active',
            start_time DATETIME NOT NULL COMMENT 'Quando o leil�o come�ou',
            end_time DATETIME NOT NULL COMMENT 'Quando o leil�o termina (start + 20min)',
            current_bid INT DEFAULT 1 COMMENT 'Lance atual (come�a em 1)',
            current_bidder_team_id INT NULL COMMENT 'Time com o maior lance atual',
            winner_team_id INT NULL COMMENT 'Time vencedor (preenchido quando termina)',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            FOREIGN KEY (player_id) REFERENCES players(id) ON DELETE CASCADE,
            FOREIGN KEY (current_bidder_team_id) REFERENCES teams(id) ON DELETE SET NULL,
            FOREIGN KEY (winner_team_id) REFERENCES teams(id) ON DELETE SET NULL,
            
            INDEX idx_auction_status (status),
            INDEX idx_auction_league (league, status),
            INDEX idx_auction_end_time (end_time)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "   ? Tabela free_agency_auctions criada!\n\n";
    
    // 2. Criar tabela free_agency_bids
    echo "2. Criando tabela free_agency_bids...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS free_agency_bids (
            id INT AUTO_INCREMENT PRIMARY KEY,
            auction_id INT NOT NULL,
            team_id INT NOT NULL,
            bid_amount INT NOT NULL COMMENT 'Valor do lance em pontos',
            bid_time DATETIME DEFAULT CURRENT_TIMESTAMP,
            
            FOREIGN KEY (auction_id) REFERENCES free_agency_auctions(id) ON DELETE CASCADE,
            FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE,
            
            INDEX idx_bid_auction (auction_id),
            INDEX idx_bid_team (team_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "   ? Tabela free_agency_bids criada!\n\n";
    
    echo "=== Migra��o conclu�da com sucesso! ===\n";
    echo "\nRegras do Leil�o:\n";
    echo "----------------------------\n";
    echo "1. Admin inicia leil�o para jogador\n";
    echo "2. Leil�o dura 20 minutos\n";
    echo "3. Lance inicial: 1 ponto\n";
    echo "4. Maior lance vence\n";
    echo "5. Pontos debitados do vencedor\n";
    
} catch (Exception $e) {
    echo "ERRO: " . $e->getMessage() . "\n";
    exit(1);
}

