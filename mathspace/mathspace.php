<?php
/*
 * MathSpace — mapa galáctico de missões
 * Integração esperada: config.php ou conexao.php, sessão de usuário,
 * tabelas jogos, fases, progresso_usuario e usuarios.
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (file_exists(__DIR__ . '/../config.php')) {
    require_once __DIR__ . '/../config.php';
} elseif (file_exists(__DIR__ . '/conexao.php')) {
    require_once __DIR__ . '/conexao.php';
}

function h($valor): string
{
    return htmlspecialchars((string)$valor, ENT_QUOTES, 'UTF-8');
}

/* Aceita os nomes de conexão mais comuns para não amarrar a página a um único arquivo. */
$db = null;
foreach (['conn', 'conexao', 'mysqli', 'ligacao', 'ligar', 'conecta'] as $variavel) {
    if (isset($$variavel) && ($$variavel instanceof mysqli || $$variavel instanceof PDO)) {
        $db = $$variavel;
        break;
    }
}
if (!$db && isset($pdo) && $pdo instanceof PDO) {
    $db = $pdo;
}

$usuarioId = (int)($_SESSION['usuario_id']
    ?? $_SESSION['id_usuario']
    ?? $_SESSION['id']
    ?? ($_SESSION['usuario']['id'] ?? 0));

if ($usuarioId <= 0) {
    header('Location: login.php');
    exit;
}

$nomeUsuario = $_SESSION['nome']
    ?? $_SESSION['usuario_nome']
    ?? ($_SESSION['usuario']['nome'] ?? 'Explorador');

$serieUsuario = (int)($_SESSION['serie']
    ?? ($_SESSION['usuario']['serie'] ?? 0));

$turmaUsuario = $_SESSION['turma']
    ?? ($_SESSION['usuario']['turma'] ?? '');

$xpUsuario = 0;
$nivelUsuario = 1;
$pontuacaoUsuario = 0;
$fases = [];
$progresso = [];
$erroBanco = '';

