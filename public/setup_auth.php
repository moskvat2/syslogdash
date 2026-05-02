<?php
require_once 'db.php';

try {
    // Cria tabela de usuários
    $pdo->exec("CREATE TABLE IF NOT EXISTS Users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL
    )");

    // Verifica se admin já existe
    $stmt = $pdo->prepare("SELECT id FROM Users WHERE username = 'admin'");
    $stmt->execute();
    
    if (!$stmt->fetch()) {
        // Insere usuário admin com senha 'admin' (texto simples)
        $stmt = $pdo->prepare("INSERT INTO Users (username, password) VALUES ('admin', 'admin')");
        $stmt->execute();
        echo "Tabela Users criada e usuário admin inserido com sucesso!";
    } else {
        echo "Tabela já existia e usuário admin já presente.";
    }
} catch (PDOException $e) {
    echo "Erro: " . $e->getMessage();
}
?>
