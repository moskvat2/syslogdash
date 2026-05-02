<?php
header('Content-Type: application/json');
require_once 'db.php';
checkAuth();

$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
$last_id = isset($_GET['last_id']) ? (int)$_GET['last_id'] : 0;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$search = isset($_GET['search']) ? $_GET['search'] : '';

try {
    // Cláusula WHERE base
    $whereClause = "WHERE 1=1";
    if (!empty($search)) {
        $whereClause .= " AND (Message LIKE :search OR FromHost LIKE :search OR LogPrefix LIKE :search)";
    }

    // Calcula total de páginas
    $countSql = "SELECT COUNT(*) FROM SystemEvents " . $whereClause;
    $countStmt = $pdo->prepare($countSql);
    if (!empty($search)) {
        $countStmt->bindValue(':search', "%$search%", PDO::PARAM_STR);
    }
    $countStmt->execute();
    $totalRecords = (int)$countStmt->fetchColumn();

    $maxPages = 100;
    $totalPages = ceil($totalRecords / $limit);
    if ($totalPages > $maxPages) $totalPages = $maxPages;
    if ($totalPages == 0) $totalPages = 1;

    // Lógica de busca
    if ($page == 1 && $last_id > 0) {
        // Modo "Tempo Real" na página 1
        $sql = "SELECT ID, Message, FromHost, ReceivedAt, LogPrefix 
                FROM SystemEvents 
                $whereClause AND ID > :last_id 
                ORDER BY ID DESC LIMIT :limit";
    } else {
        // Modo Paginação (Página > 1 ou carregamento inicial da Página 1)
        $offset = ($page - 1) * $limit;
        $sql = "SELECT ID, Message, FromHost, ReceivedAt, LogPrefix 
                FROM SystemEvents 
                $whereClause 
                ORDER BY ID DESC LIMIT :limit OFFSET :offset";
    }

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    
    if (!empty($search)) {
        $stmt->bindValue(':search', "%$search%", PDO::PARAM_STR);
    }
    
    if ($page == 1 && $last_id > 0) {
        $stmt->bindValue(':last_id', $last_id, PDO::PARAM_INT);
    } else {
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    }
    
    $stmt->execute();
    $logs = $stmt->fetchAll();
    
    $response = [
        'logs' => $logs,
        'total_pages' => $totalPages,
        'current_page' => $page
    ];

    $json = json_encode($response);
    if ($json === false) {
        die(json_encode(['error' => 'JSON Error: ' . json_last_error_msg()]));
    }
    echo $json;
} catch (PDOException $e) {
    echo json_encode(['error' => 'DB Error: ' . $e->getMessage()]);
}
?>
