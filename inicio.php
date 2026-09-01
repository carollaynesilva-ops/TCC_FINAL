<?php

session_start();

require_once 'config/config.php';

/*
|--------------------------------------------------------------------------
| Verificação de login
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION["usuario_id"])) {
    header("Location: login.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| Dados do usuário
|--------------------------------------------------------------------------
*/

$usuarioId = $_SESSION["usuario_id"];

$sql = "
    SELECT
        id,
        nome,
        email,
        tipo,
        nivel,
        xp,
        pontuacao_total
    FROM usuarios
    WHERE id = :id
";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    ":id" => $usuarioId
]);

$usuario = $stmt->fetch();


/*
|--------------------------------------------------------------------------
| Caso o usuário não exista mais no banco
|--------------------------------------------------------------------------
*/

if (!$usuario) {

    session_destroy();

    header("Location: login.php");

    exit;
}


/*
|--------------------------------------------------------------------------
| Dados para exibição
|--------------------------------------------------------------------------
*/

$nome = $usuario["nome"];
$nivel = (int) $usuario["nivel"];
$xp = (int) $usuario["xp"];
$pontuacao = (int) $usuario["pontuacao_total"];


/*
|--------------------------------------------------------------------------
| Sistema simples de XP
|--------------------------------------------------------------------------
|
| Cada nível possui 500 XP.
|
*/

$xpPorNivel = 500;

$xpAtualNivel = $xp % $xpPorNivel;

$proximoNivel = $nivel + 1;

$porcentagemXp = ($xpAtualNivel / $xpPorNivel) * 100;


/*
|--------------------------------------------------------------------------
| Buscar jogos
|--------------------------------------------------------------------------
*/

$sqlJogos = "
    SELECT
        id,
        nome,
        descricao,
        imagem
    FROM jogos
    WHERE ativo = TRUE
    ORDER BY id ASC
";

$stmtJogos = $pdo->query($sqlJogos);

$jogos = $stmtJogos->fetchAll();


/*
|--------------------------------------------------------------------------
| Buscar quantidade de conquistas
|--------------------------------------------------------------------------
*/

$sqlMedalhas = "
    SELECT COUNT(*) AS total
    FROM usuario_medalhas
    WHERE usuario_id = :usuario_id
";

$stmtMedalhas = $pdo->prepare($sqlMedalhas);

$stmtMedalhas->execute([
    ":usuario_id" => $usuarioId
]);

$totalMedalhas = (int) $stmtMedalhas->fetch()["total"];

?>

<!DOCTYPE html>

<html lang="pt-BR">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Início | MathRun</title>

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

    <link
        rel="stylesheet"
        href="assets/css/inicio.css"
    >

</head>

