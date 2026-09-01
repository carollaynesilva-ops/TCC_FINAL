<?php

session_start();

require_once 'config/config.php';

/*
|--------------------------------------------------------------------------
| Verificar login
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION["usuario_id"])) {
    header("Location: login.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| Buscar dados atualizados do usuário
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


if (!$usuario) {

    session_destroy();

    header("Location: login.php");
    exit;
}


$nome = $usuario["nome"];
$nivel = (int) $usuario["nivel"];
$xp = (int) $usuario["xp"];
$pontuacao = (int) $usuario["pontuacao_total"];


/*
|--------------------------------------------------------------------------
| Sistema de XP
|--------------------------------------------------------------------------
*/

$xpPorNivel = 500;

$xpAtualNivel = $xp % $xpPorNivel;

$xpRestante = $xpPorNivel - $xpAtualNivel;

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
| Buscar progresso das fases
|--------------------------------------------------------------------------
*/

$sqlProgresso = "
    SELECT
        j.id AS jogo_id,
        j.nome AS jogo_nome,
        COUNT(f.id) AS total_fases,
        COUNT(
            CASE
                WHEN p.concluida = TRUE THEN 1
            END
        ) AS fases_concluidas
    FROM jogos j

    LEFT JOIN fases f
        ON f.jogo_id = j.id

    LEFT JOIN progresso_usuario p
        ON p.fase_id = f.id
        AND p.usuario_id = :usuario_id

    WHERE j.ativo = TRUE

    GROUP BY
        j.id,
        j.nome

    ORDER BY j.id ASC
";

$stmtProgresso = $pdo->prepare($sqlProgresso);

$stmtProgresso->execute([
    ":usuario_id" => $usuarioId
]);

$progressos = $stmtProgresso->fetchAll();


/*
|--------------------------------------------------------------------------
| Transformar progresso em array fácil de usar
|--------------------------------------------------------------------------
*/

$progressoJogos = [];

foreach ($progressos as $progresso) {

    $progressoJogos[$progresso["jogo_id"]] = [
        "total" => (int) $progresso["total_fases"],
        "concluidas" => (int) $progresso["fases_concluidas"]
    ];
}


/*
|--------------------------------------------------------------------------
| Buscar próxima fase
|--------------------------------------------------------------------------
*/

$sqlProximaFase = "
    SELECT
        f.id,
        f.nome,
        f.descricao,
        f.nivel_dificuldade,
        f.numero,
        j.id AS jogo_id,
        j.nome AS jogo_nome
    FROM fases f

    INNER JOIN jogos j
        ON j.id = f.jogo_id

    LEFT JOIN progresso_usuario p
        ON p.fase_id = f.id
        AND p.usuario_id = :usuario_id

    WHERE
        j.ativo = TRUE
        AND (
            p.id IS NULL
            OR p.concluida = FALSE
        )

    ORDER BY
        j.id ASC,
        f.numero ASC

    LIMIT 1
";

$stmtProxima = $pdo->prepare($sqlProximaFase);

$stmtProxima->execute([
    ":usuario_id" => $usuarioId
]);

$proximaFase = $stmtProxima->fetch();


/*
|--------------------------------------------------------------------------
| Buscar medalhas
|--------------------------------------------------------------------------
*/

$sqlMedalhas = "
    SELECT
        m.nome,
        m.descricao,
        m.imagem
    FROM usuario_medalhas um

    INNER JOIN medalhas m
        ON m.id = um.medalha_id

    WHERE um.usuario_id = :usuario_id

    ORDER BY um.data_conquista DESC

    LIMIT 1
";

$stmtMedalhas = $pdo->prepare($sqlMedalhas);

$stmtMedalhas->execute([
    ":usuario_id" => $usuarioId
]);

$ultimaMedalha = $stmtMedalhas->fetch();


/*
|--------------------------------------------------------------------------
| Quantidade total de medalhas
|--------------------------------------------------------------------------
*/

$sqlTotalMedalhas = "
    SELECT COUNT(*) AS total
    FROM usuario_medalhas
    WHERE usuario_id = :usuario_id
";

$stmtTotalMedalhas = $pdo->prepare($sqlTotalMedalhas);

$stmtTotalMedalhas->execute([
    ":usuario_id" => $usuarioId
]);

$totalMedalhas = (int) $stmtTotalMedalhas->fetch()["total"];


/*
|--------------------------------------------------------------------------
| Definir dados visuais da próxima missão
|--------------------------------------------------------------------------
*/

if ($proximaFase) {

    if ($proximaFase["jogo_nome"] === "MathChef") {

        $iconeMissao = "🍳";
        $classeMissao = "chef";
        $tituloMissao = "MATHCHEF";

    } else {

        $iconeMissao = "🚀";
        $classeMissao = "space";
        $tituloMissao = "MATHSPACE";
    }

} else {

    $iconeMissao = "🏆";
    $classeMissao = "completed";
    $tituloMissao = "JORNADA COMPLETA";
}


