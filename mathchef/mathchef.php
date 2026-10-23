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

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>MathChef | MathRun</title>

    <link rel="stylesheet" href="../assets/css/mathchef.css">


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

                <button type="button" data-theme-option="light" title="Tema claro">
                    ☀
                </button>

                <button type="button" data-theme-option="dark" title="Tema escuro">
                    ☾
                </button>

                <button type="button" data-theme-option="pink" title="Tema rosa">
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

                <div class="progress-fill" style="width: <?= $porcentagemProgresso ?>%;"></div>

            </div>

        </section>

        <!-- ======================================
         FASES
    ======================================= -->

        <section class="phases-section recipe-room">
            <div class="section-heading recipe-room-heading">
                <div>
                    <span>O LIVRO DE RECEITAS DA CHEF</span>
                    <h2>Escolha sua próxima receita</h2>
                    <p>Abra uma página do caderno para conhecer o desafio antes de começar.</p>
                </div>
                <div class="book-stamp" aria-hidden="true">✿<small>feito com<br>matemática</small></div>
            </div>

            <?php if (empty($fases)): ?>
                <div class="empty-state">
                    <div class="empty-icon">📖</div>
                    <h3>Este caderno ainda está vazio</h3>
                    <p>Nenhuma receita foi encontrada para sua série.</p>
                </div>
            <?php else: ?>
                <div class="recipe-book-shell">
                    <div class="recipe-book-spine" aria-hidden="true"><span>MATHCHEF · RECEITAS</span></div>
                    <div class="recipe-book-pages">
                        <?php foreach ($fases as $indice => $fase): ?>
                            <?php
                            $desbloqueada = (bool) $fase["desbloqueada"];
                            $concluida = (bool) $fase["concluida"];
                            $xpConquistado = (int) $fase["xp_conquistado"];
                            $classeCard = !$desbloqueada ? "locked" : ($concluida ? "completed" : "available");
                            $imagensReceita = ["../assets/img/chef_fracoes.jpg", "../assets/img/sem_derrubar.jpg", "../assets/img/primeira_receita.jpg", "../assets/img/mestre_cozinha.jpg"];
                            $imagemReceita = $imagensReceita[$indice % count($imagensReceita)];
                            $legendaReceita = trim((string) $fase["descricao"]);
                            if ($legendaReceita === "")
                                $legendaReceita = "Prepare os ingredientes, use a matemática e complete este desafio culinário.";
                            ?>
                            <button type="button" class="recipe-page <?= $classeCard ?>" data-recipe-open
                                data-recipe-id="<?= (int) $fase["id"] ?>"
                                data-recipe-name="<?= htmlspecialchars($fase["nome"], ENT_QUOTES, 'UTF-8') ?>"
                                data-recipe-description="<?= htmlspecialchars($legendaReceita, ENT_QUOTES, 'UTF-8') ?>"
                                data-recipe-image="<?= htmlspecialchars($imagemReceita, ENT_QUOTES, 'UTF-8') ?>"
                                data-recipe-number="<?= (int) $fase["numero"] ?>"
                                data-recipe-difficulty="<?= htmlspecialchars(nomeDificuldade($fase["nivel_dificuldade"]), ENT_QUOTES, 'UTF-8') ?>"
                                data-recipe-xp="<?= $xpConquistado ?>" data-recipe-required="<?= $xpParaProximaFase ?>"
                                data-recipe-unlocked="<?= $desbloqueada ? '1' : '0' ?>"
                                data-recipe-completed="<?= $concluida ? '1' : '0' ?>"
                                data-recipe-score="<?= (int) $fase["melhor_pontuacao"] ?>"
                                aria-label="<?= $desbloqueada ? 'Abrir receita ' . htmlspecialchars($fase["nome"], ENT_QUOTES, 'UTF-8') : 'Receita bloqueada: ' . htmlspecialchars($fase["nome"], ENT_QUOTES, 'UTF-8') ?>">
                                <span
                                    class="recipe-page-number"><?= !$desbloqueada ? '🔒' : ($concluida ? '✓' : sprintf('%02d', (int) $fase["numero"])) ?></span>
                                <span class="recipe-photo-wrap"><img
                                        src="<?= htmlspecialchars($imagemReceita, ENT_QUOTES, 'UTF-8') ?>"
                                        alt="Ilustração culinária da receita" loading="lazy"><span
                                        class="recipe-photo-label">RECEITA
                                        <?= sprintf('%02d', (int) $fase["numero"]) ?></span></span>
                                <span
                                    class="recipe-page-title"><?= htmlspecialchars($fase["nome"], ENT_QUOTES, 'UTF-8') ?></span>
                                <span
                                    class="recipe-page-meta"><span><?= nomeDificuldade($fase["nivel_dificuldade"]) ?></span><span><?= !$desbloqueada ? 'Bloqueada' : ($concluida ? 'Concluída ✓' : 'Pronta para fazer') ?></span></span>
                                <?php if ($desbloqueada): ?><span class="recipe-page-xp">✦ <?= $xpConquistado ?>
                                        XP</span><?php else: ?><span class="recipe-page-xp locked-hint">🔒 <?= $xpParaProximaFase ?>
                                        XP na fase anterior</span><?php endif; ?>
                                <span class="recipe-open-hint"><?= $desbloqueada ? 'Abrir receita ↗' : 'Ver bloqueio' ?></span>
                            </button>
                        <?php endforeach; ?>
                    </div>
                    <div class="book-bottom-note">✧ UMA PITADA DE MATEMÁTICA, UMA RECEITA DE CADA VEZ ✧</div>
                </div>
            <?php endif; ?>
        </section>

        <div class="recipe-modal" id="recipeModal" aria-hidden="true" role="dialog" aria-modal="true"
            aria-labelledby="recipeModalTitle">
            <button type="button" class="recipe-modal-backdrop" data-recipe-close aria-label="Fechar receita"></button>
            <section class="recipe-modal-card">
                <button type="button" class="recipe-modal-close" data-recipe-close aria-label="Fechar">×</button>
                <div class="recipe-modal-image-wrap"><img id="recipeModalImage" src="../assets/img/chef_fracoes.jpg"
                        alt="Imagem da receita"><span class="modal-recipe-number" id="recipeModalNumber">RECEITA
                        01</span></div>
                <div class="recipe-modal-copy">
                    <span class="modal-eyebrow">DO CADERNO PARA A BANCADA</span>
                    <h2 id="recipeModalTitle">Nome da receita</h2>
                    <p id="recipeModalDescription">Descrição da receita.</p>
                    <div class="recipe-modal-facts"><span>◷ Desafio matemático</span><span
                            id="recipeModalDifficulty">Dificuldade</span><span id="recipeModalProgress">0 XP</span>
                    </div>
                    <p class="recipe-modal-locked" id="recipeModalLocked" hidden>Esta receita ainda está fechada.
                        Conquiste o XP necessário na fase anterior para abrir esta página do caderno.</p>
                    <a class="recipe-modal-start" id="recipeModalStart" href="#">Começar a cozinhar <span>→</span></a>
                </div>
            </section>
        </div>

        <!-- ======================================
         VOLTAR
    ======================================= -->

        <div class="back-area">

            <a href="../inicio.php" class="back-link">
                ← Voltar para o início
            </a>

        </div>

    </main>

    <script>
        (() => {
            const modal = document.getElementById('recipeModal');
            if (!modal) return;
            const title = document.getElementById('recipeModalTitle');
            const description = document.getElementById('recipeModalDescription');
            const image = document.getElementById('recipeModalImage');
            const number = document.getElementById('recipeModalNumber');
            const difficulty = document.getElementById('recipeModalDifficulty');
            const progress = document.getElementById('recipeModalProgress');
            const lockedMessage = document.getElementById('recipeModalLocked');
            const start = document.getElementById('recipeModalStart');
            let lastTrigger = null;

            document.querySelectorAll('[data-recipe-open]').forEach((button) => {
                button.addEventListener('click', () => {
                    lastTrigger = button;
                    title.textContent = button.dataset.recipeName || 'Receita';
                    description.textContent = button.dataset.recipeDescription || 'Um novo desafio culinário espera por você.';
                    image.src = button.dataset.recipeImage || '../assets/img/chef_fracoes.jpg';
                    image.alt = 'Ilustração da receita ' + title.textContent;
                    number.textContent = 'RECEITA ' + String(button.dataset.recipeNumber || '1').padStart(2, '0');
                    difficulty.textContent = 'Dificuldade: ' + (button.dataset.recipeDifficulty || 'Nível');
                    const unlocked = button.dataset.recipeUnlocked === '1';
                    const completed = button.dataset.recipeCompleted === '1';
                    progress.textContent = unlocked ? (button.dataset.recipeXp || '0') + ' XP conquistados' : 'Receita bloqueada';
                    lockedMessage.hidden = unlocked;
                    start.hidden = !unlocked;
                    start.href = 'jogar_mathchef.php?fase=' + encodeURIComponent(button.dataset.recipeId || '');
                    start.innerHTML = (completed ? 'Preparar novamente' : 'Começar a cozinhar') + ' <span>→</span>';
                    modal.classList.add('is-open');
                    modal.setAttribute('aria-hidden', 'false');
                    document.body.classList.add('recipe-modal-open');
                    modal.querySelector('.recipe-modal-close').focus();
                });
            });
            const closeModal = () => {
                modal.classList.remove('is-open');
                modal.setAttribute('aria-hidden', 'true');
                document.body.classList.remove('recipe-modal-open');
                if (lastTrigger) lastTrigger.focus();
            };
            modal.querySelectorAll('[data-recipe-close]').forEach((button) => button.addEventListener('click', closeModal));
            document.addEventListener('keydown', (event) => { if (event.key === 'Escape' && modal.classList.contains('is-open')) closeModal(); });
        })();
    </script>

    <script src="../assets/js/tema.js"></script>


</body>

</html>