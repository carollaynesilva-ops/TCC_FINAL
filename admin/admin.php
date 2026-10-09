<?php
session_start();

// Permitir acesso somente a administradores.
if (!isset($_SESSION["tipo"]) || $_SESSION["tipo"] !== "admin") {
    header("Location: ../login.php");
    exit;
}

// Conexão oficial do projeto.
require_once "../config/config.php";

// Escapar textos antes de exibi-los no HTML.
function escapar($valor)
{
    return htmlspecialchars((string) $valor, ENT_QUOTES, "UTF-8");
}

// Estatísticas gerais.
$sqlEstatisticas = "
    SELECT
        (SELECT COUNT(*)
         FROM usuarios
         WHERE tipo = 'aluno') AS total_alunos,

        (SELECT COUNT(*)
         FROM historico_partidas) AS total_partidas,

        (SELECT COALESCE(SUM(acertos), 0)
         FROM historico_partidas) AS total_acertos,

        (SELECT COALESCE(SUM(erros), 0)
         FROM historico_partidas) AS total_erros
";

$estatisticas = $pdo->query($sqlEstatisticas)->fetch();

$totalAlunos = (int) $estatisticas["total_alunos"];
$totalPartidas = (int) $estatisticas["total_partidas"];
$totalAcertos = (int) $estatisticas["total_acertos"];
$totalErros = (int) $estatisticas["total_erros"];

$totalRespostas = $totalAcertos + $totalErros;

$aproveitamento = $totalRespostas > 0
    ? round(($totalAcertos / $totalRespostas) * 100, 1)
    : 0;

// Desempenho separado por jogo.
$sqlJogos = "
    SELECT
        j.nome,
        COUNT(hp.id) AS partidas,
        COALESCE(SUM(hp.acertos), 0) AS acertos,
        COALESCE(SUM(hp.erros), 0) AS erros
    FROM jogos j
    LEFT JOIN historico_partidas hp
        ON hp.jogo_id = j.id
    GROUP BY j.id, j.nome
    ORDER BY j.id
";

$desempenhoJogos = $pdo->query($sqlJogos)->fetchAll();

// Partidas realizadas recentemente.
$sqlRecentes = "
    SELECT
        u.nome AS aluno,
        j.nome AS jogo,
        f.nome AS fase,
        hp.pontuacao,
        hp.acertos,
        hp.erros,
        hp.data_partida
    FROM historico_partidas hp
    INNER JOIN usuarios u
        ON u.id = hp.usuario_id
    INNER JOIN jogos j
        ON j.id = hp.jogo_id
    INNER JOIN fases f
        ON f.id = hp.fase_id
    ORDER BY hp.data_partida DESC
    LIMIT 8
";

$partidasRecentes = $pdo->query($sqlRecentes)->fetchAll();

// Alunos com as maiores pontuações acumuladas.
$sqlRanking = "
    SELECT nome, pontuacao_total, xp
    FROM usuarios
    WHERE tipo = 'aluno'
    ORDER BY pontuacao_total DESC, xp DESC
    LIMIT 5
";

$rankingAlunos = $pdo->query($sqlRanking)->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Administração | MathRun</title>

    <link rel="stylesheet" href="css/admin.css">
</head>

