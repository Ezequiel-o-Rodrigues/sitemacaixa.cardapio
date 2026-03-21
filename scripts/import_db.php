<?php
/**
 * Script para importar o arquivo SQL para um banco de dados MySQL local ou remoto.
 */

// 1. Configurações de conexão (Ajuste se necessário)
$host = 'localhost';
$user = 'root'; // Geralmente root no XAMPP
$pass = '';     // Geralmente vazio no XAMPP
$dbname = 'u903648047_sis_caixa_test';
$sql_file = __DIR__ . '/../u903648047_sis_caixa.sql';

try {
    // Conectar ao MySQL sem selecionar o banco primeiro
    $pdo = new PDO("mysql:host=$host", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Conectado ao MySQL em $host\n";
    
    // 2. Criar o banco de dados se não existir
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "Banco de dados '$dbname' pronto.\n";
    
    // Conectar ao banco específico
    $pdo->exec("USE `$dbname` text;");
    
    // 3. Ler e executar o arquivo SQL
    if (!file_exists($sql_file)) {
        throw new Exception("Arquivo SQL não encontrado: $sql_file");
    }
    
    echo "Lendo arquivo SQL (isso pode levar alguns segundos)...\n";
    $sql = file_get_contents($sql_file);
    
    // Remover comentários e comandos que podem quebrar o PDO::exec se houver múltiplos
    // O ideal para arquivos grandes é ler linha a linha ou usar o CLI
    
    echo "Executando SQL...\n";
    // Nota: PDO::exec não suporta múltiplas queries em algumas configurações, 
    // mas arquivos exportados pelo phpMyAdmin geralmente precisam de tratamento.
    
    // Tentar executar via CLI se disponível (mais rápido e seguro)
    $mysql_path = 'mysql'; // Assumindo que esteja no PATH ou usaremos o path completo depois
    
    // Se não estiver no PATH, tentaremos via PHP mesmo (menos recomendado para arquivos grandes)
    $pdo->exec($sql);
    
    echo "Importação concluída com sucesso!\n";

} catch (Exception $e) {
    echo "ERRO: " . $e->getMessage() . "\n";
    echo "DICA: Se for erro de timeout ou memória, tente importar via phpMyAdmin diretamente.\n";
}
