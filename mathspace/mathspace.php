<?php
session_start();

require_once __DIR__ . '/../config/config.php';

$usuarioId = $_SESSION['usuario_id']
    ?? $_SESSION['id']
    ?? $_SESSION['user_id']
    ?? null;

if (!$usuarioId) {
    header('Location: ../login.php');
    exit;
}

$stmt = $pdo->prepare(
    "SELECT id, nome, serie, turma
     FROM usuarios
     WHERE id = ?
     LIMIT 1"
);
$stmt->execute([$usuarioId]);
$aluno = $stmt->fetch();

if (!$aluno) {
    session_destroy();
    header('Location: ../login.php');
    exit;
}

$serie = (int) $aluno['serie'];

if (!in_array($serie, [6, 7, 8, 9], true)) {
    exit('Série escolar inválida.');
}

$stmt = $pdo->prepare(
    "SELECT
        f.id,
        f.nome,
        f.descricao,
        f.nivel_dificuldade,
        f.numero,
        COALESCE(p.concluida, 0) AS concluida,
        COALESCE(p.melhor_pontuacao, 0) AS melhor_pontuacao
     FROM fases f
     LEFT JOIN progresso_usuario p
        ON p.fase_id = f.id
        AND p.usuario_id = ?
     WHERE f.jogo_id = 2
       AND f.serie = ?
     ORDER BY f.numero ASC"
);

$stmt->execute([$usuarioId, $serie]);
$fases = $stmt->fetchAll();

function escapar($valor): string
{
    return htmlspecialchars(
        (string) $valor,
        ENT_QUOTES,
        'UTF-8'
    );
}

function nomeDificuldade($valor): string
{
    return match (strtolower(trim((string) $valor))) {
        'facil', 'fácil' => 'Fácil',
        'medio', 'médio' => 'Médio',
        'dificil', 'difícil' => 'Difícil',
        default => ucfirst((string) $valor)
    };
}

/*
 * Coordenadas dos destinos no mapa.
 * Os valores são porcentagens da área de navegação.
 */
$destinos = [
    [
        'icone' => '☾',
        'classe' => 'lua',
        'x' => 14,
        'y' => 67,
        'tipo' => 'SATÉLITE NATURAL',
        'cor' => '#a9d8ff'
    ],
    [
        'icone' => '♂',
        'classe' => 'marte',
        'x' => 37,
        'y' => 35,
        'tipo' => 'PLANETA ROCHOSO',
        'cor' => '#ff967c'
    ],
    [
        'icone' => '✦',
        'classe' => 'asteroides',
        'x' => 62,
        'y' => 65,
        'tipo' => 'CAMPO DE ASTEROIDES',
        'cor' => '#85e8ed'
    ],
    [
        'icone' => '◉',
        'classe' => 'estacao',
        'x' => 84,
        'y' => 29,
        'tipo' => 'BASE ORBITAL',
        'cor' => '#c2a6ff'
    ]
];

$totalFases = count($fases);
$fasesConcluidas = 0;
$pontuacaoTotal = 0;
$liberarProxima = true;

foreach ($fases as &$fase) {
    $fase['concluida'] = (int) $fase['concluida'];
    $fase['melhor_pontuacao'] = (int) $fase['melhor_pontuacao'];

    $fase['desbloqueada'] = $liberarProxima;

    if ($fase['concluida'] === 1) {
        $fasesConcluidas++;
        $pontuacaoTotal += $fase['melhor_pontuacao'];
    }

    $liberarProxima = $fase['concluida'] === 1;
}
unset($fase);

$percentual = $totalFases > 0
    ? (int) round(($fasesConcluidas / $totalFases) * 100)
    : 0;

$primeiraDisponivel = null;

foreach ($fases as $i => $fase) {
    if ($fase['desbloqueada']) {
        $primeiraDisponivel = $i;
        break;
    }
}

