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

$nomeUsuario = $_SESSION['nome'] ?? 'Comandante';
$serieUsuario = '';
$turmaUsuario = '';
$fases = [];
$fasesConcluidas = 0;
$xpTotal = 0;
$erroBanco = false;

try {
    $stmt = $pdo->prepare(
        'SELECT id, nome, serie, turma
         FROM usuarios
         WHERE id = :id
         LIMIT 1'
    );
    $stmt->execute(['id' => $usuarioId]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($usuario) {
        $nomeUsuario = $usuario['nome'] ?: $nomeUsuario;
        $serieUsuario = preg_replace(
            '/\D/',
            '',
            (string) ($usuario['serie'] ?? '')
        );
        $turmaUsuario = $usuario['turma'] ?? '';
    }

    if (!in_array($serieUsuario, ['6', '7', '8', '9'], true)) {
        $serieUsuario = '';
    }

    if ($serieUsuario !== '') {
        $stmt = $pdo->prepare(
            'SELECT id, nome, descricao, nivel_dificuldade, numero, serie
             FROM fases
             WHERE jogo_id = 2 AND serie = :serie
             ORDER BY numero ASC, id ASC'
        );
        $stmt->execute(['serie' => (int) $serieUsuario]);
        $fasesBanco = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt = $pdo->prepare(
            'SELECT pu.fase_id, pu.concluida,
                    pu.pontuacao, pu.xp_conquistado
             FROM progresso_usuario pu
             INNER JOIN fases f ON f.id = pu.fase_id
             WHERE pu.usuario_id = :usuario
               AND f.jogo_id = 2
               AND f.serie = :serie'
        );
        $stmt->execute([
            'usuario' => $usuarioId,
            'serie' => (int) $serieUsuario
        ]);

        $progresso = [];

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $registro) {
            $idFase = (int) $registro['fase_id'];

            if (
                !isset($progresso[$idFase]) ||
                (int) $registro['pontuacao'] >
                (int) $progresso[$idFase]['pontuacao']
            ) {
                $progresso[$idFase] = $registro;
            }
        }

        $faseAnteriorConcluida = true;

        foreach ($fasesBanco as $fase) {
            $idFase = (int) $fase['id'];
            $registro = $progresso[$idFase] ?? [];

            $concluida = !empty($registro['concluida']);
            $desbloqueada = $faseAnteriorConcluida;

            $fase['id'] = $idFase;
            $fase['numero'] = (int) $fase['numero'];
            $fase['concluida'] = $concluida;
            $fase['desbloqueada'] = $desbloqueada;
            $fase['pontuacao'] = (int) ($registro['pontuacao'] ?? 0);
            $fase['xp'] = (int) ($registro['xp_conquistado'] ?? 0);

            $fases[] = $fase;

            if ($concluida) {
                $fasesConcluidas++;
            }

            $xpTotal += $fase['xp'];
            $faseAnteriorConcluida = $concluida;
        }
    }
} catch (Throwable $e) {
    $erroBanco = true;
    error_log('Erro MathSpace: ' . $e->getMessage());
}

$totalFases = count($fases);
$percentual = $totalFases > 0
    ? (int) round(($fasesConcluidas / $totalFases) * 100)
    : 0;

$fasesJson = json_encode(
    $fases,
    JSON_UNESCAPED_UNICODE |
    JSON_UNESCAPED_SLASHES |
    JSON_HEX_TAG |
    JSON_HEX_APOS |
    JSON_HEX_QUOT |
    JSON_HEX_AMP
);

