<?php
// ✅ CORRIGIDO
require_once __DIR__ . '/../config/paths.php';
require_once PathConfig::includes('database.php');

header('Content-Type: application/json; charset=utf-8');
$database = new Database();
$conn = $database->getConnection();

if ($conn) {
    echo "Conexão bem-sucedida!<br>";
    
    // Verificar qual banco estamos usando
    $stmt = $conn->query("SELECT current_database() as current_db");
    $db = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Banco de dados atual: " . $db['current_db'] . "<br><br>";

    // Verificar TODAS as tabelas no banco atual
    $stmt = $conn->query("SELECT table_name FROM information_schema.tables WHERE table_schema = 'public' ORDER BY table_name");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Tabelas no banco atual: <pre>" . print_r($tables, true) . "</pre><br>";
    
    // Contar comandas
    $stmt = $conn->query("SELECT COUNT(*) as total FROM comandas");
    $count = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "🔢 Total de comandas na tabela: " . $count['total'] . "<br>";
    
} else {
    echo "Falha na conexão!";
}
?>