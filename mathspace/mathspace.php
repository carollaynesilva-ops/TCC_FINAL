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

function escapar($valor): string
{
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}

try {
    $consultaUsuario = $pdo->prepare(
        "SELECT id, nome, serie, turma
         FROM usuarios
         WHERE id = ?
         LIMIT 1"
    );

    $consultaUsuario->execute([$usuarioId]);
    $usuario = $consultaUsuario->fetch(PDO::FETCH_ASSOC);

    if (!$usuario) {
        session_destroy();
        header('Location: ../login.php');
        exit;
    }

    $serie = (int) ($usuario['serie'] ?? 6);

    if (!in_array($serie, [6, 7, 8, 9], true)) {
        $serie = 6;
    }

    $consultaFases = $pdo->prepare(
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

    $consultaFases->execute([$usuarioId, $serie]);
    $fases = $consultaFases->fetchAll(PDO::FETCH_ASSOC);

    $pontuacaoTotal = 0;
    $fasesConcluidas = 0;

    foreach ($fases as $fase) {
        $pontuacaoTotal += (int) $fase['melhor_pontuacao'];

        if ((int) $fase['concluida'] === 1) {
            $fasesConcluidas++;
        }
    }

    $totalFases = count($fases);

    $progresso = $totalFases > 0
        ? (int) round(($fasesConcluidas / $totalFases) * 100)
        : 0;

    $faseAnteriorConcluida = true;

    $simbolos = [
        1 => '☾',
        2 => '♂',
        3 => '✦',
        4 => '⌂'
    ];

    $tipos = [
        1 => 'SATÉLITE NATURAL',
        2 => 'PLANETA ROCHOSO',
        3 => 'REGIÃO DE ASTEROIDES',
        4 => 'BASE DE EXPLORAÇÃO'
    ];

    $cores = [
        1 => '#b9c9ff',
        2 => '#ff9b91',
        3 => '#d6a5ff',
        4 => '#80e8ff'
    ];
} catch (PDOException $e) {
    error_log('Erro no MathSpace: ' . $e->getMessage());
    http_response_code(500);
    exit('Não foi possível carregar o mapa espacial. ' .
        'Verifique a estrutura das tabelas do MathSpace.');
}

$nomeAluno = $usuario['nome'] ?? 'Explorador';
$turmaAluno = $usuario['turma'] ?? 'Não informada';
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">

    <meta name="viewport"
        content="width=device-width, initial-scale=1.0">

    <meta name="theme-color" content="#090b24">

    <title>MathSpace | Central de Exploração</title>

    <link rel="stylesheet" href="../assets/css/mathspace.css">

    <script src="../assets/js/mathspace.js" defer></script>
</head>

<body class="mathspace-page">

    <div class="space-background" aria-hidden="true">
        <div class="nebula nebula-blue"></div>
        <div class="nebula nebula-pink"></div>
        <div class="nebula nebula-purple"></div>
        <div class="star-layer star-layer-one"></div>
        <div class="star-layer star-layer-two"></div>
        <div class="star-layer star-layer-three"></div>
        <div class="shooting-star"></div>
    </div>

    <div class="spacecraft-shell">

        <!-- BARRA SUPERIOR DA NAVE -->
        <header class="top-hud">

            <a class="brand" href="../inicio.php">
                <span class="brand-symbol">✦</span>

                <span class="brand-text">
                    <strong>MathSpace</strong>
                    <small>DEEP SPACE EXPLORATION</small>
                </span>
            </a>

            <div class="ship-status">
                <span class="status-dot"></span>
                SISTEMAS OPERACIONAIS
            </div>

            <nav class="hud-navigation" aria-label="Navegação principal">
                <a href="../inicio.php">Início</a>
                <a href="../perfil.php">Perfil</a>
                <a href="../conquistas.php">Conquistas</a>
                <a href="../logout.php" class="exit-link">Sair</a>
            </nav>

        </header>

        <!-- CABINE -->
        <main class="cockpit">

            <section class="cockpit-heading">
                <div>
                    <p class="eyebrow">
                        <span class="eyebrow-line"></span>
                        CENTRAL DE NAVEGAÇÃO
                    </p>

                    <h1>Além das <span>estrelas.</span></h1>

                    <p class="heading-description">
                        Cada destino guarda um desafio.
                        Cada resposta abre caminho para o desconhecido.
                    </p>
                </div>

                <div class="coordinates">
                    <span>SETOR ATUAL</span>
                    <strong>ANDRÔMEDA-0<?= $serie ?></strong>
                    <small>COORDENADAS SINCRONIZADAS</small>
                </div>
            </section>

            <div class="cockpit-grid">

                <!-- PAINEL ESQUERDO -->
                <aside class="side-console">

                    <div class="console-topline">
                        <span>01 / TRIPULAÇÃO</span>
                        <span class="live-indicator">● LIVE</span>
                    </div>

                    <div class="pilot-avatar">
                        <div class="avatar-orbit"></div>
                        <div class="avatar-core">✦</div>
                    </div>

                    <p class="pilot-label">COMANDANTE</p>

                    <h2 class="pilot-name">
                        <?= escapar($nomeAluno) ?>
                    </h2>

                    <p class="pilot-class">
                        <?= escapar($serie) ?>º ano
                        <span>·</span>
                        Turma <?= escapar($turmaAluno) ?>
                    </p>

                    <div class="console-divider"></div>

                    <div class="stat-row">
                        <div class="stat-icon">✧</div>
                        <div>
                            <small>PONTUAÇÃO TOTAL</small>
                            <strong>
                                <?= number_format($pontuacaoTotal, 0, ',', '.') ?>
                                <span>PTS</span>
                            </strong>
                        </div>
                    </div>

                    <div class="stat-row">
                        <div class="stat-icon pink-icon">◎</div>
                        <div>
                            <small>MISSÕES CONCLUÍDAS</small>
                            <strong>
                                <?= $fasesConcluidas ?>
                                <span>/ <?= $totalFases ?></span>
                            </strong>
                        </div>
                    </div>

                    <div class="console-divider"></div>

                    <div class="progress-heading">
                        <span>PROGRESSO DA EXPEDIÇÃO</span>
                        <strong><?= $progresso ?>%</strong>
                    </div>

                    <div class="progress-track">
                        <div
                            class="progress-fill"
                            data-progress="<?= $progresso ?>"
                            style="width: <?= $progresso ?>%"></div>
                    </div>

                    <p class="console-note">
                        A exploração continua.
                        Novos setores aguardam você.
                    </p>

                    <div class="radar">
                        <div class="radar-ring radar-ring-one"></div>
                        <div class="radar-ring radar-ring-two"></div>
                        <div class="radar-ring radar-ring-three"></div>
                        <div class="radar-sweep"></div>
                        <span class="radar-point radar-point-one"></span>
                        <span class="radar-point radar-point-two"></span>
                        <span class="radar-point radar-point-three"></span>
                        <span class="radar-center"></span>
                    </div>

                    <p class="radar-caption">
                        RADAR DE DESTINOS
                        <span>ATIVO</span>
                    </p>

                </aside>

                <!-- MAPA CENTRAL -->
                <section class="navigation-window">

                    <div class="window-reflection" aria-hidden="true"></div>

                    <div class="map-topbar">
                        <div>
                            <span class="section-number">02 /</span>
                            <strong>MAPA ESTELAR</strong>
                        </div>

                        <div class="map-coordinates">
                            <span>RA 00h 42m</span>
                            <span>DEC +41° 16′</span>
                        </div>
                    </div>

                    <div class="map-instructions">
                        <span class="pulse-dot"></span>
                        SELECIONE UM DESTINO PARA EXPLORAR
                    </div>

                    <div class="galaxy-map" id="galaxyMap">

                        <div class="map-nebula map-nebula-one"></div>
                        <div class="map-nebula map-nebula-two"></div>
                        <div class="map-nebula map-nebula-three"></div>

                        <div class="galaxy-core">
                            <div class="galaxy-core-ring"></div>
                            <div class="galaxy-core-light"></div>
                        </div>

                        <div class="map-grid"></div>

                        <svg
                            class="route-lines"
                            viewBox="0 0 1000 700"
                            preserveAspectRatio="none"
                            aria-hidden="true">
                            <defs>
                                <linearGradient
                                    id="routeGradient"
                                    x1="0" y1="1" x2="1" y2="0">
                                    <stop offset="0%" stop-color="#82baff" />
                                    <stop offset="50%" stop-color="#e5a8ff" />
                                    <stop offset="100%" stop-color="#ffb6df" />
                                </linearGradient>

                                <filter id="routeGlow">
                                    <feGaussianBlur stdDeviation="5"
                                        result="blur" />
                                    <feMerge>
                                        <feMergeNode in="blur" />
                                        <feMergeNode in="SourceGraphic" />
                                    </feMerge>
                                </filter>
                            </defs>

                            <path
                                class="route-path route-path-glow"
                                d="M170 460 C230 400, 310 315, 390 300
                               S520 350, 610 410
                               S750 280, 820 190"
                                filter="url(#routeGlow)" />

                            <path
                                class="route-path"
                                d="M170 460 C230 400, 310 315, 390 300
                               S520 350, 610 410
                               S750 280, 820 190" />
                        </svg>

                        <div class="map-label map-label-one">
                            <span>SETOR 01</span>
                            <small>FRONTEIRA LUNAR</small>
                        </div>

                        <div class="map-label map-label-two">
                            <span>SETOR 02</span>
                            <small>ÓRBITA VERMELHA</small>
                        </div>

                        <div class="map-label map-label-three">
                            <span>SETOR 03</span>
                            <small>CAMPO PROFUNDO</small>
                        </div>

                        <?php if (empty($fases)): ?>

                            <div class="empty-map">
                                <span>⌁</span>
                                <strong>NENHUM DESTINO ENCONTRADO</strong>
                                <p>
                                    Não há fases cadastradas para
                                    o seu ano escolar.
                                </p>
                            </div>

                        <?php else: ?>

                            <?php foreach ($fases as $indice => $fase): ?>

                                <?php
                                $numero = (int) $fase['numero'];
                                $concluida = (int) $fase['concluida'] === 1;
                                $desbloqueada = $indice === 0 || $faseAnteriorConcluida;

                                $faseAnteriorConcluida = $concluida;

                                $nomeFase = $fase['nome'];
                                $descricaoFase = $fase['descricao'] ?? '';

                                $simbolo = $simbolos[$numero] ?? '✦';
                                $tipo = $tipos[$numero] ?? 'DESTINO DESCONHECIDO';
                                $cor = $cores[$numero] ?? '#c7a8ff';

                                $classeDestino = $concluida
                                    ? 'completed'
                                    : ($desbloqueada ? 'unlocked' : 'locked');

                                $posicoes = [
                                    1 => ['x' => 17, 'y' => 66],
                                    2 => ['x' => 39, 'y' => 43],
                                    3 => ['x' => 62, 'y' => 59],
                                    4 => ['x' => 82, 'y' => 28]
                                ];

                                $posicao = $posicoes[$numero] ?? [
                                    'x' => 15 + (($indice * 19) % 75),
                                    'y' => 20 + (($indice * 23) % 65)
                                ];

                                $urlFase = 'jogar_mathspace.php?fase_id='
                                    . (int) $fase['id'];

                                $dificuldade = ucfirst(
                                    str_replace(
                                        'facil',
                                        'fácil',
                                        $fase['nivel_dificuldade']
                                    )
                                );

                                $destinoInicial = $indice === 0;
                                ?>

                                <button
                                    type="button"
                                    class="destination <?= escapar($classeDestino) ?><?= $destinoInicial ? ' selected' : '' ?>"
                                    style="
                                    --planet-x: <?= $posicao['x'] ?>%;
                                    --planet-y: <?= $posicao['y'] ?>%;
                                    --planet-color: <?= escapar($cor) ?>;
                                    --planet-order: <?= $indice ?>;
                                "
                                    data-destination
                                    data-name="<?= escapar($nomeFase) ?>"
                                    data-description="<?= escapar($descricaoFase) ?>"
                                    data-type="<?= escapar($tipo) ?>"
                                    data-difficulty="<?= escapar($dificuldade) ?>"
                                    data-points="<?= (int) $fase['melhor_pontuacao'] ?>"
                                    data-completed="<?= $concluida ? '1' : '0' ?>"
                                    data-unlocked="<?= $desbloqueada ? '1' : '0' ?>"
                                    data-url="<?= escapar($urlFase) ?>"
                                    data-number="<?= $numero ?>"
                                    aria-pressed="<?= $destinoInicial ? 'true' : 'false' ?>"
                                    aria-label="<?= escapar($nomeFase) ?>">

                                    <span class="destination-orbit orbit-one"></span>
                                    <span class="destination-orbit orbit-two"></span>

                                    <span class="destination-planet planet-<?= $numero ?>">
                                        <span class="planet-clouds"></span>
                                        <span class="planet-craters"></span>
                                        <span class="planet-ring"></span>
                                        <span class="planet-highlight"></span>
                                    </span>

                                    <span class="destination-marker">
                                        <?= escapar($simbolo) ?>
                                    </span>

                                    <span class="destination-info">
                                        <strong><?= escapar($nomeFase) ?></strong>
                                        <small>
                                            <?= $concluida
                                                ? '✓ CONCLUÍDA'
                                                : ($desbloqueada
                                                    ? '● DISPONÍVEL'
                                                    : '⌑ BLOQUEADA') ?>
                                        </small>
                                    </span>

                                    <?php if ($concluida): ?>
                                        <span class="destination-badge">✓</span>
                                    <?php elseif (!$desbloqueada): ?>
                                        <span class="destination-badge lock-badge">⌑</span>
                                    <?php endif; ?>

                                </button>

                            <?php endforeach; ?>

                        <?php endif; ?>

                        <div class="map-scale">
                            <span></span>
                            <span>1 UA</span>
                            <span></span>
                        </div>

                        <div class="map-legend">
                            <span><i class="legend-blue"></i> Disponível</span>
                            <span><i class="legend-pink"></i> Concluída</span>
                            <span><i class="legend-muted"></i> Bloqueada</span>
                        </div>

                        <div class="map-corner map-corner-top"></div>
                        <div class="map-corner map-corner-bottom"></div>

                    </div>

                    <div class="window-bottom">
                        <span>PROJEÇÃO HOLOGRÁFICA</span>
                        <span class="window-bottom-center">● MAPA SINCRONIZADO</span>
                        <span>SYS / MS-<?= $serie ?>.04</span>
                    </div>

                </section>

                <!-- PAINEL DIREITO -->
                <aside class="mission-console">

                    <div class="console-topline">
                        <span>03 / COMPUTADOR DE BORDO</span>
                        <span class="console-icon">⌘</span>
                    </div>

                    <div
                        class="selected-destination-visual"
                        id="selectedVisual">
                        <div class="visual-stars"></div>
                        <div class="selected-orbit"></div>
                        <div class="selected-orbit selected-orbit-two"></div>

                        <div class="selected-planet" id="selectedPlanet">
                            <div class="selected-planet-clouds"></div>
                            <div class="selected-planet-shade"></div>
                        </div>

                        <span class="selected-symbol" id="selectedSymbol">☾</span>

                        <span class="visual-coordinate">
                            DESTINO SELECIONADO
                        </span>
                    </div>

                    <div class="mission-details">

                        <p class="eyebrow mission-eyebrow" id="selectedType">
                            DESTINO DE EXPLORAÇÃO
                        </p>

                        <h2 id="selectedName">
                            <?= isset($fases[0])
                                ? escapar($fases[0]['nome'])
                                : 'Nenhuma missão' ?>
                        </h2>

                        <p class="mission-description" id="selectedDescription">
                            <?= isset($fases[0])
                                ? escapar($fases[0]['descricao'])
                                : 'Nenhuma descrição disponível.' ?>
                        </p>

                        <div class="mission-data">
                            <div>
                                <span>DIFICULDADE</span>
                                <strong id="selectedDifficulty">
                                    <?= isset($fases[0])
                                        ? escapar(ucfirst($fases[0]['nivel_dificuldade']))
                                        : '—' ?>
                                </strong>
                            </div>

                            <div>
                                <span>MELHOR RESULTADO</span>
                                <strong id="selectedPoints">
                                    <?= isset($fases[0])
                                        ? number_format(
                                            (int) $fases[0]['melhor_pontuacao'],
                                            0,
                                            ',',
                                            '.'
                                        ) . ' PTS'
                                        : '0 PTS' ?>
                                </strong>
                            </div>
                        </div>

                        <div class="mission-status">
                            <span class="status-dot"></span>
                            <strong id="selectedStatus">
                                <?= isset($fases[0])
                                    ? ((int) $fases[0]['concluida'] === 1
                                        ? 'MISSÃO CONCLUÍDA'
                                        : 'VERIFICANDO SISTEMAS')
                                    : 'SEM MISSÕES' ?>
                            </strong>
                        </div>

                        <a
                            href="<?= isset($fases[0])
                                        ? 'jogar_mathspace.php?fase_id=' . (int) $fases[0]['id']
                                        : '#' ?>"
                            class="launch-button"
                            id="launchButton">
                            <span class="launch-icon">➤</span>
                            <span id="launchText">Iniciar missão</span>
                            <span class="launch-arrow">↗</span>
                        </a>

                        <p class="launch-caption" id="launchCaption">
                            Sistemas prontos para a exploração.
                        </p>

                    </div>

                    <div class="console-footer">
                        <span>IA DE BORDO</span>
                        <span class="ai-status">ONLINE</span>
                    </div>

                </aside>

            </div>

            <footer class="cockpit-footer">
                <span>MathSpace Exploration Program</span>
                <span>UMA JORNADA DE CADA VEZ, UMA DESCOBERTA DE CADA VEZ.</span>
                <span>© MATHRUN</span>
            </footer>

        </main>

    </div>

    <!-- FOGUETE PERSONALIZADO DO CURSOR -->
    <div class="rocket-cursor" id="rocketCursor" aria-hidden="true">
        <span class="rocket-flame"></span>
        <span class="rocket-body">🚀</span>
        <span class="rocket-trail"></span>
    </div>

</body>

</html>