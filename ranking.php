<?php

session_start();

require_once __DIR__ . '/config/config.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

$usuarioId = $_SESSION['usuario_id'];


/* =========================================================
   USUÁRIO LOGADO
========================================================= */

$sqlUsuario = "SELECT id, nome, nivel, xp, pontuacao_total
               FROM usuarios
               WHERE id = :id";

$stmtUsuario = $pdo->prepare($sqlUsuario);

$stmtUsuario->execute([
    ':id' => $usuarioId
]);

$usuario = $stmtUsuario->fetch();

if (!$usuario) {
    session_destroy();

    header("Location: login.php");

    exit;
}


/* =========================================================
   RANKING DOS ALUNOS
========================================================= */

$sqlRanking = "SELECT
                    id,
                    nome,
                    nivel,
                    xp,
                    pontuacao_total
               FROM usuarios
               WHERE tipo = 'aluno'
               ORDER BY
                    pontuacao_total DESC,
                    xp DESC,
                    nome ASC";

$stmtRanking = $pdo->query($sqlRanking);

$ranking = $stmtRanking->fetchAll();


/* =========================================================
   DEFINIR POSIÇÃO
========================================================= */

foreach ($ranking as $indice => &$aluno) {

    $aluno['posicao'] = $indice + 1;

}

unset($aluno);


/* =========================================================
   POSIÇÃO DO USUÁRIO LOGADO
========================================================= */

$posicaoUsuario = null;

foreach ($ranking as $aluno) {

    if ((int)$aluno['id'] === (int)$usuarioId) {

        $posicaoUsuario = $aluno['posicao'];

        break;
    }
}


/* =========================================================
   TOP 3
========================================================= */

$primeiro = $ranking[0] ?? null;
$segundo  = $ranking[1] ?? null;
$terceiro = $ranking[2] ?? null;


/* =========================================================
   RANKING A PARTIR DO 4º LUGAR
========================================================= */

$restantes = array_slice($ranking, 3);


/* =========================================================
   PEGAR INICIAL DO NOME
========================================================= */

