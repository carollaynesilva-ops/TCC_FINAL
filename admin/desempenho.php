<?php

require_once "../config/config.php";

/*
 * MathRun | Desempenho administrativo
 * Os gráficos usam somente dados existentes em historico_partidas.
 * Não compara primeira e última tentativa, pois o banco não registra essa
 * informação de forma explícita.
 */

function h($valor): string
{
    return htmlspecialchars((string)($valor ?? ""), ENT_QUOTES, "UTF-8");
}

function percentual($parte, $total): float
{
    return (int)$total > 0 ? round(((int)$parte / (int)$total) * 100, 1) : 0;
}

$turma = trim($_GET["turma"] ?? "");
$alunoId = filter_input(INPUT_GET, "aluno", FILTER_VALIDATE_INT) ?: 0;
$periodo = $_GET["periodo"] ?? "30";

if (!in_array($periodo, ["7", "30", "90", "todos"], true)) {
    $periodo = "30";
}

$turmasValidas = [];
for ($serie = 6; $serie <= 9; $serie++) {
    foreach (["A", "B"] as $letra) {
        $turmasValidas[] = $serie . $letra;
    }
}
if ($turma !== "" && !in_array($turma, $turmasValidas, true)) {
    $turma = "";
}

$whereUsuarios = "u.tipo = 'aluno'";
$paramsUsuarios = [];

if ($turma !== "") {
    $whereUsuarios .= " AND CONCAT(u.serie, UPPER(u.turma)) = :turma";
    $paramsUsuarios[":turma"] = $turma;
}

$whereData = "";
if ($periodo !== "todos") {
    $whereData = " AND hp.data_partida >= DATE_SUB(CURDATE(), INTERVAL :dias DAY)";
}

$periodos = [
    "7" => "Últimos 7 dias",
    "30" => "Últimos 30 dias",
    "90" => "Últimos 90 dias",
    "todos" => "Todo o período"
];

$alunos = [];
$resumo = ["alunos_ativos" => 0, "partidas" => 0, "acertos" => 0, "erros" => 0, "media_pontos" => 0];
$labelsEvolucao = $dadosEvolucao = [];
$labelsAluno = $dadosAluno = [];
$comparativoJogos = $jogosAluno = [];
$alunoSelecionado = null;
$erroBanco = "";

