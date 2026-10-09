<?php

declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../config/config.php';

$usuarioId = $_SESSION['usuario_id'] ?? $_SESSION['id'] ?? $_SESSION['user_id'] ?? null;
if (!$usuarioId) {
    header('Location: ../login.php');
    exit;
}

if (!isset($pdo) || !($pdo instanceof PDO)) {
    http_response_code(500);
    exit('Não foi possível iniciar a conexão com o banco de dados.');
}

try {
    $stmtUsuario = $pdo->prepare('SELECT id, nome, serie, turma, nivel, xp, pontuacao_total FROM usuarios WHERE id = ? LIMIT 1');
    $stmtUsuario->execute([(int)$usuarioId]);
    $usuario = $stmtUsuario->fetch(PDO::FETCH_ASSOC);

    if (!$usuario) {
        session_destroy();
        header('Location: ../login.php');
        exit;
    }

    $serie = (int)($usuario['serie'] ?? 0);
    if (!in_array($serie, [6, 7, 8, 9], true)) {
        $serie = 6;
    }

    $stmtFases = $pdo->prepare(
        'SELECT f.id, f.nome, f.descricao, f.nivel_dificuldade, f.numero,
                COALESCE(p.concluida, 0) AS concluida,
                COALESCE(p.melhor_pontuacao, 0) AS melhor_pontuacao
         FROM fases f
         LEFT JOIN progresso_usuario p
           ON p.fase_id = f.id AND p.usuario_id = ?
         WHERE f.jogo_id = 2 AND f.serie = ?
         ORDER BY f.numero ASC'
    );
    $stmtFases->execute([(int)$usuarioId, $serie]);
    $fases = $stmtFases->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('MathSpace: ' . $e->getMessage());
    http_response_code(500);
    exit('Não foi possível carregar as missões do MathSpace. Confira as tabelas usuarios, fases e progresso_usuario.');
}

$nomesPlanetas = [
    1 => ['nome' => 'Lua', 'tipo' => 'SATÉLITE NATURAL', 'simbolo' => '☾', 'cor' => '#8eeaff', 'classe' => 'moon'],
    2 => ['nome' => 'Marte', 'tipo' => 'PLANETA ROCHOSO', 'simbolo' => '♂', 'cor' => '#ff9bba', 'classe' => 'mars'],
    3 => ['nome' => 'Cinturão de Asteroides', 'tipo' => 'ZONA DE NAVEGAÇÃO', 'simbolo' => '✦', 'cor' => '#c2a2ff', 'classe' => 'asteroids'],
    4 => ['nome' => 'Estação Espacial', 'tipo' => 'BASE ORBITAL', 'simbolo' => '⌘', 'cor' => '#83f4e6', 'classe' => 'station'],
];

$posicoes = [
    1 => ['x' => 14, 'y' => 68],
    2 => ['x' => 37, 'y' => 37],
    3 => ['x' => 62, 'y' => 65],
    4 => ['x' => 84, 'y' => 27],
];

$completas = 0;
$pontuacaoTotal = 0;
$anteriorConcluida = true;

foreach ($fases as $i => &$fase) {
    $numero = (int)$fase['numero'];
    $fase['concluida'] = (int)$fase['concluida'] === 1;
    $fase['desbloqueada'] = ($i === 0) || $anteriorConcluida;
    $fase['pontos'] = max(100, (int)$fase['melhor_pontuacao']);
    $fase['planeta'] = $nomesPlanetas[$numero] ?? [
        'nome' => 'Setor ' . str_pad((string)$numero, 2, '0', STR_PAD_LEFT),
        'tipo' => 'REGIÃO DESCONHECIDA',
        'simbolo' => '✦',
        'cor' => '#c2a2ff',
        'classe' => 'unknown'
    ];
    $fase['posicao'] = $posicoes[$numero] ?? [
        'x' => 12 + (($numero * 19) % 76),
        'y' => 18 + (($numero * 23) % 65)
    ];

    if ($fase['concluida']) {
        $completas++;
        $pontuacaoTotal += (int)$fase['melhor_pontuacao'];
    }
    $anteriorConcluida = $fase['concluida'];
}
unset($fase);

