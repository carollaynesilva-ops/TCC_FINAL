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
// BUSCAR CONFIGURAÇÃO DO MATHCHEF
// =========================================================
//
// A configuração fica no banco para não espalhar
// os valores 20 e 60 pelo código.
//

try {

    $stmt = $pdo->query("
        SELECT
            xp_por_questao,
            xp_para_proxima_fase
        FROM configuracao_mathchef
        ORDER BY id ASC
        LIMIT 1
    ");

    $configuracao = $stmt->fetch();
} catch (PDOException $e) {

    $configuracao = false;
}


//
// Caso a configuração ainda não exista,
// usamos os valores definidos para o MathChef.
//

$XP_POR_QUESTAO = $configuracao
    ? (int) $configuracao["xp_por_questao"]
    : 20;

$XP_NECESSARIO_FASE = $configuracao
    ? (int) $configuracao["xp_para_proxima_fase"]
    : 60;


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
// - ser do MathChef
// - pertencer à série do usuário
//

$stmt = $pdo->prepare("
    SELECT
        id,
        jogo_id,
        serie,
        nome,
        descricao,
        nivel_dificuldade,
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
        "mensagem" => "Você não pode jogar esta fase."
    ]);

    exit;
}


// =========================================================
// VERIFICAR SE A FASE ESTÁ DESBLOQUEADA
// =========================================================
//
// A primeira fase está liberada.
//
// Para as demais, a fase anterior precisa ter pelo menos
// 60 XP conquistados.
//

$numeroAtual = (int) $fase["numero"];

