<?php

session_start();

require_once "config/config.php";

header("Content-Type: application/json; charset=UTF-8");


/*
|--------------------------------------------------------------------------
| Verificar login
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION["usuario_id"])) {

    http_response_code(401);

    echo json_encode([
        "sucesso" => false,
        "mensagem" => "Usuário não autenticado."
    ]);

    exit;
}


$usuarioId = (int) $_SESSION["usuario_id"];


/*
|--------------------------------------------------------------------------
| Receber dados
|--------------------------------------------------------------------------
*/

$faseId = isset($_POST["fase_id"])
    ? (int) $_POST["fase_id"]
    : 0;

$pontuacao = isset($_POST["pontuacao"])
    ? (int) $_POST["pontuacao"]
    : 0;

$acertos = isset($_POST["acertos"])
    ? (int) $_POST["acertos"]
    : 0;

$erros = isset($_POST["erros"])
    ? (int) $_POST["erros"]
    : 0;

$dicasUsadas = isset($_POST["dicas_usadas"])
    ? (int) $_POST["dicas_usadas"]
    : 0;


/*
|--------------------------------------------------------------------------
| Validação básica
|--------------------------------------------------------------------------
*/

if ($faseId <= 0) {

    http_response_code(400);

    echo json_encode([
        "sucesso" => false,
        "mensagem" => "Fase inválida."
    ]);

    exit;
}


if ($pontuacao < 0) {
    $pontuacao = 0;
}

if ($acertos < 0) {
    $acertos = 0;
}

if ($erros < 0) {
    $erros = 0;
}

if ($dicasUsadas < 0) {
    $dicasUsadas = 0;
}


/*
|--------------------------------------------------------------------------
| Buscar usuário
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        id,
        nome,
        serie,
        xp,
        pontuacao_total
    FROM usuarios
    WHERE id = ?
");

$stmt->execute([$usuarioId]);

$usuario = $stmt->fetch();


if (!$usuario) {

    http_response_code(404);

    echo json_encode([
        "sucesso" => false,
        "mensagem" => "Usuário não encontrado."
    ]);

    exit;
}


$serie = (int) $usuario["serie"];


/*
|--------------------------------------------------------------------------
| Buscar fase
|--------------------------------------------------------------------------
|
| A fase precisa:
| - existir
| - ser do MathChef
| - pertencer à série do aluno
|
*/

