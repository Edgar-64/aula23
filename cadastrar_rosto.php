<?php
require 'conexao.php';

header('Content-Type: application/json; charset=utf-8');

$entrada = file_get_contents('php://input');
$dados = json_decode($entrada, true);

if (!is_array($dados)) {
    http_response_code(400);

    echo json_encode([
        "sucesso" => false,
        "mensagem" => "JSON inválido."
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$funcionarioId = filter_var(
    $dados['funcionario_id'] ?? null,
    FILTER_VALIDATE_INT
);

$embedding = $dados['face_embedding'] ?? null;

if (!$funcionarioId || !is_array($embedding) || count($embedding) === 0) {
    http_response_code(400);

    echo json_encode([
        "sucesso" => false,
        "mensagem" => "Funcionário ou dados faciais inválidos."
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

/*
 * O descriptor do face-api.js normalmente possui 128 números.
 * Aceitamos exatamente 128 para evitar salvar dados corrompidos.
 */
if (count($embedding) !== 128) {
    http_response_code(400);

    echo json_encode([
        "sucesso" => false,
        "mensagem" => "Descriptor facial inválido."
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

foreach ($embedding as $valor) {
    if (!is_numeric($valor)) {
        http_response_code(400);

        echo json_encode([
            "sucesso" => false,
            "mensagem" => "Descriptor facial contém valores inválidos."
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }
}

try {
    $stmt = $pdo->prepare("
        SELECT id, nome
        FROM funcionarios
        WHERE id = ?
          AND ativo = 1
        LIMIT 1
    ");

    $stmt->execute([$funcionarioId]);

    $funcionario = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$funcionario) {
        http_response_code(404);

        echo json_encode([
            "sucesso" => false,
            "mensagem" => "Funcionário não encontrado ou inativo."
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }

    $embeddingJson = json_encode(
        array_map('floatval', $embedding),
        JSON_PRESERVE_ZERO_FRACTION
    );

    $update = $pdo->prepare("
        UPDATE funcionarios
        SET face_embedding = ?
        WHERE id = ?
    ");

    $update->execute([
        $embeddingJson,
        $funcionarioId
    ]);

    echo json_encode([
        "sucesso" => true,
        "mensagem" => "Rosto cadastrado com sucesso.",
        "funcionario" => $funcionario['nome']
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);

    echo json_encode([
        "sucesso" => false,
        "mensagem" => "Erro ao salvar o cadastro facial."
    ], JSON_UNESCAPED_UNICODE);
}
