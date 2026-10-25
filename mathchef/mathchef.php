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

    WHERE f.jogo_id = (SELECT id FROM jogos WHERE nome = 'MathChef' LIMIT 1)
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
<?php
$configMathChef = [
    'serie' => $serieUsuario,
    'totalFases' => $totalFases,
    'fasesConcluidas' => $fasesConcluidas,
    'fases' => array_map(function ($fase) {
        return [
            'id' => (int)$fase['id'],
            'nome' => (string)$fase['nome'],
            'descricao' => (string)$fase['descricao'],
            'dificuldade' => (string)$fase['nivel_dificuldade'],
            'numero' => (int)$fase['numero'],
            'concluida' => (bool)$fase['concluida'],
            'desbloqueada' => (bool)$fase['desbloqueada'],
            'xp' => (int)$fase['xp_conquistado'],
            'pontuacao' => (int)$fase['melhor_pontuacao']
        ];
    }, $fases)
];
$configMathChefJson = json_encode($configMathChef, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
if ($configMathChefJson === false) $configMathChefJson = '{"serie":0,"totalFases":0,"fasesConcluidas":0,"fases":[]}';
?>


<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#f2cba0">
    <title>MathChef | MathRun</title>
    <link rel="stylesheet" href="../assets/css/mathchef.css">
    <script>
        window.MATHCHEF_CONFIG = <?= $configMathChefJson ?>;
    </script>
    <script src="https://cdn.jsdelivr.net/npm/phaser@3.90.0/dist/phaser.min.js" defer></script>
    <script src="../assets/js/mathchef.js" defer></script>
    <script src="../assets/js/tema.js" defer></script>
</head>

<body class="chef-game-page">
    <div class="game-shell">
        <header class="game-topbar">
            <a class="brand" href="../inicio.php" aria-label="Voltar ao MathRun"><span class="brand-icon">M<span>✦</span></span><span>MATH<span class="brand-accent">CHEF</span></span></a>
            <div class="kitchen-status"><span class="status-light"></span> COZINHA ABERTA <i></i> <span>CHEF DO <?= (int)$serieUsuario ?>º ANO</span></div>
            <div class="top-actions">
                <div class="theme-switcher" aria-label="Escolher tema">
                    <button type="button" data-theme-option="light" title="Tema claro">☀</button>
                    <button type="button" data-theme-option="dark" title="Tema escuro">☾</button>
                    <button type="button" data-theme-option="pink" title="Tema rosa">♡</button>
                </div>
                <a class="exit-link" href="../inicio.php" title="Voltar ao início">↩ <span>SAIR</span></a>
            </div>
        </header>
        <main class="game-main">
            <section class="game-hud">
                <div class="hud-copy"><span class="eyebrow"><span></span> CADERNO DE RECEITAS</span>
                    <h1>Escolha sua <em>próxima receita</em></h1>
                    <p>Abra o livro, escolha um doce e resolva os desafios para prepará-lo.</p>
                </div>
                <div class="progress-module">
                    <div class="progress-head"><span>PROGRESSO DO CHEF</span><strong><?= (int)round($porcentagemProgresso) ?>%</strong></div>
                    <div class="progress-track"><span style="width: <?= (float)$porcentagemProgresso ?>%"></span></div>
                    <div class="progress-foot"><span><?= (int)$fasesConcluidas ?> de <?= (int)$totalFases ?> receitas</span><span>✦ <?= number_format(array_sum(array_map(fn($f) => (int)$f['xp_conquistado'], $fases)), 0, ',', '.') ?> XP</span></div>
                </div>
            </section>
            <?php if (empty($fases)): ?>
                <div class="system-note">🍰 Nenhuma receita foi cadastrada para sua série ainda.</div>
            <?php endif; ?>
            <section class="kitchen-frame" aria-label="Cozinha interativa do MathChef">
                <div class="frame-topline"><span><i></i> ESTAÇÃO DE PREPARO</span><span class="frame-coordinates">MC / <?= (int)$serieUsuario ?>.<?= str_pad((string)$totalFases, 2, '0', STR_PAD_LEFT) ?></span></div>
                <div class="phaser-viewport" id="chef-game" role="application" aria-label="Cozinha 2D interativa com livro de receitas">
                    <div class="game-loading" id="game-loading"><span class="loading-spinner"></span> PREPARANDO A COZINHA...</div>
                </div>
                <div class="frame-bottomline"><span><i class="status-dot"></i> ESTAÇÃO PRONTA</span><span>CLIQUE EM UMA PÁGINA PARA VER A RECEITA</span><span><?= (int)$fasesConcluidas ?>/<?= (int)$totalFases ?> CONCLUÍDAS</span></div>
            </section>
            <section class="game-legend" aria-label="Legenda do livro"><span><i class="legend-open"></i> Disponível</span><span><i class="legend-done">✓</i> Concluída</span><span><i class="legend-locked">▣</i> Bloqueada</span><span class="legend-tip">Dica: receitas concluídas podem ser jogadas novamente.</span></section>
        </main>
        <footer class="game-footer"><span>MATHRUN <b>×</b> MATHCHEF</span><span>MATEMÁTICA COM UMA PITADA DE DIVERSÃO</span><a href="../inicio.php">VOLTAR AO INÍCIO ↗</a></footer>
    </div>
    <div class="recipe-modal" id="recipe-modal" aria-hidden="true">
        <button class="modal-backdrop" type="button" data-close-modal aria-label="Fechar receita"></button>
        <section class="recipe-card" role="dialog" aria-modal="true" aria-labelledby="recipe-title">
            <button class="modal-close" type="button" data-close-modal aria-label="Fechar">×</button>
            <div class="recipe-art" id="recipe-art"><small>RECEITA DO CADERNO</small></div>
            <div class="recipe-info">
                <div class="recipe-overline"><span id="recipe-number">RECEITA 01</span><span id="recipe-status">DISPONÍVEL</span></div>
                <h2 id="recipe-title">Bolo de chocolate</h2>
                <p id="recipe-description"></p>
                <div class="recipe-stats"><span><small>DIFICULDADE</small><strong id="recipe-difficulty">Fácil</strong></span><span><small>XP CONQUISTADO</small><strong id="recipe-xp">0 XP</strong></span><span><small>MELHOR PONTUAÇÃO</small><strong id="recipe-score">0</strong></span></div>
                <div class="recipe-math"><span>✎</span>
                    <div><strong>O desafio desta receita</strong>
                        <p id="recipe-math-description">Resolva os desafios matemáticos para reunir os ingredientes.</p>
                    </div>
                </div><a id="start-recipe" class="start-recipe" href="#">COMEÇAR A COZINHAR <span>→</span></a>
                <div id="locked-message" class="locked-message" hidden>Conquiste XP na receita anterior para liberar esta página.</div>
            </div>
        </section>
    </div>
</body>

</html>