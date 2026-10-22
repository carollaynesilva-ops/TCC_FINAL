<?php
// MathSpace: tela da partida.
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$configPath = __DIR__ . '/../../config/config.php';

if (!is_file($configPath)) {
    http_response_code(500);
    exit('Não encontrei config/config.php. Confira a localização da pasta MathRun.');
}

require_once $configPath;

$usuarioId = (int) (
    $_SESSION['usuario_id']
    ?? $_SESSION['id_usuario']
    ?? $_SESSION['user_id']
    ?? $_SESSION['id']
    ?? 0
);

$faseId = filter_input(INPUT_GET, 'fase', FILTER_VALIDATE_INT) ?: 0;

if ($usuarioId < 1) {
    http_response_code(401);
    exit('Você precisa entrar na sua conta do MathRun para jogar.');
}

if ($faseId < 1) {
    http_response_code(400);
    exit('Fase inválida. Volte ao mapa do MathSpace.');
}

// Localiza a conexão com o banco definida em config.php.
$db = null;

foreach (['pdo', 'conn', 'conexao', 'conexaoPDO', 'mysqli'] as $candidate) {
    if (
        isset($$candidate)
        && ($$candidate instanceof PDO || $$candidate instanceof mysqli)
    ) {
        $db = $$candidate;
        break;
    }
}

if (!$db) {
    http_response_code(500);
    exit('Não encontrei uma conexão PDO ou MySQLi no arquivo config.php.');
}

// Busca a fase selecionada.
try {
    $sql = '
        SELECT
            f.id,
            f.nome,
            f.descricao,
            f.nivel_dificuldade,
            f.numero,
            f.serie
        FROM fases f
        INNER JOIN jogos j ON j.id = f.jogo_id
        WHERE f.id = ?
          AND j.nome = "MathSpace"
        LIMIT 1
    ';

    if ($db instanceof PDO) {
        $stmt = $db->prepare($sql);
        $stmt->execute([$faseId]);
        $fase = $stmt->fetch(PDO::FETCH_ASSOC);
    } else {
        $stmt = $db->prepare($sql);
        $stmt->bind_param('i', $faseId);
        $stmt->execute();
        $fase = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    }
} catch (Throwable $e) {
    error_log('MathSpace: ' . $e->getMessage());
    $fase = false;
}

if (!$fase) {
    http_response_code(404);
    exit('Essa missão do MathSpace não foi encontrada.');
}

// Token de segurança para as requisições do jogo.
$_SESSION['mathspace_csrf'] =
    $_SESSION['mathspace_csrf'] ?? bin2hex(random_bytes(32));

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
?>
<!doctype html>
<html lang="pt-BR" data-theme="dark">

<head>
    <meta charset="utf-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1, viewport-fit=cover">

    <meta name="theme-color" content="#080b20">

    <title>
        MathSpace |
        <?= htmlspecialchars($fase['nome'], ENT_QUOTES, 'UTF-8') ?>
    </title>

    <link rel="stylesheet" href="../css/jogar_mathspace.css">

    <script
        src="https://cdn.jsdelivr.net/npm/phaser@3.90.0/dist/phaser.min.js"
        defer></script>

    <script>
        window.MATHSPACE_GAME = <?= json_encode(
                                    $payload,
                                    JSON_UNESCAPED_UNICODE
                                        | JSON_UNESCAPED_SLASHES
                                        | JSON_HEX_TAG
                                        | JSON_HEX_APOS
                                        | JSON_HEX_QUOT
                                        | JSON_HEX_AMP
                                ) ?>;
    </script>

    <script src="../js/jogar_mathspace.js" defer></script>
</head>

