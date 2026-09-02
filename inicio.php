<?php
session_start();

if (!isset($_SESSION["usuario_id"])) {
    header("Location: login.php");
    exit;
}

require_once "config/config.php";

$usuarioId = $_SESSION["usuario_id"];

/* =========================
   USUÁRIO
========================= */

$stmt = $pdo->prepare("
    SELECT *
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

/* =========================
   JOGOS
========================= */

$stmt = $pdo->query("
    SELECT *
    FROM jogos
    WHERE ativo = TRUE
    ORDER BY id
");

$jogos = $stmt->fetchAll();

/* =========================
   PROGRESSO DOS JOGOS
========================= */

$progressoJogos = [];

foreach ($jogos as $jogo) {

    $stmt = $pdo->prepare("
        SELECT
            COUNT(f.id) AS total_fases,
            COUNT(
                CASE
                    WHEN p.concluida = TRUE THEN 1
                END
            ) AS fases_concluidas
        FROM fases f
        LEFT JOIN progresso_usuario p
            ON p.fase_id = f.id
            AND p.usuario_id = ?
        WHERE f.jogo_id = ?
    ");

    $stmt->execute([
        $usuarioId,
        $jogo["id"]
    ]);

    $progresso = $stmt->fetch();

    $progressoJogos[$jogo["id"]] = $progresso;
}

/* =========================
   PRÓXIMA FASE
========================= */

$stmt = $pdo->prepare("
    SELECT
        f.*,
        j.nome AS jogo_nome,
        j.imagem AS jogo_imagem
    FROM fases f
    INNER JOIN jogos j
        ON j.id = f.jogo_id
    LEFT JOIN progresso_usuario p
        ON p.fase_id = f.id
        AND p.usuario_id = ?
    WHERE
        j.ativo = TRUE
        AND (
            p.concluida IS NULL
            OR p.concluida = FALSE
        )
    ORDER BY
        j.id,
        f.numero
    LIMIT 1
");

$stmt->execute([$usuarioId]);

$proximaFase = $stmt->fetch();

/* =========================
   MEDALHAS
========================= */

$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM usuario_medalhas
    WHERE usuario_id = ?
");

$stmt->execute([$usuarioId]);

$totalMedalhas = (int)$stmt->fetchColumn();

/* =========================
   NÍVEL / XP
========================= */

$xp = (int)$usuario["xp"];
$nivel = (int)$usuario["nivel"];

$xpPorNivel = 500;

$xpAtualNivel = $xp % $xpPorNivel;

$porcentagemXP = ($xpAtualNivel / $xpPorNivel) * 100;

if ($porcentagemXP > 100) {
    $porcentagemXP = 100;
}

/* =========================
   HORÁRIO
========================= */

$hora = (int)date("H");

if ($hora < 12) {
    $saudacao = "Bom dia";
} elseif ($hora < 18) {
    $saudacao = "Boa tarde";
} else {
    $saudacao = "Boa noite";
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>MathRun | Início</title>

    <link rel="stylesheet" href="assets/css/inicio.css">

    <script src="assets/js/tema.js" defer></script>

</head>

<body>

    <header class="navbar">

        <a href="inicio.php" class="brand">
            Math<span>Run</span>
        </a>

        <nav class="nav-links">
            <a href="inicio.php" class="active">Início</a>
            <a href="ranking.php">Ranking</a>
            <a href="conquistas.php">Conquistas</a>
        </nav>

        <div class="nav-user">

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

            <div class="avatar">
                <a href="perfil.php">
                    <?= strtoupper(substr($usuario['nome'], 0, 1)) ?>
                </a>
            </div>

            <div class="user-info">
                <a href="perfil.php">
                    <strong><?= htmlspecialchars($usuario['nome']) ?></strong>
                    <span>LEVEL <?= $nivel ?></span>
                </a>
            </div>

            <a href="logout.php" class="logout">↪</a>

        </div>

    </header>


    <main class="home">

        <!-- =========================
         INTRO
    ========================== -->

        <section class="intro">

            <div>

                <span class="intro-label">
                    <?= $saudacao ?>,
                    <?= htmlspecialchars($usuario["nome"]) ?>
                </span>

                <h1>
                    Continue sua
                    <span>jornada matemática.</span>
                </h1>

                <p>
                    Resolva desafios, conquiste XP e desbloqueie novos mundos.
                </p>

            </div>


            <div class="level-badge">

                <div class="level-number">
                    <?= $nivel ?>
                </div>

                <div>

                    <small>
                        LEVEL ATUAL
                    </small>

                    <strong>
                        <?= $xp ?> XP
                    </strong>

                </div>

            </div>

        </section>


        <!-- =========================
         PRÓXIMA MISSÃO
    ========================== -->

        <?php if ($proximaFase): ?>

            <section class="mission">

                <div class="mission-content">

                    <span class="mission-label">
                        ⚡ PRÓXIMA MISSÃO
                    </span>

                    <span class="mission-game">
                        <?= strtoupper(htmlspecialchars($proximaFase["jogo_nome"])) ?>
                    </span>

                    <h2>
                        <?= htmlspecialchars($proximaFase["nome"]) ?>
                    </h2>

                    <p>
                        <?= htmlspecialchars($proximaFase["descricao"]) ?>
                    </p>


                    <div class="mission-tags">

                        <span>
                            FASE <?= $proximaFase["numero"] ?>
                        </span>

                        <span>
                            <?= strtoupper($proximaFase["nivel_dificuldade"]) ?>
                        </span>

                        <span>
                            +<?= $proximaFase["numero"] * 100 ?> XP
                        </span>

                    </div>


                    <a
                        href="jogar.php?fase=<?= $proximaFase["id"] ?>"
                        class="mission-button">
                        CONTINUAR MISSÃO
                        <span>→</span>
                    </a>

                </div>


                <div class="mission-art">

                    <div class="math-symbol symbol-one">
                        π
                    </div>

                    <div class="math-symbol symbol-two">
                        +
                    </div>

                    <div class="math-symbol symbol-three">
                        ×
                    </div>

                    <div class="orbit orbit-one"></div>
                    <div class="orbit orbit-two"></div>
                    <div class="orbit orbit-three"></div>

                    <div class="mission-object">

                        <?php if ($proximaFase["jogo_nome"] === "MathChef"): ?>

                            🍳

                        <?php else: ?>

                            🚀

                        <?php endif; ?>

                    </div>

                    <div class="xp-floating">
                        +100 XP
                    </div>

                </div>

            </section>

        <?php endif; ?>


        <!-- =========================
         MUNDOS
    ========================== -->

        <section class="world-section">

            <div class="section-heading">

                <div>

                    <span>
                        EXPLORE
                    </span>

                    <h2>
                        Escolha seu mundo
                    </h2>

                </div>

                <p>
                    Cada missão é uma nova forma de aprender.
                </p>

            </div>


            <div class="world-grid">

                <?php foreach ($jogos as $jogo): ?>

                    <?php

                    $totalFases =
                        (int)$progressoJogos[$jogo["id"]]["total_fases"];

                    $fasesConcluidas =
                        (int)$progressoJogos[$jogo["id"]]["fases_concluidas"];

                    $progresso =
                        $totalFases > 0
                        ? ($fasesConcluidas / $totalFases) * 100
                        : 0;

                    $isChef =
                        $jogo["nome"] === "MathChef";

                    ?>

                    <a
                        href="jogos.php?id=<?= $jogo["id"] ?>"
                        class="world-card <?= $isChef ? 'chef' : 'space' ?>">

                        <div class="world-background">

                            <?php if ($isChef): ?>

                                <span>⅓</span>
                                <span>¼</span>
                                <span>½</span>
                                <span>⅔</span>

                            <?php else: ?>

                                <span>✦</span>
                                <span>+</span>
                                <span>π</span>
                                <span>×</span>

                            <?php endif; ?>

                        </div>


                        <div class="world-icon">

                            <?= $isChef ? "🍳" : "🚀" ?>

                        </div>


                        <div class="world-info">

                            <span class="world-kicker">
                                <?= $isChef ? "COZINHA MATEMÁTICA" : "EXPEDIÇÃO ESPACIAL" ?>
                            </span>

                            <h3>
                                <?= htmlspecialchars($jogo["nome"]) ?>
                            </h3>

                            <p>
                                <?= htmlspecialchars($jogo["descricao"]) ?>
                            </p>

                        </div>


                        <div class="world-progress">

                            <div>

                                <span>
                                    PROGRESSO
                                </span>

                                <strong>
                                    <?= $fasesConcluidas ?>/<?= $totalFases ?>
                                </strong>

                            </div>

                            <div class="progress-track">

                                <div
                                    class="progress-fill"
                                    style="width: <?= $progresso ?>%"></div>

                            </div>

                        </div>


                        <span class="world-arrow">
                            →
                        </span>

                    </a>

                <?php endforeach; ?>

            </div>

        </section>


        <!-- =========================
         STATUS
    ========================== -->

        <section class="status-grid">


            <article class="status-card level-card">

                <div class="status-icon">
                    ⚡
                </div>

                <div class="status-content">

                    <span>
                        SEU PROGRESSO
                    </span>

                    <h3>
                        Level <?= $nivel ?>
                    </h3>

                    <div class="xp-row">

                        <strong>
                            <?= $xpAtualNivel ?>
                        </strong>

                        <span>
                            / <?= $xpPorNivel ?> XP
                        </span>

                    </div>

                    <div class="xp-track">

                        <div
                            style="width: <?= $porcentagemXP ?>%"></div>

                    </div>

                </div>

            </article>


            <article class="status-card achievement-card">

                <div class="status-icon">
                    🏆
                </div>

                <div class="status-content">

                    <span>
                        CONQUISTAS
                    </span>

                    <h3>
                        <?= $totalMedalhas ?>
                    </h3>

                    <p>
                        medalhas desbloqueadas
                    </p>

                </div>

                <a href="conquistas.php">
                    →
                </a>

            </article>


        </section>

    </main>

</body>

</html>