<?php
// TCC_FINAL/mathspace/mathspace.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/config.php';

/*
 * Identifica o usuário pela sessão do MathRun.
 * Se o login usar outro nome de variável, ajustaremos ao código real.
 */
$usuarioId = $_SESSION['usuario_id']
    ?? $_SESSION['id_usuario']
    ?? $_SESSION['id']
    ?? null;

if (!$usuarioId) {
    header('Location: ../login.php');
    exit;
}

$nomeUsuario = $_SESSION['nome']
    ?? $_SESSION['nome_usuario']
    ?? 'Explorador';

$fasesPorSerie = [];
$fasesConcluidas = [];
$melhorPontuacao = 0;
$erroBanco = false;

try {
    // Dados da conta, incluindo a série do aluno.
    $stmtUsuario = $pdo->prepare(
        'SELECT id, nome, serie, turma
         FROM usuarios
         WHERE id = :id
         LIMIT 1'
    );
    $stmtUsuario->execute(['id' => $usuarioId]);
    $usuario = $stmtUsuario->fetch(PDO::FETCH_ASSOC);

    if ($usuario) {
        $nomeUsuario = $usuario['nome'] ?: $nomeUsuario;
        $serieUsuario = preg_replace('/\D/', '', (string) ($usuario['serie'] ?? ''));
    } else {
        $serieUsuario = '';
    }

    // Carrega apenas fases do MathSpace.
    $stmtFases = $pdo->prepare(
        'SELECT id, nome, descricao, nivel_dificuldade, numero, serie
         FROM fases
         WHERE jogo_id = 2
         ORDER BY numero ASC, id ASC'
    );
    $stmtFases->execute();
    $todasFases = $stmtFases->fetchAll(PDO::FETCH_ASSOC);

    // Carrega o progresso do usuário.
    $stmtProgresso = $pdo->prepare(
        'SELECT fase_id, concluida, melhor_pontuacao
         FROM progresso_usuario
         WHERE usuario_id = :usuario_id'
    );
    $stmtProgresso->execute(['usuario_id' => $usuarioId]);

    foreach ($stmtProgresso->fetchAll(PDO::FETCH_ASSOC) as $progresso) {
        $faseId = (int) $progresso['fase_id'];

        if (!empty($progresso['concluida'])) {
            $fasesConcluidas[$faseId] = true;
        }

        $melhorPontuacao += (int) ($progresso['melhor_pontuacao'] ?? 0);
    }

    // Normaliza valores como "6", "6º ano" ou "6º".
    foreach ($todasFases as $fase) {
        $serieFase = preg_replace('/\D/', '', (string) ($fase['serie'] ?? ''));

        if (!in_array($serieFase, ['6', '7', '8', '9'], true)) {
            continue;
        }

        // Se houver série na conta, mostra inicialmente a série do aluno.
        // O seletor permite consultar as demais séries.
        $fasesPorSerie[$serieFase][] = $fase;
    }
} catch (Throwable $e) {
    $erroBanco = true;
    $serieUsuario = '';
}

/*
 * Ordem de desbloqueio:
 * a primeira fase fica disponível; cada fase seguinte exige a conclusão
 * da anterior, dentro da mesma série.
 */
foreach ($fasesPorSerie as $serie => &$listaFases) {
    usort($listaFases, function ($a, $b) {
        return (int) $a['numero'] <=> (int) $b['numero'];
    });

    $anteriorConcluida = true;

    foreach ($listaFases as &$fase) {
        $faseId = (int) $fase['id'];
        $fase['concluida'] = isset($fasesConcluidas[$faseId]);
        $fase['desbloqueada'] = $anteriorConcluida;
        $fase['pontuacao'] = 0;

        $anteriorConcluida = $fase['concluida'];
    }
    unset($fase);
}
unset($listaFases);

$seriesDisponiveis = ['6', '7', '8', '9'];

if (!isset($fasesPorSerie[$serieUsuario])) {
    $serieInicial = isset($fasesPorSerie['6']) ? '6' : (array_key_first($fasesPorSerie) ?? '6');
} else {
    $serieInicial = $serieUsuario;
}

$totalConcluidas = count($fasesConcluidas);
$totalFases = 0;

foreach ($fasesPorSerie as $lista) {
    $totalFases += count($lista);
}

$cssPath = 'css/mathspace.css';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#090d20">
    <title>Mapa Galáctico | MathSpace</title>
    <link rel="stylesheet" href="<?= htmlspecialchars($cssPath, ENT_QUOTES, 'UTF-8') ?>">
</head>
<body>
<a class="skip-link" href="#mapa-principal">Pular para o mapa de missões</a>

