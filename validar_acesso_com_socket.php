// ... (código anterior de validação do QR Code) ...

if ($registro) {
    // Invalida o token
    $update = $pdo->prepare("UPDATE tokens_acesso SET usado = 1 WHERE id = ?");
    $update->execute([$registro['id']]);

    // Descobre o ID do funcionário atrelado àquele token (supondo que esteja na tabela)
    $funcionario_id = $registro['funcionario_id'];

    // GATILHO PARA O NODE.JS
    $ch = curl_init('http://localhost:3000/api/notificar-acesso');
    $payload = json_encode([
        "funcionario_id" => $funcionario_id,
        "status" => "sucesso",
        "local" => "Portaria Principal"
    ]);

    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    // Executa a requisição assíncrona sem travar o PHP
    curl_setopt($ch, CURLOPT_TIMEOUT, 1);
    curl_exec($ch);
    curl_close($ch);

    echo json_encode(["status" => "sucesso", "mensagem" => "Catraca aberta."]);
}