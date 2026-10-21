
<?php
// TCC_FINAL/mathspace/paginas/mathspace.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/config.php';

// ==================================================
// 1. IDENTIFICAR O ALUNO
// ==================================================

$usuarioId = $_SESSION['usuario_id']
    ?? $_SESSION['id_usuario']
    ?? $_SESSION['id']
    ?? null;

if (!$usuarioId) {
    header('Location: ../../login.php');
    exit;
}

$nomeUsuario = $_SESSION['nome']
    ?? $_SESSION['nome_usuario']
    ?? 'Explorador';

$serieUsuario = '';
$turmaUsuario = '';
$fases = [];
$fasesConcluidas = [];
$erroBanco = false;
$mensagemErro = '';
$xpTotal = 0;

try {
    // Buscar os dados reais do aluno.
    $stmtUsuario = $pdo->prepare(
        'SELECT id, nome, serie, turma
         FROM usuarios
         WHERE id = :id
         LIMIT 1'
    );

    $stmtUsuario->execute([
        'id' => $usuarioId
    ]);

    $usuario = $stmtUsuario->fetch(PDO::FETCH_ASSOC);

    if (!$usuario) {
        header('Location: ../../login.php');
        exit;
    }

    $nomeUsuario = $usuario['nome'] ?: $nomeUsuario;
    $turmaUsuario = $usuario['turma'] ?? '';

    // Aceita valores como "6", "6º" e "6º ano".
    $serieUsuario = preg_replace(
        '/\D/',
        '',
        (string) ($usuario['serie'] ?? '')
    );

    // Nunca mostra o mapa de uma série diferente.
    if (!in_array($serieUsuario, ['6', '7', '8', '9'], true)) {
        $serieUsuario = '';
    }

    if ($serieUsuario !== '') {
        // Buscar somente as fases do MathSpace da série do aluno.
        $stmtFases = $pdo->prepare(
            'SELECT id, nome, descricao, nivel_dificuldade, numero, serie
             FROM fases
             WHERE jogo_id = 2
               AND CAST(serie AS CHAR) = :serie
             ORDER BY numero ASC, id ASC'
        );

        $stmtFases->execute([
            'serie' => $serieUsuario
        ]);

        $fases = $stmtFases->fetchAll(PDO::FETCH_ASSOC);

        // Progresso filtrado pelo jogo e pela série.
        // Isso evita misturar pontos de outros jogos.
        $stmtProgresso = $pdo->prepare(
            'SELECT
                pu.fase_id,
                pu.concluida,
                pu.melhor_pontuacao,
                pu.xp_conquistado
             FROM progresso_usuario pu
             INNER JOIN fases f ON f.id = pu.fase_id
             WHERE pu.usuario_id = :usuario_id
               AND f.jogo_id = 2
               AND CAST(f.serie AS CHAR) = :serie'
        );

        $stmtProgresso->execute([
            'usuario_id' => $usuarioId,
            'serie' => $serieUsuario
        ]);

        foreach ($stmtProgresso->fetchAll(PDO::FETCH_ASSOC) as $registro) {
            $faseId = (int) $registro['fase_id'];

            if (!empty($registro['concluida'])) {
                $fasesConcluidas[$faseId] = true;
            }

            $xpTotal += (int) ($registro['xp_conquistado'] ?? 0);
        }
    }
} catch (Throwable $e) {
    $erroBanco = true;
    $mensagemErro = 'Não foi possível carregar os dados do MathSpace.';
    
    // Detalhes técnicos ficam no log, não expostos ao aluno.
    error_log('Erro MathSpace: ' . $e->getMessage());
}

// ==================================================
// 2. PREPARAR AS MISSÕES E O DESBLOQUEIO
// ==================================================

$totalFases = count($fases);
$totalConcluidas = 0;
$fasesMapa = [];

$anteriorConcluida = true;

