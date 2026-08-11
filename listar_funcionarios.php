<?php
require 'conexao.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $stmt = $pdo->query("
        SELECT id, nome, matricula
        FROM funcionarios
        WHERE ativo = 1
        ORDER BY nome ASC
    ");

    $funcionarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "sucesso" => true,
        "funcionarios" => $funcionarios
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);

    echo json_encode([
        "sucesso" => false,
        "mensagem" => "Erro ao buscar funcionários."
    ], JSON_UNESCAPED_UNICODE);
}
