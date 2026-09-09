<?php

session_start();

require_once "config/config.php";

if (!isset($_SESSION["usuario_id"])) {
    header("Location: login.php");
    exit;
}

$usuarioId = $_SESSION["usuario_id"];

/*
|--------------------------------------------------------------------------
| BUSCAR DADOS DO USUÁRIO
|--------------------------------------------------------------------------
*/

$sqlUsuario = "
    SELECT
        id,
        nome,
        serie
    FROM usuarios
    WHERE id = ?
";

$stmtUsuario = $pdo->prepare($sqlUsuario);
$stmtUsuario->execute([$usuarioId]);

$usuario = $stmtUsuario->fetch();

if (!$usuario) {
    session_destroy();

    header("Location: login.php");
    exit;
}

$nomeUsuario = $usuario["nome"];
$serieUsuario = (int) $usuario["serie"];

/*
|--------------------------------------------------------------------------
| BUSCAR AS FASES DO MATHCHEF
|--------------------------------------------------------------------------
|
| jogo_id = 1 → MathChef
| A série vem do cadastro do usuário.
|
*/

$sqlFases = "
    SELECT
        f.id,
        f.nome,
        f.descricao,
        f.nivel_dificuldade,
        f.numero,
        COALESCE(p.concluida, 0) AS concluida,
        COALESCE(p.melhor_pontuacao, 0) AS melhor_pontuacao
    FROM fases f

    LEFT JOIN progresso_usuario p
        ON p.fase_id = f.id
        AND p.usuario_id = ?

    WHERE f.jogo_id = 1
      AND f.serie = ?

    ORDER BY f.numero ASC
";

$stmtFases = $pdo->prepare($sqlFases);
$stmtFases->execute([
    $usuarioId,
    $serieUsuario
]);

$fases = $stmtFases->fetchAll();

/*
|--------------------------------------------------------------------------
| DEFINIR FASES DESBLOQUEADAS
|--------------------------------------------------------------------------
|
| A primeira fase sempre começa desbloqueada.
| As próximas são liberadas quando a anterior é concluída.
|
*/

$proximaDesbloqueada = true;

foreach ($fases as &$fase) {

    $fase["concluida"] = (bool) $fase["concluida"];

    if ($proximaDesbloqueada) {
        $fase["desbloqueada"] = true;
    } else {
        $fase["desbloqueada"] = false;
    }

    if ($fase["concluida"]) {
        $proximaDesbloqueada = true;
    } else {
        $proximaDesbloqueada = false;
    }
}

unset($fase);

/*
|--------------------------------------------------------------------------
| TRADUZIR DIFICULDADE
|--------------------------------------------------------------------------
*/

function nomeDificuldade($dificuldade)
{
    switch ($dificuldade) {

        case "facil":
            return "Fácil";

        case "medio":
            return "Médio";

        case "dificil":
            return "Difícil";

        default:
            return "Nível";
    }
}

function classeDificuldade($dificuldade)
{
    switch ($dificuldade) {

        case "facil":
            return "difficulty-easy";

        case "medio":
            return "difficulty-medium";

        case "dificil":
            return "difficulty-hard";

        default:
            return "";
    }
}

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>MathChef | MathRun</title>

    <link
        rel="stylesheet"
        href="assets/css/mathchef.css"
    >

</head>