if ($numeroAtual > 1) {

    $numeroAnterior = $numeroAtual - 1;

    $stmt = $pdo->prepare("
        SELECT
            COALESCE(pu.xp_conquistado, 0) AS xp_conquistado
        FROM fases f

        LEFT JOIN progresso_usuario pu
            ON pu.fase_id = f.id
            AND pu.usuario_id = ?

        WHERE f.jogo_id = 1
          AND f.serie = ?
          AND f.numero = ?

        LIMIT 1
    ");

    $stmt->execute([
        $usuarioId,
        $serie,
        $numeroAnterior
    ]);

    $faseAnterior = $stmt->fetch();

    $xpFaseAnterior = $faseAnterior
        ? (int) $faseAnterior["xp_conquistado"]
        : 0;

    if ($xpFaseAnterior < $XP_NECESSARIO_FASE) {

        http_response_code(403);

        echo json_encode([
            "sucesso" => false,
            "mensagem" =>
            "Você ainda não possui XP suficiente para desbloquear esta fase.",
            "xp_atual" => $xpFaseAnterior,
            "xp_necessario" => $XP_NECESSARIO_FASE
        ]);

        exit;
    }
}


// =========================================================
// BUSCAR TODAS AS QUESTÕES DA FASE
// =========================================================

$stmt = $pdo->prepare("
    SELECT
        id,
        pontuacao
    FROM questoes
    WHERE fase_id = ?
    ORDER BY id ASC
");

$stmt->execute([
    $faseId
]);

$questoesBanco = $stmt->fetchAll();

$totalQuestoes = count($questoesBanco);


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

    $questaoId = (int) $questao["id"];

    $questoesValidas[$questaoId] = [
        "pontuacao" => (int) $questao["pontuacao"]
    ];
}


// =========================================================
// BUSCAR QUESTÕES QUE O USUÁRIO JÁ ACERTOU
// =========================================================
//
// Esta é a parte principal do sistema de XP.
//
// Se uma questão já foi acertada anteriormente,
// ela nunca mais dará XP.
//
// Exemplo:
//
// Primeira tentativa:
// questão 1 → certa → +20 XP
//
// Segunda tentativa:
// questão 1 → certa → +0 XP
//
// Segunda tentativa:
// questão 2, que antes estava errada → certa → +20 XP
//

$stmt = $pdo->prepare("
    SELECT DISTINCT
        ru.questao_id

    FROM respostas_usuario ru

    INNER JOIN questoes q
        ON q.id = ru.questao_id

    WHERE ru.usuario_id = ?
      AND q.fase_id = ?
      AND ru.correta = 1
");

$stmt->execute([
    $usuarioId,
    $faseId
]);

$questoesJaAcertadas = [];

while ($linha = $stmt->fetch()) {

    $questaoAcertadaId =
        (int) $linha["questao_id"];

    $questoesJaAcertadas[$questaoAcertadaId] = true;
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
// VARIÁVEIS DA PARTIDA
// =========================================================

$pontuacao = 0;

$acertos = 0;

$erros = 0;

$dicasUsadas = 0;

$xpGanho = 0;

$xpQuestoesNovas = 0;


// =========================================================
// CONTROLE DAS QUESTÕES RESPONDIDAS
// =========================================================

$questoesRespondidas = [];


// =========================================================
// RESPOSTAS VALIDADAS
// =========================================================

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
    // TEMPO
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
    // VERIFICAR QUESTÃO
    // -----------------------------------------------------

    if (!isset($questoesValidas[$questaoId])) {

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

    if (isset($questoesRespondidas[$questaoId])) {

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

    if ($tempoResposta > 3600) {

        $tempoResposta = 3600;
    }


    // -----------------------------------------------------
    // VALIDAR ALTERNATIVA
    // -----------------------------------------------------

    if ($alternativaId <= 0) {

        http_response_code(400);

        echo json_encode([
            "sucesso" => false,
            "mensagem" => "Alternativa inválida."
        ]);

        exit;
    }


    // -----------------------------------------------------
    // BUSCAR ALTERNATIVA NO BANCO
    // -----------------------------------------------------

    $stmtAlternativa->execute([
        $alternativaId,
        $questaoId
    ]);

    $alternativa =
        $stmtAlternativa->fetch();


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


    // -----------------------------------------------------
    // PONTUAÇÃO DA TENTATIVA
    // -----------------------------------------------------
    //
    // A pontuação da tentativa considera todos os acertos
    // realizados agora.
    //
    // Isso é diferente do XP:
    //
    // pontuação = resultado desta tentativa
    //
    // XP = somente questões que ainda não tinham sido
    //      acertadas anteriormente.
    //

    if ($correta) {

        $acertos++;

        $pontuacao +=
            $questoesValidas[$questaoId]["pontuacao"];
    } else {

        $erros++;
    }


    // -----------------------------------------------------
    // XP DA QUESTÃO
    // -----------------------------------------------------
    //
    // Só damos XP se:
    //
    // 1. a resposta estiver correta;
    // 2. a questão ainda não tiver sido acertada antes.
    //

    if (
        $correta &&
        !isset($questoesJaAcertadas[$questaoId])
    ) {

        $xpGanho +=
            $XP_POR_QUESTAO;

        $xpQuestoesNovas++;

        //
        // Marcamos imediatamente para evitar
        // qualquer duplicação nesta requisição.
        //

        $questoesJaAcertadas[$questaoId] = true;
    }


    // -----------------------------------------------------
    // DICAS
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
// DESCOBRIR QUESTÕES AINDA PENDENTES
// =========================================================
//
// Depois da tentativa atual, as questões que continuam
// sem nenhum acerto ficam pendentes.
//

$questoesPendentes = [];

foreach (
    $questoesValidas
    as $questaoId => $dadosQuestao
) {

    if (
        !isset(
            $questoesJaAcertadas[$questaoId]
        )
    ) {

        $questoesPendentes[] =
            (int) $questaoId;
    }
}


// =========================================================
// QUANTIDADE DE QUESTÕES JÁ ACERTADAS
// =========================================================

$quantidadeQuestoesAcertadas =
    count($questoesJaAcertadas);


// =========================================================
// XP TOTAL CONQUISTADO NA FASE
// =========================================================
//
// Cada questão só pode fornecer XP uma vez.
//

$xpDaFase =
    $quantidadeQuestoesAcertadas *
    $XP_POR_QUESTAO;


// =========================================================
// VERIFICAR CONCLUSÃO DA FASE
// =========================================================
//
// A fase só é realmente concluída quando todas as
// questões já tiverem sido acertadas pelo menos uma vez.
//

$faseConcluida =
    (
        $quantidadeQuestoesAcertadas >=
        $totalQuestoes
    );


// =========================================================
// INICIAR TRANSAÇÃO
// =========================================================

try {

    $pdo->beginTransaction();


    // =====================================================
    // BUSCAR PROGRESSO ATUAL
    // =====================================================

    $stmt = $pdo->prepare("
        SELECT
            id,
            concluida,
            pontuacao,
            tentativas,
            melhor_pontuacao,
            xp_conquistado
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

            $respostaValida["correta"]
                ? 1
                : 0,

            $respostaValida["tempo_resposta"],

            $respostaValida["usou_dica"]
                ? 1
                : 0
        ]);
    }


    // =====================================================
    // ATUALIZAR PROGRESSO DA FASE
    // =====================================================

    $melhorPontuacaoAnterior =
        $progresso
        ? (int) $progresso["melhor_pontuacao"]
        : 0;


    $melhorPontuacao =
        max(
            $melhorPontuacaoAnterior,
            $pontuacao
        );


    $tentativas =
        $progresso
        ? (int) $progresso["tentativas"] + 1
        : 1;


    if ($progresso) {

        $stmt = $pdo->prepare("
            UPDATE progresso_usuario
            SET
                concluida = ?,
                pontuacao = ?,
                tentativas = ?,
                melhor_pontuacao = ?,
                xp_conquistado = ?,
                data_conclusao = ?
            WHERE id = ?
        ");

        $stmt->execute([

            $faseConcluida ? 1 : 0,

            $pontuacao,

            $tentativas,

            $melhorPontuacao,

            $xpDaFase,

            $faseConcluida
                ? date("Y-m-d H:i:s")
                : null,

            $progresso["id"]
        ]);
    } else {

        $stmt = $pdo->prepare("
            INSERT INTO progresso_usuario (
                usuario_id,
                fase_id,
                concluida,
                pontuacao,
                tentativas,
                melhor_pontuacao,
                xp_conquistado,
                data_conclusao
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([

            $usuarioId,

            $faseId,

            $faseConcluida ? 1 : 0,

            $pontuacao,

            1,

            $melhorPontuacao,

            $xpDaFase,

            $faseConcluida
                ? date("Y-m-d H:i:s")
                : null
        ]);
    }


    // =====================================================
    // ATUALIZAR XP GLOBAL DO USUÁRIO
    // =====================================================
    //
    // SOMENTE o XP novo desta tentativa é acrescentado.
    //
    // Exemplo:
    //
    // Primeira tentativa:
    // 2 acertos novos = +40 XP
    //
    // Replay:
    // 1 questão anteriormente errada foi acertada
    // = +20 XP
    //
    // Replay novamente:
    // nenhuma questão nova acertada
    // = +0 XP
    //

    if ($xpGanho > 0) {

        $stmt = $pdo->prepare("
            UPDATE usuarios
            SET
                xp = xp + ?,
                pontuacao_total = pontuacao_total + ?
            WHERE id = ?
        ");

        $stmt->execute([

            $xpGanho,

            $xpGanho,

            $usuarioId
        ]);
    }


    // =====================================================
    // VERIFICAR SE A PRÓXIMA FASE FOI LIBERADA
    // =====================================================

    $faseLiberada =
        (
            $xpDaFase >=
            $XP_NECESSARIO_FASE
        );


    // =====================================================
    // BUSCAR PRÓXIMA FASE
    // =====================================================

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
    // XP GLOBAL ATUALIZADO
    // =====================================================

    $xpAtualizado =
        (int) $usuario["xp"] +
        $xpGanho;


    // =====================================================
    // FINALIZAR TRANSAÇÃO
    // =====================================================

    $pdo->commit();


    // =====================================================
    // RESPOSTA PARA O JAVASCRIPT
    // =====================================================

    echo json_encode([

        "sucesso" => true,

        "mensagem" =>
        $xpGanho > 0
            ? "Fase finalizada com sucesso!"
            : "Fase finalizada. Nenhum XP novo foi conquistado nesta tentativa.",


        // -------------------------------------------------
        // PARTIDA
        // -------------------------------------------------

        "partida_id" =>
        $partidaId,


        // -------------------------------------------------
        // FASE
        // -------------------------------------------------

        "fase" => [

            "id" =>
            $faseId,

            "nome" =>
            $fase["nome"],

            "numero" =>
            $numeroAtual
        ],


        // -------------------------------------------------
        // RESULTADO DA TENTATIVA
        // -------------------------------------------------

        "pontuacao" =>
        $pontuacao,

        "acertos" =>
        $acertos,

        "erros" =>
        $erros,

        "dicas_usadas" =>
        $dicasUsadas,


        // -------------------------------------------------
        // XP
        // -------------------------------------------------

        "xp_ganho" =>
        $xpGanho,

        "xp_atual" =>
        $xpAtualizado,

        "xp_da_fase" =>
        $xpDaFase,

        "xp_necessario" =>
        $XP_NECESSARIO_FASE,

        "xp_questoes_novas" =>
        $xpQuestoesNovas,


        // -------------------------------------------------
        // PROGRESSO DA FASE
        // -------------------------------------------------

        "fase_concluida" =>
        $faseConcluida,

        "fase_liberada" =>
        $faseLiberada,

        "questoes_acertadas" =>
        $quantidadeQuestoesAcertadas,

        "total_questoes" =>
        $totalQuestoes,

        "questoes_pendentes" =>
        $questoesPendentes,


        // -------------------------------------------------
        // DADOS DA TENTATIVA
        // -------------------------------------------------

        "melhor_pontuacao" =>
        $melhorPontuacao,

        "tentativas" =>
        $tentativas,


        // -------------------------------------------------
        // PRÓXIMA FASE
        // -------------------------------------------------

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
    // ERRO DO BANCO
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
