<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (($_SESSION["admin_autenticado"] ?? false) !== true) {
    header("Location: ../login.php");
    exit;
}

require_once "../config/config.php";

function h($valor): string
{
    return htmlspecialchars((string)($valor ?? ""), ENT_QUOTES, "UTF-8");
}

function percentual($acertos, $total): float
{
    return (int)$total > 0
        ? round(((int)$acertos / (int)$total) * 100, 1)
        : 0;
}

$periodos = [
    "7" => "Últimos 7 dias",
    "30" => "Últimos 30 dias",
    "90" => "Últimos 90 dias",
    "todos" => "Todo o período"
];

$periodo = $_GET["periodo"] ?? "30";
if (!array_key_exists($periodo, $periodos)) {
    $periodo = "30";
}

$turma = trim($_GET["turma"] ?? "");
$alunoId = filter_input(INPUT_GET, "aluno", FILTER_VALIDATE_INT) ?: 0;

$turmasValidas = [];
for ($serie = 6; $serie <= 9; $serie++) {
    foreach (["A", "B"] as $letra) {
        $turmasValidas[] = $serie . $letra;
    }
}

if (!in_array($turma, $turmasValidas, true)) {
    $turma = "";
}

/*
 * Os dias são convertidos para inteiro e só aceitam
 * valores definidos na lista de períodos acima.
 */
$whereData = "";
if ($periodo !== "todos") {
    $dias = (int)$periodo;
    $whereData = " AND hp.data_partida >= DATE_SUB(CURDATE(), INTERVAL $dias DAY)";
}

$whereUsuarios = "u.tipo = 'aluno'";
$params = [];

if ($turma !== "") {
    $whereUsuarios .= " AND CONCAT(u.serie, UPPER(u.turma)) = :turma";
    $params[":turma"] = $turma;
}

$resumo = [
    "alunos_ativos" => 0,
    "partidas" => 0,
    "acertos" => 0,
    "erros" => 0,
    "media_pontos" => 0
];

$alunos = [];
$evolucao = [];
$comparativoJogos = [];
$evolucaoAluno = [];
$jogosAluno = [];
$alunoSelecionado = null;
$erroBanco = "";

$totalAcertos = 0;
$totalErros = 0;
$taxaAcerto = 0;

