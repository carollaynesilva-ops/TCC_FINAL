<?php

session_start();

require_once "config/config.php";

if (!isset($_SESSION["usuario_id"])) {
    header("Location: login.php");
    exit;
}

$usuarioId = (int) $_SESSION["usuario_id"];
$faseId = isset($_GET["fase"]) ? (int) $_GET["fase"] : 0;

if ($faseId <= 0) {
    header("Location: mathchef.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| Buscar usuário
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT id, nome, serie, turma, xp, pontuacao_total
    FROM usuarios
    WHERE id = ?
");
$stmt->execute([$usuarioId]);

$usuario = $stmt->fetch();

if (!$usuario) {
    session_destroy();
    header("Location: login.php");
    exit;
}

$serie = (int) $usuario["serie"];

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
        nome,
        descricao,
        nivel_dificuldade,
        numero
    FROM fases
    WHERE id = ?
      AND jogo_id = 1
      AND serie = ?
");
$stmt->execute([$faseId, $serie]);

$fase = $stmt->fetch();

if (!$fase) {
    header("Location: mathchef.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| Verificar se a fase está liberada
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
");
$stmt->execute([$usuarioId, $faseId]);

$progresso = $stmt->fetch();

/*
|--------------------------------------------------------------------------
| Fases anteriores
|--------------------------------------------------------------------------
*/

if ((int) $fase["numero"] > 1) {

    $numeroAnterior = (int) $fase["numero"] - 1;

    $stmt = $pdo->prepare("
        SELECT id
        FROM fases
        WHERE jogo_id = 1
          AND serie = ?
          AND numero = ?
        LIMIT 1
    ");

    $stmt->execute([$serie, $numeroAnterior]);

    $faseAnterior = $stmt->fetch();

    if ($faseAnterior) {

        $stmt = $pdo->prepare("
            SELECT concluida
            FROM progresso_usuario
            WHERE usuario_id = ?
              AND fase_id = ?
        ");

        $stmt->execute([
            $usuarioId,
            $faseAnterior["id"]
        ]);

        $progressoAnterior = $stmt->fetch();

        if (!$progressoAnterior || !$progressoAnterior["concluida"]) {
            header("Location: mathchef.php");
            exit;
        }
    }
}

/*
|--------------------------------------------------------------------------
| Buscar questões
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        id,
        materia,
        pergunta,
        resposta_correta,
        explicacao,
        pontuacao
    FROM questoes
    WHERE fase_id = ?
    ORDER BY id
");

$stmt->execute([$faseId]);

$questoes = $stmt->fetchAll();

if (!$questoes) {
    $semQuestoes = true;
} else {
    $semQuestoes = false;
}

/*
|--------------------------------------------------------------------------
| Buscar alternativas e dicas
|--------------------------------------------------------------------------
*/

$questoesCompletas = [];

foreach ($questoes as $questao) {

    $stmt = $pdo->prepare("
        SELECT
            id,
            texto,
            correta
        FROM alternativas
        WHERE questao_id = ?
        ORDER BY id
    ");

    $stmt->execute([$questao["id"]]);

    $alternativas = $stmt->fetchAll();

    $stmt = $pdo->prepare("
        SELECT
            id,
            ordem,
            texto,
            custo_xp
        FROM dicas
        WHERE questao_id = ?
        ORDER BY ordem
    ");

    $stmt->execute([$questao["id"]]);

    $dicas = $stmt->fetchAll();

    $questoesCompletas[] = [
        "id" => (int) $questao["id"],
        "materia" => $questao["materia"],
        "pergunta" => $questao["pergunta"],
        "resposta_correta" => $questao["resposta_correta"],
        "explicacao" => $questao["explicacao"],
        "pontuacao" => (int) $questao["pontuacao"],
        "alternativas" => $alternativas,
        "dicas" => $dicas
    ];
}

/*
|--------------------------------------------------------------------------
| Dificuldade
|--------------------------------------------------------------------------
*/

$dificuldades = [
    "facil" => "Fácil",
    "medio" => "Médio",
    "dificil" => "Difícil"
];

$dificuldade = $dificuldades[$fase["nivel_dificuldade"]] ?? "Fácil";

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= htmlspecialchars($fase["nome"]) ?> | MathChef</title>

    <link rel="stylesheet" href="assets/css/jogar_mathchef.css">
</head>

<body>

    <!-- Fundo -->
    <div class="food-background" aria-hidden="true">
        <span class="food-icon food-1">🍎</span>
        <span class="food-icon food-2">🥕</span>
        <span class="food-icon food-3">🍋</span>
        <span class="food-icon food-4">🍳</span>
        <span class="food-icon food-5">🥛</span>
        <span class="food-icon food-6">🧁</span>
        <span class="food-icon food-7">🍪</span>
        <span class="food-icon food-8">🥣</span>
        <span class="food-icon food-9">🍓</span>
        <span class="food-icon food-10">🥄</span>
        <span class="food-icon food-11">🍰</span>
        <span class="food-icon food-12">🧂</span>
    </div>

    <!-- Barra superior -->
    <header class="topbar">

        <div class="topbar-content">

            <a href="mathchef.php" class="brand">
                <span class="brand-icon">🍳</span>

                <div>
                    <strong>MathChef</strong>
                    <small>Matemática na cozinha</small>
                </div>
            </a>

            <div class="game-user">

                <div class="user-score">
                    <span>⭐</span>
                    <strong id="scoreTop">0</strong>
                    <small>pontos</small>
                </div>

                <div class="theme-switcher">

                    <button
                        type="button"
                        data-theme-option="light"
                        title="Tema claro">
                        ☀
                    </button>

                    <button
                        type="button"
                        data-theme-option="dark"
                        title="Tema escuro">
                        ☾
                    </button>

                    <button
                        type="button"
                        data-theme-option="pink"
                        title="Tema rosa">
                        ♡
                    </button>

                </div>

            </div>

        </div>

    </header>


    <main class="game-container">

        <?php if ($semQuestoes): ?>

            <section class="empty-game">

                <div class="empty-icon">🍳</div>

                <h1>Essa fase ainda está sendo preparada!</h1>

                <p>
                    As questões dessa fase ainda não foram cadastradas.
                </p>

                <a href="mathchef.php" class="back-button">
                    ← Voltar para as fases
                </a>

            </section>

        <?php else: ?>

            <!-- Cabeçalho da fase -->

            <section class="game-header">

                <a href="mathchef.php" class="back-link">
                    ← Voltar para fases
                </a>

                <div class="phase-info">

                    <div class="phase-number">
                        FASE <?= (int) $fase["numero"] ?>
                    </div>

                    <h1>
                        <?= htmlspecialchars($fase["nome"]) ?>
                    </h1>

                    <p>
                        <?= htmlspecialchars($fase["descricao"]) ?>
                    </p>

                </div>

                <div class="phase-badges">

                    <span class="badge difficulty-badge">
                        <?= htmlspecialchars($dificuldade) ?>
                    </span>

                    <span class="badge">
                        <?= count($questoesCompletas) ?> questões
                    </span>

                </div>

            </section>


            <!-- Barra de progresso -->

            <section class="progress-area">

                <div class="progress-info">

                    <span>
                        Questão
                        <strong id="questionNumber">1</strong>
                        de
                        <strong id="totalQuestions">
                            <?= count($questoesCompletas) ?>
                        </strong>
                    </span>

                    <span id="progressPercent">0%</span>

                </div>

                <div class="progress-bar">
                    <div
                        class="progress-fill"
                        id="progressFill">
                    </div>
                </div>

            </section>


            <!-- Área principal -->

            <section class="game-layout">

                <!-- Pergunta -->

                <div class="question-card">

                    <div class="question-top">

                        <div class="question-tag">
                            <span>🧠</span>
                            Desafio matemático
                        </div>

                        <div class="question-points">
                            +<span id="questionPoints">0</span> XP
                        </div>

                    </div>


                    <div class="subject-label" id="questionSubject">
                        Matemática
                    </div>


                    <h2 id="questionText"></h2>


                    <!-- Alternativas -->

                    <div
                        class="alternatives"
                        id="alternativesContainer">
                    </div>


                    <!-- Feedback -->

                    <div
                        class="feedback"
                        id="feedback"
                        hidden>

                        <div class="feedback-icon" id="feedbackIcon">
                            ✓
                        </div>

                        <div class="feedback-content">

                            <h3 id="feedbackTitle">
                                Muito bem!
                            </h3>

                            <p id="feedbackText"></p>

                            <div
                                class="explanation"
                                id="explanation">
                            </div>

                        </div>

                    </div>


                    <!-- Botão próxima -->

                    <button
                        type="button"
                        class="next-button"
                        id="nextButton"
                        hidden>

                        <span id="nextButtonText">
                            Próxima questão
                        </span>

                        <span>→</span>

                    </button>

                </div>


                <!-- Painel lateral -->

                <aside class="side-panel">

                    <!-- Pontuação -->

                    <div class="score-card">

                        <div class="score-icon">
                            ⭐
                        </div>

                        <div>

                            <span>Pontuação</span>

                            <strong id="score">
                                0
                            </strong>

                        </div>

                    </div>


                    <!-- Dicas -->

                    <div class="hint-card">

                        <div class="hint-header">

                            <div class="hint-icon">
                                💡
                            </div>

                            <div>
                                <h3>Dicas</h3>
                                <p>Está difícil?</p>
                            </div>

                        </div>

                        <div id="hintsContainer">
                        </div>

                    </div>


                    <!-- Informações -->

                    <div class="recipe-card">

                        <div class="recipe-icon">
                            👨‍🍳
                        </div>

                        <h3>Modo Chef</h3>

                        <p>
                            Leia com calma, pense na estratégia
                            e escolha a alternativa correta.
                        </p>

                    </div>

                </aside>

            </section>

        <?php endif; ?>

    </main>


    <!-- Dados das questões para o JavaScript -->

    <?php if (!$semQuestoes): ?>

        <script>
            const questoes = <?= json_encode(
                $questoesCompletas,
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            ) ?>;

            const usuarioId = <?= $usuarioId ?>;
            const faseId = <?= $faseId ?>;
            const serie = <?= $serie ?>;
        </script>

    <?php endif; ?>


    <script src="assets/js/jogar_mathchef.js"></script>

</body>

</html>