try {
    // Lista de alunos do filtro de turma.
    $sql = "SELECT u.id, u.nome, u.serie, u.turma
            FROM usuarios u
            WHERE $whereUsuarios
            ORDER BY u.nome";
    $stmt = $pdo->prepare($sql);
    foreach ($paramsUsuarios as $chave => $valor) {
        $stmt->bindValue($chave, $valor, PDO::PARAM_STR);
    }
    $stmt->execute();
    $alunos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Evita mostrar um aluno que não pertence à turma filtrada.
    if ($alunoId > 0 && !array_filter($alunos, fn($a) => (int)$a["id"] === $alunoId)) {
        $alunoId = 0;
    }

    // Indicadores gerais.
    $sql = "SELECT
                COUNT(DISTINCT hp.usuario_id) AS alunos_ativos,
                COUNT(hp.id) AS partidas,
                COALESCE(SUM(hp.acertos), 0) AS acertos,
                COALESCE(SUM(hp.erros), 0) AS erros,
                COALESCE(AVG(hp.pontuacao), 0) AS media_pontos
            FROM historico_partidas hp
            INNER JOIN usuarios u ON u.id = hp.usuario_id
            WHERE $whereUsuarios $whereData";
    $stmt = $pdo->prepare($sql);
    foreach ($paramsUsuarios as $chave => $valor) {
        $stmt->bindValue($chave, $valor, PDO::PARAM_STR);
    }
    if ($periodo !== "todos") {
        $stmt->bindValue(":dias", (int)$periodo, PDO::PARAM_INT);
    }
    $stmt->execute();
    $resumo = $stmt->fetch(PDO::FETCH_ASSOC) ?: $resumo;

    $totalAcertos = (int)$resumo["acertos"];
    $totalErros = (int)$resumo["erros"];
    $taxaAcerto = percentual($totalAcertos, $totalAcertos + $totalErros);

    // Taxa de acerto geral agrupada por dia.
    $sql = "SELECT DATE(hp.data_partida) AS dia,
                   SUM(hp.acertos) AS acertos,
                   SUM(hp.erros) AS erros
            FROM historico_partidas hp
            INNER JOIN usuarios u ON u.id = hp.usuario_id
            WHERE $whereUsuarios $whereData
            GROUP BY DATE(hp.data_partida)
            ORDER BY dia";
    $stmt = $pdo->prepare($sql);
    foreach ($paramsUsuarios as $chave => $valor) {
        $stmt->bindValue($chave, $valor, PDO::PARAM_STR);
    }
    if ($periodo !== "todos") {
        $stmt->bindValue(":dias", (int)$periodo, PDO::PARAM_INT);
    }
    $stmt->execute();
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $linha) {
        $labelsEvolucao[] = date("d/m", strtotime($linha["dia"]));
        $dadosEvolucao[] = percentual(
            $linha["acertos"],
            (int)$linha["acertos"] + (int)$linha["erros"]
        );
    }

    // Comparação de resultados entre os jogos.
    $sql = "SELECT j.nome, COUNT(hp.id) AS partidas,
                   COALESCE(SUM(hp.acertos), 0) AS acertos,
                   COALESCE(SUM(hp.erros), 0) AS erros,
                   COALESCE(AVG(hp.pontuacao), 0) AS media_pontos
            FROM historico_partidas hp
            INNER JOIN jogos j ON j.id = hp.jogo_id
            INNER JOIN usuarios u ON u.id = hp.usuario_id
            WHERE $whereUsuarios $whereData
            GROUP BY j.id, j.nome
            ORDER BY j.id";
    $stmt = $pdo->prepare($sql);
    foreach ($paramsUsuarios as $chave => $valor) {
        $stmt->bindValue($chave, $valor, PDO::PARAM_STR);
    }
    if ($periodo !== "todos") {
        $stmt->bindValue(":dias", (int)$periodo, PDO::PARAM_INT);
    }
    $stmt->execute();
    $comparativoJogos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Dados individuais: evolução diária e resumo por jogo.
    if ($alunoId > 0) {
        $stmt = $pdo->prepare(
            "SELECT id, nome, serie, turma
             FROM usuarios
             WHERE id = :id AND tipo = 'aluno'
             LIMIT 1"
        );
        $stmt->execute([":id" => $alunoId]);
        $alunoSelecionado = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

        if ($alunoSelecionado) {
            $sql = "SELECT DATE(data_partida) AS dia,
                           SUM(acertos) AS acertos,
                           SUM(erros) AS erros
                    FROM historico_partidas
                    WHERE usuario_id = :aluno";
            if ($periodo !== "todos") {
                $sql .= " AND data_partida >= DATE_SUB(CURDATE(), INTERVAL :dias DAY)";
            }
            $sql .= " GROUP BY DATE(data_partida) ORDER BY dia";
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(":aluno", $alunoId, PDO::PARAM_INT);
            if ($periodo !== "todos") {
                $stmt->bindValue(":dias", (int)$periodo, PDO::PARAM_INT);
            }
            $stmt->execute();

            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $linha) {
                $labelsAluno[] = date("d/m", strtotime($linha["dia"]));
                $dadosAluno[] = percentual(
                    $linha["acertos"],
                    (int)$linha["acertos"] + (int)$linha["erros"]
                );
            }

            $sql = "SELECT j.nome, COUNT(hp.id) AS partidas,
                           COALESCE(SUM(hp.acertos), 0) AS acertos,
                           COALESCE(SUM(hp.erros), 0) AS erros
                    FROM historico_partidas hp
                    INNER JOIN jogos j ON j.id = hp.jogo_id
                    WHERE hp.usuario_id = :aluno";
            if ($periodo !== "todos") {
                $sql .= " AND hp.data_partida >= DATE_SUB(CURDATE(), INTERVAL :dias DAY)";
            }
            $sql .= " GROUP BY j.id, j.nome ORDER BY j.id";
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(":aluno", $alunoId, PDO::PARAM_INT);
            if ($periodo !== "todos") {
                $stmt->bindValue(":dias", (int)$periodo, PDO::PARAM_INT);
            }
            $stmt->execute();
            $jogosAluno = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }
} catch (PDOException $e) {
    error_log("Erro em admin/desempenho.php: " . $e->getMessage());
    $erroBanco = "Não foi possível carregar os dados. Confira as tabelas e colunas do banco de dados.";
    $alunos = $comparativoJogos = $jogosAluno = [];
    $labelsEvolucao = $dadosEvolucao = $labelsAluno = $dadosAluno = [];
    $alunoSelecionado = null;
    $totalAcertos = $totalErros = $taxaAcerto = 0;
}