function obterInicial($nome)
{
    return strtoupper(
        mb_substr(
            trim($nome),
            0,
            1,
            'UTF-8'
        )
    );
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

    <title>Ranking | MathRun</title>


    <!-- =====================================================
         FONTE
    ====================================================== -->

    <link rel="preconnect" href="https://fonts.googleapis.com">

    <link
        rel="preconnect"
        href="https://fonts.gstatic.com"
        crossorigin
    >

    <link
        href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&display=swap"
        rel="stylesheet"
    >


    <!-- =====================================================
         CSS
    ====================================================== -->

    <link
        rel="stylesheet"
        href="assets/css/ranking.css"
    >

</head>


<body>


<!-- =========================================================
     NAVBAR
========================================================= -->

<header class="navbar">


    <!-- LOGO -->

    <a
        href="inicio.php"
        class="logo"
    >
        Math<span>Run</span>
    </a>


    <!-- MENU -->

    <nav class="nav-links">

        <a href="inicio.php">
            Início
        </a>

        <a
            href="ranking.php"
            class="active"
        >
            Ranking
        </a>

        <a href="conquistas.php">
            Conquistas
        </a>

    </nav>


    <!-- USUÁRIO -->

    <div class="nav-user">


        <!-- TEMA -->

        <button
            class="theme-toggle"
            id="themeToggle"
            type="button"
            aria-label="Alterar tema"
        >
            ◐
        </button>


        <!-- PERFIL -->

        <a
            href="editar_perfil.php"
            class="user-info"
        >

            <div class="user-avatar">

                <?= htmlspecialchars(
                    obterInicial($usuario['nome'])
                ) ?>

            </div>


            <div class="user-data">

                <strong>

                    <?= htmlspecialchars(
                        $usuario['nome']
                    ) ?>

                </strong>

                <span>

                    Nível <?= (int)$usuario['nivel'] ?>

                </span>

            </div>

        </a>


        <!-- SAIR -->

        <a
            href="logout.php"
            class="logout"
        >
            Sair
        </a>

    </div>

</header>



<!-- =========================================================
     CONTEÚDO
========================================================= -->

<main class="ranking-page">


    <!-- =====================================================
         CABEÇALHO
    ====================================================== -->

    <section class="ranking-header">


        <div>

            <span class="section-tag">
                CLASSIFICAÇÃO
            </span>

            <h1>
                Ranking MathRun
            </h1>

            <p>
                Os jogadores com maior pontuação total.
            </p>

        </div>


        <!-- POSIÇÃO DO USUÁRIO -->

        <div class="my-position">

            <span>
                SUA POSIÇÃO
            </span>

            <strong>

                <?= $posicaoUsuario !== null
                    ? '#' . $posicaoUsuario
                    : '--'
                ?>

            </strong>

        </div>

    </section>



    <!-- =====================================================
         PÓDIO
    ====================================================== -->

    <?php if ($primeiro): ?>

        <section class="podium">


            <!-- =================================================
                 2º LUGAR
            ================================================== -->

            <?php if ($segundo): ?>

                <article
                    class="
                        podium-player
                        second-place

                        <?= (int)$segundo['id'] === (int)$usuarioId
                            ? 'current-player'
                            : ''
                        ?>
                    "
                >


                    <div class="podium-medal">

                        <img
                            src="assets/img/segundo.png"
                            alt="Medalha de segundo lugar"
                        >

                    </div>


                    <div class="podium-position">

                        <strong>
                            2º
                        </strong>

                        <span>
                            LUGAR
                        </span>

                    </div>


                    <div class="player-icon">

                        <span>
                            <?= htmlspecialchars(
                                obterInicial($segundo['nome'])
                            ) ?>
                        </span>

                    </div>


                    <h2>

                        <?= htmlspecialchars(
                            $segundo['nome']
                        ) ?>

                    </h2>


                    <span class="player-level">

                        Nível <?= (int)$segundo['nivel'] ?>

                    </span>


                    <div class="player-points">

                        <strong>

                            <?= number_format(
                                (int)$segundo['pontuacao_total'],
                                0,
                                ',',
                                '.'
                            ) ?>

                        </strong>

                        <span>
                            PONTOS
                        </span>

                    </div>


                    <div class="podium-base">
                        2
                    </div>

                </article>

            <?php endif; ?>



            <!-- =================================================
                 1º LUGAR
            ================================================== -->

            <article
                class="
                    podium-player
                    first-place

                    <?= (int)$primeiro['id'] === (int)$usuarioId
                        ? 'current-player'
                        : ''
                    ?>
                "
            >


                <div class="podium-medal">

                    <img
                        src="assets/img/primeiro.png"
                        alt="Medalha de primeiro lugar"
                    >

                </div>


                <div class="podium-position">

                    <strong>
                        1º
                    </strong>

                    <span>
                        LUGAR
                    </span>

                </div>


                <div class="player-icon">

                    <span>
                        <?= htmlspecialchars(
                            obterInicial($primeiro['nome'])
                        ) ?>
                    </span>

                </div>


                <h2>

                    <?= htmlspecialchars(
                        $primeiro['nome']
                    ) ?>

                </h2>


                <span class="player-level">

                    Nível <?= (int)$primeiro['nivel'] ?>

                </span>


                <div class="player-points">

                    <strong>

                        <?= number_format(
                            (int)$primeiro['pontuacao_total'],
                            0,
                            ',',
                            '.'
                        ) ?>

                    </strong>

                    <span>
                        PONTOS
                    </span>

                </div>


                <div class="podium-base">
                    1
                </div>

            </article>



            <!-- =================================================
                 3º LUGAR
            ================================================== -->

            <?php if ($terceiro): ?>

                <article
                    class="
                        podium-player
                        third-place

                        <?= (int)$terceiro['id'] === (int)$usuarioId
                            ? 'current-player'
                            : ''
                        ?>
                    "
                >


                    <div class="podium-medal">

                        <img
                            src="assets/img/terceiro.png"
                            alt="Medalha de terceiro lugar"
                        >

                    </div>


                    <div class="podium-position">

                        <strong>
                            3º
                        </strong>

                        <span>
                            LUGAR
                        </span>

                    </div>


                    <div class="player-icon">

                        <span>
                            <?= htmlspecialchars(
                                obterInicial($terceiro['nome'])
                            ) ?>
                        </span>

                    </div>


                    <h2>

                        <?= htmlspecialchars(
                            $terceiro['nome']
                        ) ?>

                    </h2>


                    <span class="player-level">

                        Nível <?= (int)$terceiro['nivel'] ?>

                    </span>


                    <div class="player-points">

                        <strong>

                            <?= number_format(
                                (int)$terceiro['pontuacao_total'],
                                0,
                                ',',
                                '.'
                            ) ?>

                        </strong>

                        <span>
                            PONTOS
                        </span>

                    </div>


                    <div class="podium-base">
                        3
                    </div>

                </article>

            <?php endif; ?>


        </section>

    <?php else: ?>


        <!-- =================================================
             RANKING VAZIO
        ================================================== -->

        <section class="empty-ranking">

            <div class="empty-icon">
                🏆
            </div>

            <h2>
                Ainda não há jogadores no ranking.
            </h2>

            <p>
                Complete uma missão para aparecer aqui.
            </p>

        </section>

    <?php endif; ?>



    <!-- =====================================================
         RANKING DO 4º EM DIANTE
    ====================================================== -->

    <?php if (count($restantes) > 0): ?>

        <section class="ranking-list-section">


            <div class="list-header">

                <div>

                    <span class="section-tag">
                        CLASSIFICAÇÃO
                    </span>

                    <h2>
                        Ranking completo
                    </h2>

                </div>


                <span class="players-count">

                    <?= count($restantes) ?>

                    <?= count($restantes) === 1
                        ? ' jogador'
                        : ' jogadores'
                    ?>

                </span>

            </div>



            <div class="ranking-list">


                <?php foreach ($restantes as $aluno): ?>

                    <article
                        class="
                            ranking-row

                            <?= (int)$aluno['id'] === (int)$usuarioId
                                ? 'current-user'
                                : ''
                            ?>
                        "
                    >


                        <!-- POSIÇÃO -->

                        <div class="row-position">

                            #<?= (int)$aluno['posicao'] ?>

                        </div>


                        <!-- AVATAR -->

                        <div class="row-avatar">

                            <?= htmlspecialchars(
                                obterInicial($aluno['nome'])
                            ) ?>

                        </div>


                        <!-- USUÁRIO -->

                        <div class="row-user">

                            <strong>

                                <?= htmlspecialchars(
                                    $aluno['nome']
                                ) ?>

                            </strong>

                            <span>

                                Nível <?= (int)$aluno['nivel'] ?>

                            </span>

                        </div>


                        <!-- XP -->

                        <div class="row-xp">

                            <span>
                                XP
                            </span>

                            <strong>

                                <?= number_format(
                                    (int)$aluno['xp'],
                                    0,
                                    ',',
                                    '.'
                                ) ?>

                            </strong>

                        </div>


                        <!-- PONTOS -->

                        <div class="row-score">

                            <span>
                                PONTUAÇÃO TOTAL
                            </span>

                            <strong>

                                <?= number_format(
                                    (int)$aluno['pontuacao_total'],
                                    0,
                                    ',',
                                    '.'
                                ) ?>

                            </strong>

                        </div>


                        <!-- VOCÊ -->

                        <?php if (
                            (int)$aluno['id'] ===
                            (int)$usuarioId
                        ): ?>

                            <span class="you-badge">
                                VOCÊ
                            </span>

                        <?php endif; ?>


                    </article>

                <?php endforeach; ?>


            </div>

        </section>

    <?php endif; ?>


</main>



<!-- =========================================================
     TEMA
========================================================= -->

<script src="assets/js/tema.js"></script>


</body>

</html>