foreach ($fases as $fase) {
    $faseId = (int) $fase['id'];
    $concluida = isset($fasesConcluidas[$faseId]);

    // A primeira fase fica disponível.
    // As seguintes exigem a conclusão da fase anterior.
    $desbloqueada = $anteriorConcluida;

    $status = $concluida
        ? 'completed'
        : ($desbloqueada ? 'available' : 'locked');

    if ($concluida) {
        $totalConcluidas++;
    }

    $fasesMapa[] = [
        'id' => $faseId,
        'numero' => (int) $fase['numero'],
        'nome' => (string) $fase['nome'],
        'descricao' => (string) (
            $fase['descricao']
            ?? 'Explore este planeta e resolva os desafios matemáticos.'
        ),
        'dificuldade' => (string) (
            $fase['nivel_dificuldade'] ?? 'normal'
        ),
        'status' => $status,
        'concluida' => $concluida,
        'desbloqueada' => $desbloqueada,
        'url' => 'jogar_mathspace.php?fase=' . $faseId
    ];

    $anteriorConcluida = $concluida;
}

$progressoPercentual = $totalFases > 0
    ? (int) round(($totalConcluidas / $totalFases) * 100)
    : 0;

// Arquivo CSS relativo a paginas/mathspace.php.
$cssPath = '../css/mathspace.css';

