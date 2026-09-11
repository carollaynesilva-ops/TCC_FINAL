<?php

session_start();

require_once __DIR__ . "/config/config.php";

header("Content-Type: application/json; charset=UTF-8");


// =========================================================
// VERIFICAR LOGIN
// =========================================================

if (!isset($_SESSION["usuario_id"])) {

    http_response_code(401);

    echo json_encode([
        "sucesso" => false,
        "mensagem" => "Usuário não autenticado."
    ]);

    exit;
}


$usuarioId = (int) $_SESSION["usuario_id"];


// =========================================================
// RECEBER DADOS
// =========================================================

$faseId = isset($_POST["fase_id"])
    ? (int) $_POST["fase_id"]
    : 0;

$respostasJson = $_POST["respostas"] ?? "";


// =========================================================
// VALIDAR FASE
// =========================================================

if ($faseId <= 0) {

    http_response_code(400);

    echo json_encode([
        "sucesso" => false,
        "mensagem" => "Fase inválida."
    ]);

    exit;
}


// =========================================================
// DECODIFICAR RESPOSTAS
// =========================================================

$respostas = json_decode(
    $respostasJson,
    true
);


if (
    !is_array($respostas) ||
    empty($respostas)
) {

    http_response_code(400);

    echo json_encode([
        "sucesso" => false,
        "mensagem" => "Nenhuma resposta foi enviada."
    ]);

    exit;
}


// =========================================================
// BUSCAR USUÁRIO
// =========================================================

