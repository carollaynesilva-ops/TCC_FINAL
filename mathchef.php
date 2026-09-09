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
| BUSCAR USUÁRIO
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
| BUSCAR FASES DO MATHCHEF
|--------------------------------------------------------------------------
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
| FUNÇÕES DE DIFICULDADE
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

/*
|--------------------------------------------------------------------------
| PROGRESSO
|--------------------------------------------------------------------------
*/

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
         ELEMENTOS DECORATIVOS DA COZINHA
    =========================================== -->

    <div class="kitchen-background" aria-hidden="true">

        <span class="food-icon food-1">🍳</span>
        <span class="food-icon food-2">🥕</span>
        <span class="food-icon food-3">🍅</span>
        <span class="food-icon food-4">🥄</span>
        <span class="food-icon food-5">🧀</span>
        <span class="food-icon food-6">🥣</span>
        <span class="food-icon food-7">🍞</span>
        <span class="food-icon food-8">🥛</span>
        <span class="food-icon food-9">🍓</span>
        <span class="food-icon food-10">🍋</span>
        <span class="food-icon food-11">🥄</span>
        <span class="food-icon food-12">🧁</span>
        <span class="food-icon food-13">🍕</span>
        <span class="food-icon food-14">🥚</span>

    </div>


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
         CONTEÚDO PRINCIPAL
    =========================================== -->

    <main class="mathchef-container">


        <!-- ======================================
             HERO
        ======================================= -->

        <section class="game-header">

            <div class="game-header-content">

                <div class="game-kicker">

                    <span class="kicker-line"></span>

                    <span>
                        DESAFIO CULINÁRIO
                    </span>

                </div>


                <h1>
                    Math<span>Chef</span>
                </h1>


                <p>
                    Entre na cozinha, prepare suas receitas
                    e resolva desafios matemáticos para
                    avançar de fase.
                </p>


                <div class="hero-details">

                    <div class="hero-detail">
                        <span class="detail-icon">👨‍🍳</span>
                        <span>Modo aventura</span>
                    </div>

                    <div class="hero-detail">
                        <span class="detail-icon">🧮</span>
                        <span>Matemática</span>
                    </div>

                    <div class="hero-detail">
                        <span class="detail-icon">🏆</span>
                        <span>Ganhe pontos</span>
                    </div>

                </div>

            </div>


            <div class="chef-badge">

                <div class="chef-badge-icon">
                    👨‍🍳
                </div>

                <span>
                    SUA SÉRIE
                </span>

                <strong>
                    <?= $serieUsuario ?>º ano
                </strong>

            </div>

        </section>


        <!-- ======================================
             PROGRESSO
        ======================================= -->

        <section class="progress-section">

            <div class="progress-top">

                <div class="progress-title">

                    <span class="progress-icon">
                        🍽️
                    </span>

                    <div>

                        <span>
                            Jornada culinária
                        </span>

                        <strong>
                            <?= $fasesConcluidas ?> de <?= $totalFases ?>
                            fases concluídas
                        </strong>

                    </div>

                </div>


                <strong class="progress-percentage">
                    <?= round($porcentagemProgresso) ?>%
                </strong>

            </div>


            <div class="progress-bar">

                <div
                    class="progress-fill"
                    style="width: <?= $porcentagemProgresso ?>%;"
                ></div>

            </div>

        </section>


        <!-- ======================================
             FASES
        ======================================= -->

        <section class="phases-section">

            <div class="section-heading">

                <div>

                    <span>
                        MENU DE FASES
                    </span>

                    <h2>
                        Sua cozinha
                    </h2>

                </div>

                <div class="cutlery-decoration">
                    ✦ ✦ ✦
                </div>

            </div>


            <div class="phases-list">

                <?php if (empty($fases)): ?>

                    <div class="empty-state">

                        <div class="empty-icon">
                            🍳
                        </div>

                        <h3>
                            A cozinha está vazia
                        </h3>

                        <p>
                            Nenhuma fase foi encontrada para sua série.
                        </p>

                    </div>

                <?php else: ?>


                    <?php foreach ($fases as $fase): ?>

                        <?php

                        $desbloqueada = $fase["desbloqueada"];
                        $concluida = $fase["concluida"];

                        if (!$desbloqueada) {

                            $classeCard = "locked";

                        } elseif ($concluida) {

                            $classeCard = "completed";

                        } else {

                            $classeCard = "available";

                        }

                        ?>


                        <article class="phase-card <?= $classeCard ?>">


                            <!-- Número -->

                            <div class="phase-number">

                                <?php if (!$desbloqueada): ?>

                                    <span>
                                        🔒
                                    </span>

                                <?php elseif ($concluida): ?>

                                    <span>
                                        ✓
                                    </span>

                                <?php else: ?>

                                    <span>
                                        <?= $fase["numero"] ?>
                                    </span>

                                <?php endif; ?>

                            </div>


                            <!-- Conteúdo -->

                            <div class="phase-content">


                                <div class="phase-top">

                                    <span class="phase-tag">

                                        FASE
                                        <?= $fase["numero"] ?>

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
                                            ✓ Receita concluída
                                        </span>


                                        <?php if ($fase["melhor_pontuacao"] > 0): ?>

                                            <strong>
                                                <?= (int) $fase["melhor_pontuacao"] ?>
                                                pts
                                            </strong>

                                        <?php endif; ?>

                                    </div>


                                <?php elseif ($desbloqueada): ?>

                                    <div class="phase-status">

                                        <span>
                                            🍴 Bancada liberada
                                        </span>


                                        <a
                                            href="jogar_mathchef.php?fase=<?= (int) $fase["id"] ?>"
                                            class="play-button"
                                        >

                                            Preparar

                                            <span>
                                                →
                                            </span>

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


        <!-- ======================================
             VOLTAR
        ======================================= -->

        <div class="back-area">

            <a
                href="inicio.php"
                class="back-link"
            >
                ← Voltar para o início
            </a>

        </div>


    </main>


    <script src="assets/js/tema.js"></script>

</body>

</html>