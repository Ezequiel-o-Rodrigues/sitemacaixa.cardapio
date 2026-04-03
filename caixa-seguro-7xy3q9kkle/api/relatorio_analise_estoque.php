<?php
// api/relatorio_analise_estoque.php
require_once '../config/database.php';

header('Content-Type: application/json');

try {
    $database = new Database();
    $db = $database->getConnection();

    $data_inicio = $_GET['data_inicio'] ?? date('Y-m-01');
    $data_fim = $_GET['data_fim'] ?? date('Y-m-d');

    // Calcular análise de estoque diretamente via SQL (sem stored procedure)
    $stmt = $db->prepare("
        SELECT
            p.id,
            p.nome,
            cat.nome AS categoria,
            p.preco,
            p.estoque_atual AS estoque_real_final,
            COALESCE(ent.total_entradas, 0) AS entradas_periodo,
            COALESCE(vendas.total_vendido, 0) AS saidas_periodo,
            (COALESCE(ent.total_entradas, 0) - COALESCE(vendas.total_vendido, 0)) AS estoque_teorico_final,
            COALESCE(ent.total_entradas, 0) AS estoque_inicial,
            GREATEST(
                (COALESCE(ent.total_entradas, 0) - COALESCE(vendas.total_vendido, 0)) - p.estoque_atual,
                0
            ) AS perdas_quantidade,
            ROUND(
                GREATEST(
                    (COALESCE(ent.total_entradas, 0) - COALESCE(vendas.total_vendido, 0)) - p.estoque_atual,
                    0
                ) * p.preco, 2
            ) AS perdas_valor,
            ROUND(COALESCE(vendas.total_vendido, 0) * p.preco, 2) AS faturamento_periodo
        FROM produtos p
        JOIN categorias cat ON p.categoria_id = cat.id
        LEFT JOIN (
            SELECT produto_id, SUM(quantidade) AS total_entradas
            FROM movimentacoes_estoque
            WHERE tipo = 'entrada'
            AND data_movimentacao BETWEEN :data_inicio AND (:data_fim::date + INTERVAL '1 day')
            GROUP BY produto_id
        ) ent ON ent.produto_id = p.id
        LEFT JOIN (
            SELECT ic.produto_id, SUM(ic.quantidade) AS total_vendido
            FROM itens_comanda ic
            JOIN comandas c ON ic.comanda_id = c.id
            WHERE c.status = 'fechada'
            AND c.data_venda BETWEEN :data_inicio2 AND (:data_fim2::date + INTERVAL '1 day')
            GROUP BY ic.produto_id
        ) vendas ON vendas.produto_id = p.id
        WHERE p.ativo = true
        ORDER BY perdas_quantidade DESC
    ");
    $stmt->bindParam(':data_inicio', $data_inicio);
    $stmt->bindParam(':data_fim', $data_fim);
    $stmt->bindParam(':data_inicio2', $data_inicio);
    $stmt->bindParam(':data_fim2', $data_fim);
    $stmt->execute();

    $dados = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calcular totais
    $totais = [
        'total_produtos_com_perda' => 0,
        'total_perdas_quantidade' => 0,
        'total_perdas_valor' => 0,
        'total_faturamento' => 0
    ];

    foreach ($dados as $item) {
        if ($item['perdas_quantidade'] > 0) {
            $totais['total_produtos_com_perda']++;
            $totais['total_perdas_quantidade'] += $item['perdas_quantidade'];
            $totais['total_perdas_valor'] += $item['perdas_valor'];
        }
        $totais['total_faturamento'] += $item['faturamento_periodo'];
    }

    echo json_encode([
        'success' => true,
        'data' => $dados,
        'totais' => $totais,
        'periodo' => [
            'data_inicio' => $data_inicio,
            'data_fim' => $data_fim
        ]
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao gerar relatório: ' . $e->getMessage()
    ]);
}
?>