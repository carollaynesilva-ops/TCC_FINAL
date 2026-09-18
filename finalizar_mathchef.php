<?php

session_start();

require_once __DIR__ . "/config/config.php";

header("Content-Type: application/json; charset=UTF-8");


// =========================================================
// CONFIGURAÇÕES DO MATHCHEF
// =========================================================

/*
 * Cada questão vale 20 XP.
 */
$XP_POR_QUESTAO = 20;

/*
 * São necessários 60 XP conquistados na fase
 * para liberar a próxima fase.
 */
$XP_NECESSARIO_FASE = 60;


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
// BUSCAR TODAS AS QUESTÕES DA FASE
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

    $questoesValidas[(int) $questao["id"]] = [
        "pontuacao" => (int) $questao["pontuacao"]
    ];
}


// =========================================================
// BUSCAR QUESTÕES QUE O ALUNO JÁ ACERTOU
// =========================================================
//
// Essa consulta é a parte importante da nova lógica.
//
// Se o aluno já acertou determinada questão em qualquer
// tentativa anterior dessa fase, ela NÃO poderá gerar XP
// novamente.
//
// Portanto:
//
// primeira vez + acerto = XP
// primeira vez + erro   = 0 XP
// replay + acerto de questão anteriormente errada = XP
// replay + acerto de questão já acertada = 0 XP
//

$stmt = $pdo->prepare("
    SELECT DISTINCT ru.questao_id
    FROM respostas_usuario ru
    INNER JOIN questoes q
        ON q.id = ru.questao_id
    INNER JOIN historico_partidas hp
        ON hp.id = ru.partida_id
    WHERE ru.usuario_id = ?
      AND q.fase_id = ?
      AND ru.correta = 1
      AND hp.fase_id = ?
");

$stmt->execute([
    $usuarioId,
    $faseId,
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
// QUESTÕES QUE AINDA PRECISAM SER RECUPERADAS
// =========================================================

$questoesPendentes = [];


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
    // VALIDAR QUESTÃO
    // -----------------------------------------------------

    if (!isset($questoesValidas[$questaoId])) {

        http_response_code(400);

        echo json_encode([
            "sucesso" => false,
            "mensagem" => "Foi enviada uma questão inválida."
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
    // VERIFICAR SE ESTÁ CORRETA
    // -----------------------------------------------------

    $correta =
        (bool) $alternativa["correta"];


    // -----------------------------------------------------
    // PONTUAÇÃO
    // -----------------------------------------------------

    if ($correta) {

        $acertos++;

        $pontuacao +=
            $questoesValidas[$questaoId]["pontuacao"];
    } else {

        $erros++;

        /*
         * Se errou agora, essa questão continua pendente.
         */
        $questoesPendentes[] = $questaoId;
    }


    // -----------------------------------------------------
    // XP
    // -----------------------------------------------------
    //
    // O XP depende de a questão já ter sido acertada
    // anteriormente.
    //

    if (
        $correta &&
        !isset($questoesJaAcertadas[$questaoId])
    ) {

        /*
         * Primeira vez que o aluno acerta esta questão.
         */
        $xpGanho += $XP_POR_QUESTAO;

        $xpQuestoesNovas++;

        /*
         * Marcamos localmente como acertada para impedir
         * qualquer duplicação dentro da mesma requisição.
         */
        $questoesJaAcertadas[$questaoId] = true;
    }


    // -----------------------------------------------------
    // DICAS
    // -----------------------------------------------------

    if ($usouDica) {

        $dicasUsadas++;
    }


    // -----------------------------------------------------
    // GUARDAR RESPOSTA
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
// BUSCAR QUESTÕES QUE AINDA ESTÃO PENDENTES
// =========================================================
//
// Depois de analisar a tentativa atual, precisamos descobrir
// todas as questões da fase que ainda NÃO foram acertadas.
//
// Isso será utilizado pelo jogar_mathchef.js para montar
// o próximo replay.
//

$questoesPendentes = [];

foreach ($questoesValidas as $questaoId => $dadosQuestao) {

    if (!isset($questoesJaAcertadas[$questaoId])) {

        $questoesPendentes[] =
            (int) $questaoId;
    }
}


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
    // VERIFICAR XP JÁ GANHO NESTA FASE
    // =====================================================
    //
    // O XP global do usuário não permite descobrir quanto
    // foi conquistado especificamente nesta fase.
    //
    // Por isso usamos as respostas corretas já registradas
    // para calcular a quantidade de questões que já deram XP.
    //
    // Como cada questão vale 20 XP:
    //
    // questões acertadas × 20 = XP conquistado na fase
    //

    $quantidadeQuestoesAcertadas =
        count($questoesJaAcertadas);


    $xpDaFase =
        $quantidadeQuestoesAcertadas *
        $XP_POR_QUESTAO;


    // =====================================================
    // VERIFICAR SE A FASE ESTÁ CONCLUÍDA
    // =====================================================
    //
    // A fase passa a ser considerada concluída quando
    // todas as questões tiverem sido acertadas pelo menos
    // uma vez.
    //

    $faseConcluida =
        (
            $quantidadeQuestoesAcertadas >=
            $totalQuestoes
        );


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
    // ATUALIZAR PROGRESSO
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
                data_conclusao = ?
            WHERE id = ?
        ");


        $stmt->execute([

            $faseConcluida ? 1 : 0,

            $pontuacao,

            $tentativas,

            $melhorPontuacao,

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
                data_conclusao
            )
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");


        $stmt->execute([

            $usuarioId,

            $faseId,

            $faseConcluida ? 1 : 0,

            $pontuacao,

            1,

            $melhorPontuacao,

            $faseConcluida
                ? date("Y-m-d H:i:s")
                : null
        ]);
    }


    // =====================================================
    // ATUALIZAR XP DO USUÁRIO
    // =====================================================
    //
    // SOMENTE o XP novo desta tentativa é acrescentado.
    //
    // Exemplo:
    //
    // Primeira tentativa:
    // acertou 2 → +40 XP
    //
    // Replay:
    // acertou 1 das erradas → +20 XP
    //
    // Replay novamente:
    // acertou uma já acertada → +0 XP
    //

    if ($xpGanho > 0) {

        $stmt = $pdo->prepare("
            UPDATE usuarios
            SET
                xp = xp + ?,
                pontuacao_total = pontuacao_total + ?
            WHERE id = ?
        ");

        /*
         * A pontuação total também recebe somente
         * a pontuação correspondente às questões que
         * geraram XP nesta tentativa.
         *
         * Como cada questão vale 20 XP, usamos o
         * número de novas questões acertadas.
         */
        $pontuacaoNova =
            $xpQuestoesNovas *
            $XP_POR_QUESTAO;


        $stmt->execute([

            $xpGanho,

            $pontuacaoNova,

            $usuarioId
        ]);
    }


    // =====================================================
    // VERIFICAR SE A PRÓXIMA FASE ESTÁ LIBERADA
    // =====================================================

    $faseLiberada =
        (
            $xpDaFase >=
            $XP_NECESSARIO_FASE
        );


    // =====================================================
    // BUSCAR PRÓXIMA FASE
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

        "xp_da_fase" =>
        $xpDaFase,

        "xp_necessario" =>
        $XP_NECESSARIO_FASE,

        "xp_questoes_novas" =>
        $xpQuestoesNovas,

        // -------------------------------------------------
        // PROGRESSO
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
