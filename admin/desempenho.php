<?php
session_start();

if (!isset($_SESSION["admin_autenticado"])) {
    header("Location: login.php");
    exit;
}

require_once "../config/config.php";

/*
|--------------------------------------------------------------------------
| FUNÇÕES AUXILIARES
|--------------------------------------------------------------------------
*/

function h($valor)
{
    return htmlspecialchars(
        (string) ($valor ?? ""),
        ENT_QUOTES,
        "UTF-8"
    );
}

function percentual($acertos, $erros)
{
    $total = (int) $acertos + (int) $erros;

    if ($total === 0) {
        return 0;
    }

    return round(((int) $acertos / $total) * 100, 1);
}

function formatarNumero($numero)
{
    return number_format((float) $numero, 0, ",", ".");
}

/*
|--------------------------------------------------------------------------
| VERIFICAÇÃO DAS COLUNAS OPCIONAIS
|--------------------------------------------------------------------------
| O sistema pode ter versões do banco com ou sem as colunas serie e sala.
| A verificação evita que a página quebre caso elas ainda não existam.
*/

$colunasUsuarios = [];

try {
    $consultaColunas = $pdo->query("SHOW COLUMNS FROM usuarios");

    foreach ($consultaColunas->fetchAll(PDO::FETCH_ASSOC) as $coluna) {
        $colunasUsuarios[] = $coluna["Field"];
    }
} catch (PDOException $e) {
    die("Não foi possível consultar a estrutura dos usuários.");
}

$temSerie = in_array("serie", $colunasUsuarios, true);
$temSala = in_array("sala", $colunasUsuarios, true);

$temTurma = $temSerie && $temSala;

/*
|--------------------------------------------------------------------------
| FILTROS
|--------------------------------------------------------------------------
*/

$periodosPermitidos = [
    "7" => 7,
    "30" => 30,
    "90" => 90,
    "todos" => 0
];

$periodo = $_GET["periodo"] ?? "30";

if (!array_key_exists($periodo, $periodosPermitidos)) {
    $periodo = "30";
}

$dias = $periodosPermitidos[$periodo];

$turma = trim($_GET["turma"] ?? "");
$alunoId = filter_input(INPUT_GET, "aluno", FILTER_VALIDATE_INT);
$jogoId = filter_input(INPUT_GET, "jogo", FILTER_VALIDATE_INT);

$alunoId = $alunoId && $alunoId > 0 ? $alunoId : 0;
$jogoId = $jogoId && $jogoId > 0 ? $jogoId : 0;

/*
|--------------------------------------------------------------------------
| LISTAS PARA OS FILTROS
|--------------------------------------------------------------------------
*/

$sqlTurmas = [];