$stmt = $pdo->prepare("
    SELECT
        id,
        nome,
        serie,
        xp,
        nivel,
        pontuacao_total
    FROM usuarios
    WHERE id = ?
");

$stmt->execute([
    $usuarioId
]);

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


// =========================================================
// BUSCAR FASE
// =========================================================
//
// A fase precisa:
// - existir
// - ser MathChef
// - pertencer à série do aluno
//

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
    LIMIT 1
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


// =========================================================
// BUSCAR QUESTÕES DA FASE
// =========================================================

$stmt = $pdo->prepare("
    SELECT
        id,
        pontuacao
    FROM questoes
    WHERE fase_id = ?
    ORDER BY id
");

$stmt->execute([
    $faseId
]);

$questoesBanco = $stmt->fetchAll();


$totalQuestoes =
    count($questoesBanco);


if ($totalQuestoes === 0) {

    http_response_code(400);

    echo json_encode([
        "sucesso" => false,
        "mensagem" => "Esta fase não possui questões."
    ]);

    exit;
}


// =========================================================
// CRIAR LISTA DE QUESTÕES VÁLIDAS
// =========================================================

$questoesValidas = [];

foreach ($questoesBanco as $questao) {

    $questoesValidas[
        (int) $questao["id"]
    ] = [
        "pontuacao" =>
            (int) $questao["pontuacao"]
    ];
}


// =========================================================
// VALIDAR QUANTIDADE DE RESPOSTAS
// =========================================================
//
// O aluno precisa responder todas as questões
// antes de finalizar a fase.
//

if (
    count($respostas) !==
    $totalQuestoes
) {

    http_response_code(400);

    echo json_encode([
        "sucesso" => false,
        "mensagem" =>
            "É necessário responder todas as questões antes de finalizar a fase."
    ]);

    exit;
}


// =========================================================
// PREPARAR CONSULTA DAS ALTERNATIVAS
// =========================================================

$stmtAlternativa = $pdo->prepare("
    SELECT
        id,
        questao_id,
        texto,
        correta
    FROM alternativas
    WHERE id = ?
      AND questao_id = ?
    LIMIT 1
");


// =========================================================
// VARIÁVEIS DO RESULTADO
// =========================================================

$pontuacao = 0;

$acertos = 0;

$erros = 0;

$dicasUsadas = 0;


// Guarda quais questões já foram respondidas.
// Isso impede enviar a mesma questão duas vezes.
$questoesRespondidas = [];


// Guarda os dados que serão inseridos
// em respostas_usuario.
$respostasValidadas = [];


// =========================================================
// VALIDAR CADA RESPOSTA
// =========================================================

foreach ($respostas as $resposta) {

    // -----------------------------------------------------
    // ID DA QUESTÃO
    // -----------------------------------------------------

    $questaoId =
        isset($resposta["questao_id"])
            ? (int) $resposta["questao_id"]
            : 0;


    // -----------------------------------------------------
    // ID DA ALTERNATIVA
    // -----------------------------------------------------

    $alternativaId =
        isset($resposta["alternativa_id"])
            ? (int) $resposta["alternativa_id"]
            : 0;


    // -----------------------------------------------------
    // TEMPO DE RESPOSTA
    // -----------------------------------------------------

    $tempoResposta =
        isset($resposta["tempo_resposta"])
            ? (int) $resposta["tempo_resposta"]
            : 0;


    // -----------------------------------------------------
    // DICA
    // -----------------------------------------------------

    $usouDica =
        !empty($resposta["usou_dica"]);


    // -----------------------------------------------------
    // VALIDAR ID DA QUESTÃO
    // -----------------------------------------------------

    if (
        !isset(
            $questoesValidas[$questaoId]
        )
    ) {

        http_response_code(400);

        echo json_encode([
            "sucesso" => false,
            "mensagem" =>
                "Foi enviada uma questão inválida."
        ]);

        exit;
    }


    // -----------------------------------------------------
    // IMPEDIR QUESTÃO DUPLICADA
    // -----------------------------------------------------

    if (
        isset(
            $questoesRespondidas[$questaoId]
        )
    ) {

        http_response_code(400);

        echo json_encode([
            "sucesso" => false,
            "mensagem" =>
                "Uma questão foi enviada mais de uma vez."
        ]);

        exit;
    }


    $questoesRespondidas[$questaoId] = true;


    // -----------------------------------------------------
    // VALIDAR TEMPO
    // -----------------------------------------------------

    if ($tempoResposta < 0) {
        $tempoResposta = 0;
    }

    /*
     * Evita valores absurdos enviados manualmente.
     * 1 hora para responder uma questão já é uma
     * relação bastante generosa com a matemática.
     */

    if ($tempoResposta > 3600) {
        $tempoResposta = 3600;
    }


    // -----------------------------------------------------
    // BUSCAR ALTERNATIVA NO BANCO
    // -----------------------------------------------------

    if ($alternativaId <= 0) {

        http_response_code(400);

        echo json_encode([
            "sucesso" => false,
            "mensagem" =>
                "Alternativa inválida."
        ]);

        exit;
    }


    $stmtAlternativa->execute([
        $alternativaId,
        $questaoId
    ]);

    $alternativa =
        $stmtAlternativa->fetch();


    /*
     * Se a alternativa não pertence à questão,
     * rejeitamos a resposta.
     */

    if (!$alternativa) {

        http_response_code(400);

        echo json_encode([
            "sucesso" => false,
            "mensagem" =>
                "A alternativa enviada não pertence à questão."
        ]);

        exit;
    }


    // -----------------------------------------------------
    // VERIFICAR RESPOSTA CORRETA
    // -----------------------------------------------------

    $correta =
        (bool) $alternativa["correta"];


    if ($correta) {

        $acertos++;

        $pontuacao +=
            $questoesValidas[$questaoId]["pontuacao"];

    } else {

        $erros++;
    }


    // -----------------------------------------------------
    // CONTAR DICAS
    // -----------------------------------------------------

    if ($usouDica) {
        $dicasUsadas++;
    }


    // -----------------------------------------------------
    // GUARDAR RESPOSTA VALIDADA
    // -----------------------------------------------------

    $respostasValidadas[] = [

        "questao_id" =>
            $questaoId,

        "resposta" =>
            $alternativa["texto"],

        "correta" =>
            $correta,

        "tempo_resposta" =>
            $tempoResposta,

        "usou_dica" =>
            $usouDica
    ];
}


// =========================================================
// GARANTIR QUE TODAS AS QUESTÕES FORAM RESPONDIDAS
// =========================================================

if (
    count($questoesRespondidas) !==
    $totalQuestoes
) {

    http_response_code(400);

    echo json_encode([
        "sucesso" => false,
        "mensagem" =>
            "Nem todas as questões foram respondidas."
    ]);

    exit;
}


// =========================================================
// INICIAR TRANSAÇÃO
// =========================================================

try {

    $pdo->beginTransaction();


    // =====================================================
    // REGISTRAR PARTIDA
    // =====================================================

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


    $partidaId =
        (int) $pdo->lastInsertId();


    // =====================================================
    // REGISTRAR CADA RESPOSTA
    // =====================================================

    $stmtResposta = $pdo->prepare("
        INSERT INTO respostas_usuario (
            usuario_id,
            questao_id,
            partida_id,
            resposta,
            correta,
            tempo_resposta,
            usou_dica
        )
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");


    foreach (
        $respostasValidadas
        as $respostaValida
    ) {

        $stmtResposta->execute([

            $usuarioId,

            $respostaValida["questao_id"],

            $partidaId,

            $respostaValida["resposta"],

            $respostaValida["correta"] ? 1 : 0,

            $respostaValida["tempo_resposta"],

            $respostaValida["usou_dica"] ? 1 : 0
        ]);
    }


    // =====================================================
    // VERIFICAR PROGRESSO DA FASE
    // =====================================================

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

    $progresso =
        $stmt->fetch();


    // =====================================================
    // ATUALIZAR PROGRESSO EXISTENTE
    // =====================================================

    if ($progresso) {

        $melhorPontuacaoAnterior =
            (int) $progresso[
                "melhor_pontuacao"
            ];


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

        // =================================================
        // PRIMEIRO REGISTRO DA FASE
        // =================================================

        $melhorPontuacao =
            $pontuacao;

        $tentativas = 1;


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

            $melhorPontuacao
        ]);
    }


    // =====================================================
    // ATUALIZAR XP E PONTUAÇÃO
    // =====================================================
    //
    // Por enquanto:
    //
    // 1 ponto = 1 XP
    //

    $xpGanho =
        $pontuacao;


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


    // =====================================================
    // VERIFICAR PRÓXIMA FASE
    // =====================================================

    $numeroAtual =
        (int) $fase["numero"];

    $proximoNumero =
        $numeroAtual + 1;


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


    $proximaFase =
        $stmt->fetch();


    // =====================================================
    // CONFIRMAR TRANSAÇÃO
    // =====================================================

    $pdo->commit();


    // =====================================================
    // RESPOSTA PARA O JAVASCRIPT
    // =====================================================

    echo json_encode([

        "sucesso" => true,

        "mensagem" =>
            "Fase finalizada com sucesso!",

        "partida_id" =>
            $partidaId,

        "fase" => [

            "id" =>
                $faseId,

            "nome" =>
                $fase["nome"],

            "numero" =>
                $numeroAtual
        ],

        "pontuacao" =>
            $pontuacao,

        "acertos" =>
            $acertos,

        "erros" =>
            $erros,

        "dicas_usadas" =>
            $dicasUsadas,

        "xp_ganho" =>
            $xpGanho,

        "melhor_pontuacao" =>
            $melhorPontuacao,

        "tentativas" =>
            $tentativas,

        "proxima_fase" =>
            $proximaFase
                ? [

                    "id" =>
                        (int) $proximaFase["id"],

                    "nome" =>
                        $proximaFase["nome"],

                    "numero" =>
                        (int) $proximaFase["numero"]

                ]
                : null
    ]);


// =========================================================
// ERRO
// =========================================================

} catch (PDOException $e) {

    if ($pdo->inTransaction()) {

        $pdo->rollBack();
    }


    http_response_code(500);

    echo json_encode([

        "sucesso" => false,

        "mensagem" =>
            "Não foi possível salvar o resultado da fase."
    ]);
}