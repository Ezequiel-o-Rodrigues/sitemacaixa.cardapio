<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

$database = new Database();
$db = $database->getConnection();

$data_inicio = $_GET['data_inicio'] ?? date('Y-m-d', strtotime('-30 days'));
$data_fim = $_GET['data_fim'] ?? date('Y-m-d');
$tipo_periodo = $_GET['tipo'] ?? 'diario';

try {
    // Buscar taxa de comissão do banco ou usar padrão
    $rate = 0.03; // padrão 3%
    try {
        $stmt = $db->prepare('SELECT valor FROM configuracoes_sistema WHERE chave = "commission_rate"');
        if ($stmt->execute()) {
            $config = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($config && isset($config['valor'])) {
                $rate = floatval($config['valor']);
            }
        }
    } catch (Exception $e) {
        error_log("Erro ao buscar taxa de comissão: " . $e->getMessage());
    }
    
    switch($tipo_periodo) {
        case 'diario':
            $query = "SELECT 
                        DATE(data_venda) as data,
                        COUNT(id) as total_comandas,
                        COALESCE(SUM(valor_total), 0) as valor_total,
                        COALESCE(AVG(valor_total), 0) as ticket_medio
                      FROM comandas 
                      WHERE status = 'fechada' 
                        AND DATE(data_venda) BETWEEN :data_inicio AND :data_fim
                      GROUP BY DATE(data_venda)
                      ORDER BY data";
            break;
            
        case 'semanal':
            $query = "SELECT 
                         CONCAT('Semana ', WEEK(data_venda), ' - ', YEAR(data_venda)) as periodo,
                         COUNT(id) as total_comandas,
                         COALESCE(SUM(valor_total), 0) as valor_total,
                         COALESCE(AVG(valor_total), 0) as ticket_medio
                      FROM comandas 
                      WHERE status = 'fechada'
                        AND DATE(data_venda) BETWEEN :data_inicio AND :data_fim
                      GROUP BY YEAR(data_venda), WEEK(data_venda)
                      ORDER BY YEAR(data_venda) DESC, WEEK(data_venda) DESC";
            break;
            
        case 'mensal':
            $query = "SELECT 
                         CONCAT(YEAR(data_venda), '-', LPAD(MONTH(data_venda), 2, '0')) as periodo,
                         COUNT(id) as total_comandas,
                         COALESCE(SUM(valor_total), 0) as valor_total,
                         COALESCE(AVG(valor_total), 0) as ticket_medio
                      FROM comandas 
                      WHERE status = 'fechada'
                        AND DATE(data_venda) BETWEEN :data_inicio AND :data_fim
                      GROUP BY YEAR(data_venda), MONTH(data_venda)
                      ORDER BY YEAR(data_venda) DESC, MONTH(data_venda) DESC";
            break;
            
        default:
            throw new Exception('Tipo de período inválido');
    }
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':data_inicio', $data_inicio);
    $stmt->bindParam(':data_fim', $data_fim);
    $stmt->execute();
    
    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calcular comissões baseado na taxa configurada
    if ($resultados) {
        foreach ($resultados as &$linha) {
            $linha['total_comissoes'] = round(floatval($linha['valor_total']) * $rate, 2);
        }
    }
    
    // Garantir que sempre retornamos um array, mesmo vazio
    if (!$resultados) {
        $resultados = [];
    }
    
    echo json_encode([
        'success' => true,
        'data' => $resultados,
        'periodo' => $tipo_periodo,
        'commission_rate' => $rate,
        'commission_rate_percent' => round($rate * 100, 1)
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao gerar relatório: ' . $e->getMessage(),
        'data' => [],
        'periodo' => $tipo_periodo
    ]);
}
?>