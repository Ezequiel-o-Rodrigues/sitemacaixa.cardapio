<?php
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit();
}

try {
    $database = new Database();
    $db = $database->getConnection();

    $input = json_decode(file_get_contents('php://input'), true);
    $id = $input['id'] ?? null;

    if (!$id) {
        throw new Exception('ID do produto é obrigatório');
    }

    // Verificar se produto está em alguma comanda aberta
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM itens_comanda ic JOIN comandas c ON ic.comanda_id = c.id WHERE ic.produto_id = ? AND c.status = 'aberta'");
    $stmt->execute([$id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result['total'] > 0) {
        throw new Exception('Produto está em comandas abertas e não pode ser removido. Desative-o em vez de excluir.');
    }

    $stmt = $db->prepare("DELETE FROM produtos WHERE id = ?");
    $stmt->execute([$id]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Produto removido com sucesso']);
    } else {
        throw new Exception('Produto não encontrado');
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