try {
    /*
     * Lista de alunos de acordo com a turma escolhida.
     */
    $sql = "SELECT id, nome, serie, turma
            FROM usuarios
            WHERE tipo = 'aluno'";

    if ($turma !== "") {
        $sql .= " AND CONCAT(serie, UPPER(turma)) = :turma";
    }

    $sql .= " ORDER BY nome";

    $stmt = $pdo->prepare($sql);

    if ($turma !== "") {
        $stmt->bindValue(":turma", $turma, PDO::PARAM_STR);
    }

    $stmt->execute();
    $alunos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    /*
     * Confere se o aluno escolhido pertence ao filtro atual.
     */
    if ($alunoId > 0) {
        $alunoEncontrado = false;

        foreach ($alunos as $aluno) {
            if ((int)$aluno["id"] === $alunoId) {
                $alunoEncontrado = true;
                break;
            }
        }

        if (!$alunoEncontrado) {
            $alunoId = 0;
        }
    }

    /*
     * Indicadores gerais.
     */
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

    foreach ($params as $chave => $valor) {
        $stmt->bindValue($chave, $valor, PDO::PARAM_STR);
    }

    $stmt->execute();
    $resumo = $stmt->fetch(PDO::FETCH_ASSOC) ?: $resumo;

    $totalAcertos = (int)$resumo["acertos"];
    $totalErros = (int)$resumo["erros"];

    $taxaAcerto = percentual(
        $totalAcertos,
        $totalAcertos + $totalErros
    );


    /*
 * Desempenho por fase.
 * Cada fase aparece separadamente, mesmo que o aluno
 * tenha jogado várias fases no mesmo dia.
 */
    $sql = "SELECT
            f.id AS fase_id,
            f.nome AS fase_nome,
            f.numero AS fase_numero,
            f.serie AS fase_serie,
            j.nome AS jogo_nome,
            COALESCE(SUM(hp.acertos), 0) AS acertos,
            COALESCE(SUM(hp.erros), 0) AS erros,
            COUNT(hp.id) AS partidas
        FROM historico_partidas hp
        INNER JOIN usuarios u ON u.id = hp.usuario_id
        INNER JOIN fases f ON f.id = hp.fase_id
        INNER JOIN jogos j ON j.id = hp.jogo_id
        WHERE $whereUsuarios $whereData";

    if ($alunoId > 0) {
        $sql .= " AND hp.usuario_id = :aluno_grafico";
    }

    $sql .= " GROUP BY
            f.id, f.nome, f.numero, f.serie, j.nome
          ORDER BY
            j.id, f.serie, f.numero";

    $stmt = $pdo->prepare($sql);

    foreach ($params as $chave => $valor) {
        $stmt->bindValue($chave, $valor, PDO::PARAM_STR);
    }

    if ($alunoId > 0) {
        $stmt->bindValue(":aluno_grafico", $alunoId, PDO::PARAM_INT);
    }

    $stmt->execute();

    $evolucao = [];

    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $linha) {
        $nomeFase = $linha["jogo_nome"]
            . " · Fase "
            . $linha["fase_numero"]
            . ": "
            . $linha["fase_nome"];

        if (!empty($linha["fase_serie"])) {
            $nomeFase .= " (" . $linha["fase_serie"] . "º ano)";
        }

        $evolucao[] = [
            "fase" => $nomeFase,
            "acertos" => (int)$linha["acertos"],
            "erros" => (int)$linha["erros"],
            "partidas" => (int)$linha["partidas"]
        ];
    }


    /*
     * Comparação entre MathChef e MathSpace.
     */
    $sql = "SELECT
                j.nome,
                COUNT(hp.id) AS partidas,
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

    foreach ($params as $chave => $valor) {
        $stmt->bindValue($chave, $valor, PDO::PARAM_STR);
    }

    $stmt->execute();
    $comparativoJogos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    /*
     * Análise individual do aluno selecionado.
     */
    if ($alunoId > 0) {
        $stmt = $pdo->prepare(
            "SELECT id, nome, serie, turma
             FROM usuarios
             WHERE id = :id AND tipo = 'aluno'
             LIMIT 1"
        );

        $stmt->execute([":id" => $alunoId]);
        $alunoSelecionado = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($alunoSelecionado) {
            $sql = "SELECT
                        DATE(data_partida) AS dia,
                        SUM(acertos) AS acertos,
                        SUM(erros) AS erros
                    FROM historico_partidas
                    WHERE usuario_id = :aluno $whereData
                    GROUP BY DATE(data_partida)
                    ORDER BY dia";

            /*
             * whereData usa o alias hp. Para a consulta individual,
             * substituímos o alias pelo nome da tabela.
             */
            $whereDataIndividual = "";

            if ($periodo !== "todos") {
                $dias = (int)$periodo;
                $whereDataIndividual =
                    " AND data_partida >= DATE_SUB(CURDATE(), INTERVAL $dias DAY)";
            }

            $sql = "SELECT
                        DATE(data_partida) AS dia,
                        SUM(acertos) AS acertos,
                        SUM(erros) AS erros
                    FROM historico_partidas
                    WHERE usuario_id = :aluno $whereDataIndividual
                    GROUP BY DATE(data_partida)
                    ORDER BY dia";

            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(":aluno", $alunoId, PDO::PARAM_INT);
            $stmt->execute();

            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $linha) {
                $total = (int)$linha["acertos"] + (int)$linha["erros"];

                $evolucaoAluno[] = [
                    "dia" => date("d/m", strtotime($linha["dia"])),
                    "taxa" => percentual($linha["acertos"], $total)
                ];
            }

            $sql = "SELECT
                        j.nome,
                        COUNT(hp.id) AS partidas,
                        COALESCE(SUM(hp.acertos), 0) AS acertos,
                        COALESCE(SUM(hp.erros), 0) AS erros
                    FROM historico_partidas hp
                    INNER JOIN jogos j ON j.id = hp.jogo_id
                    WHERE hp.usuario_id = :aluno $whereData
                    GROUP BY j.id, j.nome
                    ORDER BY j.id";

            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(":aluno", $alunoId, PDO::PARAM_INT);
            $stmt->execute();

            $jogosAluno = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }
} catch (PDOException $e) {
    error_log("Erro em desempenho.php: " . $e->getMessage());

    $erroBanco = "Não foi possível carregar os dados. Verifique a conexão, as tabelas e as colunas do banco.";

    $resumo = [
        "alunos_ativos" => 0,
        "partidas" => 0,
        "acertos" => 0,
        "erros" => 0,
        "media_pontos" => 0
    ];

    $totalAcertos = 0;
    $totalErros = 0;
    $taxaAcerto = 0;

    $evolucao = [];
    $comparativoJogos = [];
    $evolucaoAluno = [];
    $jogosAluno = [];
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
                <span>
                    MathRun
                    <small>ADMINISTRAÇÃO</small>
                </span>
            </a>

            <p class="menu-label">PAINEL</p>

            <nav class="menu" aria-label="Menu administrativo">
                <a href="admin.php" class="menu-link">
                    <span>▦</span> Visão geral
                </a>

                <a href="alunos.php" class="menu-link">
                    <span>♙</span> Alunos
                </a>

                <a href="desempenho.php" class="menu-link ativo">
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

        <main class="main-content desempenho-main">

            <header class="topbar">
                <div>
                    <p class="breadcrumb">
                        MathRun / Administração / Desempenho
                    </p>

                    <h1>Desempenho dos alunos</h1>

                    <p class="subtitle">
                        Acompanhe os resultados registrados nos jogos e identifique tendências.
                    </p>
                </div>

                <div class="topbar-tag">
                    <span class="status-dot"></span>
                    Dados do sistema
                </div>
            </header>

            <?php if ($erroBanco !== ""): ?>
                <div class="admin-alert error">
                    <?= h($erroBanco) ?>
                </div>
            <?php endif; ?>

            <form class="filters-panel panel" method="get">

                <div class="filters-heading">
                    <div>
                        <span class="section-kicker">FILTROS</span>
                        <h2>Personalize a análise</h2>
                    </div>

                    <a href="desempenho.php" class="text-link">
                        Limpar filtros
                    </a>
                </div>

                <div class="filters-grid">

                    <label>
                        Turma
                        <select
                            name="turma"
                            id="filtroTurma"
                            onchange="document.getElementById('filtroAluno').value=''; this.form.submit();">
                            <option value="">Todas as turmas</option>

                            <?php foreach ($turmasValidas as $opcaoTurma): ?>
                                <option
                                    value="<?= h($opcaoTurma) ?>"
                                    <?= $turma === $opcaoTurma ? "selected" : "" ?>>
                                    <?= h($opcaoTurma) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>

                    <label>
                        Período
                        <select name="periodo">
                            <?php foreach ($periodos as $valor => $texto): ?>
                                <option
                                    value="<?= h($valor) ?>"
                                    <?= $periodo === $valor ? "selected" : "" ?>>
                                    <?= h($texto) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>

                    <label>
                        Aluno
                        <select name="aluno" id="filtroAluno">
                            <option value="">Visão geral da turma</option>

                            <?php foreach ($alunos as $aluno): ?>
                                <option
                                    value="<?= (int)$aluno["id"] ?>"
                                    <?= $alunoId === (int)$aluno["id"] ? "selected" : "" ?>>
                                    <?= h($aluno["nome"]) ?>
                                    ·
                                    <?= h($aluno["serie"] . strtoupper($aluno["turma"] ?? "")) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>

                    <button type="submit" class="btn-filter">
                        Aplicar filtros <span>↗</span>
                    </button>

                </div>
            </form>

            <section class="stats-grid desempenho-stats">

                <article class="stat-card">
                    <div class="stat-top">
                        <span class="stat-label">Alunos ativos</span>
                        <span class="stat-icon roxo">♙</span>
                    </div>

                    <strong class="stat-number">
                        <?= number_format((int)$resumo["alunos_ativos"], 0, ",", ".") ?>
                    </strong>

                    <span class="stat-description">
                        <?= h($serieLabel) ?> · <?= h($periodos[$periodo]) ?>
                    </span>
                </article>

                <article class="stat-card">
                    <div class="stat-top">
                        <span class="stat-label">Partidas realizadas</span>
                        <span class="stat-icon azul">▦</span>
                    </div>

                    <strong class="stat-number">
                        <?= number_format((int)$resumo["partidas"], 0, ",", ".") ?>
                    </strong>

                    <span class="stat-description">
                        <?= h($periodos[$periodo]) ?>
                    </span>
                </article>

                <article class="stat-card">
                    <div class="stat-top">
                        <span class="stat-label">Taxa de acerto</span>
                        <span class="stat-icon verde">✓</span>
                    </div>

                    <strong class="stat-number">
                        <?= number_format($taxaAcerto, 1, ",", ".") ?>%
                    </strong>

                    <span class="stat-description">
                        <?= number_format($totalAcertos, 0, ",", ".") ?> acertos registrados
                    </span>
                </article>

                <article class="stat-card">
                    <div class="stat-top">
                        <span class="stat-label">Média de pontuação</span>
                        <span class="stat-icon roxo">✦</span>
                    </div>

                    <strong class="stat-number">
                        <?= number_format((float)$resumo["media_pontos"], 0, ",", ".") ?>
                    </strong>

                    <span class="stat-description">
                        Pontos por partida · <?= h($periodos[$periodo]) ?>
                    </span>
                </article>

            </section>

            <section class="content-grid desempenho-grid">

                <article class="panel performance-panel">
                    <div class="panel-heading">
                        <div>
                            <h2>Acertos por fase</h2>
                            <p>Quantidade total de respostas corretas registradas em cada fase.</p>
                        </div>
                    </div>

                    <?php if (empty($evolucao)): ?>
                        <div class="empty-state">
                            <span>⌁</span>
                            <p>Não há partidas registradas neste período.</p>
                        </div>
                    <?php else: ?>
                        <div class="chart-wrap">
                            <canvas id="graficoGeral"></canvas>
                        </div>
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
                        <div class="empty-state">
                            <span>▦</span>
                            <p>Não há partidas para comparar.</p>
                        </div>
                    <?php else: ?>

                        <?php foreach ($comparativoJogos as $jogo):
                            $totalJogo = (int)$jogo["acertos"] + (int)$jogo["erros"];
                            $taxaJogo = percentual($jogo["acertos"], $totalJogo);
                        ?>

                            <div class="game-result">
                                <div class="game-result-head">
                                    <div class="game-title">
                                        <strong><?= h($jogo["nome"]) ?></strong>
                                        <small><?= (int)$jogo["partidas"] ?> partidas</small>
                                    </div>

                                    <strong class="game-percent">
                                        <?= number_format($taxaJogo, 1, ",", ".") ?>%
                                    </strong>
                                </div>

                                <div class="progress-track">
                                    <div
                                        class="progress-correct"
                                        style="width: <?= min(100, max(0, $taxaJogo)) ?>%"></div>
                                </div>

                                <div class="game-meta">
                                    <span><?= (int)$jogo["acertos"] ?> acertos</span>
                                    <span><?= (int)$jogo["erros"] ?> erros</span>
                                </div>
                            </div>

                        <?php endforeach; ?>
                    <?php endif; ?>
                </article>

            </section>

            <?php if ($alunoId > 0 && $alunoSelecionado): ?>

                <section class="panel individual-panel">

                    <div class="panel-heading">
                        <div>
                            <h2><?= h($alunoSelecionado["nome"]) ?></h2>
                            <p>
                                Acompanhamento individual · turma
                                <?= h($alunoSelecionado["serie"] . strtoupper($alunoSelecionado["turma"] ?? "")) ?>
                            </p>
                        </div>
                    </div>

                    <?php if (empty($evolucaoAluno)): ?>
                        <div class="empty-state">
                            <p>Este aluno não possui partidas no período selecionado.</p>
                        </div>
                    <?php else: ?>
                        <div class="chart-wrap individual-chart">
                            <canvas id="graficoAluno"></canvas>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($jogosAluno)): ?>
                        <div class="individual-game-grid">

                            <?php foreach ($jogosAluno as $jogo):
                                $total = (int)$jogo["acertos"] + (int)$jogo["erros"];
                            ?>

                                <div class="mini-stat">
                                    <span><?= h($jogo["nome"]) ?></span>

                                    <strong>
                                        <?= number_format(percentual($jogo["acertos"], $total), 1, ",", ".") ?>%
                                    </strong>

                                    <small>
                                        <?= (int)$jogo["partidas"] ?> partidas ·
                                        <?= (int)$jogo["acertos"] ?> acertos ·
                                        <?= (int)$jogo["erros"] ?> erros
                                    </small>
                                </div>

                            <?php endforeach; ?>

                        </div>
                    <?php endif; ?>

                </section>

            <?php endif; ?>

            <div class="method-note">
                <strong>Como interpretar:</strong>
                os gráficos mostram a taxa de acerto agregada por dia.
                O sistema não compara a primeira e a última tentativa da mesma
                questão, porque essa sequência não é registrada explicitamente
                no banco de dados.
            </div>

            <footer class="admin-footer">
                MathRun · Painel administrativo · Dados do histórico registrado.
            </footer>

        </main>
    </div>


    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const dadosFases = <?= json_encode(
                                    $evolucao,
                                    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                                ) ?>;

            const canvas = document.getElementById("graficoGeral");

            if (!canvas) {
                console.error("Canvas graficoGeral não encontrado.");
                return;
            }

            if (typeof Chart === "undefined") {
                console.error("Chart.js não foi carregado.");
                canvas.parentElement.innerHTML =
                    "<p class='chart-error'>Não foi possível carregar a biblioteca do gráfico. Verifique sua conexão.</p>";
                return;
            }

            if (!Array.isArray(dadosFases) || dadosFases.length === 0) {
                canvas.parentElement.innerHTML =
                    "<p class='chart-error'>Não há acertos registrados nas fases para os filtros selecionados.</p>";
                return;
            }

            new Chart(canvas, {
                type: "bar",
                data: {
                    labels: dadosFases.map(item => item.fase),

                    datasets: [{
                        label: "Acertos por fase",
                        data: dadosFases.map(item => Number(item.acertos) || 0),
                        backgroundColor: "rgba(139, 123, 255, 0.8)",
                        borderColor: "#8b7bff",
                        borderWidth: 1,
                        borderRadius: 6
                    }]
                },

                options: {
                    responsive: true,
                    maintainAspectRatio: false,

                    plugins: {
                        legend: {
                            labels: {
                                color: "#c5c9dc"
                            }
                        },

                        tooltip: {
                            callbacks: {
                                afterLabel: function(context) {
                                    const fase = dadosFases[context.dataIndex];

                                    return [
                                        "Erros: " + fase.erros,
                                        "Partidas: " + fase.partidas
                                    ];
                                }
                            }
                        }
                    },

                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0,
                                color: "#9297ab"
                            },
                            grid: {
                                color: "rgba(143, 151, 190, 0.12)"
                            }
                        },

                        x: {
                            ticks: {
                                color: "#9297ab",
                                autoSkip: false,
                                maxRotation: 45,
                                minRotation: 0
                            },
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });
        });
    </script>


</body>

</html>