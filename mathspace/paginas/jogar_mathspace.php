<?php
// MathSpace - tela imersiva da partida.
if (session_status() !== PHP_SESSION_ACTIVE)
    session_start();
$configPath = __DIR__ . '/../../config/config.php';
if (!is_file($configPath)) {
    http_response_code(500);
    exit('Não encontrei config/config.php. Confira a localização da pasta MathRun.');
}
require_once $configPath;

$usuarioId = (int) ($_SESSION['usuario_id'] ?? $_SESSION['id_usuario'] ?? $_SESSION['user_id'] ?? $_SESSION['id'] ?? 0);
$faseId = filter_input(INPUT_GET, 'fase', FILTER_VALIDATE_INT) ?: 0;
if ($usuarioId < 1) {
    http_response_code(401);
    exit('Você precisa entrar na sua conta do MathRun para jogar. Faça login e abra a missão novamente.');
}
if ($faseId < 1) {
    http_response_code(400);
    exit('Fase inválida. Volte ao mapa do MathSpace e selecione uma missão.');
}

$db = null;
foreach (['pdo', 'conn', 'conexao', 'conexaoPDO', 'mysqli'] as $candidate) {
    if (isset($$candidate) && ($$candidate instanceof PDO || $$candidate instanceof mysqli)) {
        $db = $$candidate;
        break;
    }
}
if (!$db) {
    http_response_code(500);
    exit('Não encontrei uma conexão PDO ou MySQLi no config/config.php. Veja o README incluído com os arquivos.');
}

try {
    if ($db instanceof PDO) {
        $stmt = $db->prepare('SELECT f.id, f.nome, f.descricao, f.nivel_dificuldade, f.numero, f.serie FROM fases f JOIN jogos j ON j.id=f.jogo_id WHERE f.id=? AND j.nome="MathSpace" LIMIT 1');
        $stmt->execute([$faseId]);
        $fase = $stmt->fetch(PDO::FETCH_ASSOC);
    } else {
        $stmt = $db->prepare('SELECT f.id, f.nome, f.descricao, f.nivel_dificuldade, f.numero, f.serie FROM fases f JOIN jogos j ON j.id=f.jogo_id WHERE f.id=? AND j.nome="MathSpace" LIMIT 1');
        $stmt->bind_param('i', $faseId);
        $stmt->execute();
        $fase = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    }
} catch (Throwable $e) {
    $fase = false;
}
if (!$fase) {
    http_response_code(404);
    exit('Essa missão do MathSpace não foi encontrada.');
}

