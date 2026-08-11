<?php

require 'conexao.php';

header('Content-Type: application/json; charset=utf-8');

$entrada = file_get_contents('php://input');

$dados = json_decode($entrada, true);

if (!is_array($dados)) {

    http_response_code(400);

    echo json_encode([
        "sucesso" => false,
        "mensagem" => "Dados inválidos."
    ], JSON_UNESCAPED_UNICODE);

    exit;
}


$embeddingRecebido =
    $dados['face_embedding'] ?? null;

$codigoPonto =
    $dados['codigo_ponto'] ?? null;


/*
|--------------------------------------------------------------------------
| VALIDA EMBEDDING
|--------------------------------------------------------------------------
*/

if (
    !is_array($embeddingRecebido) ||
    count($embeddingRecebido) !== 128
) {

    http_response_code(400);

    echo json_encode([
        "sucesso" => false,
        "mensagem" => "Rosto inválido."
    ], JSON_UNESCAPED_UNICODE);

    exit;
}


/*
|--------------------------------------------------------------------------
| CONVERTE PARA FLOAT
|--------------------------------------------------------------------------
*/

$embeddingRecebido =
    array_map('floatval', $embeddingRecebido);


/*
|--------------------------------------------------------------------------
| BUSCA FUNCIONÁRIOS COM ROSTO CADASTRADO
|--------------------------------------------------------------------------
*/

try {

    $stmt = $pdo->query("
        SELECT
            id,
            nome,
            matricula,
            face_embedding
        FROM funcionarios
        WHERE ativo = 1
          AND face_embedding IS NOT NULL
          AND face_embedding <> ''
    ");

    $funcionarios =
        $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {

    http_response_code(500);

    echo json_encode([
        "sucesso" => false,
        "mensagem" =>
            "Erro ao consultar funcionários."
    ], JSON_UNESCAPED_UNICODE);

    exit;
}


/*
|--------------------------------------------------------------------------
| DISTÂNCIA EUCLIDIANA
|--------------------------------------------------------------------------
*/

function distanciaEuclidiana(
    array $a,
    array $b
) {

    if (count($a) !== count($b)) {
        return INF;
    }

    $soma = 0;

    for ($i = 0; $i < count($a); $i++) {

        $d =
            $a[$i] - $b[$i];

        $soma +=
            $d * $d;
    }

    return sqrt($soma);
}


/*
|--------------------------------------------------------------------------
| COMPARAÇÃO
|--------------------------------------------------------------------------
*/

/*
 * O valor 0.50 é relativamente rigoroso.
 *
 * Quanto MENOR a distância,
 * mais parecido é o rosto.
 *
 * Se estiver rejeitando o próprio funcionário,
 * podemos ajustar depois.
 */

$limiar = 0.50;

$melhorFuncionario = null;

$menorDistancia = INF;


foreach ($funcionarios as $funcionario) {

    $cadastrado =
        json_decode(
            $funcionario['face_embedding'],
            true
        );

    if (
        !is_array($cadastrado) ||
        count($cadastrado) !== 128
    ) {
        continue;
    }

    $cadastrado =
        array_map(
            'floatval',
            $cadastrado
        );


    $distancia =
        distanciaEuclidiana(
            $embeddingRecebido,
            $cadastrado
        );


    if (
        $distancia < $menorDistancia
    ) {

        $menorDistancia =
            $distancia;

        $melhorFuncionario =
            $funcionario;
    }
}


/*
|--------------------------------------------------------------------------
| NENHUM ROSTO ENCONTRADO
|--------------------------------------------------------------------------
*/

if (
    $melhorFuncionario === null ||
    $menorDistancia > $limiar
) {

    echo json_encode([
        "sucesso" => false,
        "mensagem" =>
            "Rosto não reconhecido."
    ], JSON_UNESCAPED_UNICODE);

    exit;
}


/*
|--------------------------------------------------------------------------
| ACESSO LIBERADO
|--------------------------------------------------------------------------
*/

try {

    /*
     * Aqui podemos registrar a movimentação
     * quando você me mostrar a estrutura da
     * tabela movimentacoes.
     *
     * Por enquanto não inserimos nada nela,
     * para não inventar colunas que talvez
     * não existam no seu banco.
     */

    echo json_encode([
        "sucesso" => true,
        "mensagem" =>
            "Rosto reconhecido.",
        "funcionario" =>
            $melhorFuncionario['nome'],
        "matricula" =>
            $melhorFuncionario['matricula'],
        "codigo_ponto" =>
            $codigoPonto,
        "distancia" =>
            round($menorDistancia, 4)
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {

    http_response_code(500);

    echo json_encode([
        "sucesso" => false,
        "mensagem" =>
            "Erro ao liberar acesso."
    ], JSON_UNESCAPED_UNICODE);
}