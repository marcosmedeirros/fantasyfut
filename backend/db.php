<?php
require_once __DIR__ . '/helpers.php';

// Define timezone padr�o para todo o sistema: S�o Paulo/Bras�lia
date_default_timezone_set('America/Sao_Paulo');

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $config = loadConfig();
    $dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', $config['db']['host'], $config['db']['name'], $config['db']['charset']);
    $pdo = new PDO($dsn, $config['db']['user'], $config['db']['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    // Definir timezone no MySQL tamb�m
    $pdo->exec("SET time_zone = '-03:00'");

    ensureSchema($pdo, $config['db']['name']);

    return $pdo;
}

function ensureSchema(PDO $pdo, string $dbName): void
{
    // Carrega e executa migra��es autom�ticas
    require_once __DIR__ . '/migrations.php';
    
    try {
        runMigrations();
    } catch (Exception $e) {
        error_log('Erro ao executar migra��es: ' . $e->getMessage());
    }
}

