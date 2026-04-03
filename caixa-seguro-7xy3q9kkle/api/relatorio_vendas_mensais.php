<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

$database = new Database();
$db = $database->getConnection();

$ano = $_GET['ano'] ?? date('Y');

try {
    // Verificar se a view existe, senão usar query direta
    $stmt_check = $db->prepare("SELECT 1 FROM information_schema.tables WHERE table_name = 'view_vendas_mensais'");
    $stmt_check->execute();

    if ($stmt_check->rowCount() > 0) {
        $query = "SELECT * FROM view_vendas_mensais WHERE ano = :ano ORDER BY mes";
    } else {
        $query = "SELECT
                    TO_CHAR(data_venda, 'YYYY-MM') as mes_ano,
                    EXTRACT(YEAR FROM data_venda)::INT as ano,
                    EXTRACT(MONTH FROM data_venda)::INT as mes,
                    COUNT(id) as total_comandas,
                    COALESCE(SUM(valor_total), 0) as valor_total_vendas,
                    COALESCE(SUM(taxa_gorjeta), 0) as total_gorjetas,
                    COALESCE(AVG(valor_total), 0) as ticket_medio
                  FROM comandas
                  WHERE status = 'fechada' AND EXTRACT(YEAR FROM data_venda) = :ano
                  GROUP BY EXTRACT(YEAR FROM data_venda), EXTRACT(MONTH FROM data_venda)
                  ORDER BY mes";
    }
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':ano', $ano);
    $stmt->execute();
    
    $vendas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Garantir que todos os meses estejam representados
    $dadosCompletos = [];
    for ($mes = 1; $mes <= 12; $mes++) {
        $mesFormatado = str_pad($mes, 2, '0', STR_PAD_LEFT);
        $mesAno = $ano . '-' . $mesFormatado;
        $encontrado = false;
        
        foreach ($vendas as $venda) {
            if ($venda['mes_ano'] == $mesAno || $venda['mes'] == $mes) {
                $dadosCompletos[] = $venda;
                $encontrado = true;
                break;
            }
        }
        
        if (!$encontrado) {
            $dadosCompletos[] = [
                'mes_ano' => $mesAno,
                'ano' => $ano,
                'mes' => $mes,
                'total_comandas' => 0,
                'valor_total_vendas' => 0,
                'total_gorjetas' => 0,
                'ticket_medio' => 0
            ];
        }
    }
    
    // Preparar dados para gráfico
    $labels = [];
    $valores = [];
    $comandas = [];
    
    foreach ($dadosCompletos as $venda) {
        $nomeMes = DateTime::createFromFormat('!m', $venda['mes'])->format('M');
        $labels[] = $nomeMes;
        $valores[] = (float)$venda['valor_total_vendas'];
        $comandas[] = (int)$venda['total_comandas'];
    }
    
    echo json_encode([
        'success' => true,
        'data' => $dadosCompletos,
        'grafico' => [
            'labels' => $labels,
            'valores' => $valores,
            'comandas' => $comandas
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao carregar vendas mensais: ' . $e->getMessage(),
        'data' => [],
        'grafico' => [
            'labels' => [],
            'valores' => [],
            'comandas' => []
        ]
    ]);
}
?>