<?php

session_start();

require_once __DIR__ . "/config/config.php";

header("Content-Type: application/json; charset=UTF-8");

/*
|--------------------------------------------------------------------------
| Verifica se o usuário está logado
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
| Recebe a questão
|--------------------------------------------------------------------------
*/

$questaoId = isset($_POST["questao_id"])
    ? (int) $_POST["questao_id"]
    : 0;

$faseId = isset($_POST["fase_id"])
    ? (int) $_POST["fase_id"]
    : 0;

if ($questaoId <= 0 || $faseId <= 0) {
    http_response_code(400);

    echo json_encode([
        "sucesso" => false,
        "mensagem" => "Questão ou fase inválida."
    ]);

    exit;
}

/*
|--------------------------------------------------------------------------
| Busca o usuário
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        id,
        nome,
        serie,
        xp
    FROM usuarios
    WHERE id = ?
    LIMIT 1
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
$xpAtual = (int) $usuario["xp"];

/*
|--------------------------------------------------------------------------
| Verifica se a fase pertence ao MathChef e à série do usuário
|--------------------------------------------------------------------------
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
        "mensagem" => "Você não pode usar uma dica nesta fase."
    ]);

    exit;
}

/*
|--------------------------------------------------------------------------
| Verifica se a questão pertence à fase
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        id,
        fase_id
    FROM questoes
    WHERE id = ?
      AND fase_id = ?
    LIMIT 1
");

$stmt->execute([
    $questaoId,
    $faseId
]);

$questao = $stmt->fetch();

if (!$questao) {
    http_response_code(403);

    echo json_encode([
        "sucesso" => false,
        "mensagem" => "Esta questão não pertence à fase informada."
    ]);

    exit;
}

/*
|--------------------------------------------------------------------------
| Busca a dica da questão
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        id,
        questao_id,
        ordem,
        texto,
        custo_xp
    FROM dicas
    WHERE questao_id = ?
    ORDER BY ordem ASC
    LIMIT 1
");

$stmt->execute([$questaoId]);

$dica = $stmt->fetch();

if (!$dica) {
    http_response_code(404);

    echo json_encode([
        "sucesso" => false,
        "mensagem" => "Esta questão ainda não possui uma dica cadastrada."
    ]);

    exit;
}

$dicaId = (int) $dica["id"];
$custoDica = (int) $dica["custo_xp"];

/*
|--------------------------------------------------------------------------
| Garante que a dica realmente custa 50 XP
|--------------------------------------------------------------------------
|
| O banco é a fonte oficial.
| Se alguma dica estiver cadastrada com outro valor,
| ela será considerada inválida.
|
*/

if ($custoDica !== 50) {
    http_response_code(500);

    echo json_encode([
        "sucesso" => false,
        "mensagem" => "O custo desta dica está configurado incorretamente."
    ]);

    exit;
}

/*
|--------------------------------------------------------------------------
| Verifica se o usuário possui XP suficiente
|--------------------------------------------------------------------------
*/

if ($xpAtual < $custoDica) {
    http_response_code(400);

    echo json_encode([
        "sucesso" => false,
        "mensagem" => "Você não possui XP suficiente para usar esta dica.",
        "xp_atual" => $xpAtual,
        "custo_dica" => $custoDica
    ]);

    exit;
}

/*
|--------------------------------------------------------------------------
| Desconta os 50 XP
|--------------------------------------------------------------------------
|
| Fazemos a verificação novamente dentro do UPDATE.
| Isso evita que duas requisições simultâneas consigam
| gastar o mesmo XP de forma indevida.
|
*/

try {

    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        UPDATE usuarios
        SET xp = xp - ?
        WHERE id = ?
          AND xp >= ?
    ");

    $stmt->execute([
        $custoDica,
        $usuarioId,
        $custoDica
    ]);

    /*
    |--------------------------------------------------------------------------
    | Verifica se o desconto realmente aconteceu
    |--------------------------------------------------------------------------
    */

    if ($stmt->rowCount() !== 1) {

        $pdo->rollBack();

        http_response_code(400);

        echo json_encode([
            "sucesso" => false,
            "mensagem" => "Não foi possível descontar o XP. Tente novamente."
        ]);

        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | Busca o novo XP do usuário
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->prepare("
        SELECT xp
        FROM usuarios
        WHERE id = ?
        LIMIT 1
    ");

    $stmt->execute([$usuarioId]);

    $novoXp = (int) $stmt->fetchColumn();

    $pdo->commit();

    /*
    |--------------------------------------------------------------------------
    | Retorna a dica
    |--------------------------------------------------------------------------
    */

    echo json_encode([
        "sucesso" => true,
        "mensagem" => "Dica liberada!",
        "dica" => [
            "id" => $dicaId,
            "questao_id" => $questaoId,
            "texto" => $dica["texto"]
        ],
        "custo_xp" => $custoDica,
        "xp_anterior" => $xpAtual,
        "xp_atual" => $novoXp
    ]);
} catch (PDOException $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    http_response_code(500);

    echo json_encode([
        "sucesso" => false,
        "mensagem" => "Não foi possível liberar a dica."
    ]);
}