if ($temTurma) {
    $consultaTurmas = $pdo->query("
        SELECT DISTINCT
            CONCAT(serie, sala) AS turma
        FROM usuarios
        WHERE tipo = 'aluno'
          AND serie IS NOT NULL
          AND sala IS NOT NULL
        ORDER BY serie, sala
    ");

    $sqlTurmas = $consultaTurmas->fetchAll(PDO::FETCH_COLUMN);
}

$sqlAlunos = "
    SELECT id, nome
    FROM usuarios
    WHERE tipo = 'aluno'
";

$paramsAlunos = [];

if ($temTurma && $turma !== "") {
    $sqlAlunos .= " AND CONCAT(serie, sala) = :turma";
    $paramsAlunos[":turma"] = $turma;
}

$sqlAlunos .= " ORDER BY nome";

$consultaAlunos = $pdo->prepare($sqlAlunos);
$consultaAlunos->execute($paramsAlunos);
$alunos = $consultaAlunos->fetchAll(PDO::FETCH_ASSOC);

$consultaJogos = $pdo->query("
    SELECT id, nome
    FROM jogos
    ORDER BY nome
");

$jogos = $consultaJogos->fetchAll(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| VALIDAR OS FILTROS RECEBIDOS
|--------------------------------------------------------------------------
*/

$idsAlunos = array_map(
    "intval",
    array_column($alunos, "id")
);

if ($alunoId > 0 && !in_array($alunoId, $idsAlunos, true)) {
    $alunoId = 0;
}

$idsJogos = array_map(
    "intval",
    array_column($jogos, "id")
);

if ($jogoId > 0 && !in_array($jogoId, $idsJogos, true)) {
    $jogoId = 0;
}

/*
|--------------------------------------------------------------------------
| CONDIÇÕES DAS CONSULTAS
|--------------------------------------------------------------------------
*/

$whereData = "";
$paramsData = [];

if ($dias > 0) {
    $whereData = " AND hp.data_partida >= DATE_SUB(NOW(), INTERVAL :dias DAY)";
    $paramsData[":dias"] = $dias;
}

$whereTurma = "";
$paramsTurma = [];

if ($temTurma && $turma !== "") {
    $whereTurma = " AND CONCAT(u.serie, u.sala) = :turma";
    $paramsTurma[":turma"] = $turma;
}

$whereAluno = "";
$paramsAluno = [];

if ($alunoId > 0) {
    $whereAluno = " AND hp.usuario_id = :aluno";
    $paramsAluno[":aluno"] = $alunoId;
}

$whereJogo = "";
$paramsJogo = [];

if ($jogoId > 0) {
    $whereJogo = " AND hp.jogo_id = :jogo";
    $paramsJogo[":jogo"] = $jogoId;
}

$paramsBase = array_merge(
    $paramsData,
    $paramsTurma,
    $paramsAluno,
    $paramsJogo
);

/*
|--------------------------------------------------------------------------
| INDICADORES GERAIS
|--------------------------------------------------------------------------
*/

$sqlResumo = "
    SELECT
        COUNT(DISTINCT hp.usuario_id) AS alunos_analisados,
        COUNT(hp.id) AS partidas,
        COALESCE(SUM(hp.acertos), 0) AS acertos,
        COALESCE(SUM(hp.erros), 0) AS erros,
        COALESCE(AVG(hp.pontuacao), 0) AS media_pontos
    FROM historico_partidas hp
    INNER JOIN usuarios u ON u.id = hp.usuario_id
    WHERE u.tipo = 'aluno'
    $whereData
    $whereTurma
    $whereAluno
    $whereJogo
";

$consultaResumo = $pdo->prepare($sqlResumo);
$consultaResumo->execute($paramsBase);
$resumo = $consultaResumo->fetch(PDO::FETCH_ASSOC);

$alunosAnalisados = (int) ($resumo["alunos_analisados"] ?? 0);
$partidas = (int) ($resumo["partidas"] ?? 0);
$totalAcertos = (int) ($resumo["acertos"] ?? 0);
$totalErros = (int) ($resumo["erros"] ?? 0);
$mediaPontos = (float) ($resumo["media_pontos"] ?? 0);

$taxaGeral = percentual($totalAcertos, $totalErros);

/*
|--------------------------------------------------------------------------
| GRÁFICO: DESEMPENHO POR FASE
|--------------------------------------------------------------------------
*/

$sqlEvolucao = "
    SELECT
        f.id AS fase_id,
        f.nome AS fase,
        j.nome AS jogo,
        COALESCE(SUM(hp.acertos), 0) AS acertos,
        COALESCE(SUM(hp.erros), 0) AS erros,
        COUNT(hp.id) AS partidas
    FROM historico_partidas hp
    INNER JOIN usuarios u ON u.id = hp.usuario_id
    INNER JOIN fases f ON f.id = hp.fase_id
    INNER JOIN jogos j ON j.id = hp.jogo_id
    WHERE u.tipo = 'aluno'
    $whereData
    $whereTurma
    $whereAluno
    $whereJogo
    GROUP BY f.id, f.nome, j.nome, f.numero
    ORDER BY j.nome, f.numero
";

$consultaEvolucao = $pdo->prepare($sqlEvolucao);
$consultaEvolucao->execute($paramsBase);
$evolucao = $consultaEvolucao->fetchAll(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| COMPARAÇÃO ENTRE OS JOGOS
|--------------------------------------------------------------------------
| A comparação considera todos os jogos para continuar sendo útil mesmo
| quando o filtro de um jogo específico estiver selecionado.
*/

$sqlComparacao = "
    SELECT
        j.id,
        j.nome,
        COUNT(hp.id) AS partidas,
        COALESCE(SUM(hp.acertos), 0) AS acertos,
        COALESCE(SUM(hp.erros), 0) AS erros,
        COALESCE(AVG(hp.pontuacao), 0) AS media_pontos
    FROM jogos j
    LEFT JOIN historico_partidas hp
        ON hp.jogo_id = j.id
";

$paramsComparacao = [];

if ($dias > 0) {
    $sqlComparacao .= "
        AND hp.data_partida >= DATE_SUB(NOW(), INTERVAL :dias DAY)
    ";

    $paramsComparacao[":dias"] = $dias;
}

$sqlComparacao .= "
    LEFT JOIN usuarios u ON u.id = hp.usuario_id
    WHERE (u.tipo = 'aluno' OR hp.id IS NULL)
";

if ($temTurma && $turma !== "") {
    $sqlComparacao .= " AND (CONCAT(u.serie, u.sala) = :turma OR hp.id IS NULL)";
    $paramsComparacao[":turma"] = $turma;
}

if ($alunoId > 0) {
    $sqlComparacao .= " AND (hp.usuario_id = :aluno OR hp.id IS NULL)";
    $paramsComparacao[":aluno"] = $alunoId;
}

$sqlComparacao .= "
    GROUP BY j.id, j.nome
    ORDER BY j.nome
";

$consultaComparacao = $pdo->prepare($sqlComparacao);
$consultaComparacao->execute($paramsComparacao);
$comparacaoJogos = $consultaComparacao->fetchAll(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| ANÁLISE INDIVIDUAL
|--------------------------------------------------------------------------
*/

$alunoSelecionado = null;
$evolucaoAluno = [];
$resumoAlunoJogos = [];
$taxaAluno = 0;
$pontosAluno = 0;
$partidasAluno = 0;

if ($alunoId > 0) {
    $consultaAluno = $pdo->prepare("
        SELECT id, nome, email
        FROM usuarios
        WHERE id = :id
          AND tipo = 'aluno'
        LIMIT 1
    ");

    $consultaAluno->execute([":id" => $alunoId]);
    $alunoSelecionado = $consultaAluno->fetch(PDO::FETCH_ASSOC);

    if ($alunoSelecionado) {
        $whereJogoAluno = "";
        $paramsJogoAluno = [];

        if ($jogoId > 0) {
            $whereJogoAluno = " AND hp.jogo_id = :jogo";
            $paramsJogoAluno[":jogo"] = $jogoId;
        }

        $sqlResumoAluno = "
            SELECT
                COUNT(hp.id) AS partidas,
                COALESCE(SUM(hp.acertos), 0) AS acertos,
                COALESCE(SUM(hp.erros), 0) AS erros,
                COALESCE(SUM(hp.pontuacao), 0) AS pontos
            FROM historico_partidas hp
            WHERE hp.usuario_id = :aluno
            $whereData
            $whereJogoAluno
        ";

        $paramsResumoAluno = array_merge(
            [":aluno" => $alunoId],
            $paramsData,
            $paramsJogoAluno
        );

        $consultaResumoAluno = $pdo->prepare($sqlResumoAluno);
        $consultaResumoAluno->execute($paramsResumoAluno);
        $dadosResumoAluno = $consultaResumoAluno->fetch(PDO::FETCH_ASSOC);

        $partidasAluno = (int) ($dadosResumoAluno["partidas"] ?? 0);
        $pontosAluno = (int) ($dadosResumoAluno["pontos"] ?? 0);

        $taxaAluno = percentual(
            $dadosResumoAluno["acertos"] ?? 0,
            $dadosResumoAluno["erros"] ?? 0
        );

        /*
|--------------------------------------------------------------------------
| EVOLUÇÃO INDIVIDUAL POR FASE
|--------------------------------------------------------------------------
*/

        $sqlEvolucaoAluno = "
    SELECT
        f.id AS fase_id,
        f.nome AS fase,
        f.numero AS numero_fase,
        j.nome AS jogo,
        COALESCE(SUM(hp.acertos), 0) AS acertos,
        COALESCE(SUM(hp.erros), 0) AS erros,
        COUNT(hp.id) AS partidas
    FROM historico_partidas hp
    INNER JOIN fases f ON f.id = hp.fase_id
    INNER JOIN jogos j ON j.id = hp.jogo_id
    WHERE hp.usuario_id = :aluno
    $whereData
    $whereJogoAluno
    GROUP BY
        f.id,
        f.nome,
        f.numero,
        j.nome
    ORDER BY
        j.nome,
        f.numero
";

        $consultaEvolucaoAluno = $pdo->prepare($sqlEvolucaoAluno);
        $consultaEvolucaoAluno->execute($paramsResumoAluno);

        $evolucaoAluno = $consultaEvolucaoAluno->fetchAll(PDO::FETCH_ASSOC);

        foreach ($evolucaoAluno as &$dia) {
            $dia["taxa"] = percentual(
                $dia["acertos"],
                $dia["erros"]
            );
        }

        unset($dia);

        /*
        |------------------------------------------------------------------
        | Resultado do aluno por jogo
        |------------------------------------------------------------------
        */

        $sqlJogosAluno = "
            SELECT
                j.nome,
                COUNT(hp.id) AS partidas,
                COALESCE(SUM(hp.acertos), 0) AS acertos,
                COALESCE(SUM(hp.erros), 0) AS erros,
                COALESCE(SUM(hp.pontuacao), 0) AS pontos
            FROM jogos j
            LEFT JOIN historico_partidas hp
                ON hp.jogo_id = j.id
                AND hp.usuario_id = :aluno
        ";

        if ($dias > 0) {
            $sqlJogosAluno .= "
                AND hp.data_partida >= DATE_SUB(NOW(), INTERVAL :dias DAY)
            ";
        }

        $sqlJogosAluno .= "
            GROUP BY j.id, j.nome
            ORDER BY j.nome
        ";

        $consultaJogosAluno = $pdo->prepare($sqlJogosAluno);

        $paramsJogosAluno = [":aluno" => $alunoId];

        if ($dias > 0) {
            $paramsJogosAluno[":dias"] = $dias;
        }

        $consultaJogosAluno->execute($paramsJogosAluno);
        $resumoAlunoJogos = $consultaJogosAluno->fetchAll(PDO::FETCH_ASSOC);
    }
}

/*
|--------------------------------------------------------------------------
| DADOS PARA OS GRÁFICOS
|--------------------------------------------------------------------------
*/

$labelsFases = [];
$dadosAcertosFases = [];
$dadosErrosFases = [];

foreach ($evolucao as $fase) {
    $labelsFases[] = $fase["jogo"] . " - " . $fase["fase"];
    $dadosAcertosFases[] = (int) $fase["acertos"];
    $dadosErrosFases[] = (int) $fase["erros"];
}

$labelsJogos = [];
$dadosTaxaJogos = [];

foreach ($comparacaoJogos as $jogo) {
    $labelsJogos[] = $jogo["nome"];
    $dadosTaxaJogos[] = percentual(
        $jogo["acertos"],
        $jogo["erros"]
    );
}

$labelsAluno = [];
$dadosAcertosAluno = [];
$dadosErrosAluno = [];

foreach ($evolucaoAluno as $faseAluno) {
    $labelsAluno[] = $faseAluno["jogo"] . " - " . $faseAluno["fase"];

    $dadosAcertosAluno[] = (int) $faseAluno["acertos"];
    $dadosErrosAluno[] = (int) $faseAluno["erros"];
}

$nomePeriodo = [
    "7" => "Últimos 7 dias",
    "30" => "Últimos 30 dias",
    "90" => "Últimos 90 dias",
    "todos" => "Todo o período"
][$periodo];

?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>Desempenho | MathRun</title>

    <link rel="stylesheet" href="css/admin.css">
    <link rel="stylesheet" href="css/desempenho.css">
    <link rel="stylesheet" href="css/admin-layout.css">
    <link rel="stylesheet" href="css/admin-legacy-pages.css">

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <?php require_once __DIR__ . '/../includes/acessibilidade-head.php'; ?>
</head>

<body class="admin-page admin-desempenho-page">

    <div class="admin-layout">

        <header class="mathrun-topnav">
            <a class="mathrun-brand" href="admin.php" aria-label="MathRun, painel administrativo">
                <span class="mathrun-brand-mark">M<span>+</span></span>
                <span class="mathrun-brand-name">MathRun<small>ÁREA ADMINISTRATIVA</small></span>
            </a>
            <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="admin-navigation" aria-label="Abrir menu">
                <span></span><span></span><span></span>
            </button>
            <nav class="mathrun-navigation" id="admin-navigation" aria-label="Navegação administrativa">
                <a href="admin.php"><span class="nav-symbol">⌂</span> Visão geral</a>
                <a href="alunos.php"><span class="nav-symbol">♙</span> Alunos</a>
                <a href="fases.php"><span class="nav-symbol">▤</span> Fases</a>
                <a href="questoes.php"><span class="nav-symbol">∑</span> Questões</a>
                <a href="desempenho.php" class="is-active"><span class="nav-symbol">↗</span> Desempenho</a>
            </nav>
            <span class="topnav-status"><i></i> Painel</span>
        </header>

        <main class="admin-main desempenho-main">

            <header class="page-topbar">
                <div>
                    <p class="topbar-eyebrow">PAINEL ADMINISTRATIVO</p>
                    <h1>Desempenho</h1>
                    <p class="topbar-description">
                        Acompanhe os resultados e a evolução dos alunos no MathRun.
                    </p>
                </div>

                <div class="topbar-status-legacy">
                    <span class="status-dot"></span>
                    Área administrativa
                </div>
            </header>

            <!-- FILTROS -->

            <section class="painel filtros-panel">

                <div class="section-heading">
                    <div>
                        <h2>Filtros de análise</h2>
                        <p>Personalize os dados exibidos nos indicadores.</p>
                    </div>
                </div>

                <form method="GET" action="desempenho.php" class="filtros-form">

                    <?php if ($temTurma): ?>
                        <div class="filtro-campo">
                            <label for="turma">Turma</label>

                            <select name="turma" id="turma">
                                <option value="">Todas as turmas</option>

                                <?php foreach ($sqlTurmas as $opcaoTurma): ?>
                                    <option
                                        value="<?= h($opcaoTurma) ?>"
                                        <?= $turma === $opcaoTurma ? "selected" : "" ?>>
                                        <?= h($opcaoTurma) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>

                    <div class="filtro-campo">
                        <label for="aluno">Aluno</label>

                        <select name="aluno" id="aluno">
                            <option value="">Todos os alunos</option>

                            <?php foreach ($alunos as $aluno): ?>
                                <option
                                    value="<?= (int) $aluno["id"] ?>"
                                    <?= $alunoId === (int) $aluno["id"] ? "selected" : "" ?>>
                                    <?= h($aluno["nome"]) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="filtro-campo">
                        <label for="jogo">Jogo</label>

                        <select name="jogo" id="jogo">
                            <option value="">Todos os jogos</option>

                            <?php foreach ($jogos as $jogo): ?>
                                <option
                                    value="<?= (int) $jogo["id"] ?>"
                                    <?= $jogoId === (int) $jogo["id"] ? "selected" : "" ?>>
                                    <?= h($jogo["nome"]) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="filtro-campo">
                        <label for="periodo">Período</label>

                        <select name="periodo" id="periodo">
                            <option value="7" <?= $periodo === "7" ? "selected" : "" ?>>
                                Últimos 7 dias
                            </option>

                            <option value="30" <?= $periodo === "30" ? "selected" : "" ?>>
                                Últimos 30 dias
                            </option>

                            <option value="90" <?= $periodo === "90" ? "selected" : "" ?>>
                                Últimos 90 dias
                            </option>

                            <option value="todos" <?= $periodo === "todos" ? "selected" : "" ?>>
                                Todo o período
                            </option>
                        </select>
                    </div>

                    <div class="filtro-acoes">
                        <button type="submit" class="btn-filtrar">
                            Aplicar filtros
                        </button>

                        <a href="desempenho.php" class="btn-limpar">
                            Limpar
                        </a>
                    </div>

                </form>

                <p class="filtros-legenda">
                    Período selecionado: <strong><?= h($nomePeriodo) ?></strong>
                </p>

            </section>

            <!-- INDICADORES -->

            <section class="metricas-grid">

                <article class="metrica-card">
                    <div class="metrica-topo">
                        <span class="metrica-label">Alunos analisados</span>
                        <span class="metrica-icone roxo">♙</span>
                    </div>

                    <strong class="metrica-valor">
                        <?= formatarNumero($alunosAnalisados) ?>
                    </strong>

                    <span class="metrica-descricao">
                        Alunos com partidas registradas
                    </span>
                </article>

                <article class="metrica-card">
                    <div class="metrica-topo">
                        <span class="metrica-label">Taxa de acerto</span>
                        <span class="metrica-icone azul">✓</span>
                    </div>

                    <strong class="metrica-valor">
                        <?= number_format($taxaGeral, 1, ",", ".") ?>%
                    </strong>

                    <span class="metrica-descricao">
                        Acertos em relação ao total de respostas
                    </span>
                </article>

                <article class="metrica-card">
                    <div class="metrica-topo">
                        <span class="metrica-label">Partidas realizadas</span>
                        <span class="metrica-icone rosa">▣</span>
                    </div>

                    <strong class="metrica-valor">
                        <?= formatarNumero($partidas) ?>
                    </strong>

                    <span class="metrica-descricao">
                        Partidas registradas no período
                    </span>
                </article>

                <article class="metrica-card">
                    <div class="metrica-topo">
                        <span class="metrica-label">Pontuação média</span>
                        <span class="metrica-icone dourado">★</span>
                    </div>

                    <strong class="metrica-valor">
                        <?= number_format($mediaPontos, 1, ",", ".") ?>
                    </strong>

                    <span class="metrica-descricao">
                        Média de pontos por partida
                    </span>
                </article>

            </section>

            <!-- GRÁFICO DE FASES -->

            <section class="painel grafico-panel">

                <div class="section-heading">
                    <div>
                        <h2>Desempenho por fase</h2>
                        <p>
                            Comparação entre acertos e erros nas fases realizadas.
                        </p>
                    </div>

                    <span class="periodo-badge">
                        <?= h($nomePeriodo) ?>
                    </span>
                </div>

                <?php if (count($evolucao) > 0): ?>

                    <div class="grafico-container grafico-fases">
                        <canvas id="graficoGeral"></canvas>
                    </div>

                <?php else: ?>

                    <div class="estado-vazio">
                        <span class="vazio-icone">▥</span>
                        <h3>Nenhum dado encontrado</h3>
                        <p>
                            Não existem partidas para os filtros selecionados.
                            Tente alterar o período ou remover algum filtro.
                        </p>
                    </div>

                <?php endif; ?>

            </section>

            <!-- COMPARAÇÃO DOS JOGOS -->

            <section class="painel comparacao-panel">

                <div class="section-heading">
                    <div>
                        <h2>Comparação entre jogos</h2>
                        <p>
                            Veja a taxa de acerto dos alunos em cada experiência.
                        </p>
                    </div>
                </div>

                <?php if (count($comparacaoJogos) > 0): ?>

                    <div class="grafico-container grafico-comparacao">
                        <canvas id="graficoJogos"></canvas>
                    </div>

                    <div class="jogos-resumo-grid">

                        <?php foreach ($comparacaoJogos as $jogo): ?>

                            <?php
                            $taxaJogo = percentual(
                                $jogo["acertos"],
                                $jogo["erros"]
                            );
                            ?>

                            <article class="jogo-resumo-card">

                                <div class="jogo-resumo-topo">
                                    <h3><?= h($jogo["nome"]) ?></h3>

                                    <span class="taxa-jogo">
                                        <?= number_format($taxaJogo, 1, ",", ".") ?>%
                                    </span>
                                </div>

                                <div class="barra-progresso">
                                    <div
                                        class="barra-progresso-preenchida"
                                        style="width: <?= min(100, max(0, $taxaJogo)) ?>%;"></div>
                                </div>

                                <div class="jogo-resumo-detalhes">
                                    <span>
                                        <?= formatarNumero($jogo["partidas"]) ?>
                                        partidas
                                    </span>

                                    <span>
                                        <?= formatarNumero($jogo["pontos"]) ?>
                                        pontos
                                    </span>
                                </div>

                            </article>

                        <?php endforeach; ?>

                    </div>

                <?php else: ?>

                    <div class="estado-vazio">
                        <h3>Sem dados para comparar</h3>
                        <p>Os resultados aparecerão quando houver partidas registradas.</p>
                    </div>

                <?php endif; ?>

            </section>

            <!-- ANÁLISE INDIVIDUAL -->

            <section class="painel individual-panel">

                <div class="section-heading">
                    <div>
                        <h2>Análise individual</h2>
                        <p>
                            Consulte a evolução de um aluno específico.
                        </p>
                    </div>
                </div>

                <?php if ($alunoSelecionado): ?>

                    <div class="aluno-destaque">

                        <div class="avatar-aluno">
                            <?= h(mb_strtoupper(mb_substr($alunoSelecionado["nome"], 0, 1, "UTF-8"), "UTF-8")) ?>
                        </div>

                        <div class="aluno-dados">
                            <h3><?= h($alunoSelecionado["nome"]) ?></h3>
                            <p><?= h($alunoSelecionado["email"]) ?></p>
                        </div>

                        <a
                            class="btn-trocar-aluno"
                            href="desempenho.php?periodo=<?= h($periodo) ?><?= $turma !== "" ? "&turma=" . urlencode($turma) : "" ?>">
                            Ver todos os alunos
                        </a>

                    </div>

                    <div class="individual-metricas">

                        <div class="individual-metrica">
                            <span>Taxa de acerto</span>
                            <strong>
                                <?= number_format($taxaAluno, 1, ",", ".") ?>%
                            </strong>
                        </div>

                        <div class="individual-metrica">
                            <span>Partidas realizadas</span>
                            <strong><?= formatarNumero($partidasAluno) ?></strong>
                        </div>

                        <div class="individual-metrica">
                            <span>Pontuação acumulada</span>
                            <strong><?= formatarNumero($pontosAluno) ?></strong>
                        </div>

                    </div>

                    <div class="subsecao-titulo">
                        <h3>Desempenho por fase</h3>
                        <p>Compare a quantidade de acertos e erros do aluno em cada fase.</p>
                    </div>

                    <?php if (count($evolucaoAluno) > 0): ?>

                        <div class="grafico-container grafico-aluno">
                            <canvas id="graficoAluno"></canvas>
                        </div>

                    <?php else: ?>

                        <div class="estado-vazio">
                            <h3>Este aluno ainda não possui partidas</h3>
                            <p>
                                Quando o aluno jogar, a evolução aparecerá aqui.
                            </p>
                        </div>

                    <?php endif; ?>

                    <div class="subsecao-titulo">
                        <h3>Resultados por jogo</h3>
                        <p>Resumo individual de cada jogo disponível.</p>
                    </div>

                    <div class="tabela-responsiva">

                        <table class="tabela-desempenho">

                            <thead>
                                <tr>
                                    <th>Jogo</th>
                                    <th>Partidas</th>
                                    <th>Acertos</th>
                                    <th>Erros</th>
                                    <th>Taxa de acerto</th>
                                    <th>Pontos</th>
                                </tr>
                            </thead>

                            <tbody>

                                <?php foreach ($resumoAlunoJogos as $jogoAluno): ?>

                                    <?php
                                    $taxaIndividualJogo = percentual(
                                        $jogoAluno["acertos"],
                                        $jogoAluno["erros"]
                                    );
                                    ?>

                                    <tr>
                                        <td><?= h($jogoAluno["nome"]) ?></td>

                                        <td>
                                            <?= formatarNumero($jogoAluno["partidas"]) ?>
                                        </td>

                                        <td class="valor-acerto">
                                            <?= formatarNumero($jogoAluno["acertos"]) ?>
                                        </td>

                                        <td class="valor-erro">
                                            <?= formatarNumero($jogoAluno["erros"]) ?>
                                        </td>

                                        <td>
                                            <span class="taxa-tabela">
                                                <?= number_format($taxaIndividualJogo, 1, ",", ".") ?>%
                                            </span>
                                        </td>

                                        <td>
                                            <?= formatarNumero($jogoAluno["pontos"]) ?>
                                        </td>
                                    </tr>

                                <?php endforeach; ?>

                            </tbody>

                        </table>

                    </div>

                <?php else: ?>

                    <div class="estado-vazio estado-individual">
                        <span class="vazio-icone">♙</span>
                        <h3>Selecione um aluno nos filtros</h3>
                        <p>
                            A análise individual mostrará a taxa de acerto,
                            a pontuação, as partidas e a evolução diária.
                        </p>
                    </div>

                <?php endif; ?>

            </section>

            <footer class="desempenho-footer">
                <span>MathRun</span>
                <span>Área administrativa · Desempenho dos alunos</span>
            </footer>

        </main>

    </div>

    <!-- DADOS DOS GRÁFICOS -->

    <script>
        const labelsFases = <?= json_encode(
                                $labelsFases,
                                JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
                            ) ?>;

        const dadosAcertosFases = <?= json_encode($dadosAcertosFases) ?>;
        const dadosErrosFases = <?= json_encode($dadosErrosFases) ?>;

        const labelsJogos = <?= json_encode(
                                $labelsJogos,
                                JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
                            ) ?>;

        const dadosTaxaJogos = <?= json_encode($dadosTaxaJogos) ?>;

        const labelsAluno = <?= json_encode(
                                $labelsAluno,
                                JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
                            ) ?>;

        const dadosAcertosAluno = <?= json_encode($dadosAcertosAluno) ?>;
        const dadosErrosAluno = <?= json_encode($dadosErrosAluno) ?>;

        /*
        |--------------------------------------------------------------------------
        | CONFIGURAÇÃO VISUAL DOS GRÁFICOS
        |--------------------------------------------------------------------------
        */

        Chart.defaults.font.family = "Inter, system-ui, sans-serif";
        Chart.defaults.color = "#9ca3af";

        /*
        |--------------------------------------------------------------------------
        | GRÁFICO DE DESEMPENHO POR FASE
        |--------------------------------------------------------------------------
        */

        const canvasGeral = document.getElementById("graficoGeral");

        if (canvasGeral) {
            new Chart(canvasGeral, {
                type: "bar",

                data: {
                    labels: labelsFases,

                    datasets: [{
                            label: "Acertos",
                            data: dadosAcertosFases,
                            backgroundColor: "#8b7cf6",
                            borderRadius: 6,
                            borderSkipped: false
                        },
                        {
                            label: "Erros",
                            data: dadosErrosFases,
                            backgroundColor: "#e879a9",
                            borderRadius: 6,
                            borderSkipped: false
                        }
                    ]
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
                            position: "top",
                            align: "end",

                            labels: {
                                usePointStyle: true,
                                pointStyle: "circle",
                                boxWidth: 8,
                                padding: 18
                            }
                        },

                        tooltip: {
                            backgroundColor: "#171a2b",
                            titleColor: "#ffffff",
                            bodyColor: "#d1d5db",
                            padding: 12,
                            cornerRadius: 8
                        }
                    },

                    scales: {
                        x: {
                            grid: {
                                display: false
                            },

                            ticks: {
                                maxRotation: 35,
                                minRotation: 0
                            },

                            border: {
                                display: false
                            }
                        },

                        y: {
                            beginAtZero: true,

                            ticks: {
                                precision: 0
                            },

                            grid: {
                                color: "rgba(148, 163, 184, 0.10)"
                            },

                            border: {
                                display: false
                            }
                        }
                    }
                }
            });
        }

        /*
        |--------------------------------------------------------------------------
        | GRÁFICO DE COMPARAÇÃO DOS JOGOS
        |--------------------------------------------------------------------------
        */

        const canvasJogos = document.getElementById("graficoJogos");

        if (canvasJogos) {
            new Chart(canvasJogos, {
                type: "bar",

                data: {
                    labels: labelsJogos,

                    datasets: [{
                        label: "Taxa de acerto",
                        data: dadosTaxaJogos,
                        backgroundColor: ["#8b7cf6", "#62b5f9"],
                        borderRadius: 8,
                        borderSkipped: false,
                        maxBarThickness: 70
                    }]
                },

                options: {
                    indexAxis: "y",
                    responsive: true,
                    maintainAspectRatio: false,

                    plugins: {
                        legend: {
                            display: false
                        },

                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return " " + context.parsed.x + "% de acertos";
                                }
                            }
                        }
                    },

                    scales: {
                        x: {
                            beginAtZero: true,
                            max: 100,

                            ticks: {
                                callback: function(value) {
                                    return value + "%";
                                }
                            },

                            grid: {
                                color: "rgba(148, 163, 184, 0.10)"
                            },

                            border: {
                                display: false
                            }
                        },

                        y: {
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

        /*
        |--------------------------------------------------------------------------
        | GRÁFICO INDIVIDUAL
        |--------------------------------------------------------------------------
        */

        const ctxAluno = document.getElementById("graficoAluno");

        new Chart(ctxAluno, {
            type: "line",
            data: {
                labels: <?= json_encode($labelsAluno) ?>,
                datasets: [{
                        label: "Acertos",
                        data: <?= json_encode($dadosAcertosAluno) ?>,
                        borderColor: "#8b7cf6",
                        backgroundColor: "rgba(139, 124, 246, 0.15)",
                        borderWidth: 3,
                        pointRadius: 5,
                        pointHoverRadius: 7,
                        tension: 0.35,
                        fill: false
                    },
                    {
                        label: "Erros",
                        data: <?= json_encode($dadosErrosAluno) ?>,
                        borderColor: "#e879a9",
                        backgroundColor: "rgba(232, 121, 169, 0.15)",
                        borderWidth: 3,
                        pointRadius: 5,
                        pointHoverRadius: 7,
                        tension: 0.35,
                        fill: false
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: "index",
                    intersect: false
                },
                plugins: {
                    legend: {
                        display: true,
                        labels: {
                            color: "#777990",
                            usePointStyle: true,
                            padding: 20
                        }
                    }
                },
                scales: {
                    x: {
                        ticks: {
                            color: "#85879d"
                        },
                        grid: {
                            color: "rgba(108, 99, 217, 0.08)"
                        }
                    },
                    y: {
                        beginAtZero: true,
                        ticks: {
                            color: "#85879d",
                            precision: 0
                        },
                        grid: {
                            color: "rgba(108, 99, 217, 0.09)"
                        }
                    }
                }
            }
        });
    </script>

    <script src="js/admin-layout.js" defer></script>
    <?php require_once __DIR__ . '/../includes/acessibilidade.php'; ?>
</body>

</html>