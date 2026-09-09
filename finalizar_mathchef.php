<?php

session_start();

header("Content-Type: application/json; charset=utf-8");

require_once "config/config.php";

/*
|--------------------------------------------------------------------------
| Verificar login
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION["usuario_id"])) {
    echo json_encode([
        "sucesso" => false,
        "mensagem" => "Usuário não autenticado."
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| Dados recebidos
|--------------------------------------------------------------------------
*/

$usuarioId = (int) $_SESSION["usuario_id"];

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
| Buscar fase
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        id,
        jogo_id,
        serie,
        numero
    FROM fases
    WHERE id = ?
      AND jogo_id = 1
");

$stmt->execute([$faseId]);

$fase = $stmt->fetch();

if (!$fase) {

    echo json_encode([
        "sucesso" => false,
        "mensagem" => "Fase não encontrada."
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| Verificar se a fase pertence à série do usuário
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT serie
    FROM usuarios
    WHERE id = ?
");

$stmt->execute([$usuarioId]);

$usuario = $stmt->fetch();

if (!$usuario) {

    echo json_encode([
        "sucesso" => false,
        "mensagem" => "Usuário não encontrado."
    ]);

    exit;
}

if ((int) $usuario["serie"] !== (int) $fase["serie"]) {

    echo json_encode([
        "sucesso" => false,
        "mensagem" => "Você não pode realizar esta fase."
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| Garantir valores coerentes
|--------------------------------------------------------------------------
*/

if ($acertos + $erros <= 0) {

    echo json_encode([
        "sucesso" => false,
        "mensagem" => "Nenhuma questão foi respondida."
    ]);

    exit;
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
    | Buscar melhor pontuação anterior
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->prepare("
        SELECT
            id,
            concluida,
            melhor_pontuacao,
            tentativas
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


    /*
    |--------------------------------------------------------------------------
    | Criar histórico da partida
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->prepare("
        INSERT INTO historico_partidas
        (
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
        $fase["jogo_id"],
        $faseId,
        $pontuacao,
        $acertos,
        $erros,
        $dicasUsadas
    ]);

    $partidaId = $pdo->lastInsertId();


    /*
    |--------------------------------------------------------------------------
    | Melhor pontuação
    |--------------------------------------------------------------------------
    */

    if ($progresso) {

        $melhorPontuacaoAnterior =
            (int) $progresso["melhor_pontuacao"];

    } else {

        $melhorPontuacaoAnterior = 0;
    }


    $melhorPontuacao =
        max(
            $melhorPontuacaoAnterior,
            $pontuacao
        );


    /*
    |--------------------------------------------------------------------------
    | Fase concluída
    |--------------------------------------------------------------------------
    |
    | Consideramos concluída quando o aluno termina
    | todas as questões.
    |
    */

    $concluida = true;


    /*
    |--------------------------------------------------------------------------
    | Salvar / atualizar progresso
    |--------------------------------------------------------------------------
    */

    if ($progresso) {

        $stmt = $pdo->prepare("
            UPDATE progresso_usuario

            SET
                concluida = ?,
                pontuacao = ?,
                tentativas = tentativas + 1,
                melhor_pontuacao = ?,
                data_conclusao = NOW()

            WHERE usuario_id = ?
              AND fase_id = ?
        ");

        $stmt->execute([
            $concluida ? 1 : 0,
            $pontuacao,
            $melhorPontuacao,
            $usuarioId,
            $faseId
        ]);

    } else {

        $stmt = $pdo->prepare("
            INSERT INTO progresso_usuario
            (
                usuario_id,
                fase_id,
                concluida,
                pontuacao,
                tentativas,
                melhor_pontuacao,
                data_conclusao
            )
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");

        $stmt->execute([
            $usuarioId,
            $faseId,
            1,
            $pontuacao,
            1,
            $melhorPontuacao
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Atualizar pontuação total
    |--------------------------------------------------------------------------
    |
    | Só adicionamos à pontuação total a diferença entre
    | a nova pontuação e a melhor pontuação anterior.
    |
    | Isso evita que o aluno jogue a mesma fase 500 vezes
    | e fique milionário em XP por acidente.
    |
    */

    $pontosNovos =
        max(
            0,
            $pontuacao - $melhorPontuacaoAnterior
        );


    /*
    |--------------------------------------------------------------------------
    | Atualizar XP e pontuação total
    |--------------------------------------------------------------------------
    */

    if ($pontosNovos > 0) {

        $stmt = $pdo->prepare("
            UPDATE usuarios

            SET
                xp = xp + ?,
                pontuacao_total = pontuacao_total + ?

            WHERE id = ?
        ");

        $stmt->execute([
            $pontosNovos,
            $pontosNovos,
            $usuarioId
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Verificar próxima fase
    |--------------------------------------------------------------------------
    */

    $numeroAtual =
        (int) $fase["numero"];

    $proximoNumero =
        $numeroAtual + 1;


    $stmt = $pdo->prepare("
        SELECT id
        FROM fases
        WHERE jogo_id = 1
          AND serie = ?
          AND numero = ?
        LIMIT 1
    ");

    $stmt->execute([
        $fase["serie"],
        $proximoNumero
    ]);

    $proximaFase = $stmt->fetch();


    /*
    |--------------------------------------------------------------------------
    | Commit
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
        "mensagem" => "Fase finalizada com sucesso.",
        "partida_id" => (int) $partidaId,
        "pontuacao" => $pontuacao,
        "acertos" => $acertos,
        "erros" => $erros,
        "melhor_pontuacao" => $melhorPontuacao,
        "pontos_novos" => $pontosNovos,
        "proxima_fase" => $proximaFase
            ? (int) $proximaFase["id"]
            : null
    ]);

} catch (Exception $e) {

    /*
    |--------------------------------------------------------------------------
    | Desfazer alterações caso aconteça algum erro
    |--------------------------------------------------------------------------
    */

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    echo json_encode([
        "sucesso" => false,
        "mensagem" => "Não foi possível salvar a partida."
    ]);

}