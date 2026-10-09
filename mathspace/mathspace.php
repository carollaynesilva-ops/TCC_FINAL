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
    die('Série escolar inválida.');
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

$totalFases = count($fases);
$fasesConcluidas = 0;
$pontuacaoTotal = 0;
$proximaDesbloqueada = true;

foreach ($fases as &$fase) {
    $fase['concluida'] = (int) $fase['concluida'];
    $fase['melhor_pontuacao'] = (int) $fase['melhor_pontuacao'];

    $fase['desbloqueada'] = $proximaDesbloqueada;

    if ($fase['concluida'] === 1) {
        $fasesConcluidas++;
        $pontuacaoTotal += $fase['melhor_pontuacao'];
    }

    $proximaDesbloqueada = $fase['concluida'] === 1;
}
unset($fase);

$percentual = $totalFases > 0
    ? (int) round(($fasesConcluidas / $totalFases) * 100)
    : 0;

$planetas = [
    [
        'icone' => '🌙',
        'classe' => 'lua',
        'subtitulo' => 'O começo da jornada',
        'coordenada' => '01'
    ],
    [
        'icone' => '🔴',
        'classe' => 'marte',
        'subtitulo' => 'Prepare-se para o desafio',
        'coordenada' => '02'
    ],
    [
        'icone' => '🪐',
        'classe' => 'asteroides',
        'subtitulo' => 'Desvie dos obstáculos',
        'coordenada' => '03'
    ],
    [
        'icone' => '🌌',
        'classe' => 'estacao',
        'subtitulo' => 'O desafio final',
        'coordenada' => '04'
    ]
];

function escapar($valor)
{
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}

