<?php

session_start();

require_once __DIR__ . '/config/config.php';


// =========================================================
// VERIFICAR LOGIN
// =========================================================

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

$usuarioId = $_SESSION['usuario_id'];


// =========================================================
// BUSCAR USUÁRIO
// =========================================================

$stmt = $pdo->prepare("
    SELECT nome, nivel
    FROM usuarios
    WHERE id = ?
");

$stmt->execute([$usuarioId]);

$usuario = $stmt->fetch();

if (!$usuario) {

    session_unset();
    session_destroy();

    header("Location: login.php");
    exit;
}

$nome = $usuario['nome'];
$nivel = (int) $usuario['nivel'];


// =========================================================
// BUSCAR TODAS AS MEDALHAS
// =========================================================

$stmt = $pdo->prepare("
    SELECT
        m.id,
        m.nome,
        m.descricao,
        m.imagem,
        m.criterio,
        um.data_conquista

    FROM medalhas m

    LEFT JOIN usuario_medalhas um
        ON um.medalha_id = m.id
        AND um.usuario_id = ?

    ORDER BY
        CASE
            WHEN um.id IS NOT NULL THEN 0
            ELSE 1
        END,
        m.id ASC
");

$stmt->execute([$usuarioId]);

$medalhas = $stmt->fetchAll();


// =========================================================
// CONTADORES
// =========================================================

$totalMedalhas = count($medalhas);

$desbloqueadas = 0;

foreach ($medalhas as $medalha) {

    if ($medalha['data_conquista'] !== null) {
        $desbloqueadas++;
    }
}

$bloqueadas = $totalMedalhas - $desbloqueadas;


// =========================================================
// PORCENTAGEM
// =========================================================

$porcentagem = 0;

if ($totalMedalhas > 0) {

    $porcentagem =
        ($desbloqueadas / $totalMedalhas) * 100;
}


// =========================================================
// INICIAL
// =========================================================

$inicial = strtoupper(
    mb_substr($nome, 0, 1, 'UTF-8')
);

?>

<!DOCTYPE html>

<html lang="pt-BR">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Conquistas | MathRun</title>

    <link
        rel="stylesheet"
        href="assets/css/conquistas.css"
    >

</head>


<body>


<!-- ======================================================
     NAVBAR
====================================================== -->

<header class="navbar">

    <a href="inicio.php" class="brand">
        Math<span>Run</span>
    </a>


    <nav class="nav-links">

        <a href="inicio.php">
            Início
        </a>

        <a href="ranking.php">
            Ranking
        </a>

        <a
            href="conquistas.php"
            class="active"
        >
            Conquistas
        </a>

    </nav>


    <div class="nav-user">


        <!-- TEMA -->

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


        <!-- AVATAR -->

        <div class="avatar">
            <?= htmlspecialchars($inicial) ?>
        </div>


        <!-- USUÁRIO -->

        <div class="user-info">

            <strong>
                <?= htmlspecialchars($nome) ?>
            </strong>

            <span>
                LEVEL <?= $nivel ?>
            </span>

        </div>


        <!-- LOGOUT -->

        <a
            href="logout.php"
            class="logout"
            title="Sair"
        >
            ↪
        </a>

    </div>

</header>



<!-- ======================================================
     CONTEÚDO
====================================================== -->

<main class="achievements-container">


    <!-- ==================================================
         CABEÇALHO
    ================================================== -->

    <section class="achievements-header">

        <div>

            <span class="section-label">
                SUA JORNADA
            </span>

            <h1>
                Conquistas.
            </h1>

            <p>
                Cada desafio concluído deixa uma marca na sua jornada.
            </p>

        </div>


        <div class="achievement-counter">

            <strong>
                <?= $desbloqueadas ?>
            </strong>

            <span>
                / <?= $totalMedalhas ?>
            </span>

        </div>

    </section>



    <!-- ==================================================
         PROGRESSO
    ================================================== -->

    <section class="achievement-progress">

        <div class="progress-heading">

            <div>

                <span>
                    PROGRESSO DAS CONQUISTAS
                </span>

                <strong>
                    <?= $desbloqueadas ?> desbloqueadas
                </strong>

            </div>

            <strong>
                <?= round($porcentagem) ?>%
            </strong>

        </div>


        <div class="progress-bar">

            <div
                style="width: <?= $porcentagem ?>%;"
            ></div>

        </div>


        <p>
            <?= $bloqueadas ?> conquista<?= $bloqueadas == 1 ? '' : 's' ?>
            ainda aguardando você.
        </p>

    </section>



    <!-- ==================================================
         TÍTULO
    ================================================== -->

    <div class="collection-heading">

        <div>

            <span>
                COLEÇÃO
            </span>

            <h2>
                Todas as conquistas
            </h2>

        </div>

        <span class="collection-count">
            <?= $totalMedalhas ?> medalhas
        </span>

    </div>



    <!-- ==================================================
         GRID
    ================================================== -->

    <section class="achievement-grid">


        <?php foreach ($medalhas as $medalha): ?>

            <?php

            $desbloqueada =
                $medalha['data_conquista'] !== null;

            ?>


            <article
                class="
                    achievement-card
                    <?= $desbloqueada
                        ? 'unlocked'
                        : 'locked'
                    ?>
                "
            >


                <!-- ÍCONE -->

                <div class="medal-area">


                    <?php if (
                        !empty($medalha['imagem'])
                    ): ?>

                        <img
                            src="assets/img/<?= htmlspecialchars($medalha['imagem']) ?>"
                            alt="<?= htmlspecialchars($medalha['nome']) ?>"
                            class="medal-image"
                        >

                    <?php else: ?>

                        <div class="medal-placeholder">

                            <?= $desbloqueada ? '🏆' : '🔒' ?>

                        </div>

                    <?php endif; ?>


                    <?php if ($desbloqueada): ?>

                        <span class="unlocked-badge">
                            ✓
                        </span>

                    <?php else: ?>

                        <span class="locked-badge">
                            🔒
                        </span>

                    <?php endif; ?>

                </div>



                <!-- CONTEÚDO -->

                <div class="achievement-content">

                    <span class="achievement-status">

                        <?= $desbloqueada
                            ? 'DESBLOQUEADA'
                            : 'BLOQUEADA'
                        ?>

                    </span>


                    <h3>
                        <?= htmlspecialchars($medalha['nome']) ?>
                    </h3>


                    <p>
                        <?= htmlspecialchars($medalha['descricao']) ?>
                    </p>


                    <div class="achievement-criterion">

                        <span>
                            COMO CONQUISTAR
                        </span>

                        <p>
                            <?= htmlspecialchars($medalha['criterio']) ?>
                        </p>

                    </div>


                    <?php if ($desbloqueada): ?>

                        <div class="achievement-date">

                            ✓ Conquistada em
                            <?= date(
                                'd/m/Y',
                                strtotime(
                                    $medalha['data_conquista']
                                )
                            ) ?>

                        </div>

                    <?php else: ?>

                        <div class="achievement-date locked-text">

                            🔒 Ainda não conquistada

                        </div>

                    <?php endif; ?>

                </div>

            </article>

        <?php endforeach; ?>


    </section>


</main>


<script src="assets/js/tema.js"></script>

</body>

</html>