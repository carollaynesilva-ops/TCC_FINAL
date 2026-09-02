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
| Buscar usuário logado
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
| Buscar ranking
|--------------------------------------------------------------------------
|
| A classificação é feita por:
| 1. Pontuação total DESC
| 2. XP DESC
| 3. Nome ASC
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
| Descobrir posição de cada aluno
|--------------------------------------------------------------------------
*/

$posicaoUsuario = null;

foreach ($ranking as $index => &$aluno) {

    $aluno['posicao'] = $index + 1;

    if ((int)$aluno['id'] === (int)$usuarioId) {
        $posicaoUsuario = $aluno['posicao'];
    }
}

unset($aluno);

/*
|--------------------------------------------------------------------------
| Separar Top 3
|--------------------------------------------------------------------------
*/

$top3 = array_slice($ranking, 0, 3);
$restantes = array_slice($ranking, 3);

/*
|--------------------------------------------------------------------------
| Inicial do nome
|--------------------------------------------------------------------------
*/

function obterInicial($nome)
{
    return strtoupper(mb_substr(trim($nome), 0, 1, 'UTF-8'));
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

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link
        href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&display=swap"
        rel="stylesheet"
    >

    <link
        rel="stylesheet"
        href="assets/css/ranking.css"
    >

</head>

<body>

    <!-- =========================================================
         NAVBAR
    ========================================================== -->

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

            <!-- Tema -->
            <button
                class="theme-toggle"
                id="themeToggle"
                type="button"
                aria-label="Alterar tema"
            >
                ◐
            </button>

            <a href="editar_perfil.php" class="user-info">

                <div class="user-avatar">
                    <?= htmlspecialchars(obterInicial($usuario['nome'])) ?>
                </div>

                <div class="user-data">

                    <strong>
                        <?= htmlspecialchars($usuario['nome']) ?>
                    </strong>

                    <span>
                        Nível <?= (int)$usuario['nivel'] ?>
                    </span>

                </div>

            </a>

            <a href="logout.php" class="logout">
                Sair
            </a>

        </div>

    </header>


    <!-- =========================================================
         CONTEÚDO
    ========================================================== -->

    <main class="ranking-page">

        <section class="ranking-header">

            <div>

                <span class="section-tag">
                    COMPETIÇÃO
                </span>

                <h1>
                    Ranking MathRun
                </h1>

                <p>
                    Veja quem está dominando os desafios matemáticos.
                </p>

            </div>

            <div class="my-position">

                <span>
                    SUA POSIÇÃO
                </span>

                <strong>
                    <?= $posicaoUsuario !== null ? '#' . $posicaoUsuario : '--' ?>
                </strong>

            </div>

        </section>


        <!-- =====================================================
             PODIUM
        ====================================================== -->

        <?php if (count($ranking) > 0): ?>

            <section class="podium">

                <?php foreach ($top3 as $aluno): ?>

                    <article
                        class="podium-card
                        <?= $aluno['posicao'] === 1 ? 'first' : '' ?>
                        <?= $aluno['posicao'] === 2 ? 'second' : '' ?>
                        <?= $aluno['posicao'] === 3 ? 'third' : '' ?>
                        <?= (int)$aluno['id'] === (int)$usuarioId ? 'me' : '' ?>"
                    >

                        <div class="position">

                            <?php if ($aluno['posicao'] === 1): ?>
                                🥇
                            <?php elseif ($aluno['posicao'] === 2): ?>
                                🥈
                            <?php else: ?>
                                🥉
                            <?php endif; ?>

                        </div>

                        <div class="ranking-avatar">
                            <?= htmlspecialchars(obterInicial($aluno['nome'])) ?>
                        </div>

                        <h2>
                            <?= htmlspecialchars($aluno['nome']) ?>
                        </h2>

                        <span class="level">
                            Nível <?= (int)$aluno['nivel'] ?>
                        </span>

                        <div class="score">

                            <strong>
                                <?= number_format(
                                    (int)$aluno['pontuacao_total'],
                                    0,
                                    ',',
                                    '.'
                                ) ?>
                            </strong>

                            <span>
                                pontos
                            </span>

                        </div>

                        <span class="xp">
                            <?= number_format(
                                (int)$aluno['xp'],
                                0,
                                ',',
                                '.'
                            ) ?> XP
                        </span>

                    </article>

                <?php endforeach; ?>

            </section>

        <?php else: ?>

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
             LISTA DO RANKING
        ====================================================== -->

        <?php if (count($restantes) > 0): ?>

            <section class="ranking-list-section">

                <div class="list-title">

                    <div>
                        <span class="section-tag">
                            CLASSIFICAÇÃO
                        </span>

                        <h2>
                            Demais jogadores
                        </h2>
                    </div>

                    <span class="players-count">
                        <?= count($ranking) ?> jogadores
                    </span>

                </div>


                <div class="ranking-list">

                    <?php foreach ($restantes as $aluno): ?>

                        <article
                            class="ranking-row
                            <?= (int)$aluno['id'] === (int)$usuarioId ? 'current-user' : '' ?>"
                        >

                            <div class="row-position">
                                #<?= (int)$aluno['posicao'] ?>
                            </div>

                            <div class="row-avatar">
                                <?= htmlspecialchars(obterInicial($aluno['nome'])) ?>
                            </div>

                            <div class="row-user">

                                <strong>
                                    <?= htmlspecialchars($aluno['nome']) ?>
                                </strong>

                                <span>
                                    Nível <?= (int)$aluno['nivel'] ?>
                                </span>

                            </div>

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

                            <div class="row-score">

                                <span>
                                    Pontos
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

                            <?php if ((int)$aluno['id'] === (int)$usuarioId): ?>

                                <span class="you-badge">
                                    VOCÊ
                                </span>

                            <?php endif; ?>

                        </article>

                    <?php endforeach; ?>

                </div>

            </section>

        <?php endif; ?>


        <!-- =====================================================
             INFORMAÇÕES DO USUÁRIO
        ====================================================== -->

        <section class="my-ranking-card">

            <div class="my-ranking-icon">
                ✦
            </div>

            <div class="my-ranking-text">

                <span>
                    SEU DESEMPENHO
                </span>

                <h2>
                    <?= htmlspecialchars($usuario['nome']) ?>
                </h2>

            </div>

            <div class="my-stat">

                <span>
                    Posição
                </span>

                <strong>
                    <?= $posicaoUsuario !== null
                        ? '#' . $posicaoUsuario
                        : '--'
                    ?>
                </strong>

            </div>

            <div class="my-stat">

                <span>
                    Pontos
                </span>

                <strong>
                    <?= number_format(
                        (int)$usuario['pontuacao_total'],
                        0,
                        ',',
                        '.'
                    ) ?>
                </strong>

            </div>

            <div class="my-stat">

                <span>
                    XP
                </span>

                <strong>
                    <?= number_format(
                        (int)$usuario['xp'],
                        0,
                        ',',
                        '.'
                    ) ?>
                </strong>

            </div>

        </section>

    </main>


    <script src="assets/js/tema.js"></script>

</body>

</html>