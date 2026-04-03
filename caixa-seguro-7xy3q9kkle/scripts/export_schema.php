<?php
/**
 * Script de utilidade para exportar a estrutura do banco de dados (Schema)
 * sem os dados sensíveis, facilitando a implantação para novos clientes.
 */

require_once __DIR__ . '/../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $tables = [
        'categorias',
        'produtos',
        'comandas',
        'itens_comanda',
        'movimentacoes_estoque',
        'usuarios',
        'garcons',
        'configuracoes_sistema'
    ];
    
    $sql_output = "-- SQL Template for Restaurant POS\n";
    $sql_output .= "-- Generated on: " . date('Y-m-d H:i:s') . "\n\n";
    
    foreach ($tables as $table) {
        // PostgreSQL: usar pg_dump style ou information_schema
        $stmt = $db->prepare("
            SELECT column_name, data_type, is_nullable, column_default, character_maximum_length
            FROM information_schema.columns
            WHERE table_name = ? AND table_schema = 'public'
            ORDER BY ordinal_position
        ");
        $stmt->execute([$table]);
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $sql_output .= "DROP TABLE IF EXISTS $table CASCADE;\n";
        $sql_output .= "-- Columns for $table:\n";
        foreach ($columns as $col) {
            $sql_output .= "--   {$col['column_name']} {$col['data_type']} " . ($col['is_nullable'] === 'NO' ? 'NOT NULL' : 'NULL') . " DEFAULT {$col['column_default']}\n";
        }
        $sql_output .= "\n";
    }

    // Inserir dados básicos (usuário admin padrão)
    $sql_output .= "-- Default Admin User (senha: admin123)\n";
    $admin_pw = password_hash('admin123', PASSWORD_DEFAULT);
    $sql_output .= "INSERT INTO usuarios (nome, email, senha, perfil, ativo, created_at) VALUES ('Administrador', 'admin@admin.com', '$admin_pw', 'admin', 1, NOW());\n\n";
    
    // Salvar em arquivo
    $filename = __DIR__ . '/../database_template.sql';
    file_put_contents($filename, $sql_output);
    
    echo "Sucesso! Template de banco de dados gerado em: $filename\n";
    echo "Você pode usar este arquivo para configurar novos clientes.\n";

} catch (Exception $e) {
    echo "Erro ao gerar template: " . $e->getMessage() . "\n";
}