<div class="game-shell">
    <header class="game-header">
        <a class="brand" href="../inicio.php" aria-label="Voltar ao MathRun">
            <span class="brand-symbol" aria-hidden="true">M</span>
            <span>MATH<span class="brand-highlight">SPACE</span></span>
        </a>

        <div class="pilot-info">
            <span class="pilot-avatar" aria-hidden="true">✦</span>
            <div>
                <span class="small-label">COMANDANTE</span>
                <strong><?= htmlspecialchars($nomeUsuario, ENT_QUOTES, 'UTF-8') ?></strong>
            </div>
        </div>

        <div class="header-actions">
            <div class="theme-switcher" role="group" aria-label="Tema visual">
                <button type="button" data-theme-option="light" title="Tema claro" aria-label="Ativar tema claro" aria-pressed="false">☀</button>
                <button type="button" data-theme-option="dark" title="Tema escuro" aria-label="Ativar tema escuro" aria-pressed="true">☾</button>
                <button type="button" data-theme-option="pink" title="Tema rosa" aria-label="Ativar tema rosa" aria-pressed="false">♡</button>
            </div>
            <a class="exit-link" href="../inicio.php">Sair do mapa <span aria-hidden="true">↗</span></a>
        </div>
    </header>

    <main id="mapa-principal">
        <section class="command-bar" aria-labelledby="page-title">
            <div class="command-heading">
                <p class="eyebrow"><span class="live-dot" aria-hidden="true"></span> SISTEMA DE NAVEGAÇÃO ONLINE</p>
                <h1 id="page-title">Mapa <span>galáctico</span></h1>
                <p class="subtitle">Escolha seu próximo destino, comandante. O universo não vai se explorar sozinho.</p>
            </div>

            <div class="progress-card" aria-label="Progresso geral">
                <div class="progress-card-top">
                    <span>PROGRESSO DA EXPEDIÇÃO</span>
                    <strong><?= $totalFases > 0 ? (int) round(($totalConcluidas / $totalFases) * 100) : 0 ?>%</strong>
                </div>
                <div class="progress-track"
                     role="progressbar"
                     aria-label="Progresso das fases"
                     aria-valuemin="0"
                     aria-valuemax="<?= max(1, $totalFases) ?>"
                     aria-valuenow="<?= min($totalConcluidas, max(1, $totalFases)) ?>">
                    <span style="width: <?= $totalFases > 0 ? min(100, ($totalConcluidas / $totalFases) * 100) : 0 ?>%"></span>
                </div>
                <div class="progress-card-bottom">
                    <span><?= $totalConcluidas ?> de <?= $totalFases ?> fases concluídas</span>
                    <span>✦ <?= number_format($melhorPontuacao, 0, ',', '.') ?> XP</span>
                </div>
            </div>
        </section>

        <section class="map-toolbar" aria-label="Controles do mapa">
            <div class="series-picker">
                <span class="toolbar-label">SETOR DE ENSINO</span>
                <div class="series-buttons" role="group" aria-label="Selecionar série">
                    <?php foreach ($seriesDisponiveis as $serie): ?>
                        <button
                            type="button"
                            class="series-button <?= $serie === (string) $serieInicial ? 'active' : '' ?>"
                            data-serie="<?= $serie ?>"
                            aria-pressed="<?= $serie === (string) $serieInicial ? 'true' : 'false' ?>"
                        ><?= $serie ?>º ano</button>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="map-legend" aria-label="Legenda das missões">
                <span><i class="legend-dot available"></i> Disponível</span>
                <span><i class="legend-dot completed"></i> Concluída</span>
                <span><i class="legend-dot locked"></i> Bloqueada</span>
            </div>
        </section>

        <?php if ($erroBanco): ?>
            <div class="notice notice-error" role="alert">
                Não foi possível carregar o mapa do banco de dados. Confira a conexão em
                <code>config/config.php</code> e as tabelas do MathSpace.
            </div>
        <?php elseif ($totalFases === 0): ?>
            <div class="notice" role="status">
                Ainda não existem fases cadastradas para o MathSpace (<code>jogo_id = 2</code>).
                Quando as fases forem inseridas no banco, elas aparecerão aqui automaticamente.
            </div>
        <?php endif; ?>

        <section class="galaxy-map" aria-label="Mapa de planetas e missões">
            <div class="map-sky" aria-hidden="true">
                <div class="nebula nebula-one"></div>
                <div class="nebula nebula-two"></div>
                <div class="nebula nebula-three"></div>
                <canvas id="starfield"></canvas>
                <div class="map-grid"></div>
            </div>

            <div class="map-top-label" aria-hidden="true">
                <span class="radar-icon">⌾</span>
                <span>HOLOMAPA <small>MAPEAMENTO ATIVO</small></span>
            </div>

            <div class="map-coordinates" aria-hidden="true">SECTOR 04 // 27.04.09</div>

            <?php foreach ($seriesDisponiveis as $serie): ?>
                <?php
                $listaFases = $fasesPorSerie[$serie] ?? [];
                $quantidadeFases = count($listaFases);
                ?>
                <div class="series-map <?= $serie === (string) $serieInicial ? 'active' : '' ?>"
                     data-serie-map="<?= $serie ?>"
                     <?= $serie === (string) $serieInicial ? '' : 'hidden' ?>>

                    <div class="route-line route-line-a" aria-hidden="true"></div>
                    <div class="route-line route-line-b" aria-hidden="true"></div>
                    <div class="route-line route-line-c" aria-hidden="true"></div>

                    <?php if ($quantidadeFases === 0): ?>
                        <div class="empty-sector">
                            <span class="empty-icon" aria-hidden="true">⌖</span>
                            <h2>Setor em cartografia</h2>
                            <p>As missões desta série ainda não foram cadastradas.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($listaFases as $indice => $fase): ?>
                            <?php
                            $faseId = (int) $fase['id'];
                            $faseNumero = (int) $fase['numero'];
                            $concluida = !empty($fase['concluida']);
                            $desbloqueada = !empty($fase['desbloqueada']);
                            $status = $concluida ? 'completed' : ($desbloqueada ? 'available' : 'locked');

                            // Posições alternadas para formar uma rota de exploração.
                            $posicoes = [
                                ['x' => 16, 'y' => 70],
                                ['x' => 39, 'y' => 43],
                                ['x' => 63, 'y' => 63],
                                ['x' => 83, 'y' => 29],
                                ['x' => 71, 'y' => 15],
                                ['x' => 27, 'y' => 19],
                            ];
                            $posicao = $posicoes[$indice % count($posicoes)];

                            $destino = 'jogar_mathspace.php?fase=' . $faseId;
                            ?>
                            <article
                                class="mission-node <?= $status ?>"
                                style="--x: <?= $posicao['x'] ?>%; --y: <?= $posicao['y'] ?>%; --node-index: <?= $indice ?>"
                            >
                                <?php if ($desbloqueada): ?>
                                    <a class="planet-button"
                                       href="<?= htmlspecialchars($destino, ENT_QUOTES, 'UTF-8') ?>"
                                       aria-label="Fase <?= $faseNumero ?>: <?= htmlspecialchars($fase['nome'], ENT_QUOTES, 'UTF-8') ?>. <?= $concluida ? 'Concluída' : 'Disponível' ?>.">
                                <?php else: ?>
                                    <div class="planet-button"
                                         aria-label="Fase <?= $faseNumero ?>: <?= htmlspecialchars($fase['nome'], ENT_QUOTES, 'UTF-8') ?>. Bloqueada. Conclua a fase anterior para desbloquear."
                                         aria-disabled="true">
                                <?php endif; ?>

                                    <span class="planet-ring" aria-hidden="true"></span>
                                    <span class="planet-core" aria-hidden="true">
                                        <?php if ($concluida): ?>
                                            <span class="planet-status">✓</span>
                                        <?php elseif (!$desbloqueada): ?>
                                            <span class="planet-status">⌑</span>
                                        <?php else: ?>
                                            <span class="planet-number"><?= $faseNumero ?></span>
                                        <?php endif; ?>
                                    </span>
                                    <span class="planet-satellite" aria-hidden="true"></span>

                                <?php if ($desbloqueada): ?>
                                    </a>
                                <?php else: ?>
                                    </div>
                                <?php endif; ?>

                                <div class="mission-label">
                                    <span class="mission-kicker">
                                        <?= $concluida ? 'MISSÃO CONCLUÍDA' : ($desbloqueada ? 'MISSÃO DISPONÍVEL' : 'SINAL BLOQUEADO') ?>
                                    </span>
                                    <h3><?= htmlspecialchars($fase['nome'], ENT_QUOTES, 'UTF-8') ?></h3>
                                    <p><?= htmlspecialchars($fase['descricao'] ?? 'Explore este setor e supere os desafios matemáticos.', ENT_QUOTES, 'UTF-8') ?></p>
                                    <span class="difficulty"><?= htmlspecialchars(ucfirst((string) ($fase['nivel_dificuldade'] ?? 'normal')), ENT_QUOTES, 'UTF-8') ?></span>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>

            <div class="map-bottom-hud">
                <div class="hud-indicator"><span class="hud-pulse" aria-hidden="true"></span> NAVEGAÇÃO ESTÁVEL</div>
                <div class="hud-indicator">MISSÕES: <?= $totalConcluidas ?>/<?= $totalFases ?></div>
            </div>
        </section>

        <div class="map-footer">
            <p><span aria-hidden="true">✦</span> Cada planeta guarda um desafio. Cada desafio leva você mais longe.</p>
            <button type="button" class="accessibility-toggle" id="accessibility-toggle" aria-pressed="false">
                Acessibilidade: reduzir movimento
            </button>
            <button type="button" class="accessibility-toggle" id="text-toggle" aria-pressed="false">
                Aumentar texto
            </button>
        </div>
    </main>