<body>

    <!-- ==========================================
         NAVBAR
    =========================================== -->

    <header class="topbar">

        <div class="topbar-content">

            <a href="inicio.php" class="brand">
                MathRun
            </a>

            <nav class="main-nav">

                <a href="inicio.php">
                    Início
                </a>

                <a href="conquistas.php">
                    Conquistas
                </a>

                <a href="ranking.php">
                    Ranking
                </a>

                <a href="perfil.php">
                    Perfil
                </a>

            </nav>

            <div class="theme-switcher">

                <button
                    type="button"
                    data-theme-option="light"
                    title="Tema claro"
                >
                    ☀
                </button>

                <button
                    type="button"
                    data-theme-option="dark"
                    title="Tema escuro"
                >
                    ☾
                </button>

                <button
                    type="button"
                    data-theme-option="pink"
                    title="Tema rosa"
                >
                    ♡
                </button>

            </div>

        </div>

    </header>


    <!-- ==========================================
         CONTEÚDO
    =========================================== -->

    <main class="mathchef-container">

        <!-- Cabeçalho -->

        <section class="game-header">

            <div class="game-header-text">

                <span class="game-label">
                    JOGO DE MATEMÁTICA
                </span>

                <h1>
                    MathChef
                </h1>

                <p>
                    Prepare receitas, resolva desafios
                    e domine a matemática na cozinha.
                </p>

            </div>

            <div class="series-badge">

                <span>
                    Sua série
                </span>

                <strong>
                    <?= $serieUsuario ?>º ano
                </strong>

            </div>

        </section>


        <!-- Progresso geral -->

        <?php

        $totalFases = count($fases);
        $fasesConcluidas = 0;

        foreach ($fases as $fase) {

            if ($fase["concluida"]) {
                $fasesConcluidas++;
            }

        }

        $porcentagemProgresso = $totalFases > 0
            ? ($fasesConcluidas / $totalFases) * 100
            : 0;

        ?>

        <section class="progress-section">

            <div class="progress-info">

                <div>

                    <span>
                        Progresso
                    </span>

                    <strong>
                        <?= $fasesConcluidas ?> de <?= $totalFases ?> fases
                    </strong>

                </div>

                <span>
                    <?= round($porcentagemProgresso) ?>%
                </span>

            </div>

            <div class="progress-bar">

                <div
                    class="progress-fill"
                    style="width: <?= $porcentagemProgresso ?>%;"
                ></div>

            </div>

        </section>


        <!-- ==========================================
             FASES
        =========================================== -->

        <section class="phases-section">

            <div class="section-title">

                <span>
                    SUA JORNADA
                </span>

                <h2>
                    Fases do MathChef
                </h2>

            </div>


            <div class="phases-list">

                <?php if (empty($fases)): ?>

                    <div class="empty-state">

                        <h3>
                            Nenhuma fase encontrada
                        </h3>

                        <p>
                            Não existem fases cadastradas para esta série.
                        </p>

                    </div>

                <?php else: ?>

                    <?php foreach ($fases as $fase): ?>

                        <?php
                        $desbloqueada = $fase["desbloqueada"];
                        $concluida = $fase["concluida"];

                        $classeCard = "";

                        if (!$desbloqueada) {
                            $classeCard = "locked";
                        } elseif ($concluida) {
                            $classeCard = "completed";
                        } else {
                            $classeCard = "available";
                        }
                        ?>

                        <article class="phase-card <?= $classeCard ?>">

                            <div class="phase-number">

                                <?php if (!$desbloqueada): ?>

                                    <span class="lock-icon">
                                        🔒
                                    </span>

                                <?php elseif ($concluida): ?>

                                    <span class="check-icon">
                                        ✓
                                    </span>

                                <?php else: ?>

                                    <span>
                                        <?= $fase["numero"] ?>
                                    </span>

                                <?php endif; ?>

                            </div>


                            <div class="phase-content">

                                <div class="phase-top">

                                    <span class="phase-tag">
                                        FASE <?= $fase["numero"] ?>
                                    </span>

                                    <span
                                        class="difficulty <?= classeDificuldade($fase["nivel_dificuldade"]) ?>"
                                    >
                                        <?= nomeDificuldade($fase["nivel_dificuldade"]) ?>
                                    </span>

                                </div>


                                <h3>
                                    <?= htmlspecialchars($fase["nome"]) ?>
                                </h3>


                                <p>
                                    <?= htmlspecialchars($fase["descricao"]) ?>
                                </p>


                                <?php if ($concluida): ?>

                                    <div class="phase-status completed-status">

                                        <span>
                                            ✓ Fase concluída
                                        </span>

                                        <?php if ($fase["melhor_pontuacao"] > 0): ?>

                                            <strong>
                                                <?= (int) $fase["melhor_pontuacao"] ?> pts
                                            </strong>

                                        <?php endif; ?>

                                    </div>


                                <?php elseif ($desbloqueada): ?>

                                    <div class="phase-status">

                                        <span>
                                            Fase disponível
                                        </span>

                                        <a
                                            href="jogar_mathchef.php?fase=<?= (int) $fase["id"] ?>"
                                            class="play-button"
                                        >
                                            Jogar
                                            <span>→</span>
                                        </a>

                                    </div>


                                <?php else: ?>

                                    <div class="phase-status locked-status">

                                        <span>
                                            🔒 Complete a fase anterior
                                        </span>

                                    </div>

                                <?php endif; ?>

                            </div>

                        </article>

                    <?php endforeach; ?>

                <?php endif; ?>

            </div>

        </section>


        <!-- Voltar -->

        <div class="back-area">

            <a href="inicio.php" class="back-link">
                ← Voltar para o início
            </a>

        </div>

    </main>


    <!-- ==========================================
         TEMA
    =========================================== -->

    <script src="assets/js/tema.js"></script>

</body>

</html>