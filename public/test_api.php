<?php
require_once 'db.php';
header('Content-Type: application/json');

try {
    $sql = "SELECT ID, Message, FromHost, ReceivedAt, LogPrefix 
            FROM SystemEvents 
            ORDER BY ID DESC LIMIT 5";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $logs = $stmt->fetchAll();
    
    $json = json_encode($logs);
    if ($json === false) {
        echo json_encode(['error' => 'JSON Error: ' . json_last_error_msg()]);
    } else {
        echo $json;
    }
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
