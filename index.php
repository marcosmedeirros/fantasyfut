<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// N�o carrega nada desnecess�rio, apenas redireciona
if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

header('Location: /dashboard.php');
exit;