/*
|--------------------------------------------------------------------------
| Saudação
|--------------------------------------------------------------------------
*/

$hora = (int) date("H");

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
        content="width=device-width, initial-scale=1.0"
    >

    <title>MathRun | Sua jornada</title>

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

        <span class="math-symbol symbol-1">+</span>

        <span class="math-symbol symbol-2">×</span>

        <span class="math-symbol symbol-3">π</span>

        <span class="math-symbol symbol-4">√</span>

        <span class="math-symbol symbol-5">÷</span>

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
                    LEVEL <?= $nivel ?>
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
             CABEÇALHO
        ======================================= -->

        <section class="welcome-header">

            <div>

                <span class="mini-tag">
                    <?= $saudacao ?>, <?= htmlspecialchars($nome) ?>
                </span>

                <h1>
                    Sua próxima missão<br>
                    <span>está esperando.</span>
                </h1>

            </div>

            <div class="level-mini">

                <div class="level-number">
                    <?= $nivel ?>
                </div>

                <div>

                    <span>
                        LEVEL ATUAL
                    </span>

                    <strong>
                        <?= $xp ?> XP
                    </strong>

                </div>

            </div>

        </section>


        <!-- ======================================
             HERO / PRÓXIMA MISSÃO
        ======================================= -->

        <section class="mission-hero <?= $classeMissao ?>">

            <div class="mission-content">

                <span class="mission-label">
                    ⚡ SUA PRÓXIMA MISSÃO
                </span>


                <?php if ($proximaFase): ?>

                    <div class="mission-game">
                        <?= $iconeMissao ?>
                        <?= $tituloMissao ?>
                    </div>


                    <h2>
                        <?= htmlspecialchars($proximaFase["nome"]) ?>
                    </h2>


                    <p>
                        <?= htmlspecialchars($proximaFase["descricao"]) ?>
                    </p>


                    <div class="mission-meta">

                        <span>
                            FASE <?= $proximaFase["numero"] ?>
                        </span>

                        <span>
                            <?= strtoupper($proximaFase["nivel_dificuldade"]) ?>
                        </span>

                        <span>
                            +100 XP
                        </span>

                    </div>


                    <a
                        href="jogos/<?= strtolower($proximaFase["jogo_nome"]) ?>/index.php"
                        class="mission-button"
                    >

                        CONTINUAR MISSÃO

                        <strong>
                            →
                        </strong>

                    </a>

                <?php else: ?>

                    <div class="mission-game">
                        🏆 JORNADA COMPLETA
                    </div>

                    <h2>
                        Você zerou o MathRun!
                    </h2>

                    <p>
                        Todas as missões disponíveis foram concluídas.
                        Seu próximo desafio é superar sua própria pontuação.
                    </p>

                    <a
                        href="ranking.php"
                        class="mission-button"
                    >
                        VER RANKING →
                    </a>

                <?php endif; ?>

            </div>


            <div class="mission-visual">

                <div class="orbit orbit-1"></div>

                <div class="orbit orbit-2"></div>

                <div class="planet">

                    <?= $iconeMissao ?>

                </div>

                <div class="floating-xp">
                    +100 XP
                </div>

                <div class="floating-symbol symbol-a">
                    +
                </div>

                <div class="floating-symbol symbol-b">
                    π
                </div>

            </div>

        </section>


        <!-- ======================================
             STATUS
        ======================================= -->

        <section class="status-bar">

            <div class="status-item">

                <div class="status-icon">
                    ⚡
                </div>

                <div>

                    <span>
                        XP TOTAL
                    </span>

                    <strong>
                        <?= number_format($xp, 0, ",", ".") ?>
                    </strong>

                </div>

            </div>


            <div class="status-divider"></div>


            <div class="status-item">

                <div class="status-icon">
                    ★
                </div>

                <div>

                    <span>
                        PONTOS
                    </span>

                    <strong>
                        <?= number_format($pontuacao, 0, ",", ".") ?>
                    </strong>

                </div>

            </div>


            <div class="status-divider"></div>


            <div class="status-item">

                <div class="status-icon">
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


            <div class="status-divider"></div>


            <div class="status-item level-progress">

                <div class="status-icon">
                    <?= $nivel ?>
                </div>

                <div>

                    <span>
                        PRÓXIMO LEVEL
                    </span>

                    <div class="small-progress">

                        <div
                            style="width: <?= $porcentagemXp ?>%;"
                        ></div>

                    </div>

                </div>

            </div>

        </section>


        <!-- ======================================
             ESCOLHA SUA MISSÃO
        ======================================= -->

        <section class="games-section">

            <div class="section-title">

                <div>

                    <span class="mini-tag">
                        DESAFIOS
                    </span>

                    <h2>
                        Escolha sua missão.
                    </h2>

                </div>

                <p>
                    Dois mundos. Oito fases.<br>
                    Uma jornada matemática.
                </p>

            </div>


            <div class="games-grid">

                <?php foreach ($jogos as $jogo): ?>

                    <?php

                    $idJogo = (int) $jogo["id"];

                    $nomeJogo = $jogo["nome"];

                    $isChef = $nomeJogo === "MathChef";

                    $icone = $isChef ? "🍳" : "🚀";

                    $classe = $isChef ? "chef-card" : "space-card";

                    $progresso = $progressoJogos[$idJogo] ?? [
                        "total" => 0,
                        "concluidas" => 0
                    ];

                    $totalFases = $progresso["total"];

                    $fasesConcluidas = $progresso["concluidas"];

                    $porcentagemJogo = $totalFases > 0
                        ? ($fasesConcluidas / $totalFases) * 100
                        : 0;

                    ?>

                    <article class="game-card <?= $classe ?>">

                        <div class="game-art">

                            <div class="game-glow"></div>

                            <div class="game-big-icon">
                                <?= $icone ?>
                            </div>

                            <span class="game-decoration decoration-1">
                                <?= $isChef ? "½" : "∞" ?>
                            </span>

                            <span class="game-decoration decoration-2">
                                <?= $isChef ? "⅓" : "π" ?>
                            </span>

                        </div>


                        <div class="game-info">

                            <span class="game-type">
                                <?= $isChef ? "CULINÁRIA" : "EXPLORAÇÃO ESPACIAL" ?>
                            </span>


                            <h3>
                                <?= htmlspecialchars($nomeJogo) ?>
                            </h3>


                            <p>
                                <?= htmlspecialchars($jogo["descricao"]) ?>
                            </p>


                            <div class="game-progress-info">

                                <span>
                                    PROGRESSO
                                </span>

                                <strong>
                                    <?= $fasesConcluidas ?>/<?= $totalFases ?>
                                </strong>

                            </div>


                            <div class="game-progress">

                                <div
                                    style="width: <?= $porcentagemJogo ?>%;"
                                ></div>

                            </div>


                            <a
                                href="jogos/<?= strtolower($nomeJogo) ?>/index.php"
                                class="game-button"
                            >

                                <?= $fasesConcluidas > 0 ? "CONTINUAR" : "COMEÇAR" ?>

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
             PROGRESSO + CONQUISTA
        ======================================= -->

        <section class="bottom-grid">


            <!-- PROGRESSO DE XP -->

            <article class="progress-card">

                <div class="card-heading">

                    <div>

                        <span class="mini-tag">
                            EVOLUÇÃO
                        </span>

                        <h3>
                            Seu próximo nível
                        </h3>

                    </div>

                    <strong>
                        <?= $xpAtualNivel ?>/<?= $xpPorNivel ?> XP
                    </strong>

                </div>


                <div class="big-progress">

                    <div
                        style="width: <?= $porcentagemXp ?>%;"
                    ></div>

                </div>


                <div class="progress-footer">

                    <span>
                        LEVEL <?= $nivel ?>
                    </span>

                    <span>
                        LEVEL <?= $proximoNivel ?>
                    </span>

                </div>


                <p>
                    Faltam <strong><?= $xpRestante ?> XP</strong>
                    para desbloquear o próximo nível.
                </p>

            </article>


            <!-- CONQUISTA -->

            <article class="achievement-card">

                <?php if ($ultimaMedalha): ?>

                    <span class="mini-tag">
                        ÚLTIMA CONQUISTA
                    </span>


                    <div class="achievement-content">

                        <div class="medal-icon">

                            <?php if (!empty($ultimaMedalha["imagem"])): ?>

                                <img
                                    src="assets/img/medalhas/<?= htmlspecialchars($ultimaMedalha["imagem"]) ?>"
                                    alt=""
                                >

                            <?php else: ?>

                                🏅

                            <?php endif; ?>

                        </div>


                        <div>

                            <h3>
                                <?= htmlspecialchars($ultimaMedalha["nome"]) ?>
                            </h3>

                            <p>
                                <?= htmlspecialchars($ultimaMedalha["descricao"]) ?>
                            </p>

                        </div>

                    </div>


                    <a href="conquistas.php">
                        VER TODAS →
                    </a>

                <?php else: ?>

                    <span class="mini-tag">
                        PRÓXIMA CONQUISTA
                    </span>


                    <div class="achievement-content">

                        <div class="medal-icon locked">
                            ?
                        </div>


                        <div>

                            <h3>
                                Sua primeira medalha
                            </h3>

                            <p>
                                Complete uma missão para começar
                                sua coleção.
                            </p>

                        </div>

                    </div>


                    <a href="conquistas.php">
                        VER CONQUISTAS →
                    </a>

                <?php endif; ?>

            </article>

        </section>

    </main>

</body>

</html>