// Serialização segura dos dados enviados ao JavaScript.
$fasesJson = json_encode(
    $fasesMapa,
    JSON_HEX_TAG
    | JSON_HEX_APOS
    | JSON_HEX_QUOT
    | JSON_HEX_AMP
    | JSON_UNESCAPED_UNICODE
    | JSON_UNESCAPED_SLASHES
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

    <meta name="theme-color" content="#080718">

    <title>MathSpace | Mapa Galáctico</title>

    <link
        rel="stylesheet"
        href="<?= htmlspecialchars($cssPath, ENT_QUOTES, 'UTF-8') ?>"
    >
</head>

<body>

<a class="skip-link" href="#galaxy-game">
    Ir para o mapa galáctico
</a>

<div class="game-shell">

    <!-- CABEÇALHO -->
    <header class="game-header">

        <a class="brand" href="../../inicio.php" aria-label="Voltar ao MathRun">
            <span class="brand-symbol" aria-hidden="true">M</span>

            <span>
                MATH<span class="brand-highlight">SPACE</span>
            </span>
        </a>

        <div class="pilot-info">
            <span class="pilot-avatar" aria-hidden="true">✦</span>

            <div>
                <span class="small-label">COMANDANTE</span>

                <strong>
                    <?= htmlspecialchars($nomeUsuario, ENT_QUOTES, 'UTF-8') ?>
                </strong>

                <span class="pilot-class">
                    <?php if ($serieUsuario !== ''): ?>
                        <?= htmlspecialchars($serieUsuario, ENT_QUOTES, 'UTF-8') ?>º ano
                        <?php if ($turmaUsuario !== ''): ?>
                            · <?= htmlspecialchars($turmaUsuario, ENT_QUOTES, 'UTF-8') ?>
                        <?php endif; ?>
                    <?php else: ?>
                        Série não identificada
                    <?php endif; ?>
                </span>
            </div>
        </div>

        <div class="header-actions">

            <div class="theme-switcher" role="group" aria-label="Tema visual">
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

            <a class="exit-link" href="../../inicio.php">
                Voltar ao MathRun <span aria-hidden="true">↗</span>
            </a>

        </div>
    </header>

    <main>

        <!-- APRESENTAÇÃO E PROGRESSO -->
        <section class="command-bar">

            <div class="command-heading">
                <p class="eyebrow">
                    <span class="live-dot" aria-hidden="true"></span>
                    SISTEMA DE NAVEGAÇÃO ONLINE
                </p>

                <h1>
                    Explore a <span>galáxia</span>
                </h1>

                <p class="subtitle">
                    Cada planeta guarda uma missão matemática.
                    Prepare sua nave, comandante.
                </p>
            </div>

            <section class="progress-card" aria-label="Seu progresso no MathSpace">

                <div class="progress-card-top">
                    <span>PROGRESSO DA EXPEDIÇÃO</span>
                    <strong><?= $progressoPercentual ?>%</strong>
                </div>

                <div
                    class="progress-track"
                    role="progressbar"
                    aria-label="Fases concluídas"
                    aria-valuemin="0"
                    aria-valuemax="<?= $totalFases ?>"
                    aria-valuenow="<?= $totalConcluidas ?>"
                >
                    <span style="width: <?= $progressoPercentual ?>%"></span>
                </div>

                <div class="progress-card-bottom">
                    <span>
                        <?= $totalConcluidas ?> de <?= $totalFases ?> missões
                    </span>

                    <span>
                        ✦ <?= number_format($xpTotal, 0, ',', '.') ?> XP
                    </span>
                </div>

            </section>
        </section>

        <!-- LEGENDA -->
        <section class="map-toolbar" aria-label="Legenda do mapa">

            <div class="sector-label">
                <span class="sector-icon" aria-hidden="true">⌖</span>

                <div>
                    <span class="toolbar-label">SETOR DE EXPLORAÇÃO</span>
                    <strong>
                        <?= $serieUsuario !== ''
                            ? 'Setor do ' . htmlspecialchars($serieUsuario, ENT_QUOTES, 'UTF-8') . 'º ano'
                            : 'Setor não identificado'
                        ?>
                    </strong>
                </div>
            </div>

            <div class="map-legend">
                <span><i class="legend-dot available"></i> Disponível</span>
                <span><i class="legend-dot completed"></i> Concluída</span>
                <span><i class="legend-dot locked"></i> Bloqueada</span>
            </div>

        </section>

        <?php if ($erroBanco): ?>

            <div class="notice notice-error" role="alert">
                <?= htmlspecialchars($mensagemErro, ENT_QUOTES, 'UTF-8') ?>
                Confira a conexão com o banco de dados.
            </div>

        <?php elseif ($serieUsuario === ''): ?>

            <div class="notice" role="status">
                Não conseguimos identificar sua série no cadastro.
                Confira os dados do seu perfil para carregar o mapa correto.
            </div>

        <?php elseif ($totalFases === 0): ?>

            <div class="notice" role="status">
                Ainda não há missões do MathSpace cadastradas para sua série.
                Assim que as fases forem cadastradas, elas aparecerão neste mapa.
            </div>

        <?php endif; ?>

        <!-- MAPA DESENHADO PELO PHASER -->
        <section class="galaxy-map" aria-label="Mapa interativo do MathSpace">

            <div class="map-overlay-top" aria-hidden="true">
                <span class="map-system-icon">✧</span>

                <div>
                    <strong>HOLOMAPA</strong>
                    <span>MAPEAMENTO ESTELAR ATIVO</span>
                </div>
            </div>

            <div class="map-coordinates" aria-hidden="true">
                SECTOR 04 // MATHSPACE
            </div>

            <div id="galaxy-game" aria-label="Universo interativo"></div>

            <div class="map-hud" aria-hidden="true">
                <span><i class="hud-pulse"></i> NAVEGAÇÃO ATIVA</span>
                <span><?= $totalConcluidas ?> / <?= $totalFases ?> MISSÕES</span>
            </div>

        </section>

        <!-- DETALHES DO PLANETA SELECIONADO -->
        <section class="mission-panel" id="mission-panel" aria-live="polite">

            <div class="mission-planet-icon" id="mission-icon" aria-hidden="true">
                ✦
            </div>

            <div class="mission-information">

                <span class="eyebrow" id="mission-status">
                    CENTRAL DE MISSÕES
                </span>

                <h2 id="mission-name">
                    <?php if (!empty($fasesMapa)): ?>
                        <?= htmlspecialchars($fasesMapa[0]['nome'], ENT_QUOTES, 'UTF-8') ?>
                    <?php else: ?>
                        Aguardando coordenadas
                    <?php endif; ?>
                </h2>

                <p id="mission-description">
                    <?php if (!empty($fasesMapa)): ?>
                        <?= htmlspecialchars($fasesMapa[0]['descricao'], ENT_QUOTES, 'UTF-8') ?>
                    <?php else: ?>
                        As informações das missões aparecerão aqui.
                    <?php endif; ?>
                </p>

                <div class="mission-tags">
                    <span id="mission-difficulty">Dificuldade: normal</span>
                    <span id="mission-number">Missão 1</span>
                </div>

            </div>

            <div class="mission-action">
                <a
                    href="<?= !empty($fasesMapa) && $fasesMapa[0]['desbloqueada']
                        ? htmlspecialchars($fasesMapa[0]['url'], ENT_QUOTES, 'UTF-8')
                        : '#mission-panel'
                    ?>"
                    class="launch-button"
                    id="launch-button"
                    <?= empty($fasesMapa) || empty($fasesMapa[0]['desbloqueada'])
                        ? 'aria-disabled="true"'
                        : ''
                    ?>
                >
                    <span id="launch-button-text">
                        <?= !empty($fasesMapa) && $fasesMapa[0]['concluida']
                            ? 'Jogar novamente'
                            : (!empty($fasesMapa) && $fasesMapa[0]['desbloqueada']
                                ? 'Iniciar missão'
                                : 'Missão bloqueada')
                        ?>
                    </span>
                    <span aria-hidden="true">↗</span>
                </a>
            </div>

        </section>

        <!-- VERSÃO ACESSÍVEL PARA QUEM NÃO UTILIZA O MAPA VISUAL -->
        <details class="accessible-missions">
            <summary>Ver missões em lista</summary>

            <?php if (!empty($fasesMapa)): ?>
                <div class="accessible-mission-list">
                    <?php foreach ($fasesMapa as $fase): ?>
                        <button
                            type="button"
                            class="accessible-mission"
                            data-mission-id="<?= (int) $fase['id'] ?>"
                        >
                            <strong>
                                <?= (int) $fase['numero'] ?>.
                                <?= htmlspecialchars($fase['nome'], ENT_QUOTES, 'UTF-8') ?>
                            </strong>

                            <span>
                                <?php
                                if ($fase['concluida']) {
                                    echo 'Concluída';
                                } elseif ($fase['desbloqueada']) {
                                    echo 'Disponível';
                                } else {
                                    echo 'Bloqueada';
                                }
                                ?>
                            </span>
                        </button>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p>Nenhuma missão disponível para listar.</p>
            <?php endif; ?>
        </details>

        <footer class="map-footer">
            <p>
                <span aria-hidden="true">✦</span>
                Explore, aprenda e conquiste novos planetas.
            </p>

            <button
                type="button"
                class="accessibility-toggle"
                id="motion-toggle"
                aria-pressed="false"
            >
                Reduzir animações
            </button>
        </footer>

    </main>
</div>

<!-- Biblioteca Phaser -->
<script src="https://cdn.jsdelivr.net/npm/phaser@3.90.0/dist/phaser.min.js"></script>

<script>
(() => {
    'use strict';

    const fases = <?= $fasesJson ?>;

    const root = document.documentElement;
    const gameContainer = document.getElementById('galaxy-game');

    const missionName = document.getElementById('mission-name');
    const missionDescription = document.getElementById('mission-description');
    const missionStatus = document.getElementById('mission-status');
    const missionDifficulty = document.getElementById('mission-difficulty');
    const missionNumber = document.getElementById('mission-number');
    const missionIcon = document.getElementById('mission-icon');
    const launchButton = document.getElementById('launch-button');
    const launchButtonText = document.getElementById('launch-button-text');
    const motionToggle = document.getElementById('motion-toggle');

    let reduzirMovimento = false;
    let cenaJogo = null;
    let planetaSelecionado = null;

    // ------------------------------------------
    // TEMAS VISUAIS
    // ------------------------------------------

    const botoesTema = document.querySelectorAll('[data-theme-option]');

    function aplicarTema(tema) {
        root.dataset.theme = tema;

        botoesTema.forEach(botao => {
            botao.setAttribute(
                'aria-pressed',
                String(botao.dataset.themeOption === tema)
            );
        });
    }

    botoesTema.forEach(botao => {
        botao.addEventListener('click', () => {
            aplicarTema(botao.dataset.themeOption);
        });
    });

    aplicarTema('dark');

    // ------------------------------------------
    // DETALHES DA MISSÃO
    // ------------------------------------------

    function selecionarMissao(fase) {
        if (!fase) return;

        planetaSelecionado = fase;

        missionName.textContent = fase.nome;
        missionDescription.textContent = fase.descricao;
        missionDifficulty.textContent =
            'Dificuldade: ' + fase.dificuldade;
        missionNumber.textContent =
            'Missão ' + fase.numero;

        if (fase.status === 'completed') {
            missionStatus.textContent = 'MISSÃO CONCLUÍDA';
            missionIcon.textContent = '✓';
            launchButtonText.textContent = 'Jogar novamente';
            launchButton.href = fase.url;
            launchButton.setAttribute('aria-disabled', 'false');
        } else if (fase.status === 'available') {
            missionStatus.textContent = 'MISSÃO DISPONÍVEL';
            missionIcon.textContent = '✦';
            launchButtonText.textContent = 'Iniciar missão';
            launchButton.href = fase.url;
            launchButton.setAttribute('aria-disabled', 'false');
        } else {
            missionStatus.textContent = 'SINAL BLOQUEADO';
            missionIcon.textContent = '🔒';
            launchButtonText.textContent = 'Missão bloqueada';
            launchButton.href = '#mission-panel';
            launchButton.setAttribute('aria-disabled', 'true');
        }

        if (cenaJogo) {
            cenaJogo.destacarPlaneta(fase.id);
        }
    }

    launchButton.addEventListener('click', event => {
        if (launchButton.getAttribute('aria-disabled') === 'true') {
            event.preventDefault();
        }
    });

    document.querySelectorAll('[data-mission-id]').forEach(botao => {
        botao.addEventListener('click', () => {
            const fase = fases.find(
                item => item.id === Number(botao.dataset.missionId)
            );

            selecionarMissao(fase);
        });
    });

    // ------------------------------------------
    // SE O PHASER NÃO CARREGAR
    // ------------------------------------------

    if (typeof Phaser === 'undefined') {
        gameContainer.innerHTML =
            '<p class="game-error">Não foi possível carregar o Phaser. ' +
            'Confira sua conexão com a internet e recarregue a página.</p>';
        return;
    }

    // ------------------------------------------
    // CONFIGURAÇÃO DO UNIVERSO
    // ------------------------------------------

    const largura = 1200;
    const altura = 720;

    const config = {
        type: Phaser.AUTO,
        parent: 'galaxy-game',
        width: largura,
        height: altura,
        backgroundColor: '#050718',

        scale: {
            mode: Phaser.Scale.FIT,
            autoCenter: Phaser.Scale.CENTER_BOTH
        },

        render: {
            antialias: true,
            pixelArt: false
        },

        scene: {
            create: criarUniverso,
            update: atualizarUniverso
        }
    };

    new Phaser.Game(config);

    // ------------------------------------------
    // CENA PRINCIPAL
    // ------------------------------------------

    function criarUniverso() {
        const cena = this;
        cenaJogo = cena;

        cena.estrelas = [];
        cena.planetas = [];
        cena.tempo = 0;
        cena.reduzirMovimento = reduzirMovimento;

        // Fundo galáctico
        cena.add.rectangle(
            largura / 2,
            altura / 2,
            largura,
            altura,
            0x050718
        );

        criarNebulosas(cena);
        criarEstrelas(cena);
        criarRota(cena);
        criarPlanetasDoBanco(cena);
        criarEstrelasCadentes(cena);

        // Legenda dentro da própria cena
        cena.add.text(28, 86, 'SISTEMA ESTELAR', {
            fontFamily: 'Arial',
            fontSize: '13px',
            color: '#a8b9ff',
            fontStyle: 'bold',
            letterSpacing: 3
        }).setAlpha(0.85);

        cena.destacarPlaneta = id => {
            cena.planetas.forEach(planeta => {
                const selecionado = planeta.fase.id === Number(id);
                planeta.selecionado = selecionado;

                cena.tweens.add({
                    targets: planeta.aura,
                    alpha: selecionado ? 0.45 : 0.12,
                    duration: reduzirMovimento ? 0 : 180
                });
            });
        };

        // Selecionar a primeira missão automaticamente.
        if (fases.length > 0) {
            selecionarMissao(fases[0]);
            cena.destacarPlaneta(fases[0].id);
        }
    }

    // ------------------------------------------
    // NEBULOSAS
    // ------------------------------------------

    function criarNebulosas(cena) {
        const nuvens = [
            { x: 180, y: 180, cor: 0x762be8, raio: 250 },
            { x: 670, y: 250, cor: 0x154ed8, raio: 280 },
            { x: 930, y: 520, cor: 0xb52cbf, raio: 250 },
            { x: 370, y: 570, cor: 0x1b9ac4, raio: 230 }
        ];

        nuvens.forEach((nuvem, indice) => {
            const halo = cena.add.circle(
                nuvem.x,
                nuvem.y,
                nuvem.raio,
                nuvem.cor,
                0.07
            );

            halo.setBlendMode(Phaser.BlendModes.ADD);

            const haloInterno = cena.add.circle(
                nuvem.x,
                nuvem.y,
                nuvem.raio * 0.55,
                nuvem.cor,
                0.07
            );

            haloInterno.setBlendMode(Phaser.BlendModes.ADD);

            if (!reduzirMovimento) {
                cena.tweens.add({
                    targets: [halo, haloInterno],
                    alpha: { from: 0.035, to: 0.11 },
                    scale: { from: 0.92, to: 1.08 },
                    duration: 4500 + indice * 600,
                    yoyo: true,
                    repeat: -1,
                    ease: 'Sine.inOut'
                });
            }
        });
    }

    // ------------------------------------------
    // ESTRELAS
    // ------------------------------------------

    function criarEstrelas(cena) {
        for (let i = 0; i < 230; i++) {
            const tamanho = Phaser.Math.FloatBetween(0.7, 2.4);
            const brilho = Phaser.Math.FloatBetween(0.25, 0.95);

            const estrela = cena.add.circle(
                Phaser.Math.Between(0, largura),
                Phaser.Math.Between(0, altura),
                tamanho,
                i % 9 === 0 ? 0xb9d9ff : 0xffffff,
                brilho
            );

            cena.estrelas.push({
                objeto: estrela,
                velocidade: Phaser.Math.FloatBetween(0.03, 0.22),
                alphaOriginal: brilho
            });

            if (!reduzirMovimento && i % 3 === 0) {
                cena.tweens.add({
                    targets: estrela,
                    alpha: Math.max(0.1, brilho * 0.2),
                    duration: Phaser.Math.Between(700, 2800),
                    yoyo: true,
                    repeat: -1,
                    delay: Math.random() * 1800
                });
            }
        }
    }

    // ------------------------------------------
    // ROTA ESPACIAL ENTRE AS FASES
    // ------------------------------------------

    function calcularPosicoes() {
        const quantidade = fases.length;
        const posicoes = [];

        const esquerda = 140;
        const direita = 1060;
        const larguraUtil = direita - esquerda;

        fases.forEach((fase, indice) => {
            let x;
            let y;

            if (quantidade === 1) {
                x = largura / 2;
                y = altura / 2;
            } else {
                const progresso = indice / (quantidade - 1);

                x = esquerda + larguraUtil * progresso;

                // Caminho ondulado para parecer uma rota galáctica.
                y = 350 + Math.sin(indice * 1.35) * 150;

                if (indice % 2 === 1) {
                    y -= 35;
                }

                y = Phaser.Math.Clamp(y, 190, 520);
            }

            posicoes.push({ fase, x, y });
        });

        return posicoes;
    }

    function criarRota(cena) {
        const posicoes = calcularPosicoes();

        if (posicoes.length < 2) return;

        // Linha de rota principal
        const rota = cena.add.graphics();

        rota.lineStyle(2, 0x8d8bff, 0.3);
        rota.beginPath();
        rota.moveTo(posicoes[0].x, posicoes[0].y);

        for (let i = 1; i < posicoes.length; i++) {
            rota.lineTo(posicoes[i].x, posicoes[i].y);
        }

        rota.strokePath();

        // Pequenos pontos luminosos no caminho
        posicoes.forEach((posicao, indice) => {
            if (indice === 0) return;

            const ponto = cena.add.circle(
                (posicoes[indice - 1].x + posicao.x) / 2,
                (posicoes[indice - 1].y + posicao.y) / 2,
                3,
                0x9b9cff,
                0.75
            );

            if (!reduzirMovimento) {
                cena.tweens.add({
                    targets: ponto,
                    alpha: 0.2,
                    duration: 1100,
                    yoyo: true,
                    repeat: -1
                });
            }
        });
    }

    // ------------------------------------------
    // PLANETAS GERADOS A PARTIR DAS FASES
    // ------------------------------------------

    function criarPlanetasDoBanco(cena) {
        const posicoes = calcularPosicoes();

        posicoes.forEach((posicao, indice) => {
            criarPlaneta(cena, posicao, indice);
        });
    }

    function criarPlaneta(cena, posicao, indice) {
        const fase = posicao.fase;

        const grupo = cena.add.container(posicao.x, posicao.y);

        const corPorIndice = [
            0x9b67ff,
            0x45cfff,
            0xffa66b,
            0xf27dc6,
            0x64e6b5,
            0xe5d17a
        ];

        const cor = fase.status === 'locked'
            ? 0x5c6682
            : corPorIndice[indice % corPorIndice.length];

        const raio = 38 + (indice % 3) * 5;

        // Aura externa
        const aura = cena.add.circle(
            0, 0, raio * 1.8, cor, 0.12
        );

        aura.setBlendMode(Phaser.BlendModes.ADD);

        // Anéis do planeta
        const aneis = cena.add.graphics();

        aneis.lineStyle(4, cor, 0.72);
        aneis.strokeEllipse(0, 0, raio * 2.6, raio * 0.82);

        aneis.lineStyle(8, cor, 0.14);
        aneis.strokeEllipse(0, 0, raio * 2.8, raio * 0.94);

        // Sombra por trás da esfera
        const sombra = cena.add.circle(
            5, 6, raio, 0x02030c, 0.8
        );

        // Esfera principal
        const esfera = cena.add.circle(
            0, 0, raio, cor
        );

        esfera.setStrokeStyle(2, 0xe0e7ff, 0.6);

        // Camada de sombra para dar volume
        const sombraSuperficie = cena.add.circle(
            raio * 0.22,
            raio * 0.12,
            raio * 0.88,
            0x050718,
            0.25
        );

        // Manchas e crateras
        const detalhes = cena.add.graphics();

        for (let i = 0; i < 8; i++) {
            const angulo = (Math.PI * 2 * i) / 8;
            const distancia = raio * Phaser.Math.FloatBetween(0.2, 0.68);

            const cx = Math.cos(angulo) * distancia;
            const cy = Math.sin(angulo) * distancia;
            const tamanho = Phaser.Math.Between(3, 8);

            detalhes.fillStyle(
                i % 2 === 0 ? 0xffffff : 0x11132f,
                0.15
            );

            detalhes.fillCircle(cx, cy, tamanho);
        }

        // Faixa atmosférica
        const atmosfera = cena.add.graphics();

        atmosfera.lineStyle(3, 0xffffff, 0.22);
        atmosfera.beginPath();
        atmosfera.arc(
            0, 0,
            raio * 0.83,
            Phaser.Math.DegToRad(205),
            Phaser.Math.DegToRad(315)
        );
        atmosfera.strokePath();

        grupo.add([
            aura,
            aneis,
            sombra,
            esfera,
            sombraSuperficie,
            detalhes,
            atmosfera
        ]);

        // Ícone central conforme o estado da missão
        const simbolo = fase.status === 'completed'
            ? '✓'
            : fase.status === 'locked'
                ? '🔒'
                : String(fase.numero);

        const textoSimbolo = cena.add.text(
            0, 0, simbolo,
            {
                fontFamily: 'Arial',
                fontSize: fase.status === 'locked' ? '18px' : '22px',
                color: '#ffffff',
                fontStyle: 'bold'
            }
        ).setOrigin(0.5);

        grupo.add(textoSimbolo);

        // Nome e número abaixo do planeta
        const nome = cena.add.text(
            0, raio + 28,
            fase.nome,
            {
                fontFamily: 'Arial',
                fontSize: '15px',
                color: fase.status === 'locked' ? '#9aa2bc' : '#ffffff',
                fontStyle: 'bold',
                align: 'center',
                stroke: '#080718',
                strokeThickness: 4,
                wordWrap: { width: 190 },
                align: 'center'
            }
        ).setOrigin(0.5);

        grupo.add(nome);

        const numero = cena.add.text(
            0, raio + 55,
            fase.status === 'completed'
                ? 'SETOR CONCLUÍDO'
                : fase.status === 'locked'
                    ? 'SETOR BLOQUEADO'
                    : 'SINAL DISPONÍVEL',
            {
                fontFamily: 'Arial',
                fontSize: '10px',
                color: fase.status === 'completed'
                    ? '#72f0bf'
                    : fase.status === 'locked'
                        ? '#8c94ad'
                        : '#80eaff',
                letterSpacing: 1
            }
        ).setOrigin(0.5);

        grupo.add(numero);

        // Área clicável
        const areaClique = cena.add.circle(
            0, 0, raio * 1.55, 0xffffff, 0.001
        );

        areaClique.setInteractive({ useHandCursor: true });
        grupo.add(areaClique);

        areaClique.on('pointerover', () => {
            selecionarMissao(fase);

            if (!reduzirMovimento) {
                cena.tweens.add({
                    targets: grupo,
                    scale: 1.08,
                    duration: 160,
                    yoyo: true
                });
            }
        });

        areaClique.on('pointerdown', () => {
            selecionarMissao(fase);

            cena.cameras.main.flash(
                130,
                100,
                90,
                255,
                false
            );
        });

        // Animações individuais
        if (!reduzirMovimento) {
            cena.tweens.add({
                targets: aura,
                alpha: { from: 0.06, to: 0.24 },
                scale: { from: 0.92, to: 1.12 },
                duration: 1600 + indice * 180,
                yoyo: true,
                repeat: -1,
                ease: 'Sine.inOut'
            });

            cena.tweens.add({
                targets: grupo,
                y: posicao.y - 8,
                duration: 2200 + indice * 200,
                yoyo: true,
                repeat: -1,
                ease: 'Sine.inOut'
            });
        }

        cena.planetas.push({
            fase,
            grupo,
            aura,
            esfera,
            selecionado: false
        });
    }

    // ------------------------------------------
    // ESTRELAS CADENTES
    // ------------------------------------------

    function criarEstrelasCadentes(cena) {
        if (reduzirMovimento) return;

        cena.time.addEvent({
            delay: 1800,
            loop: true,
            callback: () => {
                const x = Phaser.Math.Between(100, 1050);
                const y = Phaser.Math.Between(80, 390);

                const estrelaCadente = cena.add.graphics();

                estrelaCadente.lineStyle(2, 0xbceeff, 0.95);
                estrelaCadente.beginPath();
                estrelaCadente.moveTo(0, 0);
                estrelaCadente.lineTo(-65, -24);
                estrelaCadente.strokePath();

                estrelaCadente.setPosition(x, y);

                cena.tweens.add({
                    targets: estrelaCadente,
                    x: x + 190,
                    y: y + 75,
                    alpha: 0,
                    duration: 850,
                    onComplete: () => estrelaCadente.destroy()
                });
            }
        });
    }

    // ------------------------------------------
    // ANIMAÇÃO CONTÍNUA
    // ------------------------------------------

    function atualizarUniverso(tempo, delta) {
        if (reduzirMovimento) return;

        this.tempo += delta;

        this.estrelas.forEach(item => {
            item.objeto.y += item.velocidade * (delta / 16.67);

            if (item.objeto.y > altura) {
                item.objeto.y = 0;
                item.objeto.x = Phaser.Math.Between(0, largura);
            }
        });
    }

    // ------------------------------------------
    // ACESSIBILIDADE: REDUZIR MOVIMENTO
    // ------------------------------------------

    motionToggle.addEventListener('click', () => {
        reduzirMovimento = !reduzirMovimento;

        motionToggle.setAttribute(
            'aria-pressed',
            String(reduzirMovimento)
        );

        motionToggle.textContent = reduzirMovimento
            ? 'Animações reduzidas: ativado'
            : 'Reduzir animações';

        // A cena será atualizada quando recriada.
        // As animações existentes são pausadas ou retomadas.
        if (cenaJogo) {
            cenaJogo.tweens.getAllTweens().forEach(tween => {
                if (reduzirMovimento) {
                    tween.pause();
                } else {
                    tween.resume();
                }
            });

            if (reduzirMovimento) {
                cenaJogo.time.paused = true;
            } else {
                cenaJogo.time.paused = false;
            }
        }
    });

})();
</script>

</body>
</html>
