    
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/config.php';

$usuarioId = $_SESSION['usuario_id']
    ?? $_SESSION['id_usuario']
    ?? $_SESSION['id']
    ?? null;

if (!$usuarioId) {
    header('Location: ../../login.php');
    exit;
}

$nomeUsuario = $_SESSION['nome'] ?? 'Explorador';
$serieUsuario = '';
$turmaUsuario = '';
$fases = [];
$totalFases = 0;
$totalConcluidas = 0;
$totalXP = 0;
$erroBanco = false;

try {
    // Dados do jogador.
    $stmt = $pdo->prepare(
        'SELECT id, nome, serie, turma
         FROM usuarios
         WHERE id = :id
         LIMIT 1'
    );

    $stmt->execute(['id' => $usuarioId]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$usuario) {
        session_destroy();
        header('Location: ../../login.php');
        exit;
    }

    $nomeUsuario = $usuario['nome'] ?: 'Explorador';
    $serieUsuario = preg_replace(
        '/\D/',
        '',
        (string) ($usuario['serie'] ?? '')
    );
    $turmaUsuario = $usuario['turma'] ?? '';

    // O jogador vê somente as fases da própria série.
    if (!in_array($serieUsuario, ['6', '7', '8', '9'], true)) {
        $serieUsuario = '';
    }

    if ($serieUsuario !== '') {
        $stmt = $pdo->prepare(
            'SELECT
                f.id,
                f.nome,
                f.descricao,
                f.nivel_dificuldade,
                f.numero,
                COALESCE(pu.concluida, 0) AS concluida,
                COALESCE(pu.melhor_pontuacao, 0) AS pontuacao,
                COALESCE(pu.xp_conquistado, 0) AS xp
             FROM fases f
             LEFT JOIN progresso_usuario pu
                ON pu.fase_id = f.id
                AND pu.usuario_id = :usuario_id
             WHERE f.jogo_id = 2
                AND CAST(f.serie AS CHAR) = :serie
             ORDER BY f.numero ASC, f.id ASC'
        );

        $stmt->execute([
            'usuario_id' => $usuarioId,
            'serie' => $serieUsuario
        ]);

        $fases = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // A primeira fase fica liberada.
        // As próximas dependem da conclusão da anterior.
        $faseAnteriorConcluida = true;

        foreach ($fases as &$fase) {
            $fase['id'] = (int) $fase['id'];
            $fase['numero'] = (int) $fase['numero'];
            $fase['concluida'] = (bool) $fase['concluida'];
            $fase['pontuacao'] = (int) $fase['pontuacao'];
            $fase['xp'] = (int) $fase['xp'];
            $fase['desbloqueada'] = $faseAnteriorConcluida;

            if ($fase['concluida']) {
                $totalConcluidas++;
            }

            $totalXP += $fase['xp'];

            $faseAnteriorConcluida = $fase['concluida'];
        }

        unset($fase);
    }

    $totalFases = count($fases);

} catch (Throwable $e) {
    $erroBanco = true;

    // O erro técnico não é exibido ao jogador.
    error_log('Erro no MathSpace: ' . $e->getMessage());
}

$percentual = $totalFases > 0
    ? (int) round(($totalConcluidas / $totalFases) * 100)
    : 0;

$configJogo = [
    'fases' => $fases,
    'serie' => $serieUsuario,
    'totalFases' => $totalFases,
    'fasesConcluidas' => $totalConcluidas,
    'xp' => $totalXP,
    'totalEstrelas' => 180
];

$configJson = json_encode(
    $configJogo,
    JSON_HEX_TAG
    | JSON_HEX_APOS
    | JSON_HEX_QUOT
    | JSON_HEX_AMP
    | JSON_UNESCAPED_UNICODE
    | JSON_UNESCAPED_SLASHES
);