function dificuldadeTexto($dificuldade)
{
    $dificuldade = strtolower(trim((string) $dificuldade));

    return match ($dificuldade) {
        'facil', 'fácil' => 'Fácil',
        'medio', 'médio' => 'Médio',
        'dificil', 'difícil' => 'Difícil',
        default => ucfirst($dificuldade ?: 'Exploração')
    };
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

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

    <header class="game-header">
        <a href="../inicio.php" class="game-logo">
            <span class="logo-icon">✦</span>
            <span>Math<span class="logo-highlight">Run</span></span>
        </a>

        <nav class="game-nav" aria-label="Navegação principal">
            <a href="../inicio.php">Início</a>
            <a href="../perfil.php">Meu perfil</a>
            <a href="../conquistas.php">Conquistas</a>
        </nav>

        <div class="header-player">
            <span class="player-status"></span>
            <span>Explorador espacial</span>
        </div>
    </header>

    <main class="space-container">

        <section class="hero-section">
            <div class="hero-copy">
                <div class="eyebrow">
                    <span class="eyebrow-dot"></span>
                    CENTRO DE EXPLORAÇÃO MATHRUN
                </div>

                <h1>
                    Sua próxima missão
                    <span>está entre as estrelas.</span>
                </h1>

                <p>
                    Explore planetas, resolva desafios matemáticos e avance
                    pelo universo. Cada resposta aproxima você da próxima descoberta.
                </p>

                <div class="hero-tags">
                    <span>✦ Série <?= escapar($serie) ?>º ano</span>
                    <span>🚀 <?= $totalFases ?> missões</span>
                    <span>◈ <?= $fasesConcluidas ?> concluídas</span>
                </div>
            </div>

            <div class="hero-planet" aria-hidden="true">
                <div class="planet-orbit orbit-one"></div>
                <div class="planet-orbit orbit-two"></div>
                <div class="planet-core">
                    <div class="planet-crater crater-one"></div>
                    <div class="planet-crater crater-two"></div>
                    <div class="planet-crater crater-three"></div>
                </div>
                <span class="planet-satellite">✦</span>
                <span class="planet-spark spark-one">✧</span>
                <span class="planet-spark spark-two">✦</span>
            </div>
        </section>

        <section class="pilot-panel">
            <div class="pilot-info">
                <div class="pilot-avatar">👩‍🚀</div>

                <div>
                    <span class="small-label">COMANDANTE DA EXPEDIÇÃO</span>
                    <h2><?= escapar($aluno['nome']) ?></h2>
                    <p>
                        <?= escapar($aluno['turma'] ?? 'Turma não informada') ?>
                        · <?= escapar($serie) ?>º ano
                    </p>
                </div>
            </div>

            <div class="pilot-stat">
                <span class="stat-icon">🏆</span>
                <div>
                    <span class="small-label">PONTUAÇÃO</span>
                    <strong><?= number_format($pontuacaoTotal, 0, ',', '.') ?></strong>
                    <small>pontos acumulados</small>
                </div>
            </div>

            <div class="pilot-progress">
                <div class="progress-heading">
                    <span class="small-label">PROGRESSO GALÁCTICO</span>
                    <strong><?= $percentual ?>%</strong>
                </div>

                <div
                    class="progress-track"
                    role="progressbar"
                    aria-label="Progresso das missões"
                    aria-valuenow="<?= $percentual ?>"
                    aria-valuemin="0"
                    aria-valuemax="100">
                    <div
                        class="progress-fill"
                        data-progress="<?= $percentual ?>"></div>
                </div>

                <small>
                    <?= $fasesConcluidas ?> de <?= $totalFases ?>
                    missões concluídas
                </small>
            </div>
        </section>

        <section class="missions-section">
            <div class="section-heading">
                <div>
                    <span class="small-label">MAPA DE NAVEGAÇÃO</span>
                    <h2>Escolha seu próximo destino<span>.</span></h2>
                    <p>Cada planeta guarda um novo desafio matemático.</p>
                </div>

                <div class="map-indicator">
                    <span class="signal-dot"></span>
                    SISTEMA ONLINE
                </div>
            </div>

            <?php if (empty($fases)): ?>
                <div class="empty-space">
                    <span>🛰️</span>
                    <h3>Nenhuma missão encontrada</h3>
                    <p>
                        Ainda não há missões cadastradas para o
                        <?= escapar($serie) ?>º ano.
                    </p>
                </div>
            <?php else: ?>

                <div class="galaxy-map">
                    <div class="map-route" aria-hidden="true"></div>

                    <?php foreach ($fases as $indice => $fase): ?>
                        <?php
                        $planeta = $planetas[$indice % count($planetas)];

                        $concluida = $fase['concluida'] === 1;
                        $desbloqueada = $fase['desbloqueada'];
                        $bloqueada = !$desbloqueada;

                        $statusClasse = $concluida
                            ? 'mission-completed'
                            : ($bloqueada
                                ? 'mission-locked'
                                : 'mission-available');

                        $statusTexto = $concluida
                            ? 'Missão concluída'
                            : ($bloqueada
                                ? 'Missão bloqueada'
                                : 'Pronto para decolar');

                        $dificuldade = dificuldadeTexto(
                            $fase['nivel_dificuldade']
                        );
                        ?>

                        <article
                            class="mission-card <?= escapar($statusClasse) ?>"
                            style="--mission-index: <?= (int) $indice ?>">
                            <div class="mission-topline">
                                <span class="mission-number">
                                    SETOR <?= escapar($planeta['coordenada']) ?>
                                </span>

                                <?php if ($concluida): ?>
                                    <span class="mission-status completed">
                                        ✓ CONCLUÍDA
                                    </span>
                                <?php elseif ($bloqueada): ?>
                                    <span class="mission-status locked">
                                        🔒 BLOQUEADA
                                    </span>
                                <?php else: ?>
                                    <span class="mission-status available">
                                        ● DISPONÍVEL
                                    </span>
                                <?php endif; ?>
                            </div>

                            <div class="mission-planet <?= escapar($planeta['classe']) ?>">
                                <div class="planet-ring"></div>
                                <span class="planet-emoji">
                                    <?= escapar($planeta['icone']) ?>
                                </span>

                                <?php if ($bloqueada): ?>
                                    <span class="lock-overlay">🔒</span>
                                <?php elseif ($concluida): ?>
                                    <span class="planet-check">✓</span>
                                <?php endif; ?>
                            </div>

                            <div class="mission-content">
                                <span class="mission-kicker">
                                    <?= escapar($planeta['subtitulo']) ?>
                                </span>

                                <h3><?= escapar($fase['nome']) ?></h3>

                                <p>
                                    <?= escapar(
                                        $fase['descricao']
                                            ?: 'Prepare-se para resolver desafios e explorar este setor.'
                                    ) ?>
                                </p>

                                <div class="mission-details">
                                    <span class="difficulty">
                                        <span class="difficulty-dot <?= strtolower($dificuldade) ?>"></span>
                                        <?= escapar($dificuldade) ?>
                                    </span>

                                    <span class="mission-points">
                                        ✦ <?= number_format(
                                                $fase['melhor_pontuacao'],
                                                0,
                                                ',',
                                                '.'
                                            ) ?> pts
                                    </span>
                                </div>

                                <?php if ($bloqueada): ?>
                                    <button
                                        class="mission-button button-locked"
                                        type="button"
                                        disabled>
                                        <span>Complete a missão anterior</span>
                                        <span>🔒</span>
                                    </button>
                                <?php else: ?>
                                    <a
                                        class="mission-button <?= $concluida ? 'button-replay' : '' ?>"
                                        href="jogar_mathspace.php?fase_id=<?= (int) $fase['id'] ?>">
                                        <span>
                                            <?= $concluida
                                                ? 'Jogar novamente'
                                                : 'Iniciar missão' ?>
                                        </span>
                                        <span class="button-arrow">↗</span>
                                    </a>
                                <?php endif; ?>
                            </div>

                            <span class="card-orbit-decoration" aria-hidden="true"></span>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <section class="mission-tip">
            <div class="tip-icon">💡</div>
            <div>
                <strong>Transmissão da central</strong>
                <p>
                    Resolva uma missão por vez. Suas descobertas ajudam a
                    desbloquear novos setores da galáxia.
                </p>
            </div>
            <span class="tip-signal">● AO VIVO</span>
        </section>

        <footer class="game-footer">
            <span>✦ MATHRUN SPACE EXPLORATION</span>
            <span>O universo é enorme. Sua curiosidade também pode ser.</span>
        </footer>
    </main>
</body>

</html>