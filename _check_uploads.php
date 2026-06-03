<?php
declare(strict_types=1);
try {
    $pdo = new PDO('mysql:host=127.0.0.1;port=3306;dbname=center_domiciliation;charset=utf8mb4', 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $stmt = $pdo->query('SELECT * FROM uploaded_docs');
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($rows, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage();
}
