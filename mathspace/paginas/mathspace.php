<?php
session_start();

require_once __DIR__ . '/../config/config.php';

// Verifica se existe um aluno conectado.
$usuarioId = $_SESSION['usuario_id']
    ?? $_SESSION['id']
    ?? $_SESSION['user_id']
    ?? null;

if (!$usuarioId) {
    header('Location: ../login.php');
    exit;
}

// Busca os dados do aluno no banco.
$stmt = $pdo->prepare("
    SELECT id, nome, serie, turma
    FROM usuarios
    WHERE id = ?
    LIMIT 1
");
$stmt->execute([$usuarioId]);
$aluno = $stmt->fetch();

if (!$aluno) {
    session_destroy();
    header('Location: ../login.php');
    exit;
}

$serie = (int) $aluno['serie'];

if (!in_array($serie, [6, 7, 8, 9], true)) {
    die('Sua série não está configurada para o MathSpace.');
}

// Busca somente as fases do MathSpace da série do aluno.
// O progresso é individual, mesmo para alunos da mesma turma.
$stmt = $pdo->prepare("
    SELECT
        f.id,
        f.nome,
        f.descricao,
        f.nivel_dificuldade,
        f.numero,
        p.concluida,
        p.melhor_pontuacao
    FROM fases f
    LEFT JOIN progresso_usuario p
        ON p.fase_id = f.id
        AND p.usuario_id = ?
    WHERE f.jogo_id = 2
      AND f.serie = ?
    ORDER BY f.numero ASC
");
$stmt->execute([$usuarioId, $serie]);
$fases = $stmt->fetchAll();

// Calcula o progresso e quais missões estão liberadas.
$totalFases = count($fases);
$fasesConcluidas = 0;
$totalPontos = 0;
$fasesLiberadas = [];

foreach ($fases as $indice => $fase) {
    $concluida = (bool) $fase['concluida'];

    if ($concluida) {
        $fasesConcluidas++;
    }

    $totalPontos += (int) ($fase['melhor_pontuacao'] ?? 0);

    // A primeira missão fica liberada.
    // As seguintes dependem da conclusão da missão anterior.
    $liberada = ($indice === 0);

    if ($indice > 0) {
        $liberada = (bool) $fases[$indice - 1]['concluida'];
    }

    $fasesLiberadas[$indice] = $liberada;
}

$porcentagem = $totalFases > 0
    ? (int) round(($fasesConcluidas / $totalFases) * 100)
    : 0;

$nomeAluno = htmlspecialchars(
    $aluno['nome'] ?? 'Explorador',
    ENT_QUOTES,
    'UTF-8'
);

$nomesDificuldade = [
    'facil' => 'Fácil',
    'medio' => 'Intermediária',
    'dificil' => 'Desafiadora'
];

$iconesFases = [
    1 => '🌙',
    2 => '🪐',
    3 => '☄️',
    4 => '🚀'
];
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

    <header class="space-header">
        <a href="../inicio.php" class="brand">
            <span class="brand-icon">✦</span>
            <span>MathRun</span>
        </a>

        <nav class="space-nav" aria-label="Navegação principal">
            <a href="../inicio.php">Início</a>
            <a href="../perfil.php">Meu perfil</a>
            <a href="../conquistas.php">Conquistas</a>
        </nav>

        <a href="../logout.php" class="logout-link">Sair</a>
    </header>

    <main class="space-container">

        <section class="space-hero">
            <div class="hero-content">
                <span class="eyebrow">
                    ✦ CENTRAL DE EXPLORAÇÃO
                </span>

                <h1>
                    Sua próxima aventura é
                    <span>fora deste mundo.</span>
                </h1>

                <p>
                    Olá, <?= $nomeAluno ?>!
                    Prepare sua nave, resolva os desafios
                    e desbloqueie novas regiões do universo.
                </p>

                <div class="hero-tags">
                    <span>🎓 <?= $serie ?>º ano</span>
                    <span>🪐 MathSpace</span>
                </div>
            </div>

            <div class="planet-art" aria-hidden="true">
                <div class="orbit orbit-one"></div>
                <div class="orbit orbit-two"></div>
                <div class="planet">✦</div>
                <span class="star star-one">✦</span>
                <span class="star star-two">✧</span>
                <span class="star star-three">✦</span>
            </div>
        </section>

        <section class="progress-panel">
            <div class="progress-heading">
                <div>
                    <span class="section-kicker">SUA JORNADA</span>
                    <h2>Progresso da exploração</h2>
                </div>

                <strong class="progress-number">
                    <?= $porcentagem ?>%
                </strong>
            </div>

            <div
                class="progress-track"
                role="progressbar"
                aria-label="Progresso das missões"
                aria-valuenow="<?= $porcentagem ?>"
                aria-valuemin="0"
                aria-valuemax="100">
                <div
                    class="progress-fill"
                    style="width: <?= $porcentagem ?>%"></div>
            </div>

            <div class="progress-details">
                <span>
                    <?= $fasesConcluidas ?> de <?= $totalFases ?>
                    missões concluídas
                </span>

                <span>🏆 <?= $totalPontos ?> pontos de melhor resultado</span>
            </div>
        </section>

        <section class="missions-section">
            <div class="section-heading">
                <div>
                    <span class="section-kicker">MAPA ESTELAR</span>
                    <h2>Escolha sua missão</h2>
                    <p>
                        Cada missão concluída aproxima você
                        do próximo destino.
                    </p>
                </div>

                <span class="mission-counter">
                    <?= $fasesConcluidas ?>/<?= $totalFases ?> concluídas
                </span>
            </div>

            <?php if (empty($fases)): ?>

                <div class="empty-state">
                    <span>🛰️</span>
                    <h3>Nenhuma missão encontrada</h3>
                    <p>
                        Não encontramos fases do MathSpace
                        cadastradas para o seu ano escolar.
                    </p>
                </div>

            <?php else: ?>

                <div class="missions-grid">

                    <?php foreach ($fases as $indice => $fase): ?>

                        <?php
                        $concluida = (bool) $fase['concluida'];
                        $liberada = $fasesLiberadas[$indice];
                        $bloqueada = !$liberada && !$concluida;

                        $classeEstado = $concluida
                            ? 'completed'
                            : ($bloqueada ? 'locked' : 'available');

                        $estadoTexto = $concluida
                            ? 'Concluída'
                            : ($bloqueada ? 'Bloqueada' : 'Disponível');

                        $icone = $iconesFases[(int) $fase['numero']]
                            ?? '🪐';

                        $dificuldade = $nomesDificuldade[$fase['nivel_dificuldade']] ?? 'Não definida';
                        ?>

                        <article class="mission-card <?= $classeEstado ?>">

                            <div class="mission-card-top">
                                <span class="mission-icon">
                                    <?= $icone ?>
                                </span>

                                <span class="mission-status">
                                    <?php if ($concluida): ?>
                                        ✓ <?= $estadoTexto ?>
                                    <?php elseif ($bloqueada): ?>
                                        🔒 <?= $estadoTexto ?>
                                    <?php else: ?>
                                        ✦ <?= $estadoTexto ?>
                                    <?php endif; ?>
                                </span>
                            </div>

                            <span class="mission-number">
                                MISSÃO <?= (int) $fase['numero'] ?>
                            </span>

                            <h3>
                                <?= htmlspecialchars(
                                    $fase['nome'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </h3>

                            <p class="mission-description">
                                <?= htmlspecialchars(
                                    $fase['descricao'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </p>

                            <div class="mission-meta">
                                <span>
                                    ◈ <?= htmlspecialchars(
                                            $dificuldade,
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                </span>

                                <span>
                                    🏆 <?= (int) (
                                            $fase['melhor_pontuacao'] ?? 0
                                        ) ?> pts
                                </span>
                            </div>

                            <?php if ($bloqueada): ?>

                                <button
                                    class="mission-button"
                                    type="button"
                                    disabled>
                                    Complete a missão anterior
                                </button>

                            <?php else: ?>

                                <a
                                    class="mission-button"
                                    href="jogar_mathspace.php?fase_id=<?= (int) $fase['id'] ?>">
                                    <?= $concluida
                                        ? 'Revisitar missão'
                                        : 'Explorar missão' ?>
                                    <span aria-hidden="true">→</span>
                                </a>

                            <?php endif; ?>

                        </article>

                    <?php endforeach; ?>

                </div>

            <?php endif; ?>
        </section>

        <footer class="space-footer">
            <span>MathRun · MathSpace</span>
            <span>Continue explorando. Continue aprendendo. ✦</span>
        </footer>

    </main>

</body>

</html>