if ($primeiraDisponivel === null && $totalFases > 0) {
    $primeiraDisponivel = 0;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <meta name="theme-color" content="#080b1b">

    <title>MathSpace | MathRun</title>

    <link rel="stylesheet" href="../assets/css/mathspace.css">
    <script src="../assets/js/mathspace.js" defer></script>
</head>

<body>
    <div class="space-background" aria-hidden="true">
        <div class="nebula nebula-one"></div>
        <div class="nebula nebula-two"></div>
        <div class="star-layer stars-small"></div>
        <div class="star-layer stars-large"></div>
        <div class="shooting-star"></div>
    </div>

    <header class="top-hud">
        <a href="../inicio.php" class="brand">
            <span class="brand-symbol">✦</span>
            <span>Math<span>Run</span></span>
        </a>

        <div class="hud-center">
            <span class="connection-light"></span>
            <span>SISTEMAS DA NAVE</span>
            <span class="hud-divider">/</span>
            <span class="hud-muted">EXPLORAÇÃO ESPACIAL</span>
        </div>

        <a href="../perfil.php" class="pilot-link">
            <span class="pilot-icon">♙</span>
            <span><?= escapar($aluno['nome']) ?></span>
            <span class="pilot-chevron">⌄</span>
        </a>
    </header>

    <main class="cockpit">

        <section class="cockpit-heading">
            <div>
                <div class="system-label">
                    <span class="system-pulse"></span>
                    TERMINAL DE NAVEGAÇÃO · SETOR <?= escapar($serie) ?>
                </div>

                <h1>Comandante, escolha seu <span>destino.</span></h1>

                <p>
                    A galáxia está diante de você. Selecione um ponto no mapa
                    para consultar sua próxima missão.
                </p>
            </div>

            <div class="ship-status">
                <span class="status-icon">⌁</span>
                <div>
                    <small>STATUS DA EXPEDIÇÃO</small>
                    <strong><?= $fasesConcluidas === $totalFases && $totalFases > 0
                                ? 'SETOR EXPLORADO'
                                : 'NAVE EM ÓRBITA' ?></strong>
                </div>
            </div>
        </section>

        <section class="cockpit-grid">

            <!-- PAINEL ESQUERDO: INFORMAÇÕES DA NAVE -->
            <aside class="side-console">
                <div class="console-heading">
                    <span class="console-icon">⌘</span>
                    <div>
                        <small>PAINEL DE BORDO</small>
                        <h2>Expedição</h2>
                    </div>
                    <span class="live-tag">LIVE</span>
                </div>

                <div class="pilot-card">
                    <div class="pilot-portrait">👩‍🚀</div>

                    <div class="pilot-data">
                        <small>COMANDANTE</small>
                        <strong><?= escapar($aluno['nome']) ?></strong>
                        <span>
                            <?= escapar($aluno['turma'] ?? 'Turma não informada') ?>
                            · <?= escapar($serie) ?>º ano
                        </span>
                    </div>
                </div>

                <div class="console-separator"></div>

                <div class="data-block">
                    <div class="data-label">
                        <span>✧</span>
                        ENERGIA DE EXPLORAÇÃO
                    </div>

                    <div class="data-value">
                        <strong><?= $percentual ?>%</strong>
                        <span><?= $fasesConcluidas ?>/<?= $totalFases ?> missões</span>
                    </div>

                    <div class="energy-bar">
                        <div
                            class="energy-fill"
                            data-progress="<?= $percentual ?>"></div>
                    </div>

                    <small class="console-hint">
                        Progresso da campanha atual
                    </small>
                </div>

                <div class="console-separator"></div>

                <div class="data-block">
                    <div class="data-label">
                        <span>◇</span>
                        CRÉDITOS ACUMULADOS
                    </div>

                    <div class="credits-display">
                        <span class="credit-symbol">✦</span>
                        <strong><?= number_format($pontuacaoTotal, 0, ',', '.') ?></strong>
                        <span>PTS</span>
                    </div>
                </div>

                <div class="console-separator"></div>

                <div class="mission-counter">
                    <div class="counter-orbit">
                        <span>✦</span>
                    </div>

                    <div>
                        <small>OBJETIVO ATUAL</small>
                        <strong>
                            <?= $fasesConcluidas === $totalFases && $totalFases > 0
                                ? 'Todas as missões concluídas!'
                                : 'Explore o próximo planeta' ?>
                        </strong>
                        <p>Novos destinos aguardam sua descoberta.</p>
                    </div>
                </div>

                <a href="../conquistas.php" class="console-link">
                    Ver conquistas e medalhas <span>↗</span>
                </a>
            </aside>

            <!-- CENTRO: VISOR PANORÂMICO E MAPA -->
            <section class="navigation-window">
                <div class="window-topbar">
                    <div class="window-title">
                        <span class="window-dot"></span>
                        <span>VISOR GALÁCTICO</span>
                    </div>

                    <div class="coordinates">
                        <span>COORD.</span>
                        <strong>MX-<?= escapar($serie) ?>.07</strong>
                    </div>

                    <div class="radar-status">
                        <span class="radar-dot"></span>
                        RADAR ATIVO
                    </div>
                </div>

                <div class="galaxy-map" id="galaxyMap">

                    <div class="map-grid" aria-hidden="true"></div>
                    <div class="map-nebula map-nebula-purple" aria-hidden="true"></div>
                    <div class="map-nebula map-nebula-blue" aria-hidden="true"></div>

                    <div class="radar-circle radar-circle-one" aria-hidden="true"></div>
                    <div class="radar-circle radar-circle-two" aria-hidden="true"></div>
                    <div class="radar-center" aria-hidden="true"></div>

                    <svg
                        class="route-lines"
                        viewBox="0 0 1000 600"
                        preserveAspectRatio="none"
                        aria-hidden="true">
                        <defs>
                            <linearGradient id="routeGradient">
                                <stop offset="0%" stop-color="#9d9bff" stop-opacity=".2" />
                                <stop offset="50%" stop-color="#83dfff" stop-opacity=".85" />
                                <stop offset="100%" stop-color="#b59cff" stop-opacity=".35" />
                            </linearGradient>
                        </defs>

                        <path
                            d="M140 402 Q230 370 370 210"
                            class="route-path" />
                        <path
                            d="M370 210 Q520 150 620 390"
                            class="route-path" />
                        <path
                            d="M620 390 Q760 320 840 174"
                            class="route-path" />

                        <path
                            d="M140 402 Q230 370 370 210"
                            class="route-glow" />
                        <path
                            d="M370 210 Q520 150 620 390"
                            class="route-glow" />
                        <path
                            d="M620 390 Q760 320 840 174"
                            class="route-glow" />
                    </svg>

                    <div class="map-label label-home">
                        <span class="label-marker"></span>
                        PONTO DE PARTIDA
                    </div>

                    <div class="map-label label-galaxy">
                        VIA LÁCTEA · SETOR <?= escapar($serie) ?>
                    </div>

                    <?php if (empty($fases)): ?>
                        <div class="map-empty">
                            <span>⌁</span>
                            <h2>Sinal de missão não encontrado</h2>
                            <p>Não existem fases cadastradas para esta série.</p>
                        </div>
                    <?php else: ?>

                        <?php foreach ($fases as $indice => $fase): ?>
                            <?php
                            $destino = $destinos[$indice % count($destinos)];
                            $concluida = $fase['concluida'] === 1;
                            $desbloqueada = $fase['desbloqueada'];
                            $bloqueada = !$desbloqueada;
                            $selecionada = $indice === $primeiraDisponivel;

                            $status = $concluida
                                ? 'completed'
                                : ($bloqueada ? 'locked' : 'available');

                            $statusTexto = $concluida
                                ? 'CONCLUÍDA'
                                : ($bloqueada ? 'BLOQUEADA' : 'DISPONÍVEL');
                            ?>

                            <button
                                type="button"
                                class="destination <?= escapar($destino['classe']) ?> <?= escapar($status) ?> <?= $selecionada ? 'selected' : '' ?>"
                                style="
                                    --planet-x: <?= (int) $destino['x'] ?>%;
                                    --planet-y: <?= (int) $destino['y'] ?>%;
                                    --planet-color: <?= escapar($destino['cor']) ?>;
                                    --planet-order: <?= (int) $indice ?>;
                                "
                                data-destination
                                data-id="<?= (int) $fase['id'] ?>"
                                data-name="<?= escapar($fase['nome']) ?>"
                                data-description="<?= escapar($fase['descricao'] ?: 'Uma nova missão matemática aguarda você neste destino.') ?>"
                                data-type="<?= escapar($destino['tipo']) ?>"
                                data-difficulty="<?= escapar(nomeDificuldade($fase['nivel_dificuldade'])) ?>"
                                data-status="<?= escapar($statusTexto) ?>"
                                data-points="<?= (int) $fase['melhor_pontuacao'] ?>"
                                data-completed="<?= $concluida ? '1' : '0' ?>"
                                data-unlocked="<?= $desbloqueada ? '1' : '0' ?>"
                                data-url="jogar_mathspace.php?fase_id=<?= (int) $fase['id'] ?>"
                                aria-pressed="<?= $selecionada ? 'true' : 'false' ?>"
                                aria-label="<?= escapar($fase['nome']) ?>: <?= escapar($statusTexto) ?>">
                                <span class="destination-orbit"></span>
                                <span class="destination-orbit orbit-second"></span>

                                <span class="destination-planet">
                                    <span class="destination-symbol">
                                        <?= escapar($destino['icone']) ?>
                                    </span>
                                </span>

                                <span class="destination-number">
                                    <?= str_pad((string) ($indice + 1), 2, '0', STR_PAD_LEFT) ?>
                                </span>

                                <span class="destination-name">
                                    <?= escapar($fase['nome']) ?>
                                </span>

                                <span class="destination-state">
                                    <?php if ($concluida): ?>
                                        ✓
                                    <?php elseif ($bloqueada): ?>
                                        🔒
                                    <?php else: ?>
                                        <span class="mini-signal"></span>
                                    <?php endif; ?>
                                </span>
                            </button>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <div class="map-scale">
                        <span>ESCALA ESTELAR</span>
                        <span class="scale-line"></span>
                        <span>1 UA</span>
                    </div>

                    <div class="map-compass" aria-hidden="true">
                        <span>N</span>
                        <div class="compass-arrow">⌃</div>
                        <span>S</span>
                    </div>
                </div>

                <div class="window-bottom">
                    <div class="bottom-indicator">
                        <span class="connection-light"></span>
                        MAPA SINCRONIZADO
                    </div>

                    <div class="bottom-hint">
                        <span>⌖</span>
                        Selecione um planeta para examiná-lo
                    </div>

                    <div class="map-zoom">
                        <span>ZOOM</span>
                        <strong>100%</strong>
                    </div>
                </div>
            </section>

            <!-- PAINEL DIREITO: MISSÃO SELECIONADA -->
            <aside class="mission-console">
                <div class="console-heading">
                    <span class="console-icon">⌖</span>
                    <div>
                        <small>COMPUTADOR DE BORDO</small>
                        <h2>Destino selecionado</h2>
                    </div>
                </div>

                <div class="selected-destination-visual" id="selectedVisual">
                    <div class="selected-orbit"></div>
                    <div class="selected-orbit selected-orbit-two"></div>
                    <div class="selected-planet">
                        <span id="selectedSymbol">☾</span>
                    </div>
                    <span class="visual-star visual-star-one">✦</span>
                    <span class="visual-star visual-star-two">✧</span>
                </div>

                <div class="selected-info">
                    <span class="selected-kicker" id="selectedType">
                        DESTINO DE EXPLORAÇÃO
                    </span>

                    <h2 id="selectedName">Selecione um planeta</h2>

                    <p id="selectedDescription">
                        Escolha um destino no visor galáctico para consultar
                        os detalhes da missão.
                    </p>
                </div>

                <div class="selected-meta">
                    <div>
                        <small>DIFICULDADE</small>
                        <strong id="selectedDifficulty">---</strong>
                    </div>

                    <div>
                        <small>MELHOR PONTUAÇÃO</small>
                        <strong id="selectedPoints">0 PTS</strong>
                    </div>
                </div>

                <div class="selected-status">
                    <span class="status-square"></span>
                    <span id="selectedStatus">AGUARDANDO DESTINO</span>
                </div>

                <a
                    href="#"
                    class="launch-button disabled"
                    id="launchButton"
                    aria-disabled="true">
                    <span class="launch-icon">➤</span>
                    <span id="launchText">Selecione um destino</span>
                    <span class="launch-arrow">↗</span>
                </a>

                <p class="launch-caption" id="launchCaption">
                    A nave aguarda suas coordenadas.
                </p>

                <div class="console-footer">
                    <span>SYS.NAV.MATHSPACE</span>
                    <span>V.01.06</span>
                </div>
            </aside>

        </section>

        <footer class="cockpit-footer">
            <div>
                <span class="footer-symbol">✦</span>
                MATHRUN <span class="footer-muted">/ SPACE EXPLORATION</span>
            </div>

            <div class="footer-center">
                TODAS AS ROTAS LEVAM A UMA DESCOBERTA
            </div>

            <a href="../inicio.php">SAIR DA NAVE ↗</a>
        </footer>

    </main>
</body>

</html>