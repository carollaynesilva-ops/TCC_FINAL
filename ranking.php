<?php
session_start();

require_once __DIR__ . '/config/config.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

$usuarioId = $_SESSION['usuario_id'];

/* =========================================================
   BUSCAR USUÁRIO LOGADO
========================================================= */

$stmtUsuario = $pdo->prepare("
    SELECT 
        id,
        nome,
        email,
        nivel,
        xp,
        pontuacao_total
    FROM usuarios
    WHERE id = ?
");

$stmtUsuario->execute([$usuarioId]);
$usuario = $stmtUsuario->fetch();

if (!$usuario) {
    session_destroy();
    header('Location: login.php');
    exit;
}

/* =========================================================
   BUSCAR RANKING
========================================================= */

$stmtRanking = $pdo->query("
    SELECT
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
        nome ASC
");

$ranking = $stmtRanking->fetchAll();

/* =========================================================
   DEFINIR POSIÇÕES
========================================================= */

foreach ($ranking as $index => &$aluno) {
    $aluno['posicao'] = $index + 1;
}

unset($aluno);

/* =========================================================
   SEPARAR PÓDIO
========================================================= */

$primeiro = $ranking[0] ?? null;
$segundo = $ranking[1] ?? null;
$terceiro = $ranking[2] ?? null;

/*
 * A lista começa no 4º lugar.
 * Assim os três primeiros aparecem somente no pódio.
 */
$restantes = array_slice($ranking, 3);

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
   FUNÇÃO PARA PEGAR INICIAL DO NOME
========================================================= */

function inicialNome($nome)
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

    <!-- =====================================================
         NAVBAR
    ====================================================== -->

    <header class="navbar">

        <div class="nav-container">

            <!-- LOGO -->

            <a href="inicio.php" class="logo">
                Math<span>Run</span>
            </a>


            <!-- MENU -->

            <nav class="nav-menu">

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


            <!-- ÁREA DO USUÁRIO -->

            <div class="nav-user">

                <!-- TEMA -->

                <button
                    type="button"
                    class="theme-toggle"
                    id="themeToggle"
                    aria-label="Alterar tema"
                    title="Alterar tema"
                >
                    <span class="theme-icon">☀</span>
                </button>


                <!-- USUÁRIO -->

                <a
                    href="editar_perfil.php"
                    class="user-profile"
                >

                    <div class="user-avatar">
                        <?= htmlspecialchars(inicialNome($usuario['nome'])) ?>
                    </div>

                    <div class="user-info">

                        <strong>
                            <?= htmlspecialchars($usuario['nome']) ?>
                        </strong>

                        <span>
                            Nível <?= (int)$usuario['nivel'] ?>
                        </span>

                    </div>

                </a>


                <!-- SAIR -->

                <a
                    href="logout.php"
                    class="logout-btn"
                >
                    Sair
                </a>

            </div>

        </div>

    </header>


    <!-- =====================================================
         CONTEÚDO
    ====================================================== -->

    <main class="ranking-page">

        <!-- CABEÇALHO -->

        <section class="ranking-header">

            <span class="ranking-tag">
                🏆 COMPETIÇÃO
            </span>

            <h1>
                Ranking
            </h1>

            <p>
                Veja quem está dominando o MathRun.
            </p>

            <?php if ($posicaoUsuario !== null): ?>

                <div class="my-position">

                    <span>
                        Sua posição
                    </span>

                    <strong>
                        #<?= (int)$posicaoUsuario ?>
                    </strong>

                </div>

            <?php endif; ?>

        </section>


        <!-- =================================================
             PÓDIO
        ================================================== -->

        <?php if ($primeiro || $segundo || $terceiro): ?>

            <section class="podium">

                <!-- =========================================
                     SEGUNDO LUGAR
                ========================================== -->

                <?php if ($segundo): ?>

                    <div class="podium-card second">

                        <div class="podium-position">
                            2
                        </div>

                        <div class="podium-avatar">

                            <?php
                            $imagemSegundo = 'assets/img/segundo.png';
                            ?>

                            <?php if (file_exists($imagemSegundo)): ?>

                                <img
                                    src="<?= htmlspecialchars($imagemSegundo) ?>"
                                    alt="Segundo lugar"
                                >

                            <?php else: ?>

                                <span>
                                    <?= htmlspecialchars(inicialNome($segundo['nome'])) ?>
                                </span>

                            <?php endif; ?>

                        </div>

                        <div class="podium-place">
                            2º LUGAR
                        </div>

                        <h2>
                            <?= htmlspecialchars($segundo['nome']) ?>
                        </h2>

                        <div class="podium-score">

                            <strong>
                                <?= number_format((int)$segundo['pontuacao_total'], 0, ',', '.') ?>
                            </strong>

                            <span>
                                pontos
                            </span>

                        </div>

                        <div class="podium-xp">
                            <?= number_format((int)$segundo['xp'], 0, ',', '.') ?> XP
                        </div>

                    </div>

                <?php endif; ?>


                <!-- =========================================
                     PRIMEIRO LUGAR
                ========================================== -->

                <?php if ($primeiro): ?>

                    <div class="podium-card first">

                        <div class="crown">
                            👑
                        </div>

                        <div class="podium-position">
                            1
                        </div>

                        <div class="podium-avatar">

                            <?php
                            $imagemPrimeiro = 'assets/img/primeiro.png';
                            ?>

                            <?php if (file_exists($imagemPrimeiro)): ?>

                                <img
                                    src="<?= htmlspecialchars($imagemPrimeiro) ?>"
                                    alt="Primeiro lugar"
                                >

                            <?php else: ?>

                                <span>
                                    <?= htmlspecialchars(inicialNome($primeiro['nome'])) ?>
                                </span>

                            <?php endif; ?>

                        </div>

                        <div class="podium-place">
                            1º LUGAR
                        </div>

                        <h2>
                            <?= htmlspecialchars($primeiro['nome']) ?>
                        </h2>

                        <div class="podium-score">

                            <strong>
                                <?= number_format((int)$primeiro['pontuacao_total'], 0, ',', '.') ?>
                            </strong>

                            <span>
                                pontos
                            </span>

                        </div>

                        <div class="podium-xp">
                            <?= number_format((int)$primeiro['xp'], 0, ',', '.') ?> XP
                        </div>

                    </div>

                <?php endif; ?>


                <!-- =========================================
                     TERCEIRO LUGAR
                ========================================== -->

                <?php if ($terceiro): ?>

                    <div class="podium-card third">

                        <div class="podium-position">
                            3
                        </div>

                        <div class="podium-avatar">

                            <?php
                            $imagemTerceiro = 'assets/img/terceiro.png';
                            ?>

                            <?php if (file_exists($imagemTerceiro)): ?>

                                <img
                                    src="<?= htmlspecialchars($imagemTerceiro) ?>"
                                    alt="Terceiro lugar"
                                >

                            <?php else: ?>

                                <span>
                                    <?= htmlspecialchars(inicialNome($terceiro['nome'])) ?>
                                </span>

                            <?php endif; ?>

                        </div>

                        <div class="podium-place">
                            3º LUGAR
                        </div>

                        <h2>
                            <?= htmlspecialchars($terceiro['nome']) ?>
                        </h2>

                        <div class="podium-score">

                            <strong>
                                <?= number_format((int)$terceiro['pontuacao_total'], 0, ',', '.') ?>
                            </strong>

                            <span>
                                pontos
                            </span>

                        </div>

                        <div class="podium-xp">
                            <?= number_format((int)$terceiro['xp'], 0, ',', '.') ?> XP
                        </div>

                    </div>

                <?php endif; ?>

            </section>

        <?php else: ?>

            <section class="empty-ranking">

                <div class="empty-icon">
                    🏆
                </div>

                <h2>
                    Ainda não há jogadores no ranking
                </h2>

                <p>
                    Complete missões para começar a aparecer aqui.
                </p>

            </section>

        <?php endif; ?>


        <!-- =================================================
             DEMAIS JOGADORES
        ================================================== -->

        <?php if (!empty($restantes)): ?>

            <section class="ranking-list-section">

                <div class="section-title">

                    <div>
                        <span>
                            CLASSIFICAÇÃO
                        </span>

                        <h2>
                            Demais jogadores
                        </h2>
                    </div>

                    <div class="total-players">
                        <?= count($ranking) ?> jogadores
                    </div>

                </div>


                <div class="ranking-list">

                    <?php foreach ($restantes as $aluno): ?>

                        <?php
                        $ehUsuario =
                            (int)$aluno['id'] === (int)$usuarioId;
                        ?>

                        <div
                            class="ranking-row <?= $ehUsuario ? 'current-user' : '' ?>"
                        >

                            <!-- POSIÇÃO -->

                            <div class="ranking-number">
                                #<?= (int)$aluno['posicao'] ?>
                            </div>


                            <!-- AVATAR -->

                            <div class="ranking-row-avatar">

                                <?= htmlspecialchars(
                                    inicialNome($aluno['nome'])
                                ) ?>

                            </div>


                            <!-- NOME -->

                            <div class="ranking-player">

                                <strong>
                                    <?= htmlspecialchars($aluno['nome']) ?>
                                </strong>

                                <span>
                                    Nível <?= (int)$aluno['nivel'] ?>
                                </span>

                            </div>


                            <!-- XP -->

                            <div class="ranking-xp">

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


                            <!-- PONTUAÇÃO -->

                            <div class="ranking-points">

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


                            <!-- VOCÊ -->

                            <?php if ($ehUsuario): ?>

                                <div class="you-badge">
                                    VOCÊ
                                </div>

                            <?php endif; ?>

                        </div>

                    <?php endforeach; ?>

                </div>

            </section>

        <?php endif; ?>


        <!-- =================================================
             RODAPÉ DO RANKING
        ================================================== -->

        <?php if (count($ranking) < 4 && count($ranking) > 0): ?>

            <div class="ranking-end">

                <span>🏆</span>

                <p>
                    Você chegou ao final do ranking.
                </p>

            </div>

        <?php endif; ?>

    </main>


    <!-- =====================================================
         TEMA
    ====================================================== -->

    <script src="assets/js/tema.js"></script>

</body>

</html> 