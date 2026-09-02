<?php

session_start();

require_once __DIR__ . '/config/config.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

$usuarioId = $_SESSION['usuario_id'];

/*
|--------------------------------------------------------------------------
| USUÁRIO LOGADO
|--------------------------------------------------------------------------
*/

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


/*
|--------------------------------------------------------------------------
| RANKING
|--------------------------------------------------------------------------
|
| Ordem:
| 1º maior pontuação total
| 2º maior XP em caso de empate
| 3º nome em ordem alfabética
|
*/

$sqlRanking = "SELECT id, nome, nivel, xp, pontuacao_total
               FROM usuarios
               WHERE tipo = 'aluno'
               ORDER BY pontuacao_total DESC,
                        xp DESC,
                        nome ASC";

$stmtRanking = $pdo->query($sqlRanking);

$ranking = $stmtRanking->fetchAll();


/*
|--------------------------------------------------------------------------
| DEFINIR POSIÇÃO
|--------------------------------------------------------------------------
*/

foreach ($ranking as $indice => &$aluno) {

    $aluno['posicao'] = $indice + 1;

}

unset($aluno);


/*
|--------------------------------------------------------------------------
| ENCONTRAR POSIÇÃO DO USUÁRIO
|--------------------------------------------------------------------------
*/

$posicaoUsuario = null;

foreach ($ranking as $aluno) {

    if ((int)$aluno['id'] === (int)$usuarioId) {

        $posicaoUsuario = $aluno['posicao'];

        break;
    }
}


/*
|--------------------------------------------------------------------------
| TOP 3
|--------------------------------------------------------------------------
*/

$primeiro = $ranking[0] ?? null;
$segundo  = $ranking[1] ?? null;
$terceiro = $ranking[2] ?? null;


/*
|--------------------------------------------------------------------------
| RESTANTE DO RANKING
|--------------------------------------------------------------------------
*/

$restantes = array_slice($ranking, 3);


/*
|--------------------------------------------------------------------------
| FUNÇÃO PARA PEGAR INICIAL
|--------------------------------------------------------------------------
*/

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


    <!-- Fonte -->

    <link rel="preconnect" href="https://fonts.googleapis.com">

    <link rel="preconnect"
          href="https://fonts.gstatic.com"
          crossorigin>

    <link
        href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&display=swap"
        rel="stylesheet"
    >


    <!-- CSS -->

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


    <a href="inicio.php" class="logo">

        Math<span>Run</span>

    </a>


    <nav class="nav-links">

        <a href="inicio.php">
            Início
        </a>

        <a href="ranking.php" class="active">
            Ranking
        </a>

        <a href="conquistas.php">
            Conquistas
        </a>

    </nav>


    <div class="nav-user">


        <!-- BOTÃO DE TEMA -->

        <button
            class="theme-toggle"
            id="themeToggle"
            type="button"
            aria-label="Alterar tema"
        >
            ◐
        </button>


        <!-- USUÁRIO -->

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
                COMPETIÇÃO
            </span>

            <h1>
                Ranking MathRun
            </h1>

            <p>
                Os jogadores com maior pontuação total.
            </p>

        </div>


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

                    <div class="podium-position">

                        <span>
                            2º
                        </span>

                        <small>
                            LUGAR
                        </small>

                    </div>


                    <div class="podium-icon">

                        <img
                            src="assets/img/segundo.png"
                            alt="Segundo lugar"
                            onerror="this.style.display='none'; this.parentElement.classList.add('no-image');"
                        >

                        <span class="initial-fallback">

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

                <div class="crown">

                    <img
                        src="assets/img/primeiro.png"
                        alt="Primeiro lugar"
                        onerror="this.style.display='none';"
                    >

                </div>


                <div class="podium-position">

                    <span>
                        1º
                    </span>

                    <small>
                        LUGAR
                    </small>

                </div>


                <div class="podium-icon">

                    <img
                        src="assets/img/primeiro.png"
                        alt="Primeiro lugar"
                        onerror="this.style.display='none'; this.parentElement.classList.add('no-image');"
                    >

                    <span class="initial-fallback">

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

                    <div class="podium-position">

                        <span>
                            3º
                        </span>

                        <small>
                            LUGAR
                        </small>

                    </div>


                    <div class="podium-icon">

                        <img
                            src="assets/img/terceiro.png"
                            alt="Terceiro lugar"
                            onerror="this.style.display='none'; this.parentElement.classList.add('no-image');"
                        >

                        <span class="initial-fallback">

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


        <!-- =====================================================
             RANKING VAZIO
        ====================================================== -->

        <section class="empty-ranking">

            <div class="empty-icon">
                🏆
            </div>

            <h2>
                Ainda não há jogadores no ranking.
            </h2>

            <p>
                Complete uma missão para começar sua pontuação.
            </p>

        </section>

    <?php endif; ?>



    <!-- =====================================================
         LISTA COMPLETA
    ====================================================== -->

    <?php if (count($ranking) > 0): ?>

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

                    <?= count($ranking) ?>

                    <?= count($ranking) === 1
                        ? ' jogador'
                        : ' jogadores'
                    ?>

                </span>

            </div>



            <div class="ranking-list">


                <?php foreach ($ranking as $aluno): ?>

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

                        <div
                            class="
                                row-position
                                position-<?= (int)$aluno['posicao'] ?>
                            "
                        >

                            #<?= (int)$aluno['posicao'] ?>

                        </div>



                        <!-- ÍCONE / AVATAR -->

                        <div class="row-avatar">

                            <?= htmlspecialchars(
                                obterInicial($aluno['nome'])
                            ) ?>

                        </div>



                        <!-- NOME -->

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



<script src="assets/js/tema.js"></script>

</body>

</html>