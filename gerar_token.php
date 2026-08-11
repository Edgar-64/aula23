
                <?php
// gerar_token.php
require 'conexao.php'; // Sua conexão PDO

// Gera um token aleatório seguro de 32 caracteres
$token = bin2hex(random_bytes(16));

// Define a expiração para 30 segundos a partir de agora
$tempo_vida = 30;
$expira_em = date('Y-m-d H:i:s', strtotime("+$tempo_vida seconds"));

// Salva no banco de dados
$stmt = $pdo->prepare("INSERT INTO tokens_acesso (token, expira_em) VALUES (?, ?)");
$stmt->execute([$token, $expira_em]);

// Retorna o token para o JavaScript em formato JSON
header('Content-Type: application/json');
echo json_encode([
    'token' => $token,
    'tempo_vida' => $tempo_vida
]);
?>