</div>

<script>
(() => {
    const root = document.documentElement;
    const themeButtons = [...document.querySelectorAll('[data-theme-option]')];

    function applyTheme(theme) {
        root.dataset.theme = theme;
        themeButtons.forEach(button => {
            button.setAttribute('aria-pressed', String(button.dataset.themeOption === theme));
        });
    }

    themeButtons.forEach(button => {
        button.addEventListener('click', () => applyTheme(button.dataset.themeOption));
    });
    applyTheme('dark');

    // Troca de série sem recarregar a página.
    const seriesButtons = [...document.querySelectorAll('[data-serie]')];
    const seriesMaps = [...document.querySelectorAll('[data-serie-map]')];

    seriesButtons.forEach(button => {
        button.addEventListener('click', () => {
            const serie = button.dataset.serie;

            seriesButtons.forEach(item => {
                const selected = item === button;
                item.classList.toggle('active', selected);
                item.setAttribute('aria-pressed', String(selected));
            });

            seriesMaps.forEach(map => {
                const selected = map.dataset.serieMap === serie;
                map.hidden = !selected;
                map.classList.toggle('active', selected);
            });
        });
    });

    // Acessibilidade: movimento reduzido e texto ampliado.
    const motionToggle = document.getElementById('accessibility-toggle');
    motionToggle.addEventListener('click', () => {
        const enabled = !root.classList.contains('reduce-motion');
        root.classList.toggle('reduce-motion', enabled);
        motionToggle.setAttribute('aria-pressed', String(enabled));
        motionToggle.textContent = enabled
            ? 'Animações reduzidas: ativado'
            : 'Acessibilidade: reduzir movimento';
    });

    const textToggle = document.getElementById('text-toggle');
    textToggle.addEventListener('click', () => {
        const enabled = !root.classList.contains('large-text');
        root.classList.toggle('large-text', enabled);
        textToggle.setAttribute('aria-pressed', String(enabled));
        textToggle.textContent = enabled ? 'Texto ampliado: ativado' : 'Aumentar texto';
    });

    // Campo de estrelas procedural: sem imagens externas ou chave de API.
    const canvas = document.getElementById('starfield');
    const context = canvas.getContext('2d');
    let stars = [];
    let animationFrame;
    let width = 0;
    let height = 0;
    let reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches
        || root.classList.contains('reduce-motion');

    function createStars() {
        const rect = canvas.getBoundingClientRect();
        const dpr = Math.min(window.devicePixelRatio || 1, 2);
        width = rect.width;
        height = rect.height;
        canvas.width = Math.floor(width * dpr);
        canvas.height = Math.floor(height * dpr);
        context.setTransform(dpr, 0, 0, dpr, 0, 0);

        const count = Math.min(180, Math.floor((width * height) / 6500));
        stars = Array.from({ length: count }, () => ({
            x: Math.random() * width,
            y: Math.random() * height,
            size: Math.random() * 1.5 + .3,
            alpha: Math.random() * .65 + .2,
            speed: Math.random() * .18 + .025
        }));
        drawStars(false);
    }

    function drawStars(animate = true) {
        context.clearRect(0, 0, width, height);

        for (const star of stars) {
            context.beginPath();
            context.arc(star.x, star.y, star.size, 0, Math.PI * 2);
            context.fillStyle = `rgba(221, 229, 255, ${star.alpha})`;
            context.fill();

            if (animate && !reducedMotion) {
                star.y += star.speed;
                if (star.y > height) {
                    star.y = 0;
                    star.x = Math.random() * width;
                }
            }
        }

        if (animate && !reducedMotion) {
            animationFrame = requestAnimationFrame(() => drawStars(true));
        }
    }

    const motionQuery = window.matchMedia('(prefers-reduced-motion: reduce)');
    motionQuery.addEventListener?.('change', event => {
        reducedMotion = event.matches || root.classList.contains('reduce-motion');
        cancelAnimationFrame(animationFrame);
        drawStars(!reducedMotion);
    });

    motionToggle.addEventListener('click', () => {
        reducedMotion = root.classList.contains('reduce-motion')
            || motionQuery.matches;
        cancelAnimationFrame(animationFrame);
        drawStars(!reducedMotion);
    });

    window.addEventListener('resize', () => {
        cancelAnimationFrame(animationFrame);
        createStars();
        if (!reducedMotion) drawStars(true);
    });

    createStars();
    if (!reducedMotion) drawStars(true);
})();
</script>
</body>
</html>