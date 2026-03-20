<?php
require_once __DIR__ . '/caixa-seguro-7xy3q9kkle/config/database.php';
try {
    $db = new Database();
    $conn = $db->getConnection();
    
    $stmt = $conn->query("DESCRIBE produtos");
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
    
    $stmt = $conn->query("DESCRIBE categorias");
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
    
    $stmt = $conn->query("SELECT * FROM categorias");
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (Exception $e) {
    echo $e->getMessage();
}