<body>

    <main id="game-shell" class="game-shell" aria-label="Partida MathSpace">

        <!-- O Phaser desenha a nave, os aliens, os lasers e os projéteis. -->
        <div id="space-canvas" class="space-canvas" aria-hidden="true"></div>

        <div class="starfield" aria-hidden="true"></div>

        <!-- PAINEL SUPERIOR DO JOGO -->
        <header class="hud">

            <div class="hud-brand">
                <span class="brand-mark">✦</span>

                <div>
                    <strong>MATHSPACE</strong>

                    <small>
                        MISSÃO
                        <?= str_pad((string) $fase['numero'], 2, '0', STR_PAD_LEFT) ?>
                    </small>
                </div>
            </div>

            <div class="hud-center">
                <span class="hud-caption">PONTUAÇÃO</span>
                <strong id="score-value">0000</strong>
            </div>

            <div class="hud-right">

                <div
                    class="lives"
                    id="lives"
                    aria-label="3 vidas">
                    <span>♥</span>
                    <span>♥</span>
                    <span>♥</span>
                </div>

                <button
                    id="pause-button"
                    class="icon-button"
                    type="button"
                    aria-label="Pausar jogo">
                    Ⅱ
                </button>

                <button
                    id="exit-button"
                    class="icon-button exit-button"
                    type="button"
                    aria-label="Sair da missão">
                    ×
                </button>

            </div>
        </header>

        <!-- BARRA DE INTEGRIDADE DA NAVE -->
        <section
            id="ship-status"
            class="ship-status"
            aria-label="Integridade da nave">
            <div class="ship-status-label">
                <span>INTEGRIDADE DA NAVE</span>
                <strong id="shield-value">100%</strong>
            </div>

            <div
                class="shield-track"
                role="progressbar"
                aria-label="Integridade"
                aria-valuemin="0"
                aria-valuemax="100"
                aria-valuenow="100"
                id="shield-track">
                <span id="shield-fill"></span>
            </div>
        </section>

        <!-- TELA INICIAL -->
        <section
            id="mission-intro"
            class="overlay intro-overlay"
            aria-labelledby="intro-title">
            <div class="intro-card">

                <div class="eyebrow">
                    TRANSMISSÃO RECEBIDA · SÉRIE
                    <?= (int) $fase['serie'] ?>º ANO
                </div>

                <div class="intro-planet" aria-hidden="true">🪐</div>

                <h1 id="intro-title">
                    <?= htmlspecialchars($fase['nome'], ENT_QUOTES, 'UTF-8') ?>
                </h1>

                <p>
                    <?= htmlspecialchars(
                        $fase['descricao']
                            ?: 'Explore o setor, enfrente os aliens e resolva os desafios matemáticos.',
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </p>

                <div class="mission-facts">
                    <span>♥ 3 vidas</span>
                    <span>◈ 100% de integridade</span>
                    <span>↗ Disparo automático</span>
                </div>

                <button
                    id="start-button"
                    class="primary-button"
                    type="button">
                    INICIAR MISSÃO <span>↗</span>
                </button>

                <small class="control-hint">
                    Mouse ou dedo: mova a nave para os lados.
                </small>

            </div>
        </section>

        <!--
        PAINEL DE QUESTÃO.
        Começa escondido. O JavaScript deverá abri-lo
        somente quando o laser atingir um alien.
    -->
        <section
            class="question-panel"
            id="question-panel"
            aria-live="polite"
            aria-labelledby="question-text"
            hidden>

            <div class="question-top">

                <span id="question-step">TRANSMISSÃO INIMIGA</span>

                <span id="question-type" class="question-type">
                    DESAFIO MATEMÁTICO
                </span>

            </div>

            <!-- CRONÔMETRO DE 20 SEGUNDOS -->
            <div class="question-timer">

                <div class="timer-label">
                    <span>TEMPO PARA RESPONDER</span>
                    <strong id="timer-value">20s</strong>
                </div>

                <div
                    id="timer-track"
                    class="timer-track"
                    role="progressbar"
                    aria-label="Tempo restante"
                    aria-valuemin="0"
                    aria-valuemax="20"
                    aria-valuenow="20">
                    <span id="timer-fill"></span>
                </div>

            </div>

            <h2 id="question-text">
                Aguardando transmissão inimiga...
            </h2>

            <div id="answer-list" class="answer-list"></div>

            <div
                id="answer-feedback"
                class="answer-feedback"
                role="status"></div>

            <!--
            O combate deverá continuar automaticamente depois
            da resposta. Não precisamos de um botão de próxima questão.
        -->
            <button
                id="continue-button"
                class="primary-button continue-button"
                type="button"
                hidden>
                CONTINUAR <span>→</span>
            </button>

        </section>

        <!-- DICA DE MOVIMENTAÇÃO -->
        <div class="flight-tip" id="flight-tip">
            ← MOUSE / TOQUE →
            <span>movimento horizontal</span>
        </div>

        <!-- AVISOS TEMPORÁRIOS -->
        <div
            class="mission-alert"
            id="mission-alert"
            role="status"
            aria-live="polite"></div>

        <!-- TELA DE PAUSA -->
        <section id="pause-overlay" class="overlay" hidden>

            <div class="result-card">

                <div class="result-symbol">Ⅱ</div>

                <div class="eyebrow">PAUSA DE MISSÃO</div>

                <h2>Respire, piloto.</h2>

                <p>
                    Os disparos foram interrompidos enquanto a missão está pausada.
                </p>

                <button
                    id="resume-button"
                    class="primary-button"
                    type="button">
                    RETOMAR MISSÃO
                </button>

                <button
                    id="pause-exit-button"
                    class="secondary-button"
                    type="button">
                    SAIR PARA O MAPA
                </button>

            </div>
        </section>

        <!-- RESULTADO DA MISSÃO -->
        <section id="result-overlay" class="overlay" hidden>

            <div class="result-card">

                <div id="result-symbol" class="result-symbol">✦</div>

                <div id="result-eyebrow" class="eyebrow">
                    RELATÓRIO DE MISSÃO
                </div>

                <h2 id="result-title">Missão concluída</h2>

                <p id="result-message"></p>

                <div class="result-stats">

                    <div>
                        <strong id="result-score">0</strong>
                        <span>PONTOS</span>
                    </div>

                    <div>
                        <strong id="result-correct">0/6</strong>
                        <span>ACERTOS</span>
                    </div>

                    <div>
                        <strong id="result-xp">0</strong>
                        <span>XP</span>
                    </div>

                </div>

                <button
                    id="retry-button"
                    class="primary-button"
                    type="button">
                    TENTAR NOVAMENTE
                </button>

                <a
                    class="secondary-button"
                    href="mathspace.php">
                    VOLTAR AO MAPA
                </a>

            </div>
        </section>

    </main>

</body>

</html>