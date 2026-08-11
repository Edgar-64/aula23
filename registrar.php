<?php
header('Content-Type: application/json; charset=utf-8');
date_default_timezone_set('America/Sao_Paulo');

// Conexão com o Banco de Dados
$host = 'localhost';
$db = 'controle_acesso';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Erro de conexão com o banco']);
    exit;
}

// Recebe os dados enviados via JSON/POST
$dados = json_decode(file_get_contents('php://input'), true);
$tag_uid = $dados['tag_uid'] ?? null;
$codigo_ponto = $dados['codigo_ponto'] ?? null;

if (!$tag_uid || !$codigo_ponto) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Dados incompletos fornecidos']);
    exit;
}

// 1. Identificar Funcionário
$stmt = $pdo->prepare("SELECT id, nome FROM funcionarios WHERE (tag_uid = :tag OR matricula = :tag) AND ativo = 1");
$stmt->execute([':tag' => $tag_uid]);
$funcionario = $stmt->fetch();

if (!$funcionario) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Funcionário não identificado']);
    exit;
}

// 2. Identificar Local
$stmt = $pdo->prepare("SELECT id, nome_local FROM locais WHERE codigo_ponto = :ponto");
$stmt->execute([':ponto' => $codigo_ponto]);
$local = $stmt->fetch();

if (!$local) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Local não cadastrado']);
    exit;
}

// 3. Verificar última movimentação para definir Entrada ou Saída
$stmt = $pdo->prepare("SELECT tipo, data_hora FROM movimentacoes WHERE funcionario_id = :func_id ORDER BY id DESC LIMIT 1");
$stmt->execute([':func_id' => $funcionario['id']]);
$ultimaMov = $stmt->fetch();

$novoTipo = 'ENTRADA';
if ($ultimaMov && $ultimaMov['tipo'] === 'ENTRADA') {
    $novoTipo = 'SAIDA';
}

// 4. Inserir Registro
$stmt = $pdo->prepare("INSERT INTO movimentacoes (funcionario_id, local_id, tipo) VALUES (:func_id, :local_id, :tipo)");
$stmt->execute([
    ':func_id' => $funcionario['id'],
    ':local_id' => $local['id'],
    ':tipo' => $novoTipo
]);

echo json_encode([
    'sucesso' => true,
    'funcionario' => $funcionario['nome'],
    'local' => $local['nome_local'],
    'tipo' => $novoTipo,
    'horario' => date('H:i:s - d/m/Y')
]);