$_SESSION['mathspace_csrf'] = $_SESSION['mathspace_csrf'] ?? bin2hex(random_bytes(32));
$payload = [
    'faseId' => (int) $fase['id'],
    'nome' => $fase['nome'],
    'descricao' => $fase['descricao'] ?? '',
    'numero' => (int) $fase['numero'],
    'dificuldade' => $fase['nivel_dificuldade'],
    'serie' => (int) $fase['serie'],
    'api' => '../endpoints/missao.php',
    'csrf' => $_SESSION['mathspace_csrf'],
    'mapa' => 'mathspace.php'
];
?><!doctype html>
<html lang="pt-BR" data-theme="dark">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#080b20">
    <title>MathSpace | <?= htmlspecialchars($fase['nome'], ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="stylesheet" href="../css/jogar_mathspace.css">
    <script src="https://cdn.jsdelivr.net/npm/phaser@3.90.0/dist/phaser.min.js" defer></script>
    <script>window.MATHSPACE_GAME = <?= json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;</script>
    <script src="../js/jogar_mathspace.js" defer></script>
</head>

<body>
    <main id="game-shell" class="game-shell" aria-label="Partida MathSpace">
        <div id="space-canvas" class="space-canvas" aria-hidden="true"></div>
        <div class="starfield" aria-hidden="true"></div>
        <header class="hud">
            <div class="hud-brand"><span class="brand-mark">✦</span>
                <div><strong>MATHSPACE</strong><small id="mission-label">MISSÃO
                        <?= str_pad((string) $fase['numero'], 2, '0', STR_PAD_LEFT) ?></small></div>
            </div>
            <div class="hud-center"><span class="hud-caption">PONTUAÇÃO</span><strong id="score-value">0000</strong>
            </div>
            <div class="integrity-widget" aria-label="Integridade da nave"><span>INTEGRIDADE</span>
                <div class="integrity-track"><span id="integrity-fill"></span></div><strong
                    id="integrity-label">100%</strong>
            </div>
            <div class="hud-right">
                <div class="lives" id="lives" aria-label="3 vidas"><span>♥</span><span>♥</span><span>♥</span></div>
                <button id="pause-button" class="icon-button" type="button" aria-label="Pausar jogo">Ⅱ</button><button
                    id="exit-button" class="icon-button exit-button" type="button"
                    aria-label="Sair da missão">×</button>
            </div>
        </header>
        <section id="mission-intro" class="overlay intro-overlay" aria-labelledby="intro-title">
            <div class="intro-card">
                <div class="eyebrow">TRANSMISSÃO RECEBIDA · SÉRIE <?= (int) $fase['serie'] ?>º ANO</div>
                <div class="intro-planet" aria-hidden="true">🪐</div>
                <h1 id="intro-title"><?= htmlspecialchars($fase['nome'], ENT_QUOTES, 'UTF-8') ?></h1>
                <p>
                    <?= htmlspecialchars($fase['descricao'] ?: 'Explore o setor e resolva os desafios para concluir a missão.', ENT_QUOTES, 'UTF-8') ?>
                </p>
                <div class="mission-facts"><span>♥ 3 vidas</span><span>◎ 5 questões + desafio</span><span>↗ Disparo
                        automático</span></div><button id="start-button" class="primary-button" type="button">INICIAR
                    MISSÃO
                    <span>↗</span></button><small class="control-hint">Mouse ou dedo: mova a nave para os lados.</small>
            </div>
        </section>
        <section class="question-panel" id="question-panel" aria-live="polite" hidden>
            <div class="question-top"><span id="question-step">QUESTÃO 1/6</span><span id="question-timer"
                    class="question-timer" role="timer" aria-live="off">20s</span><span id="question-type"
                    class="question-type">TRANSMISSÃO</span></div>
            <div class="question-progress"><span id="question-progress-fill"></span></div>
            <h2 id="question-text">Estabelecendo conexão com a base...</h2>
            <div id="answer-list" class="answer-list"></div>
            <div id="answer-feedback" class="answer-feedback" role="status"></div>
            <button id="continue-button" class="primary-button continue-button" type="button" hidden>CONTINUAR
                <span>→</span></button>
        </section>
        <div class="flight-tip" id="flight-tip">← MOUSE / TOQUE → <span>movimento horizontal</span></div>
        <div class="mission-alert" id="mission-alert" role="status" aria-live="polite"></div>
        <section id="pause-overlay" class="overlay" hidden>
            <div class="result-card">
                <div class="result-symbol">Ⅱ</div>
                <div class="eyebrow">PAUSA DE MISSÃO</div>
                <h2>Respire, piloto.</h2>
                <p>A nave está segura enquanto a missão está pausada.</p><button id="resume-button"
                    class="primary-button" type="button">RETOMAR MISSÃO</button><button id="pause-exit-button"
                    class="secondary-button" type="button">SAIR PARA O MAPA</button>
            </div>
        </section>
        <section id="result-overlay" class="overlay" hidden>
            <div class="result-card">
                <div id="result-symbol" class="result-symbol">✦</div>
                <div id="result-eyebrow" class="eyebrow">RELATÓRIO DE MISSÃO</div>
                <h2 id="result-title">Missão concluída</h2>
                <p id="result-message"></p>
                <div class="result-stats">
                    <div><strong id="result-score">0</strong><span>PONTOS</span></div>
                    <div><strong id="result-correct">0/6</strong><span>ACERTOS</span></div>
                    <div><strong id="result-xp">0</strong><span>XP</span></div>
                </div><button id="retry-button" class="primary-button" type="button">TENTAR NOVAMENTE</button><a
                    class="secondary-button" href="mathspace.php">VOLTAR AO MAPA</a>
            </div>
        </section>
    </main>
</body>

</html>