$totalFases = count($fases);
$progressoPercentual = $totalFases > 0 ? (int)round(($completas / $totalFases) * 100) : 0;
$nomeUsuario = htmlspecialchars((string)($usuario['nome'] ?? 'Explorador'), ENT_QUOTES, 'UTF-8');
$turma = htmlspecialchars((string)($usuario['turma'] ?? ''), ENT_QUOTES, 'UTF-8');
$serieTexto = $serie . 'º ano';
$nivel = max(1, (int)($usuario['nivel'] ?? 1));
$xp = max(0, (int)($usuario['xp'] ?? 0));
$creditos = max(0, (int)($usuario['pontuacao_total'] ?? 0));
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#080b1d">
    <title>MathSpace | Centro de Navegação</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;500;600;700;800&family=Rajdhani:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/mathspace.css">
    <script type="importmap">
        {"imports":{"three":"https://cdn.jsdelivr.net/npm/three@0.160.0/build/three.module.js"}}
    </script>
    <script type="module" src="../assets/js/mathspace.js"></script>
</head>

<body class="mathspace-page">
    <div class="space-scene" aria-hidden="true">
        <div class="space-stars"></div>
        <div class="space-nebula nebula-one"></div>
        <div class="space-nebula nebula-two"></div>
        <div class="distant-galaxy"></div>
    </div>

    <div class="cockpit-shell">
        <header class="ship-topbar">
            <div class="topbar-brand"><span class="brand-mark">M<span>✦</span></span>
                <div><strong>MATHSPACE</strong><small>DEEP SPACE EXPLORATION</small></div>
            </div>
            <div class="topbar-sector"><span class="status-dot"></span> SETOR DE NAVEGAÇÃO <b>//</b> VIA LÁCTEA</div>
            <div class="topbar-stats">
                <span><small>CRÉDITOS</small><strong><?= number_format($creditos, 0, ',', '.') ?></strong></span>
                <span><small>ENERGIA</small><strong class="energy-value">100%</strong></span>
                <span><small>NÍVEL</small><strong><?= $nivel ?></strong></span>
            </div>
        </header>

        <main class="cockpit-main">
            <section class="cockpit-titlebar">
                <div>
                    <p class="eyebrow"><span class="eyebrow-line"></span> INTERFACE DE COMANDO / MATHSPACE</p>
                    <h1>Mapa <span>estelar</span></h1>
                    <p class="title-description">Selecione um destino. Cada órbita guarda um novo desafio matemático.</p>
                </div>
                <div class="pilot-chip">
                    <div class="pilot-avatar"><?= strtoupper(substr((string)($usuario['nome'] ?? 'E'), 0, 1)) ?></div>
                    <div><small>COMANDANTE</small><strong><?= $nomeUsuario ?></strong><span><?= htmlspecialchars($serieTexto, ENT_QUOTES, 'UTF-8') ?><?= $turma !== '' ? ' • Turma ' . $turma : '' ?></span></div>
                </div>
            </section>

            <section class="bridge-layout">
                <aside class="ship-console left-console">
                    <div class="console-heading"><span class="console-led"></span> STATUS DA NAVE <span class="console-code">SYS.01</span></div>
                    <div class="ship-window">
                        <div class="window-stars"></div>
                        <div class="ship-silhouette">✦</div><span>STELLAR / EXPLORER</span><small>LONG RANGE VESSEL</small>
                    </div>
                    <div class="telemetry-list">
                        <div class="telemetry-item"><span>Integridade do casco</span><strong>98.7%</strong><i><b style="width:98.7%"></b></i></div>
                        <div class="telemetry-item"><span>Propulsão</span><strong>ONLINE</strong><i><b style="width:84%"></b></i></div>
                        <div class="telemetry-item"><span>Experiência (XP)</span><strong><?= number_format($xp, 0, ',', '.') ?></strong><i><b style="width:<?= min(100, $xp % 100) ?>%"></b></i></div>
                    </div>
                    <div class="console-divider"></div>
                    <div class="progress-heading"><span>EXPLORAÇÃO DO SETOR</span><strong><?= $progressoPercentual ?>%</strong></div>
                    <div class="progress-track"><span style="width:<?= $progressoPercentual ?>%"></span></div>
                    <p class="console-footnote"><?= $completas ?> de <?= $totalFases ?> missões concluídas</p>
                    <div class="console-bottom"><span class="pulse-ring"></span>
                        <div><strong>SISTEMAS ESTÁVEIS</strong><small>Todos os sensores respondendo</small></div>
                    </div>
                </aside>

                <section class="star-map-panel" aria-label="Mapa espacial interativo">
                    <div class="map-frame-corner corner-tl"></div>
                    <div class="map-frame-corner corner-tr"></div>
                    <div class="map-frame-corner corner-bl"></div>
                    <div class="map-frame-corner corner-br"></div>
                    <div class="map-header">
                        <div><span class="map-kicker">CARTA CELESTE 07-A</span>
                            <h2>Mapa de setores cósmicos</h2>
                        </div>
                        <div class="map-live"><span class="status-dot"></span> SINAL AO VIVO</div>
                    </div>
                    <div class="map-viewport" id="mapViewport">
                        <div class="map-galaxy-haze"></div>
                        <div class="map-grid"></div>
                        <div class="map-starfield"></div>
                        <svg class="route-svg" viewBox="0 0 1000 600" preserveAspectRatio="none" aria-hidden="true">
                            <defs>
                                <linearGradient id="routeGradient" x1="0" y1="0" x2="1" y2="1">
                                    <stop offset="0%" stop-color="#ff9fe8" />
                                    <stop offset="45%" stop-color="#8adfff" />
                                    <stop offset="100%" stop-color="#b8a0ff" />
                                </linearGradient>
                                <filter id="routeGlow">
                                    <feGaussianBlur stdDeviation="4" result="blur" />
                                    <feMerge>
                                        <feMergeNode in="blur" />
                                        <feMergeNode in="SourceGraphic" />
                                    </feMerge>
                                </filter>
                            </defs>
                            <path class="route-underlay" d="M140 408 C200 340 280 350 370 222 S520 220 620 390 S770 240 840 162" />
                            <path class="route-path" d="M140 408 C200 340 280 350 370 222 S520 220 620 390 S770 240 840 162" />
                            <path class="route-dash" d="M140 408 C200 340 280 350 370 222 S520 220 620 390 S770 240 840 162" />
                        </svg>

                        <?php if (!$fases): ?>
                            <div class="map-empty"><span>⌁</span><strong>Nenhuma missão detectada</strong>
                                <p>Não encontramos fases do MathSpace cadastradas para o seu ano escolar.</p>
                            </div>
                        <?php endif; ?>

                        <?php foreach ($fases as $fase): ?>
                            <?php
                            $numero = (int)$fase['numero'];
                            $planeta = $fase['planeta'];
                            $posicao = $fase['posicao'];
                            $locked = !$fase['desbloqueada'];
                            $status = $fase['concluida'] ? 'CONCLUÍDA' : ($locked ? 'BLOQUEADA' : 'DISPONÍVEL');
                            $url = 'jogar_mathspace.php?fase_id=' . (int)$fase['id'];
                            $nomeFase = htmlspecialchars((string)$fase['nome'], ENT_QUOTES, 'UTF-8');
                            $descricao = htmlspecialchars((string)($fase['descricao'] ?? ''), ENT_QUOTES, 'UTF-8');
                            $classeEstado = $fase['concluida'] ? 'is-completed' : ($locked ? 'is-locked' : 'is-available');
                            ?>
                            <button
                                type="button"
                                class="destination <?= $classeEstado ?> <?= htmlspecialchars($planeta['classe'], ENT_QUOTES, 'UTF-8') ?>"
                                style="--planet-x:<?= (int)$posicao['x'] ?>%;--planet-y:<?= (int)$posicao['y'] ?>%;--planet-color:<?= htmlspecialchars($planeta['cor'], ENT_QUOTES, 'UTF-8') ?>;--planet-index:<?= $numero ?>;"
                                data-destination
                                data-id="<?= (int)$fase['id'] ?>"
                                data-number="<?= $numero ?>"
                                data-name="<?= $nomeFase ?>"
                                data-description="<?= $descricao ?>"
                                data-type="<?= htmlspecialchars($planeta['tipo'], ENT_QUOTES, 'UTF-8') ?>"
                                data-planet="<?= htmlspecialchars($planeta['nome'], ENT_QUOTES, 'UTF-8') ?>"
                                data-planet-class="<?= htmlspecialchars($planeta['classe'], ENT_QUOTES, 'UTF-8') ?>"
                                data-color="<?= htmlspecialchars($planeta['cor'], ENT_QUOTES, 'UTF-8') ?>"
                                data-difficulty="<?= htmlspecialchars(ucfirst((string)$fase['nivel_dificuldade']), ENT_QUOTES, 'UTF-8') ?>"
                                data-points="<?= (int)$fase['pontos'] ?>"
                                data-completed="<?= $fase['concluida'] ? '1' : '0' ?>"
                                data-unlocked="<?= $fase['desbloqueada'] ? '1' : '0' ?>"
                                data-status="<?= $status ?>"
                                data-url="<?= htmlspecialchars($url, ENT_QUOTES, 'UTF-8') ?>"
                                aria-label="Selecionar <?= $nomeFase ?>. <?= $status ?>"
                                aria-pressed="false">
                                <span class="planet-orbit orbit-a"></span><span class="planet-orbit orbit-b"></span>
                                <span class="planet-render planet-<?= htmlspecialchars($planeta['classe'], ENT_QUOTES, 'UTF-8') ?>" data-planet-canvas aria-hidden="true"><span class="planet-fallback"></span></span>
                                <span class="planet-beacon"></span>
                                <span class="destination-label"><strong><?= $nomeFase ?></strong><small><?= $fase['concluida'] ? '✓ CONCLUÍDA' : ($locked ? '⌑ BLOQUEADA' : '↗ DISPONÍVEL') ?></small></span>
                                <?php if ($locked): ?><span class="lock-mark" aria-hidden="true">⌑</span><?php endif; ?>
                            </button>
                        <?php endforeach; ?>
                        <div class="map-coordinates coordinate-a">RA 05h 34m 31s<br>DEC +22° 00′ 52″</div>
                        <div class="map-coordinates coordinate-b">SECTOR 07-A / NAV GRID</div>
                        <div class="map-cursor-hint"><span>✦</span> SELECIONE UM DESTINO</div>
                        <div class="map-crosshair crosshair-a"></div>
                        <div class="map-crosshair crosshair-b"></div>
                    </div>
                    <div class="map-footer"><span><i class="legend-dot available"></i> Disponível</span><span><i class="legend-dot completed"></i> Concluída</span><span><i class="legend-dot locked"></i> Bloqueada</span><span class="map-footer-coordinates">NAV. COORD. <b>24.08 / 09.77</b></span></div>
                </section>

                <aside class="ship-console right-console">
                    <div class="console-heading"><span class="console-led pink"></span> COMPUTADOR DE MISSÃO <span class="console-code">NAV.04</span></div>
                    <div class="mission-empty-state" id="missionEmpty">
                        <div class="radar-rings"><span></span><span></span><span></span><i>⌖</i></div>
                        <strong>Nenhum destino selecionado</strong>
                        <p>Selecione um planeta no mapa para abrir o holograma de navegação.</p>
                    </div>
                    <div class="mission-details" id="missionDetails" hidden>
                        <div class="holo-window">
                            <div class="holo-topline"><span>HOLOGRAMA 3D / LIVE</span><span class="holo-live"><i></i> ATIVO</span></div>
                            <div class="planet-stage" id="planetStage">
                                <div class="planet-stage-glow"></div>
                                <div class="planet-stage-ring ring-one"></div>
                                <div class="planet-stage-ring ring-two"></div>
                                <div class="planet-stage-fallback" id="planetStageFallback"><span></span></div>
                                <canvas id="planetCanvas" aria-label="Planeta 3D giratório"></canvas>
                                <div class="planet-stage-crosshair"></div>
                                <div class="stage-caption" id="stageCaption">DESTINO 01</div>
                            </div>
                        </div>
                        <div class="mission-title-block"><span class="mission-type" id="selectedType">PLANETA ROCHOSO</span>
                            <h3 id="selectedName">Lua</h3>
                            <p id="selectedDescription"></p>
                        </div>
                        <div class="mission-data-grid">
                            <div><small>DIFICULDADE</small><strong id="selectedDifficulty">Fácil</strong></div>
                            <div><small>RECOMPENSA</small><strong id="selectedPoints">100 PTS</strong></div>
                        </div>
                        <div class="mission-status-line"><span class="status-dot" id="selectedStatusDot"></span><span id="selectedStatus">AGUARDANDO SELEÇÃO</span></div>
                        <a href="#" class="launch-button is-disabled" id="launchButton" aria-disabled="true"><span class="launch-icon">↗</span><span><strong id="launchText">Selecionar destino</strong><small id="launchCaption">O computador aguarda coordenadas.</small></span><span class="launch-arrow">⟶</span></a>
                    </div>
                    <div class="console-bottom right-console-bottom"><span class="pulse-ring"></span>
                        <div><strong>IA DE BORDO ONLINE</strong><small>Assistente de navegação ativo</small></div>
                    </div>
                </aside>
            </section>
            <footer class="ship-footer">
                <div><span class="footer-signal"></span> PLAYER: <?= $nomeUsuario ?> <b>//</b> NAVE: STELLAR EXPLORER <b>//</b> STATUS: <strong>HIPERDRIVE PRONTO</strong></div>
                <nav><a href="../inicio.php">Base</a><a href="../perfil.php">Perfil</a><a href="../conquistas.php">Conquistas</a><a href="../logout.php">Sair</a></nav>
            </footer>
        </main>
    </div>

    <div class="cursor-rocket" id="cursorRocket" aria-hidden="true">
        <svg viewBox="0 0 64 64" role="presentation">
            <defs>
                <linearGradient id="rocketMetal" x1="0" y1="0" x2="1" y2="1">
                    <stop stop-color="#fff" />
                    <stop offset=".5" stop-color="#9deeff" />
                    <stop offset="1" stop-color="#9b77ff" />
                </linearGradient>
                <linearGradient id="rocketFlame" x1="0" y1="0" x2="0" y2="1">
                    <stop stop-color="#fff" />
                    <stop offset=".35" stop-color="#ff9be9" />
                    <stop offset="1" stop-color="#8a6cff" stop-opacity="0" />
                </linearGradient>
            </defs>
            <path class="rocket-flame" d="M27 42 Q32 59 37 42 L32 49Z" fill="url(#rocketFlame)" />
            <path d="M32 5 C20 16 20 29 23 42 L32 48 L41 42 C44 29 44 16 32 5Z" fill="url(#rocketMetal)" stroke="#f4caff" stroke-width="1.5" />
            <circle cx="32" cy="27" r="5.5" fill="#17213e" stroke="#ffb6f2" stroke-width="2" />
            <path d="M23 34 L13 43 L23 42 M41 34 L51 43 L41 42" fill="#a58cff" stroke="#bfefff" stroke-width="1.5" />
            <path d="M29 42 L32 48 L35 42" fill="#ffb4ef" />
        </svg>
    </div>

    <div class="mission-toast" id="missionToast" role="status" aria-live="polite"></div>
</body>

</html>