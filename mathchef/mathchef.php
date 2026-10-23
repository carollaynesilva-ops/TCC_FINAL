<?php

session_start();

require_once __DIR__ . "/../config/config.php";

if (!isset($_SESSION["usuario_id"])) {
    header("Location: ../login.php");
    exit;
}

$usuarioId = $_SESSION["usuario_id"];

// =========================================================
// BUSCAR USUÁRIO
// =========================================================

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

// =========================================================
// BUSCAR FASES DO MATHCHEF
// =========================================================

$sqlFases = "
    SELECT
        f.id,
        f.nome,
        f.descricao,
        f.nivel_dificuldade,
        f.numero,

        COALESCE(p.concluida, 0) AS concluida,
        COALESCE(p.melhor_pontuacao, 0) AS melhor_pontuacao,
        COALESCE(p.xp_conquistado, 0) AS xp_conquistado

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

// =========================================================
// CONFIGURAÇÃO DE XP
// =========================================================
//
// A próxima fase será liberada quando a fase anterior
// possuir pelo menos 60 XP conquistados.
//
// O valor vem da tabela configuracao_mathchef.
// Caso a tabela não tenha registro, usamos 60 como
// valor de segurança.
//

$sqlConfiguracao = "
    SELECT xp_para_proxima_fase
    FROM configuracao_mathchef
    LIMIT 1
";

$stmtConfiguracao = $pdo->query($sqlConfiguracao);

$configuracao = $stmtConfiguracao->fetch();

$xpParaProximaFase = $configuracao
    ? (int) $configuracao["xp_para_proxima_fase"]
    : 60;

// =========================================================
// DEFINIR FASES DESBLOQUEADAS
// =========================================================
//
// REGRAS:
//
// 1. A primeira fase sempre está desbloqueada.
//
// 2. Uma fase concluída continua desbloqueada.
//
// 3. Uma fase ainda não concluída só será desbloqueada
//    quando a fase anterior tiver atingido o XP mínimo.
//
// 4. A pessoa pode jogar novamente uma fase concluída.
//
// =========================================================

$proximaDesbloqueada = true;

foreach ($fases as &$fase) {

    $fase["concluida"] = (bool) $fase["concluida"];

    $fase["xp_conquistado"] = (int) $fase["xp_conquistado"];

    // A fase atual recebe o estado calculado anteriormente.
    $fase["desbloqueada"] = $proximaDesbloqueada;

    // Se a fase já possui o XP necessário,
    // a próxima fase será liberada.
    if ($fase["xp_conquistado"] >= $xpParaProximaFase) {
        $proximaDesbloqueada = true;
    } else {
        $proximaDesbloqueada = false;
    }
}

unset($fase);

// =========================================================
// FUNÇÕES DE DIFICULDADE
// =========================================================

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

