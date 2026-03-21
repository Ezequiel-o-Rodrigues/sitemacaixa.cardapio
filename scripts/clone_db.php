<?php
/**
 * Script para clonar o banco de dados de produção para teste.
 * 
 * Requer que o usuário tenha permissão para criar tabelas no banco de destino.
 * No ambiente Hostinger, pode ser necessário criar o banco de destino manualmente.
 */

require_once __DIR__ . '/../caixa-seguro-7xy3q9kkle/config/database.php';

// Configurações
$source_db = "u903648047_sis_caixa";
$target_db = "u903648047_sis_caixa_test";

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    echo "--- Iniciando clonagem de $source_db para $target_db ---\n\n";
    
    // 1. Tentar criar o banco de dados de destino (se permitido)
    try {
        $conn->exec("CREATE DATABASE IF NOT EXISTS `$target_db` CHARACTER SET utf8 COLLATE utf8_general_ci");
        echo "[INFO] Banco de dados '$target_db' verificado/criado.\n";
    } catch (PDOException $e) {
        echo "[AVISO] Não foi possível criar o banco de dados via script: " . $e->getMessage() . "\n";
        echo "[INFO] Prosseguindo assumindo que o banco '$target_db' já existe ou as permissões são restritas.\n";
    }
    
    // 2. Obter todas as tabelas do banco de origem
    $stmt = $conn->query("SHOW TABLES FROM `$source_db` OR LIKE '%'");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($tables)) {
        // Fallback para quando o SHOW TABLES FROM não funciona como esperado em algumas versões/configurações
        $stmt = $conn->query("SELECT table_name FROM information_schema.tables WHERE table_schema = '$source_db'");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    
    if (empty($tables)) {
        throw new Exception("Nenhuma tabela encontrada no banco de origem '$source_db'.");
    }
    
    echo "[INFO] Total de tabelas para clonar: " . count($tables) . "\n\n";
    
    // 3. Clonar cada tabela
    foreach ($tables as $table) {
        echo "Processando tabela '$table'...\n";
        
        // Limpar tabela de destino se já existir
        $conn->exec("DROP TABLE IF EXISTS `$target_db`.`$table`");
        
        // Criar estrutura identica
        $conn->exec("CREATE TABLE `$target_db`.`$table` LIKE `$source_db`.`$table`");
        
        // Copiar dados
        $rows_affected = $conn->exec("INSERT INTO `$target_db`.`$table` SELECT * FROM `$source_db`.`$table`");
        
        echo "  - OK: Tabela clonada com $rows_affected registros.\n";
    }
    
    echo "\n--- Clonagem concluída com sucesso! ---\n";
    echo "Você pode agora usar o banco de dados '$target_db' para testes.\n";

} catch (Exception $e) {
    echo "\n[ERRO FATAL] Ocorreu um problema durante a clonagem:\n";
    echo $e->getMessage() . "\n";
    echo "\nVerifique se o banco de dados '$target_db' existe e se o usuário tem as permissões necessárias.\n";
}
