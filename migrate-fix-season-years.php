<?php
/**
 * Corrige o ano das temporadas com base no ano inicial do sprint e no n�mero da temporada.
 * F�rmula: season.year = sprints.start_year + seasons.season_number - 1
 * Execute via CLI: php migrate-fix-season-years.php
 */

require_once __DIR__ . '/backend/config.php';
require_once __DIR__ . '/backend/db.php';

function out($msg) { echo $msg . "\n"; }

try {
    $pdo = db();

    // Verificar se tabelas existem
    $hasSeasons = $pdo->query("SHOW TABLES LIKE 'seasons'")->fetch();
    $hasSprints = $pdo->query("SHOW TABLES LIKE 'sprints'")->fetch();
    if (!$hasSeasons || !$hasSprints) {
        out('[ERRO] Tabelas seasons/sprints n�o encontradas.');
        exit(1);
    }

    $pdo->beginTransaction();

    // Selecionar temporadas com seus sprints
    $stmt = $pdo->query("SELECT s.id, s.season_number, s.year AS stored_year, sp.start_year
                         FROM seasons s
                         JOIN sprints sp ON s.sprint_id = sp.id");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $fixCount = 0;
    $unchanged = 0;

    $update = $pdo->prepare("UPDATE seasons SET year = ? WHERE id = ?");

    foreach ($rows as $r) {
        $expected = (int)$r['start_year'] + (int)$r['season_number'] - 1;
        $stored = (int)$r['stored_year'];
        if ($expected !== $stored) {
            $update->execute([$expected, (int)$r['id']]);
            $fixCount++;
        } else {
            $unchanged++;
        }
    }

    $pdo->commit();
    out("[OK] Migra��o conclu�da. Corrigidos: {$fixCount}. Inalterados: {$unchanged}.");
    exit(0);
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    out('[ERRO] ' . $e->getMessage());
    exit(1);
}

