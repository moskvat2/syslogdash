<?php
session_start();

// Configuração de conexão com o Banco de Dados
$host = '172.16.0.99';
$user = 'rsyslog';
$pass = 'rsyslog';
$dbname = 'Syslog';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die(json_encode(['error' => 'Falha na conexão: ' . $e->getMessage()]));
}

function checkAuth() {
    if (!isset($_SESSION['user_id'])) {
        if (strpos($_SERVER['PHP_SELF'], 'api.php') !== false) {
            $msg = 'Unauthorized (Sessão não encontrada)';
            die(json_encode(['error' => $msg]));
        }
        header('Location: login.php');
        exit;
    }
}
?>