if ($configJson === false) {
    $configJson = '{"fases":[],"serie":"","totalFases":0,"fasesConcluidas":0,"xp":0,"totalEstrelas":180}';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#070b18">

    <title>MathSpace | Exploração Galáctica</title>

    <link rel="stylesheet" href="../css/mathspace.css">

    <script>
        window.MATHSPACE_CONFIG = <?= $configJson ?>;
    </script>

    <script
        src="https://cdn.jsdelivr.net/npm/phaser@3.90.0/dist/phaser.min.js"
        defer>
    </script>

    <script src="../js/mathspace.js" defer></script>
</head>

<body class="space-game-page">

<div class="cockpit">

    <!-- HUD superior: informações integradas à nave -->
    <header class="topbar">

        <a class="brand" href="../../inicio.php" aria-label="Voltar ao MathRun">
            <span class="brand-mark" aria-hidden="true">M<span>✦</span></span>

            <span class="brand-name">
                MATH<span>SPACE</span>
            </span>
        </a>

        <div class="flight-status">
            <span class="status-light"></span>
            SISTEMAS ONLINE
            <span class="status-divider"></span>
            <span class="flight-sector">
                SETOR <?= htmlspecialchars($serieUsuario ?: '--', ENT_QUOTES, 'UTF-8') ?>
            </span>
        </div>

        <div class="pilot">

            <span class="pilot-avatar" aria-hidden="true">✦</span>

            <div class="pilot-details">
                <small>COMANDANTE</small>
                <strong>
                    <?= htmlspecialchars($nomeUsuario, ENT_QUOTES, 'UTF-8') ?>
                </strong>
            </div>

        </div>

    </header>

    <main class="cockpit-main">

        <!-- Painel compacto de missão -->
        <section class="mission-heading">

            <div>
                <div class="micro-label">
                    <span class="micro-line"></span>
                    CENTRAL DE NAVEGAÇÃO
                </div>

                <h1>EXPLORE O <span>UNIVERSO</span></h1>

                <p>
                    <?= $serieUsuario !== ''
                        ? 'Selecione um planeta para iniciar sua missão.'
                        : 'Série não identificada. Verifique seu cadastro.' ?>
                </p>
            </div>

            <div class="expedition-progress">

                <div class="progress-copy">
                    <span>PROGRESSO</span>
                    <strong><?= $percentual ?>%</strong>
                </div>

                <div
                    class="progress-track"
                    role="progressbar"
                    aria-label="Progresso das missões"
                    aria-valuemin="0"
                    aria-valuemax="100"
                    aria-valuenow="<?= $percentual ?>"
                >
                    <span style="width: <?= $percentual ?>%"></span>
                </div>

                <div class="progress-meta">
                    <span><?= $totalConcluidas ?>/<?= $totalFases ?> destinos</span>
                    <span>✦ <?= number_format($totalXP, 0, ',', '.') ?> XP</span>
                </div>

            </div>

        </section>

        <?php if ($erroBanco): ?>
            <div class="system-alert" role="alert">
                <span>!</span>
                <div>Falha nos sistemas de navegação. Tente novamente mais tarde.</div>
            </div>
        <?php elseif ($serieUsuario === ''): ?>
            <div class="system-alert" role="alert">
                <span>!</span>
                <div>Não foi possível identificar sua série no cadastro.</div>
            </div>
        <?php elseif ($totalFases === 0): ?>
            <div class="system-alert" role="status">
                <span>i</span>
                <div>Nenhum planeta cadastrado para este setor ainda.</div>
            </div>
        <?php endif; ?>

        <!-- O universo ocupa o centro da tela -->
        <section
            class="ship-window"
            id="mapa-principal"
            aria-label="Painel de exploração espacial"
        >

            <div class="window-top">

                <div class="window-title">
                    <span class="window-icon" aria-hidden="true">⌖</span>

                    <div>
                        <strong>HOLOMAPA</strong>
                        <small>MAPEAMENTO GALÁCTICO</small>
                    </div>
                </div>

                <div class="window-readouts">
                    <span>
                        <i class="readout-dot"></i>
                        NAVEGAÇÃO ATIVA
                    </span>

                    <span>
                        SETOR <?= htmlspecialchars($serieUsuario ?: '--', ENT_QUOTES, 'UTF-8') ?>
                    </span>
                </div>

            </div>

            <div class="universe-viewport" id="universe-viewport">

                <div class="universe-backdrop" aria-hidden="true"></div>
                <div class="viewport-grid" aria-hidden="true"></div>
                <div class="viewport-vignette" aria-hidden="true"></div>

                <div class="map-coordinates coordinates-top" aria-hidden="true">
                    GALAXY MAP / MS-<?= htmlspecialchars($serieUsuario ?: '00', ENT_QUOTES, 'UTF-8') ?>
                </div>

                <div class="map-legend" aria-label="Status das fases">
                    <span><i class="legend-available"></i> DISPONÍVEL</span>
                    <span><i class="legend-completed"></i> CONCLUÍDA</span>
                    <span><i class="legend-locked"></i> BLOQUEADA</span>
                </div>

                <div class="map-crosshair crosshair-one" aria-hidden="true"></div>
                <div class="map-crosshair crosshair-two" aria-hidden="true"></div>

                <!-- O Phaser desenha o universo dentro deste elemento -->
                <div
                    id="universe-game"
                    role="application"
                    aria-label="Mapa interativo. Selecione um planeta para ver sua missão."
                ></div>

                <div class="universe-loading" id="universe-loading">
                    <span class="loading-orbit" aria-hidden="true"></span>
                    <span>INICIALIZANDO HOLOMAPA</span>
                </div>

                <div class="map-coordinates coordinates-bottom" aria-hidden="true">
                    MATHSPACE EXPLORATION SYSTEM
                </div>

            </div>

            <div class="window-bottom">

                <div class="bottom-system">
                    <span class="bottom-pulse"></span>
                    SISTEMA ESTÁVEL
                </div>

                <div class="bottom-center">
                    DESTINOS DESCOBERTOS:
                    <span><?= $totalConcluidas ?> / <?= $totalFases ?></span>
                </div>

                <div class="bottom-grade">
                    MS-<?= htmlspecialchars($serieUsuario ?: '--', ENT_QUOTES, 'UTF-8') ?>
                </div>

            </div>

        </section>

        <!-- Console de comandos da nave -->
        <section class="ship-console" aria-label="Console da nave">

            <div class="console-label">
                <span class="console-label-line"></span>
                CONSOLE DE BORDO
            </div>

            <div class="console-layout">

                <!-- Comandos funcionais -->
                <div class="command-cluster">

                    <button
                        class="command-button"
                        type="button"
                        id="center-map"
                        title="Centralizar o universo"
                    >
                        <span class="command-icon" aria-hidden="true">⌖</span>
                        <span class="command-name">CENTRALIZAR</span>
                    </button>

                    <button
                        class="command-button"
                        type="button"
                        id="toggle-effects"
                        aria-pressed="true"
                        title="Ativar ou reduzir animações"
                    >
                        <span class="command-icon" aria-hidden="true">✧</span>
                        <span class="command-name">ANIMAÇÃO</span>
                    </button>

                    <button
                        class="command-button"
                        type="button"
                        id="toggle-fullscreen"
                        title="Alternar tela cheia"
                    >
                        <span class="command-icon" aria-hidden="true">⛶</span>
                        <span class="command-name">TELA CHEIA</span>
                    </button>

                    <a
                        class="command-button"
                        href="../../inicio.php"
                        title="Voltar ao MathRun"
                    >
                        <span class="command-icon" aria-hidden="true">↩</span>
                        <span class="command-name">RETORNAR</span>
                    </a>

                </div>

                <!-- Painel da missão selecionada -->
                <div class="selected-mission" aria-live="polite">

                    <div
                        class="selected-planet-icon"
                        id="selected-planet-icon"
                        aria-hidden="true"
                    >✦</div>

                    <div class="selected-mission-copy">

                        <small id="mission-kicker">DESTINO SELECIONADO</small>

                        <strong id="mission-name">
                            Selecione um planeta
                        </strong>

                        <span id="mission-description">
                            O universo aguarda sua exploração.
                        </span>

                    </div>

                    <span class="mission-status" id="mission-status">
                        AGUARDANDO
                    </span>

                    <a
                        class="launch-button is-disabled"
                        id="launch-mission"
                        href="#"
                        aria-disabled="true"
                    >
                        <span>INICIAR</span>
                        <span class="launch-arrow" aria-hidden="true">↗</span>
                    </a>

                </div>

                <!-- Temas tratados como controles da nave -->
                <div class="theme-cluster">

                    <span class="theme-cluster-label">INTERFACE</span>

                    <div class="theme-switcher" role="group" aria-label="Tema da nave">

                        <button
                            type="button"
                            data-theme-option="light"
                            title="Tema claro"
                            aria-label="Ativar tema claro"
                            aria-pressed="false"
                        >☀</button>

                        <button
                            type="button"
                            data-theme-option="dark"
                            title="Tema escuro"
                            aria-label="Ativar tema escuro"
                            aria-pressed="true"
                        >☾</button>

                        <button
                            type="button"
                            data-theme-option="pink"
                            title="Tema rosa"
                            aria-label="Ativar tema rosa"
                            aria-pressed="false"
                        >♡</button>

                    </div>

                    <span class="theme-current" id="theme-current">TEMA ESCURO</span>

                </div>

            </div>

        </section>

    </main>

    <footer class="cockpit-footer">

        <a href="../../inicio.php">← MATHRUN</a>

        <span>FLIGHT SYSTEM · MATHSPACE</span>

        <span>
            ZONA DE EXPLORAÇÃO:
            <?= htmlspecialchars($serieUsuario ? $serieUsuario . 'º ANO' : '--', ENT_QUOTES, 'UTF-8') ?>
        </span>

    </footer>

</div>

</body>
</html>