if ($fasesJson === false) {
    $fasesJson = '[]';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#080b19">
    <title>MathSpace | Central de Comando</title>

    <link rel="stylesheet" href="../css/mathspace.css">
</head>

<body>
<div class="cockpit" id="cockpit">

    <header class="topbar">
        <a class="brand" href="../../inicio.php" title="Voltar ao MathRun">
            <span class="brand-mark">M<span>✦</span></span>
            <span class="brand-name">MATH<span>SPACE</span></span>
        </a>

        <div class="flight-status">
            <span class="status-light"></span>
            <span>SISTEMAS ONLINE</span>
            <span class="status-divider"></span>
            <span class="flight-sector">
                SETOR <?= $serieUsuario !== '' ? htmlspecialchars($serieUsuario) : '--' ?>
            </span>
        </div>

        <div class="pilot">
            <div class="pilot-avatar" aria-hidden="true">
                <?= htmlspecialchars(mb_strtoupper(mb_substr($nomeUsuario, 0, 1))) ?>
            </div>
            <div class="pilot-details">
                <small>COMANDANTE</small>
                <strong><?= htmlspecialchars($nomeUsuario) ?></strong>
            </div>
        </div>
    </header>

    <main class="cockpit-main">
        <section class="mission-heading">
            <div>
                <div class="micro-label">
                    <span class="micro-line"></span>
                    CENTRAL DE EXPLORAÇÃO
                </div>
                <h1>Explore o <span>desconhecido.</span></h1>
                <p>Selecione um planeta para iniciar sua missão.</p>
            </div>

            <div class="expedition-progress">
                <div class="progress-copy">
                    <span>EXPEDIÇÃO</span>
                    <strong><?= $percentual ?>%</strong>
                </div>
                <div class="progress-track">
                    <span style="width: <?= $percentual ?>%"></span>
                </div>
                <div class="progress-meta">
                    <span><?= $fasesConcluidas ?> / <?= $totalFases ?> missões</span>
                    <span>✦ <?= number_format($xpTotal, 0, ',', '.') ?> XP</span>
                </div>
            </div>
        </section>

        <?php if ($erroBanco): ?>
            <div class="system-alert">
                <span>!</span>
                Não foi possível carregar os dados da nave. Verifique a conexão com o banco.
            </div>
        <?php elseif ($serieUsuario === ''): ?>
            <div class="system-alert">
                <span>!</span>
                Sua série não foi identificada. Confira o cadastro do seu perfil.
            </div>
        <?php elseif ($totalFases === 0): ?>
            <div class="system-alert">
                <span>!</span>
                Nenhuma missão foi encontrada para o seu ano escolar.
            </div>
        <?php endif; ?>

        <section class="ship-window" aria-label="Painel de navegação espacial">
            <div class="window-top">
                <div class="window-title">
                    <span class="window-icon">⌖</span>
                    <div>
                        <strong>HOLOMAPA</strong>
                        <small>MAPEAMENTO GALÁCTICO</small>
                    </div>
                </div>

                <div class="window-readouts">
                    <span><i class="readout-dot"></i> AO VIVO</span>
                    <span id="star-count">ESTRELAS: --</span>
                    <span id="zoom-level">ZOOM 100%</span>
                </div>
            </div>

            <div class="universe-viewport" id="universe-viewport">
                <div class="universe-backdrop"></div>
                <div class="viewport-vignette"></div>
                <div class="viewport-grid"></div>

                <div class="universe-loading" id="universe-loading">
                    <div class="loading-orbit"></div>
                    <span>INICIALIZANDO HOLOMAPA</span>
                </div>

                <div id="universe-game" aria-label="Universo interativo com planetas">
                </div>

                <div class="map-crosshair crosshair-one"></div>
                <div class="map-crosshair crosshair-two"></div>

                <div class="map-coordinates coordinates-top">
                    <span>GALAXIA // MR-04</span>
                    <span>COORD. 08.42 / 19.07</span>
                </div>

                <div class="map-coordinates coordinates-bottom">
                    <span>ARRASTE PARA EXPLORAR</span>
                    <span>◆ NAVEGAÇÃO ATIVA</span>
                </div>

                <div class="map-legend">
                    <span><i class="legend-available"></i> DISPONÍVEL</span>
                    <span><i class="legend-completed"></i> CONCLUÍDO</span>
                    <span><i class="legend-locked"></i> BLOQUEADO</span>
                </div>
            </div>

            <div class="window-bottom">
                <div class="bottom-system">
                    <span class="bottom-pulse"></span>
                    <span>UNIDADE DE NAVEGAÇÃO</span>
                </div>
                <div class="bottom-center">MATHSPACE EXPLORATION SYSTEM <span>v1.0</span></div>
                <div class="bottom-grade">
                    <?= $serieUsuario !== ''
                        ? htmlspecialchars($serieUsuario . 'º ANO')
                        : 'SÉRIE --' ?>
                </div>
            </div>
        </section>

        <section class="ship-console" aria-label="Painel de comandos da nave">
            <div class="console-label">
                <span class="console-label-line"></span>
                PAINEL DE COMANDO
            </div>

            <div class="console-layout">
                <div class="command-cluster">
                    <button class="command-button" id="center-map" type="button"
                            title="Centralizar universo" aria-label="Centralizar universo">
                        <span class="command-icon">⌖</span>
                        <span class="command-name">CENTRALIZAR</span>
                    </button>

                    <button class="command-button" id="toggle-effects" type="button"
                            title="Ativar ou desativar efeitos" aria-label="Alternar efeitos visuais"
                            aria-pressed="true">
                        <span class="command-icon">✧</span>
                        <span class="command-name">EFEITOS</span>
                    </button>

                    <button class="command-button" id="toggle-fullscreen" type="button"
                            title="Alternar tela cheia" aria-label="Alternar tela cheia">
                        <span class="command-icon">⛶</span>
                        <span class="command-name">TELA CHEIA</span>
                    </button>
                </div>

                <div class="selected-mission" id="selected-mission" aria-live="polite">
                    <div class="selected-planet-icon" id="selected-planet-icon">✦</div>
                    <div class="selected-mission-copy">
                        <small id="mission-kicker">DESTINO SELECIONADO</small>
                        <strong id="mission-name">Nenhum planeta selecionado</strong>
                        <span id="mission-description">Selecione um planeta no holomapa.</span>
                    </div>
                    <div class="mission-status" id="mission-status">AGUARDANDO</div>
                    <a class="launch-button is-disabled" id="launch-mission"
                       href="#" aria-disabled="true">
                        <span>INICIAR</span>
                        <span class="launch-arrow">↗</span>
                    </a>
                </div>

                <div class="theme-cluster">
                    <span class="theme-cluster-label">INTERFACE</span>
                    <div class="theme-switcher" role="group" aria-label="Tema da interface">
                        <button type="button" data-theme-option="light"
                                title="Tema claro" aria-label="Ativar tema claro"
                                aria-pressed="false">☼</button>
                        <button type="button" data-theme-option="dark"
                                title="Tema escuro" aria-label="Ativar tema escuro"
                                aria-pressed="true">◐</button>
                        <button type="button" data-theme-option="pink"
                                title="Tema rosa" aria-label="Ativar tema rosa"
                                aria-pressed="false">♡</button>
                    </div>
                    <span class="theme-current" id="theme-current">TEMA ESCURO</span>
                </div>
            </div>
        </section>

        <footer class="cockpit-footer">
            <a href="../../inicio.php">↖ VOLTAR AO MATHRUN</a>
            <span>FEITO PARA EXPLORAR. DESENVOLVIDO PARA APRENDER.</span>
            <a href="../../perfil.php">PERFIL DO PILOTO ↗</a>
        </footer>
    </main>
</div>

<script>
window.MATHSPACE_CONFIG = {
    fases: <?= $fasesJson ?>,
    serie: <?= json_encode($serieUsuario, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>,
    totalEstrelas: 180
};
</script>

<script src="https://cdn.jsdelivr.net/npm/phaser@3.90.0/dist/phaser.min.js"></script>
<script src="../js/mathspace.js"></script>
</body>
</html>