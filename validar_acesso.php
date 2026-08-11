<?php
// validar_acesso.php
require 'conexao.php';

// Recebe o token enviado pelo leitor
$dados = json_decode(file_get_contents('php://input'), true);
$token_lido = $dados['token'];

// Regra de Ouro: O token deve existir, não ter sido usado, e a data atual não pode ser maior que a validade
$sql = "SELECT id FROM tokens_acesso WHERE token = ? AND usado = 0 AND expira_em > NOW()";
$stmt = $pdo->prepare($sql);
$stmt->execute([$token_lido]);
$registro = $stmt->fetch();

header('Content-Type: application/json');

if ($registro) {
    // Acesso permitido! Imediatamente invalida o token para evitar que seja lido de novo
    $update = $pdo->prepare("UPDATE tokens_acesso SET usado = 1 WHERE id = ?");
    $update->execute([$registro['id']]);

    // Aqui você pode inserir o registro na tabela de 'movimentacoes'
    
    echo json_encode(["status" => "sucesso", "mensagem" => "Acesso Liberado! Catraca aberta."]);
} else {
    // Acesso negado: token falso, já utilizado ou expirado
    http_response_code(403);
    echo json_encode(["status" => "erro", "mensagem" => "QR Code inválido ou expirado."]);
}
?>