<body>

    <!-- ==========================================
         FUNDO
    =========================================== -->

    <div class="background">

        <div class="glow glow-1"></div>

        <div class="glow glow-2"></div>

        <div class="math-symbol symbol-1">+</div>

        <div class="math-symbol symbol-2">÷</div>

        <div class="math-symbol symbol-3">×</div>

        <div class="math-symbol symbol-4">π</div>

        <div class="math-symbol symbol-5">√</div>

    </div>


    <!-- ==========================================
         NAVBAR
    =========================================== -->

    <header class="navbar">

        <a
            href="inicio.php"
            class="brand"
        >
            Math<span>Run</span>
        </a>


        <nav class="nav-links">

            <a
                href="inicio.php"
                class="active"
            >
                Início
            </a>

            <a href="ranking.php">
                Ranking
            </a>

            <a href="conquistas.php">
                Conquistas
            </a>

        </nav>


        <div class="nav-user">

            <div class="avatar">
                <?= strtoupper(substr($nome, 0, 1)) ?>
            </div>

            <div class="user-info">

                <strong>
                    <?= htmlspecialchars($nome) ?>
                </strong>

                <span>
                    Level <?= $nivel ?>
                </span>

            </div>

            <a
                href="logout.php"
                class="logout"
                title="Sair"
            >
                ↪
            </a>

        </div>

    </header>


    <!-- ==========================================
         CONTEÚDO
    =========================================== -->

    <main class="main-container">


        <!-- ======================================
             BOAS-VINDAS
        ======================================= -->

        <section class="welcome">

            <div>

                <span class="tag">
                    SUA JORNADA
                </span>

                <h1>
                    Olá,
                    <span>
                        <?= htmlspecialchars($nome) ?>
                    </span>!
                </h1>

                <p>
                    Pronto para mais um desafio?
                    Continue jogando e aumente seu XP.
                </p>

            </div>


            <div class="level-card">

                <div class="level-top">

                    <span>
                        LEVEL <?= $nivel ?>
                    </span>

                    <strong>
                        <?= $xpAtualNivel ?> / <?= $xpPorNivel ?> XP
                    </strong>

                </div>


                <div class="progress-bar">

                    <div
                        class="progress"
                        style="width: <?= $porcentagemXp ?>%;"
                    ></div>

                </div>


                <span class="next-level">
                    <?= $xpPorNivel - $xpAtualNivel ?> XP para o Level <?= $proximoNivel ?>
                </span>

            </div>

        </section>


        <!-- ======================================
             ESTATÍSTICAS
        ======================================= -->

        <section class="stats">

            <div class="stat-card">

                <div class="stat-icon">
                    XP
                </div>

                <div>

                    <span>
                        EXPERIÊNCIA
                    </span>

                    <strong>
                        <?= number_format($xp, 0, ",", ".") ?>
                    </strong>

                </div>

            </div>


            <div class="stat-card">

                <div class="stat-icon">
                    ★
                </div>

                <div>

                    <span>
                        PONTUAÇÃO
                    </span>

                    <strong>
                        <?= number_format($pontuacao, 0, ",", ".") ?>
                    </strong>

                </div>

            </div>


            <div class="stat-card">

                <div class="stat-icon">
                    ◆
                </div>

                <div>

                    <span>
                        CONQUISTAS
                    </span>

                    <strong>
                        <?= $totalMedalhas ?>
                    </strong>

                </div>

            </div>

        </section>


        <!-- ======================================
             JOGOS
        ======================================= -->

        <section class="games-section">

            <div class="section-header">

                <div>

                    <span class="tag">
                        DESAFIOS
                    </span>

                    <h2>
                        Escolha seu jogo
                    </h2>

                    <p>
                        Aprenda matemática enquanto joga.
                    </p>

                </div>

            </div>


            <div class="games-grid">

                <?php foreach ($jogos as $jogo): ?>

                    <?php

                    $classeJogo = strtolower(
                        preg_replace(
                            '/[^a-zA-Z0-9]/',
                            '',
                            $jogo["nome"]
                        )
                    );

                    ?>

                    <article class="game-card <?= $classeJogo ?>">

                        <div class="game-icon">

                            <?php if (!empty($jogo["imagem"])): ?>

                                <img
                                    src="assets/img/jogos/<?= htmlspecialchars($jogo["imagem"]) ?>"
                                    alt="<?= htmlspecialchars($jogo["nome"]) ?>"
                                >

                            <?php else: ?>

                                <span>
                                    <?= $jogo["nome"] === "MathChef" ? "🍳" : "🚀" ?>
                                </span>

                            <?php endif; ?>

                        </div>


                        <div class="game-content">

                            <span class="game-label">
                                JOGO
                            </span>

                            <h3>
                                <?= htmlspecialchars($jogo["nome"]) ?>
                            </h3>

                            <p>
                                <?= htmlspecialchars($jogo["descricao"]) ?>
                            </p>


                            <a
                                href="jogos/<?= strtolower($jogo["nome"]) ?>/index.php"
                                class="play-button"
                            >

                                Jogar agora

                                <strong>
                                    →
                                </strong>

                            </a>

                        </div>

                    </article>

                <?php endforeach; ?>

            </div>

        </section>


        <!-- ======================================
             ACESSOS RÁPIDOS
        ======================================= -->

        <section class="quick-section">

            <a
                href="ranking.php"
                class="quick-card"
            >

                <div class="quick-icon">
                    🏆
                </div>

                <div>

                    <h3>
                        Ranking
                    </h3>

                    <p>
                        Veja sua posição entre os jogadores.
                    </p>

                </div>

                <strong>
                    →
                </strong>

            </a>


            <a
                href="conquistas.php"
                class="quick-card"
            >

                <div class="quick-icon">
                    ◆
                </div>

                <div>

                    <h3>
                        Conquistas
                    </h3>

                    <p>
                        Confira suas medalhas e desafios.
                    </p>

                </div>

                <strong>
                    →
                </strong>

            </a>

        </section>

    </main>

</body>

</html>