// =========================================================
// PROGRESSO
// =========================================================

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
        content="width=device-width, initial-scale=1.0">

    <title>MathChef | MathRun</title>

    <link
        rel="stylesheet"
        href="../assets/css/mathchef.css">


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

            <a href="../inicio.php" class="brand">
                MathRun
                </a>

                <nav class="main-nav">

                    <a href="../inicio.php">
                        Início
                    </a>

                    <a href="../conquistas.php">
                        Conquistas
                    </a>

                    <a href="../ranking.php">
                        Ranking
                    </a>

                    <a href="../perfil/perfil.php">
                        Perfil
                    </a>

                </nav>

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

                        <span class="detail-icon">
                            👨‍🍳
                        </span>

                        <span>
                            Modo aventura
                        </span>

                    </div>

                    <div class="hero-detail">

                        <span class="detail-icon">
                            🧮
                        </span>

                        <span>
                            Matemática
                        </span>

                    </div>

                    <div class="hero-detail">

                        <span class="detail-icon">
                            🏆
                        </span>

                        <span>
                            Ganhe pontos
                        </span>

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
                            <?= $fasesConcluidas ?>
                            de
                            <?= $totalFases ?>
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
                    style="width: <?= $porcentagemProgresso ?>%;"></div>

            </div>

        </section>

        <!-- LIVRO DE RECEITAS SOBRE A BANCADA -->
        <section class="phases-section recipe-room">
            <div class="section-heading recipe-heading">
                <div>
                    <span>O LIVRO DA CHEF</span>
                    <h2>Escolha sua próxima receita</h2>
                    <p>Abra uma página, descubra o doce e resolva os desafios para prepará-lo.</p>
                </div>
                <div class="book-stamp" aria-hidden="true">♡<small>feito com<br>matemática</small></div>
            </div>

            <div class="recipe-book" aria-label="Livro de receitas do MathChef">
                <div class="book-spine" aria-hidden="true"></div>
                <div class="recipe-pages">
                    <?php if (empty($fases)): ?>
                        <div class="empty-state"><div class="empty-icon">🍰</div><h3>O livro ainda está vazio</h3><p>Nenhuma fase foi encontrada para sua série.</p></div>
                    <?php else: ?>
                        <?php foreach ($fases as $fase): ?>
                            <?php
                                $desbloqueada = $fase["desbloqueada"];
                                $concluida = $fase["concluida"];
                                $xpConquistado = (int) $fase["xp_conquistado"];
                                $classeReceita = !$desbloqueada ? "recipe-locked" : ($concluida ? "recipe-completed" : "recipe-available");
                                $receitas = [
                                    1 => ["Bolo de chocolate", "🍰", "Um clássico fofinho para começar sua jornada. Acerte os desafios matemáticos e separe cada ingrediente na medida certa."],
                                    2 => ["Brownie de chocolate", "🍫", "Um brownie merece proporções perfeitas. Use seu raciocínio para ajustar quantidades e deixar tudo no ponto."],
                                    3 => ["Cupcakes de morango", "🧁", "Pequenos, delicados e cheios de detalhes. Resolva os desafios para calcular porções e preparar cada cupcake."],
                                    4 => ["Bolo de festa", "🎂", "A receita final da coleção! Combine seus conhecimentos para superar desafios mais elaborados e montar um bolo especial."]
                                ];
                                $receita = $receitas[(int)$fase["numero"]] ?? ["Doce surpresa", "🍪", "Uma nova receita espera por você. Resolva os desafios matemáticos para descobrir o resultado."];
                                $urlJogo = "jogar_mathchef.php?fase=" . (int)$fase["id"];
                            ?>
                            <button
                                type="button"
                                class="recipe-page <?= $classeReceita ?>"
                                <?= $desbloqueada ? '' : 'disabled' ?>
                                data-recipe-title="<?= htmlspecialchars($receita[0], ENT_QUOTES, 'UTF-8') ?>"
                                data-recipe-emoji="<?= htmlspecialchars($receita[1], ENT_QUOTES, 'UTF-8') ?>"
                                data-recipe-description="<?= htmlspecialchars($receita[2], ENT_QUOTES, 'UTF-8') ?>"
                                data-phase-description="<?= htmlspecialchars($fase["descricao"], ENT_QUOTES, 'UTF-8') ?>"
                                data-phase-number="<?= (int)$fase["numero"] ?>"
                                data-difficulty="<?= htmlspecialchars(nomeDificuldade($fase["nivel_dificuldade"]), ENT_QUOTES, 'UTF-8') ?>"
                                data-xp="<?= $xpConquistado ?>"
                                data-xp-needed="<?= $xpParaProximaFase ?>"
                                data-score="<?= (int)$fase["melhor_pontuacao"] ?>"
                                data-completed="<?= $concluida ? '1' : '0' ?>"
                                data-url="<?= htmlspecialchars($urlJogo, ENT_QUOTES, 'UTF-8') ?>"
                                aria-label="<?= htmlspecialchars($receita[0], ENT_QUOTES, 'UTF-8') ?>">
                                <span class="recipe-page-top"><span>RECEITA Nº <?= (int)$fase["numero"] ?></span>
                                    <?php if (!$desbloqueada): ?><span class="recipe-lock">🔒</span><?php elseif ($concluida): ?><span class="recipe-check">✓</span><?php else: ?><span class="recipe-open-dot">✦</span><?php endif; ?>
                                </span>
                                <span class="recipe-art" aria-hidden="true"><span class="recipe-art-plate"></span><span class="recipe-emoji"><?= $receita[1] ?></span>
                                    <?php if (!$desbloqueada): ?><span class="recipe-art-lock">🔒</span><?php endif; ?>
                                </span>
                                <span class="recipe-name"><?= htmlspecialchars($receita[0]) ?></span>
                                <span class="recipe-subtitle"><?= htmlspecialchars($fase["nome"]) ?></span>
                                <span class="recipe-page-bottom"><span><?= htmlspecialchars(nomeDificuldade($fase["nivel_dificuldade"])) ?></span><span><?= $concluida ? '✓ concluída' : ($desbloqueada ? 'abrir receita ↗' : 'bloqueada') ?></span></span>
                            </button>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <div class="book-bottom-note"><span>✦</span> Cada desafio é um ingrediente para sua próxima conquista <span>✦</span></div>
            </div>
        </section>

        <!-- QUADRO DE DETALHES DA RECEITA -->
        <div class="recipe-modal" id="recipeModal" aria-hidden="true">
            <button type="button" class="recipe-modal-backdrop" data-close-recipe aria-label="Fechar receita"></button>
            <section class="recipe-detail" role="dialog" aria-modal="true" aria-labelledby="detailTitle">
                <button type="button" class="recipe-close" data-close-recipe aria-label="Fechar">×</button>
                <div class="detail-art" aria-hidden="true"><span class="detail-spark spark-one">✦</span><span id="detailEmoji">🍰</span><span class="detail-spark spark-two">✧</span></div>
                <div class="detail-copy">
                    <span class="detail-eyebrow" id="detailNumber">RECEITA Nº 01</span>
                    <h2 id="detailTitle">Bolo de chocolate</h2>
                    <p class="detail-description" id="detailDescription"></p>
                    <div class="detail-math"><span class="detail-math-icon">🧮</span><div><strong>Seu desafio matemático</strong><p id="detailPhaseDescription"></p></div></div>
                    <div class="detail-meta"><span id="detailDifficulty">Fácil</span><span id="detailProgress">⭐ 0 / 60 XP</span><span id="detailScore" hidden></span></div>
                    <p class="detail-status" id="detailStatus"></p>
                    <a href="#" class="detail-start" id="detailStart">Começar a cozinhar <span>→</span></a>
                </div>
            </section>
        </div>

        <!-- ======================================
         VOLTAR
    ======================================= -->

        <div class="back-area">

            <a
                href="../inicio.php"
                class="back-link">
                ← Voltar para o início
            </a>

        </div>

    </main>

    <script src="../assets/js/tema.js"></script>
    <script>
    (() => {
        const modal = document.getElementById('recipeModal');
        if (!modal) return;
        const title = document.getElementById('detailTitle');
        const emoji = document.getElementById('detailEmoji');
        const description = document.getElementById('detailDescription');
        const phaseDescription = document.getElementById('detailPhaseDescription');
        const number = document.getElementById('detailNumber');
        const difficulty = document.getElementById('detailDifficulty');
        const progress = document.getElementById('detailProgress');
        const score = document.getElementById('detailScore');
        const status = document.getElementById('detailStatus');
        const start = document.getElementById('detailStart');
        let previousFocus = null;
        document.querySelectorAll('.recipe-page:not(:disabled)').forEach(page => {
            page.addEventListener('click', () => {
                previousFocus = page;
                title.textContent = page.dataset.recipeTitle;
                emoji.textContent = page.dataset.recipeEmoji;
                description.textContent = page.dataset.recipeDescription;
                phaseDescription.textContent = page.dataset.phaseDescription;
                number.textContent = `RECEITA Nº ${String(page.dataset.phaseNumber).padStart(2, '0')}`;
                difficulty.textContent = `Dificuldade: ${page.dataset.difficulty}`;
                progress.textContent = `⭐ ${page.dataset.xp} / ${page.dataset.xpNeeded} XP`;
                if (Number(page.dataset.score) > 0) {
                    score.hidden = false;
                    score.textContent = `🏆 ${page.dataset.score} pontos`;
                } else { score.hidden = true; }
                status.textContent = page.dataset.completed === '1' ? 'Receita concluída! Você pode prepará-la novamente para melhorar sua pontuação.' : 'Prepare os ingredientes resolvendo os desafios desta fase.';
                start.href = page.dataset.url;
                start.innerHTML = page.dataset.completed === '1' ? 'Preparar novamente <span>→</span>' : 'Começar a cozinhar <span>→</span>';
                modal.classList.add('is-open');
                modal.setAttribute('aria-hidden', 'false');
                document.body.classList.add('recipe-modal-open');
                modal.querySelector('.recipe-close').focus();
            });
        });
        const closeModal = () => {
            modal.classList.remove('is-open');
            modal.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('recipe-modal-open');
            if (previousFocus) previousFocus.focus();
        };
        modal.querySelectorAll('[data-close-recipe]').forEach(button => button.addEventListener('click', closeModal));
        document.addEventListener('keydown', event => { if (event.key === 'Escape' && modal.classList.contains('is-open')) closeModal(); });
    })();
    </script>


</body>

</html>