$stmt = $pdo->prepare("
    SELECT
        id,
        jogo_id,
        serie,
        nome,
        numero
    FROM fases
    WHERE id = ?
      AND jogo_id = 1
      AND serie = ?
");

$stmt->execute([
    $faseId,
    $serie
]);

$fase = $stmt->fetch();


if (!$fase) {

    http_response_code(403);

    echo json_encode([
        "sucesso" => false,
        "mensagem" => "Você não pode finalizar esta fase."
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| Descobrir quantidade de questões
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        COUNT(*) AS total_questoes,
        COALESCE(SUM(pontuacao), 0) AS pontuacao_maxima
    FROM questoes
    WHERE fase_id = ?
");

$stmt->execute([$faseId]);

$dadosQuestoes = $stmt->fetch();

$totalQuestoes = (int) $dadosQuestoes["total_questoes"];

$pontuacaoMaxima = (int) $dadosQuestoes["pontuacao_maxima"];


/*
|--------------------------------------------------------------------------
| Validações com base nas questões
|--------------------------------------------------------------------------
*/

if ($totalQuestoes <= 0) {

    http_response_code(400);

    echo json_encode([
        "sucesso" => false,
        "mensagem" => "Esta fase não possui questões."
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| Limitar valores
|--------------------------------------------------------------------------
*/

if ($acertos > $totalQuestoes) {
    $acertos = $totalQuestoes;
}

if ($erros > $totalQuestoes) {
    $erros = $totalQuestoes;
}

if ($dicasUsadas > $totalQuestoes) {
    $dicasUsadas = $totalQuestoes;
}

if ($pontuacao > $pontuacaoMaxima) {
    $pontuacao = $pontuacaoMaxima;
}


/*
|--------------------------------------------------------------------------
| Iniciar transação
|--------------------------------------------------------------------------
*/

try {

    $pdo->beginTransaction();


    /*
    |--------------------------------------------------------------------------
    | Registrar partida
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->prepare("
        INSERT INTO historico_partidas (
            usuario_id,
            jogo_id,
            fase_id,
            pontuacao,
            acertos,
            erros,
            dicas_usadas
        )
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $usuarioId,
        1,
        $faseId,
        $pontuacao,
        $acertos,
        $erros,
        $dicasUsadas
    ]);


    $partidaId = (int) $pdo->lastInsertId();


    /*
    |--------------------------------------------------------------------------
    | Verificar progresso existente
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->prepare("
        SELECT
            id,
            concluida,
            pontuacao,
            tentativas,
            melhor_pontuacao
        FROM progresso_usuario
        WHERE usuario_id = ?
          AND fase_id = ?
        FOR UPDATE
    ");

    $stmt->execute([
        $usuarioId,
        $faseId
    ]);

    $progresso = $stmt->fetch();


    if ($progresso) {

        /*
        |--------------------------------------------------------------------------
        | Fase já possui progresso
        |--------------------------------------------------------------------------
        */

        $melhorPontuacaoAnterior =
            (int) $progresso["melhor_pontuacao"];

        $melhorPontuacao =
            max(
                $melhorPontuacaoAnterior,
                $pontuacao
            );

        $tentativas =
            (int) $progresso["tentativas"] + 1;


        $stmt = $pdo->prepare("
            UPDATE progresso_usuario
            SET
                concluida = TRUE,
                pontuacao = ?,
                tentativas = ?,
                melhor_pontuacao = ?,
                data_conclusao = NOW()
            WHERE id = ?
        ");

        $stmt->execute([
            $pontuacao,
            $tentativas,
            $melhorPontuacao,
            $progresso["id"]
        ]);

    } else {

        /*
        |--------------------------------------------------------------------------
        | Primeiro registro da fase
        |--------------------------------------------------------------------------
        */

        $stmt = $pdo->prepare("
            INSERT INTO progresso_usuario (
                usuario_id,
                fase_id,
                concluida,
                pontuacao,
                tentativas,
                melhor_pontuacao,
                data_conclusao
            )
            VALUES (?, ?, TRUE, ?, 1, ?, NOW())
        ");

        $stmt->execute([
            $usuarioId,
            $faseId,
            $pontuacao,
            $pontuacao
        ]);

        $melhorPontuacao = $pontuacao;
        $tentativas = 1;
    }


    /*
    |--------------------------------------------------------------------------
    | Atualizar XP e pontuação total
    |--------------------------------------------------------------------------
    |
    | Neste momento:
    |
    | 1 ponto = 1 XP
    |
    */

    $xpGanho = $pontuacao;

    $stmt = $pdo->prepare("
        UPDATE usuarios
        SET
            xp = xp + ?,
            pontuacao_total = pontuacao_total + ?
        WHERE id = ?
    ");

    $stmt->execute([
        $xpGanho,
        $pontuacao,
        $usuarioId
    ]);


    /*
    |--------------------------------------------------------------------------
    | Verificar próxima fase
    |--------------------------------------------------------------------------
    */

    $numeroAtual = (int) $fase["numero"];

    $proximoNumero = $numeroAtual + 1;


    $stmt = $pdo->prepare("
        SELECT
            id,
            nome,
            numero
        FROM fases
        WHERE jogo_id = 1
          AND serie = ?
          AND numero = ?
        LIMIT 1
    ");

    $stmt->execute([
        $serie,
        $proximoNumero
    ]);

    $proximaFase = $stmt->fetch();


    /*
    |--------------------------------------------------------------------------
    | Finalizar transação
    |--------------------------------------------------------------------------
    */

    $pdo->commit();


    /*
    |--------------------------------------------------------------------------
    | Resposta
    |--------------------------------------------------------------------------
    */

    echo json_encode([
        "sucesso" => true,
        "mensagem" => "Fase finalizada com sucesso!",

        "partida_id" => $partidaId,

        "fase" => [
            "id" => $faseId,
            "nome" => $fase["nome"],
            "numero" => $numeroAtual
        ],

        "pontuacao" => $pontuacao,

        "acertos" => $acertos,

        "erros" => $erros,

        "dicas_usadas" => $dicasUsadas,

        "xp_ganho" => $xpGanho,

        "melhor_pontuacao" => $melhorPontuacao,

        "tentativas" => $tentativas,

        "proxima_fase" => $proximaFase
            ? [
                "id" => (int) $proximaFase["id"],
                "nome" => $proximaFase["nome"],
                "numero" => (int) $proximaFase["numero"]
            ]
            : null
    ]);

} catch (PDOException $e) {

    /*
    |--------------------------------------------------------------------------
    | Desfazer alterações se algo der errado
    |--------------------------------------------------------------------------
    */

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }


    http_response_code(500);

    echo json_encode([
        "sucesso" => false,
        "mensagem" => "Não foi possível salvar o resultado da fase."
    ]);
}