$serieLabel = $turma !== "" ? $turma : "Todas as turmas";
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Desempenho | MathRun</title>
    <link rel="stylesheet" href="css/admin.css">
    <link rel="stylesheet" href="css/desempenho.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body>
    <div class="admin-layout">
        <aside class="sidebar">
            <a href="admin.php" class="logo">
                <span class="logo-icon">M</span>
                <span>MathRun <small>ADMINISTRAÇÃO</small></span>
            </a>

            <p class="menu-label">PAINEL</p>
            <nav class="menu" aria-label="Menu administrativo">
                <a href="admin.php" class="menu-link"><span>▦</span> Visão geral</a>
                <a href="alunos.php" class="menu-link"><span>♙</span> Alunos</a>
                <a href="desempenho.php" class="menu-link ativo"><span>▤</span> Desempenho</a>
                <a href="questoes.php" class="menu-link"><span>✎</span> Questões</a>
                <a href="fases.php" class="menu-link"><span>⚑</span> Fases dos jogos</a>
            </nav>

            <div class="sidebar-bottom">
                <div class="admin-avatar">A</div>
                <div class="admin-info">
                    <strong>Administrador</strong>
                    <span>Gestão do MathRun</span>
                </div>
                <a href="../logout.php" class="logout" title="Sair da conta">↗</a>
            </div>
        </aside>

        <main class="main-content desempenho-main">
            <header class="topbar">
                <div>
                    <p class="breadcrumb">MathRun / Administração / Desempenho</p>
                    <h1>Desempenho dos alunos</h1>
                    <p class="subtitle">Acompanhe os resultados registrados nos jogos e identifique tendências.</p>
                </div>
                <div class="topbar-tag"><span class="status-dot"></span> Dados do sistema</div>
            </header>

            <?php if ($erroBanco !== ""): ?>
                <div class="admin-alert error"><?= h($erroBanco) ?></div>
            <?php endif; ?>

            <form class="filters-panel panel" method="get">
                <div class="filters-heading">
                    <div><span class="section-kicker">FILTROS</span>
                        <h2>Personalize a análise</h2>
                    </div>
                    <a href="desempenho.php" class="text-link">Limpar filtros</a>
                </div>
                <div class="filters-grid">
                    <label>Turma
                        <select name="turma">
                            <option value="">Todas as turmas</option>
                            <?php foreach ($turmasValidas as $opcaoTurma): ?>
                                <option value="<?= h($opcaoTurma) ?>" <?= $turma === $opcaoTurma ? "selected" : "" ?>><?= h($opcaoTurma) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>Período
                        <select name="periodo">
                            <?php foreach ($periodos as $valor => $texto): ?>
                                <option value="<?= h($valor) ?>" <?= $periodo === $valor ? "selected" : "" ?>><?= h($texto) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>Aluno
                        <select name="aluno">
                            <option value="">Visão geral da turma</option>
                            <?php foreach ($alunos as $aluno): ?>
                                <option value="<?= (int)$aluno["id"] ?>" <?= $alunoId === (int)$aluno["id"] ? "selected" : "" ?>>
                                    <?= h($aluno["nome"]) ?> · <?= h($aluno["serie"] . strtoupper($aluno["turma"] ?? "")) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <button type="submit" class="btn-filter">Aplicar filtros <span>↗</span></button>
                </div>
            </form>

            <section class="stats-grid desempenho-stats">
                <article class="stat-card">
                    <div class="stat-top"><span class="stat-label">Alunos ativos</span><span class="stat-icon roxo">♙</span></div>
                    <strong class="stat-number"><?= number_format((int)($resumo["alunos_ativos"] ?? 0), 0, ",", ".") ?></strong>
                    <span class="stat-description"><?= h($serieLabel) ?></span>
                </article>
                <article class="stat-card">
                    <div class="stat-top"><span class="stat-label">Partidas realizadas</span><span class="stat-icon azul">▦</span></div>
                    <strong class="stat-number"><?= number_format((int)($resumo["partidas"] ?? 0), 0, ",", ".") ?></strong>
                    <span class="stat-description"><?= h($periodos[$periodo]) ?></span>
                </article>
                <article class="stat-card">
                    <div class="stat-top"><span class="stat-label">Taxa de acerto</span><span class="stat-icon verde">✓</span></div>
                    <strong class="stat-number"><?= number_format($taxaAcerto, 1, ",", ".") ?>%</strong>
                    <span class="stat-description"><?= number_format($totalAcertos, 0, ",", ".") ?> acertos registrados</span>
                </article>
                <article class="stat-card">
                    <div class="stat-top"><span class="stat-label">Média de pontuação</span><span class="stat-icon roxo">✦</span></div>
                    <strong class="stat-number"><?= number_format((float)($resumo["media_pontos"] ?? 0), 0, ",", ".") ?></strong>
                    <span class="stat-description">Pontos por partida</span>
                </article>
            </section>

            <section class="content-grid desempenho-grid">
                <article class="panel performance-panel">
                    <div class="panel-heading">
                        <div>
                            <h2>Taxa de acerto ao longo do tempo</h2>
                            <p>Percentual diário calculado pelas partidas registradas.</p>
                        </div>
                        <span class="panel-icon">⌁</span>
                    </div>
                    <?php if (empty($labelsEvolucao)): ?>
                        <div class="empty-state"><span>⌁</span>
                            <p>Não há partidas registradas neste período.</p>
                        </div>
                    <?php else: ?>
                        <div class="chart-wrap"><canvas id="graficoGeral" aria-label="Taxa geral de acerto por dia"></canvas></div>
                    <?php endif; ?>
                </article>

                <article class="panel game-panel">
                    <div class="panel-heading">
                        <div>
                            <h2>Desempenho por jogo</h2>
                            <p>Comparação dos resultados registrados.</p>
                        </div>
                    </div>
                    <?php if (empty($comparativoJogos)): ?>
                        <div class="empty-state"><span>▦</span>
                            <p>Não há partidas para comparar.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($comparativoJogos as $jogo):
                            $totalJogo = (int)$jogo["acertos"] + (int)$jogo["erros"];
                            $taxaJogo = percentual($jogo["acertos"], $totalJogo);
                        ?>
                            <div class="game-result">
                                <div class="game-result-head">
                                    <div class="game-title"><strong><?= h($jogo["nome"]) ?></strong><small><?= (int)$jogo["partidas"] ?> partidas</small></div>
                                    <strong class="game-percent"><?= number_format($taxaJogo, 1, ",", ".") ?>%</strong>
                                </div>
                                <div class="progress-track">
                                    <div class="progress-correct" style="width: <?= min(100, max(0, $taxaJogo)) ?>%"></div>
                                </div>
                                <div class="game-meta"><span><?= (int)$jogo["acertos"] ?> acertos</span><span><?= (int)$jogo["erros"] ?> erros</span></div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </article>
            </section>

            <?php if ($alunoId > 0): ?>
                <section class="panel individual-panel">
                    <div class="panel-heading">
                        <div>
                            <h2><?= h($alunoSelecionado["nome"] ?? "Aluno selecionado") ?></h2>
                            <p>Acompanhamento individual · turma <?= h(($alunoSelecionado["serie"] ?? "") . strtoupper($alunoSelecionado["turma"] ?? "")) ?></p>
                        </div>
                        <span class="panel-icon">♙</span>
                    </div>
                    <?php if (empty($labelsAluno)): ?>
                        <div class="empty-state">
                            <p>Este aluno não possui partidas no período selecionado.</p>
                        </div>
                    <?php else: ?>
                        <div class="chart-wrap individual-chart"><canvas id="graficoAluno" aria-label="Taxa individual de acerto por dia"></canvas></div>
                    <?php endif; ?>
                    <?php if (!empty($jogosAluno)): ?>
                        <div class="individual-game-grid">
                            <?php foreach ($jogosAluno as $jogo):
                                $total = (int)$jogo["acertos"] + (int)$jogo["erros"];
                            ?>
                                <div class="mini-stat">
                                    <span><?= h($jogo["nome"]) ?></span>
                                    <strong><?= number_format(percentual($jogo["acertos"], $total), 1, ",", ".") ?>%</strong>
                                    <small><?= (int)$jogo["partidas"] ?> partidas · <?= (int)$jogo["acertos"] ?> acertos · <?= (int)$jogo["erros"] ?> erros</small>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </section>
            <?php endif; ?>

            <div class="method-note">
                <strong>Como interpretar:</strong> os gráficos mostram a taxa de acerto agregada por dia com partidas registradas. Eles não representam a pontuação da primeira ou da última tentativa de uma mesma questão, porque essa sequência não é registrada explicitamente pelo banco.
            </div>

            <footer class="admin-footer">MathRun · Painel administrativo · Dados calculados a partir do histórico registrado.</footer>
        </main>
    </div>

    <script>
        const labelsGeral = <?= json_encode($labelsEvolucao, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
        const dadosGeral = <?= json_encode($dadosEvolucao) ?>;
        const labelsAluno = <?= json_encode($labelsAluno, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
        const dadosAluno = <?= json_encode($dadosAluno) ?>;

        function criarGrafico(id, labels, dados, cor, fundo) {
            const canvas = document.getElementById(id);
            if (!canvas || typeof Chart === "undefined") return;

            new Chart(canvas, {
                type: "line",
                data: {
                    labels: labels,
                    datasets: [{
                        label: "Taxa de acerto",
                        data: dados,
                        borderColor: cor,
                        backgroundColor: fundo,
                        fill: true,
                        tension: 0.32,
                        borderWidth: 3,
                        pointRadius: 3,
                        pointHoverRadius: 6,
                        pointBackgroundColor: cor
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        intersect: false,
                        mode: "index"
                    },
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return " " + context.parsed.y + "% de acertos";
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            min: 0,
                            max: 100,
                            ticks: {
                                color: "#9297ab",
                                callback: value => value + "%"
                            },
                            grid: {
                                color: "rgba(143, 151, 190, 0.12)"
                            },
                            border: {
                                display: false
                            }
                        },
                        x: {
                            ticks: {
                                color: "#9297ab",
                                maxRotation: 0,
                                autoSkip: true,
                                maxTicksLimit: 8
                            },
                            grid: {
                                display: false
                            },
                            border: {
                                display: false
                            }
                        }
                    }
                }
            });
        }

        criarGrafico("graficoGeral", labelsGeral, dadosGeral, "#8b7bff", "rgba(139, 123, 255, 0.14)");
        criarGrafico("graficoAluno", labelsAluno, dadosAluno, "#39b8ff", "rgba(57, 184, 255, 0.13)");
    </script>
</body>

</html>