try {
    if (!$db) {
        throw new RuntimeException('Não encontrei uma conexão mysqli ou PDO em config.php/conexao.php.');
    }

    if ($db instanceof mysqli) {
        $db->set_charset('utf8mb4');

        $stmtUsuario = $db->prepare('SELECT nome, serie, turma, xp, nivel, pontuacao_total FROM usuarios WHERE id = ? LIMIT 1');
        $stmtUsuario->bind_param('i', $usuarioId);
        $stmtUsuario->execute();
        $dadosUsuario = $stmtUsuario->get_result()->fetch_assoc();
        $stmtUsuario->close();

        if ($dadosUsuario) {
            $nomeUsuario = $dadosUsuario['nome'] ?: $nomeUsuario;
            $serieUsuario = (int)($dadosUsuario['serie'] ?? $serieUsuario);
            $turmaUsuario = $dadosUsuario['turma'] ?? $turmaUsuario;
            $xpUsuario = (int)($dadosUsuario['xp'] ?? 0);
            $nivelUsuario = (int)($dadosUsuario['nivel'] ?? 1);
            $pontuacaoUsuario = (int)($dadosUsuario['pontuacao_total'] ?? 0);
        }

        $stmtJogo = $db->prepare("SELECT id FROM jogos WHERE nome = 'MathSpace' LIMIT 1");
        $stmtJogo->execute();
        $jogo = $stmtJogo->get_result()->fetch_assoc();
        $stmtJogo->close();

        if (!$jogo) {
            throw new RuntimeException('O jogo MathSpace não foi encontrado na tabela jogos.');
        }

        if ($serieUsuario >= 6 && $serieUsuario <= 9) {
            $stmtFases = $db->prepare('
                SELECT f.id, f.nome, f.descricao, f.nivel_dificuldade, f.numero,
                       COALESCE(p.concluida, 0) AS concluida,
                       COALESCE(p.melhor_pontuacao, 0) AS melhor_pontuacao,
                       COALESCE(p.tentativas, 0) AS tentativas
                FROM fases f
                LEFT JOIN progresso_usuario p
                  ON p.fase_id = f.id AND p.usuario_id = ?
                WHERE f.jogo_id = ? AND f.serie = ?
                ORDER BY f.numero ASC
            ');
            $stmtFases->bind_param('iii', $usuarioId, $jogo['id'], $serieUsuario);
        } else {
            /* Se a série não veio na sessão, mostra as fases da série 6 como prévia,
               mas sem alterar o cadastro do aluno. */
            $serieConsulta = 6;
            $stmtFases = $db->prepare('
                SELECT f.id, f.nome, f.descricao, f.nivel_dificuldade, f.numero,
                       COALESCE(p.concluida, 0) AS concluida,
                       COALESCE(p.melhor_pontuacao, 0) AS melhor_pontuacao,
                       COALESCE(p.tentativas, 0) AS tentativas
                FROM fases f
                LEFT JOIN progresso_usuario p
                  ON p.fase_id = f.id AND p.usuario_id = ?
                WHERE f.jogo_id = ? AND f.serie = ?
                ORDER BY f.numero ASC
            ');
            $stmtFases->bind_param('iii', $usuarioId, $jogo['id'], $serieConsulta);
        }
        $stmtFases->execute();
        $resultadoFases = $stmtFases->get_result();
        while ($linha = $resultadoFases->fetch_assoc()) {
            $fases[] = $linha;
        }
        $stmtFases->close();
    } else {
        $stmtUsuario = $db->prepare('SELECT nome, serie, turma, xp, nivel, pontuacao_total FROM usuarios WHERE id = ? LIMIT 1');
        $stmtUsuario->execute([$usuarioId]);
        $dadosUsuario = $stmtUsuario->fetch(PDO::FETCH_ASSOC);
        if ($dadosUsuario) {
            $nomeUsuario = $dadosUsuario['nome'] ?: $nomeUsuario;
            $serieUsuario = (int)($dadosUsuario['serie'] ?? $serieUsuario);
            $turmaUsuario = $dadosUsuario['turma'] ?? $turmaUsuario;
            $xpUsuario = (int)($dadosUsuario['xp'] ?? 0);
            $nivelUsuario = (int)($dadosUsuario['nivel'] ?? 1);
            $pontuacaoUsuario = (int)($dadosUsuario['pontuacao_total'] ?? 0);
        }

        $jogo = $db->query("SELECT id FROM jogos WHERE nome = 'MathSpace' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        if (!$jogo) {
            throw new RuntimeException('O jogo MathSpace não foi encontrado na tabela jogos.');
        }
        $serieConsulta = ($serieUsuario >= 6 && $serieUsuario <= 9) ? $serieUsuario : 6;
        $stmtFases = $db->prepare('
            SELECT f.id, f.nome, f.descricao, f.nivel_dificuldade, f.numero,
                   COALESCE(p.concluida, 0) AS concluida,
                   COALESCE(p.melhor_pontuacao, 0) AS melhor_pontuacao,
                   COALESCE(p.tentativas, 0) AS tentativas
            FROM fases f
            LEFT JOIN progresso_usuario p
              ON p.fase_id = f.id AND p.usuario_id = ?
            WHERE f.jogo_id = ? AND f.serie = ?
            ORDER BY f.numero ASC
        ');
        $stmtFases->execute([$usuarioId, $jogo['id'], $serieConsulta]);
        $fases = $stmtFases->fetchAll(PDO::FETCH_ASSOC);
    }

    /* A primeira missão fica aberta; cada missão seguinte exige concluir a anterior. */
    $anteriorConcluida = true;
    foreach ($fases as $i => $fase) {
        $fases[$i]['desbloqueada'] = $anteriorConcluida;
        $fases[$i]['concluida'] = (int)$fase['concluida'];
        if ((int)$fase['concluida'] === 1) {
            $anteriorConcluida = true;
        } else {
            $anteriorConcluida = false;
        }
    }
} catch (Throwable $e) {
    $erroBanco = $e->getMessage();
}

$totalFases = count($fases);
$fasesConcluidas = 0;
foreach ($fases as $fase) {
    if ((int)($fase['concluida'] ?? 0) === 1) $fasesConcluidas++;
}
$percentual = $totalFases > 0 ? (int)round(($fasesConcluidas / $totalFases) * 100) : 0;

$icones = [
    1 => ['☾', 'planeta-lua', 'MISSÃO LUNAR', 'Lua'],
    2 => ['♂', 'planeta-marte', 'MISSÃO VERMELHA', 'Marte'],
    3 => ['✦', 'planeta-asteroides', 'ZONA DE RISCO', 'Asteroides'],
    4 => ['⌬', 'planeta-estacao', 'ÚLTIMA FRONTEIRA', 'Estação'],
];
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#080b20">
    <title>MathSpace | Expedição Cósmica</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;500;600;700;800;900&family=Rajdhani:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/mathspace.css">
</head>

<body>
    <header class="topbar">
        <a class="brand" href="inicio.php" aria-label="Voltar ao início">
            <span class="brand-mark">✦</span><span>MATH<span>SPACE</span></span>
        </a>
        <div class="top-actions">
            <div class="stat-pill" title="Pontos de experiência">✧ <b><?= number_format($xpUsuario, 0, ',', '.') ?> XP</b></div>
            <div class="stat-pill" title="Nível atual">◈ <b>NÍVEL <?= h($nivelUsuario) ?></b></div>
            <div class="avatar" title="<?= h($nomeUsuario) ?>"><?= h(mb_strtoupper(mb_substr(trim((string)$nomeUsuario), 0, 1, 'UTF-8'), 'UTF-8')) ?></div>
            <a class="back-link" href="inicio.php">← SAIR DO MAPA</a>
        </div>
    </header>

    <main class="shell">
        <section class="hero">
            <div>
                <div class="eyebrow">CENTRO DE COMANDO // SETOR 07</div>
                <h1>O universo<br><span class="gradient">é seu tabuleiro.</span></h1>
                <p class="hero-copy">Prepare sua nave, <strong><?= h(explode(' ', trim((string)$nomeUsuario))[0] ?: 'explorador') ?></strong>. Cada planeta esconde um desafio. Resolva os cálculos, conquiste XP e abra caminho até os confins da galáxia.</p>
                <div class="hero-status">
                    <div class="status-chip">⌁ EXPLORADOR <span>NÍVEL <?= h($nivelUsuario) ?></span></div>
                    <div class="status-chip">▦ SETOR <span><?= ($serieUsuario >= 6 && $serieUsuario <= 9) ? h($serieUsuario . 'º ano') : 'EM RECONHECIMENTO' ?></span><?= $turmaUsuario !== '' ? ' · ' . h($turmaUsuario) : '' ?></div>
                </div>
            </div>
            <div class="hero-art" aria-hidden="true">
                <div class="orbit one"></div>
                <div class="orbit two"></div>
                <div class="orbit three"></div>
                <div class="planet-core"></div>
                <div class="planet-moon"></div>
            </div>
        </section>

        <section class="section-head">
            <div>
                <h2>Mapa de expedição</h2>
                <p>Escolha sua rota. O próximo setor se abre quando você conclui a missão atual.</p>
            </div>
            <div class="progress-box">
                <div class="progress-top"><span>PROGRESSO DA EXPEDIÇÃO</span><strong><?= $percentual ?>%</strong></div>
                <div class="progress-track">
                    <div class="progress-fill"></div>
                </div>
            </div>
        </section>

        <?php if ($erroBanco !== ''): ?>
            <div class="error-state"><strong>⚠ Comunicação com a base interrompida.</strong><br><?= h($erroBanco) ?><br>Confira se o arquivo de conexão define uma variável mysqli ou PDO e se as tabelas do MathSpace já existem.</div>
        <?php else: ?>
            <section class="galaxy-map" aria-label="Mapa de missões do MathSpace">
                <div class="map-label">NAVEGAÇÃO HOLOGRÁFICA</div>
                <div class="map-coordinates">X-<?= str_pad((string)max(1, $serieUsuario), 2, '0', STR_PAD_LEFT) ?> / Y-2049</div>
                <div class="route"></div>
                <div class="missions">
                    <?php if (!$fases): ?>
                        <div class="empty-state" style="grid-column:1/-1">
                            <strong>O radar ainda não encontrou missões para este setor.</strong><br>
                            Confira se existem fases do MathSpace cadastradas para a série do seu perfil (6º, 7º, 8º ou 9º ano).
                        </div>
                    <?php endif; ?>

                    <?php foreach ($fases as $fase):
                        $numero = (int)$fase['numero'];
                        $visual = $icones[$numero] ?? ['✦', 'planeta-asteroides', 'SETOR', 'Planeta'];
                        $concluida = (int)$fase['concluida'] === 1;
                        $desbloqueada = (bool)$fase['desbloqueada'];
                        $classe = $visual[1] . (!$desbloqueada ? ' locked' : '') . ($concluida ? ' done' : '');
                        $dificuldade = strtolower((string)$fase['nivel_dificuldade']);
                        $dificuldadeTexto = ['facil' => 'INICIANTE', 'medio' => 'INTERMEDIÁRIO', 'dificil' => 'AVANÇADO'][$dificuldade] ?? strtoupper($dificuldade);
                        $dificuldadeClasse = ['facil' => 'easy', 'medio' => 'medium', 'dificil' => 'hard'][$dificuldade] ?? '';
                    ?>
                        <article class="mission <?= h($classe) ?>">
                            <a class="planet-button" href="<?= $desbloqueada ? 'mathspace_jogar.php?fase_id=' . (int)$fase['id'] : '#' ?>"
                                <?= !$desbloqueada ? 'aria-disabled="true" tabindex="-1"' : '' ?>
                                aria-label="<?= h($fase['nome']) ?><?= $desbloqueada ? ', iniciar missão' : ', bloqueada' ?>">
                                <span class="mission-number"><?= $concluida ? '✓' : str_pad((string)$numero, 2, '0', STR_PAD_LEFT) ?></span>
                                <span class="planet-sphere"><?= h($concluida ? '✓' : ($desbloqueada ? $visual[0] : '⌑')) ?></span>
                            </a>
                            <div class="mission-tag"><?= h($visual[2]) ?></div>
                            <h3><?= h($fase['nome']) ?></h3>
                            <p><?= h($fase['descricao']) ?></p>
                            <div class="mission-meta">
                                <span class="meta <?= h($dificuldadeClasse) ?>"><?= h($dificuldadeTexto) ?></span>
                                <?php if ($concluida): ?><span class="meta">★ <?= (int)$fase['melhor_pontuacao'] ?> PTS</span><?php endif; ?>
                            </div>
                            <?php if ($concluida): ?>
                                <a class="mission-cta" href="mathspace_jogar.php?fase_id=<?= (int)$fase['id'] ?>">↻ REJOGAR MISSÃO</a>
                            <?php elseif ($desbloqueada): ?>
                                <a class="mission-cta" href="mathspace_jogar.php?fase_id=<?= (int)$fase['id'] ?>">▶ INICIAR MISSÃO</a>
                            <?php else: ?>
                                <span class="mission-cta disabled">⌑ SETOR BLOQUEADO</span>
                            <?php endif; ?>
                        </article>
                    <?php endforeach; ?>
                </div>
                <div class="map-footer">
                    <span><i class="pulse"></i> SISTEMA DE NAVEGAÇÃO ONLINE</span>
                    <span><?= $fasesConcluidas ?> DE <?= $totalFases ?> MISSÕES CONCLUÍDAS</span>
                    <span>✧ PONTUAÇÃO TOTAL: <?= number_format($pontuacaoUsuario, 0, ',', '.') ?></span>
                </div>
            </section>
        <?php endif; ?>

        <section class="info-grid">
            <article class="info-card">
                <div class="info-icon">⌁</div>
                <div>
                    <h3>PROTOCOLO DE EXPLORAÇÃO</h3>
                    <p>Conclua uma missão para desbloquear a seguinte. <strong>Cada resposta certa aproxima você do próximo planeta.</strong></p>
                </div>
            </article>
            <article class="info-card">
                <div class="info-icon">✧</div>
                <div>
                    <h3>SEU OBJETIVO</h3>
                    <p>Acumule pontos, aprimore seu nível e conquiste a galáxia usando matemática. A gravidade não aceita desculpas.</p>
                </div>
            </article>
        </section>
        <footer>MATHSPACE // UMA EXPEDIÇÃO MATHRUN · TODOS OS SISTEMAS EM ÓRBITA</footer>
    </main>
</body>

</html>