<body>

    <div class="admin-layout">

        <aside class="sidebar">
            <a href="admin.php" class="logo">
                <span class="logo-icon">M</span>
                <span>MathRun <small>ADMINISTRAÇÃO</small></span>
            </a>

            <p class="menu-label">PAINEL</p>

            <nav class="menu">
                <a href="admin.php" class="menu-link ativo">
                    <span>▦</span> Visão geral
                </a>

                <a href="alunos.php" class="menu-link">
                    <span>♙</span> Alunos
                </a>

                <a href="desempenho.php" class="menu-link">
                    <span>▤</span> Desempenho
                </a>

                <a href="questoes.php" class="menu-link">
                    <span>✎</span> Questões
                </a>

                <a href="fases.php" class="menu-link">
                    <span>⚑</span> Fases dos jogos
                </a>
            </nav>

            <div class="sidebar-bottom">
                <div class="admin-avatar">A</div>

                <div class="admin-info">
                    <strong>Administrador</strong>
                    <span>Gestão do MathRun</span>
                </div>

                <a href="../logout.php" class="logout" title="Sair da conta">
                    ↗
                </a>
            </div>
        </aside>

        <main class="main-content">

            <header class="topbar">
                <div>
                    <p class="breadcrumb">MathRun / Administração</p>
                    <h1>Painel administrativo</h1>
                    <p class="subtitle">
                        Acompanhe os alunos e o desempenho nos jogos.
                    </p>
                </div>

                <div class="topbar-tag">
                    <span class="status-dot"></span>
                    Área administrativa
                </div>
            </header>

            <section class="welcome">
                <div>
                    <span class="welcome-label">VISÃO GERAL</span>

                    <h2>Os números da jornada matemática.</h2>

                    <p>
                        Acompanhe a participação dos alunos e os resultados
                        registrados no MathChef e no MathSpace.
                    </p>
                </div>

                <div class="welcome-symbol" aria-hidden="true">
                    <span>∑</span>
                    <span>÷</span>
                    <span>π</span>
                </div>
            </section>

            <section class="stats-grid">

                <article class="stat-card">
                    <div class="stat-top">
                        <span class="stat-label">Alunos cadastrados</span>
                        <span class="stat-icon roxo">♙</span>
                    </div>

                    <strong class="stat-number">
                        <?= number_format($totalAlunos, 0, ",", ".") ?>
                    </strong>

                    <span class="stat-description">
                        Contas de alunos registradas
                    </span>
                </article>

                <article class="stat-card">
                    <div class="stat-top">
                        <span class="stat-label">Partidas realizadas</span>
                        <span class="stat-icon azul">▦</span>
                    </div>

                    <strong class="stat-number">
                        <?= number_format($totalPartidas, 0, ",", ".") ?>
                    </strong>

                    <span class="stat-description">
                        Partidas registradas no sistema
                    </span>
                </article>

                <article class="stat-card">
                    <div class="stat-top">
                        <span class="stat-label">Respostas corretas</span>
                        <span class="stat-icon verde">✓</span>
                    </div>

                    <strong class="stat-number">
                        <?= number_format($totalAcertos, 0, ",", ".") ?>
                    </strong>

                    <span class="stat-description">
                        Acertos registrados nas partidas
                    </span>
                </article>

                <article class="stat-card">
                    <div class="stat-top">
                        <span class="stat-label">Respostas incorretas</span>
                        <span class="stat-icon vermelho">×</span>
                    </div>

                    <strong class="stat-number">
                        <?= number_format($totalErros, 0, ",", ".") ?>
                    </strong>

                    <span class="stat-description">
                        Erros registrados nas partidas
                    </span>
                </article>

            </section>

            <section class="content-grid">

                <article class="panel performance-panel">
                    <div class="panel-heading">
                        <div>
                            <h2>Desempenho por jogo</h2>
                            <p>Comparação dos acertos e erros registrados.</p>
                        </div>

                        <span class="panel-icon">⌁</span>
                    </div>

                    <?php foreach ($desempenhoJogos as $jogo): ?>

                        <?php
                        $acertosJogo = (int) $jogo["acertos"];
                        $errosJogo = (int) $jogo["erros"];

                        $respostasJogo = $acertosJogo + $errosJogo;

                        $percentualAcertos = $respostasJogo > 0
                            ? round(($acertosJogo / $respostasJogo) * 100, 1)
                            : 0;

                        $percentualErros = $respostasJogo > 0
                            ? round(($errosJogo / $respostasJogo) * 100, 1)
                            : 0;
                        ?>

                        <div class="game-performance">

                            <div class="game-heading">
                                <strong><?= escapar($jogo["nome"]) ?></strong>

                                <span>
                                    <?= (int) $jogo["partidas"] ?>
                                    <?= (int) $jogo["partidas"] === 1
                                        ? "partida"
                                        : "partidas" ?>
                                </span>
                            </div>

                            <div class="progress-track">
                                <div
                                    class="progress-correct"
                                    style="width: <?= $percentualAcertos ?>%">
                                </div>
                            </div>

                            <div class="game-legend">
                                <span class="legend-correct">
                                    <i></i>
                                    <?= number_format($acertosJogo, 0, ",", ".") ?>
                                    acertos
                                </span>

                                <span class="legend-wrong">
                                    <i></i>
                                    <?= number_format($errosJogo, 0, ",", ".") ?>
                                    erros
                                </span>

                                <strong><?= $percentualAcertos ?>%</strong>
                            </div>

                        </div>

                    <?php endforeach; ?>

                    <div class="general-performance">
                        <div>
                            <span>Aproveitamento geral</span>
                            <p>
                                <?= number_format($totalRespostas, 0, ",", ".") ?>
                                respostas contabilizadas
                            </p>
                        </div>

                        <strong><?= $aproveitamento ?>%</strong>
                    </div>

                    <div class="general-track">
                        <div style="width: <?= $aproveitamento ?>%"></div>
                    </div>
                </article>

                <article class="panel ranking-panel">
                    <div class="panel-heading">
                        <div>
                            <h2>Alunos em destaque</h2>
                            <p>Maiores pontuações acumuladas.</p>
                        </div>
                    </div>

                    <?php if (count($rankingAlunos) > 0): ?>

                        <div class="ranking-list">

                            <?php foreach ($rankingAlunos as $indice => $aluno): ?>

                                <div class="ranking-item">
                                    <span class="ranking-position">
                                        <?= $indice + 1 ?>
                                    </span>

                                    <div class="ranking-avatar">
                                        <?= escapar(
                                            strtoupper(
                                                substr($aluno["nome"], 0, 1)
                                            )
                                        ) ?>
                                    </div>

                                    <div class="ranking-student">
                                        <strong><?= escapar($aluno["nome"]) ?></strong>
                                        <span>
                                            <?= number_format(
                                                (int) $aluno["xp"],
                                                0,
                                                ",",
                                                "."
                                            ) ?> XP
                                        </span>
                                    </div>

                                    <strong class="ranking-points">
                                        <?= number_format(
                                            (int) $aluno["pontuacao_total"],
                                            0,
                                            ",",
                                            "."
                                        ) ?>
                                        <small>pts</small>
                                    </strong>
                                </div>

                            <?php endforeach; ?>

                        </div>

                    <?php else: ?>

                        <div class="empty-state">
                            <span>♙</span>
                            <p>Ainda não há alunos cadastrados.</p>
                        </div>

                    <?php endif; ?>

                </article>

            </section>

            <section class="panel recent-panel">

                <div class="panel-heading">
                    <div>
                        <h2>Partidas recentes</h2>
                        <p>Últimas atividades registradas pelos alunos.</p>
                    </div>

                    <a href="desempenho.php" class="text-link">
                        Ver desempenho →
                    </a>
                </div>

                <?php if (count($partidasRecentes) > 0): ?>

                    <div class="table-wrapper">
                        <table>
                            <thead>
                                <tr>
                                    <th>Aluno</th>
                                    <th>Jogo</th>
                                    <th>Fase</th>
                                    <th>Acertos</th>
                                    <th>Erros</th>
                                    <th>Pontuação</th>
                                    <th>Data</th>
                                </tr>
                            </thead>

                            <tbody>
                                <?php foreach ($partidasRecentes as $partida): ?>

                                    <tr>
                                        <td class="student-cell">
                                            <?= escapar($partida["aluno"]) ?>
                                        </td>

                                        <td><?= escapar($partida["jogo"]) ?></td>

                                        <td><?= escapar($partida["fase"]) ?></td>

                                        <td>
                                            <span class="result-pill correct">
                                                <?= (int) $partida["acertos"] ?>
                                            </span>
                                        </td>

                                        <td>
                                            <span class="result-pill wrong">
                                                <?= (int) $partida["erros"] ?>
                                            </span>
                                        </td>

                                        <td class="points-cell">
                                            <?= number_format(
                                                (int) $partida["pontuacao"],
                                                0,
                                                ",",
                                                "."
                                            ) ?> pts
                                        </td>

                                        <td>
                                            <?= date(
                                                "d/m/Y H:i",
                                                strtotime($partida["data_partida"])
                                            ) ?>
                                        </td>
                                    </tr>

                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                <?php else: ?>

                    <div class="empty-state">
                        <span>▦</span>
                        <p>Nenhuma partida foi registrada ainda.</p>
                        <small>
                            Quando os alunos jogarem, as partidas aparecerão aqui.
                        </small>
                    </div>

                <?php endif; ?>

            </section>

            <footer class="footer">
                <span>MathRun · Painel administrativo</span>
                <span>Aprender, jogar e evoluir.</span>
            </footer>

        </main>

    </div>

</body>

</html>