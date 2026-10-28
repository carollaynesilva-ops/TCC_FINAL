<?php

session_start();

// ==========================================
// PROTEÇÃO DO PAINEL ADMINISTRATIVO
// ==========================================

if (($_SESSION["admin_autenticado"] ?? false) !== true) {
    header("Location: ../login.php");
    exit;
}

require_once "../config/config.php";

// ==========================================
// VARIÁVEIS INICIAIS
// ==========================================

$erroBanco = "";

$totalAlunos = 0;
$totalJogos = 0;
$totalFases = 0;
$totalQuestoes = 0;
$totalPartidas = 0;
$totalMedalhas = 0;

$desempenhoJogos = [];
$rankingAlunos = [];
$partidasRecentes = [];

$taxaAcertos = 0;

// Escapa textos para exibição segura no HTML.
function adminEsc($valor)
{
    return htmlspecialchars(
        (string) ($valor ?? ""),
        ENT_QUOTES,
        "UTF-8"
    );
}

// ==========================================
// CONSULTAS AO BANCO DE DADOS
// ==========================================

try {

    // Total de alunos
    $stmt = $pdo->query("
        SELECT COUNT(*)
        FROM usuarios
        WHERE tipo = 'aluno'
    ");

    $totalAlunos = (int) $stmt->fetchColumn();


    // Total de jogos
    $stmt = $pdo->query("
        SELECT COUNT(*)
        FROM jogos
    ");

    $totalJogos = (int) $stmt->fetchColumn();


    // Total de fases
    $stmt = $pdo->query("
        SELECT COUNT(*)
        FROM fases
    ");

    $totalFases = (int) $stmt->fetchColumn();


    // Total de questões
    $stmt = $pdo->query("
        SELECT COUNT(*)
        FROM questoes
    ");

    $totalQuestoes = (int) $stmt->fetchColumn();


    // Total de partidas
    $stmt = $pdo->query("
        SELECT COUNT(*)
        FROM historico_partidas
    ");

    $totalPartidas = (int) $stmt->fetchColumn();


    // Total de medalhas cadastradas
    $stmt = $pdo->query("
        SELECT COUNT(*)
        FROM medalhas
    ");

    $totalMedalhas = (int) $stmt->fetchColumn();


    // ==========================================
    // DESEMPENHO POR JOGO
    // ==========================================

    $sqlDesempenho = "
        SELECT
            j.id,
            j.nome,
            COUNT(h.id) AS partidas,
            COALESCE(SUM(h.acertos), 0) AS acertos,
            COALESCE(SUM(h.erros), 0) AS erros,
            COALESCE(SUM(h.pontuacao), 0) AS pontos,
            CASE
                WHEN SUM(h.acertos + h.erros) > 0
                THEN ROUND(
                    SUM(h.acertos) * 100.0 /
                    SUM(h.acertos + h.erros),
                    1
                )
                ELSE 0
            END AS taxa_acertos
        FROM jogos j
        LEFT JOIN historico_partidas h
            ON h.jogo_id = j.id
        GROUP BY j.id, j.nome
        ORDER BY partidas DESC, j.nome ASC
    ";

    $stmt = $pdo->query($sqlDesempenho);

    $desempenhoJogos = $stmt->fetchAll(PDO::FETCH_ASSOC);


    // ==========================================
    // RANKING DOS ALUNOS
    // ==========================================

    $sqlRanking = "
        SELECT
            id,
            nome,
            email,
            serie,
            turma,
            nivel,
            xp,
            pontuacao_total
        FROM usuarios
        WHERE tipo = 'aluno'
        ORDER BY pontuacao_total DESC, xp DESC, nome ASC
        LIMIT 5
    ";

    $stmt = $pdo->query($sqlRanking);

    $rankingAlunos = $stmt->fetchAll(PDO::FETCH_ASSOC);


    // ==========================================
    // PARTIDAS RECENTES
    // ==========================================

    $sqlRecentes = "
        SELECT
            h.id,
            h.pontuacao,
            h.acertos,
            h.erros,
            h.dicas_usadas,
            h.data_partida,
            u.nome AS aluno_nome,
            j.nome AS jogo_nome,
            f.nome AS fase_nome,
            f.serie AS fase_serie
        FROM historico_partidas h
        INNER JOIN usuarios u
            ON u.id = h.usuario_id
        INNER JOIN jogos j
            ON j.id = h.jogo_id
        INNER JOIN fases f
            ON f.id = h.fase_id
        ORDER BY h.data_partida DESC, h.id DESC
        LIMIT 8
    ";

    $stmt = $pdo->query($sqlRecentes);

    $partidasRecentes = $stmt->fetchAll(PDO::FETCH_ASSOC);


    // ==========================================
    // TAXA GERAL DE ACERTOS
    // ==========================================

    $stmt = $pdo->query("
        SELECT
            COALESCE(SUM(acertos), 0) AS total_acertos,
            COALESCE(SUM(erros), 0) AS total_erros
        FROM historico_partidas
    ");

    $resumoRespostas = $stmt->fetch(PDO::FETCH_ASSOC);

    $acertosGerais = (int) $resumoRespostas["total_acertos"];
    $errosGerais = (int) $resumoRespostas["total_erros"];

    $totalRespostas = $acertosGerais + $errosGerais;

    if ($totalRespostas > 0) {
        $taxaAcertos = round(
            ($acertosGerais / $totalRespostas) * 100,
            1
        );
    }
} catch (PDOException $e) {

    error_log("Erro no painel administrativo: " . $e->getMessage());

    $erroBanco = "Não foi possível carregar todos os dados do painel. Verifique a conexão e a estrutura do banco de dados.";
}

// ==========================================
// SAUDAÇÃO
// ==========================================

$hora = (int) date("H");

if ($hora < 12) {
    $saudacao = "Bom dia";
} elseif ($hora < 18) {
    $saudacao = "Boa tarde";
} else {
    $saudacao = "Boa noite";
}

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>Painel Administrativo | MathRun</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">

    <link
        rel="preconnect"
        href="https://fonts.gstatic.com"
        crossorigin>

    <link
        href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">

    <link
        rel="stylesheet"
        href="css/admin.css">
    <link rel="stylesheet" href="css/admin-layout.css">
    <link rel="stylesheet" href="css/admin-legacy-pages.css">

    <?php require_once __DIR__ . '/../includes/acessibilidade-head.php'; ?>
</head>

<body class="admin-page admin-dashboard-page">

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
                <a href="admin.php" class="is-active"><span class="nav-symbol">⌂</span> Visão geral</a>
                <a href="alunos.php"><span class="nav-symbol">♙</span> Alunos</a>
                <a href="fases.php"><span class="nav-symbol">▤</span> Fases</a>
                <a href="questoes.php"><span class="nav-symbol">∑</span> Questões</a>
                <a href="desempenho.php"><span class="nav-symbol">↗</span> Desempenho</a>
                <a href="../logout.php" class="btn-sair"><span>🚪 Sair</span></a>
            </nav>
            <span class="topnav-status"><i></i> Painel</span>
        </header>

        <main class="admin-main">

            <header class="page-topbar">

                <div>
                    <span class="breadcrumb">
                        Painel / Visão geral
                    </span>

                    <h1>Painel administrativo</h1>
                </div>

                <div class="topbar-right">

                    <span class="status-indicator"></span>

                    <span class="status-text">
                        Área administrativa
                    </span>

                </div>

            </header>


            <section class="dashboard-content">

                <!-- BOAS-VINDAS -->

                <div class="welcome-section">

                    <div class="welcome-text">

                        <span class="section-eyebrow">
                            CENTRAL DE CONTROLE
                        </span>

                        <h2>
                            <?= adminEsc($saudacao) ?>!
                            <span>Vamos acompanhar o MathRun?</span>
                        </h2>

                        <p>
                            Aqui você acompanha os alunos, os jogos
                            e os resultados da plataforma.
                        </p>

                    </div>

                    <div class="welcome-decoration" aria-hidden="true">
                        <span class="math-symbol">+</span>
                        <span class="math-symbol">÷</span>
                        <span class="math-symbol">π</span>
                        <span class="math-symbol">√</span>
                    </div>

                </div>


                <!-- ERROS DE CONSULTA -->

                <?php if ($erroBanco !== ""): ?>

                    <div class="alerta-erro">
                        <?= adminEsc($erroBanco) ?>
                    </div>

                <?php endif; ?>


                <!-- ==================================
                     CARDS DE ESTATÍSTICAS
                =================================== -->

                <section class="stats-grid">

                    <article class="stat-card stat-blue">

                        <div class="stat-top">
                            <span class="stat-label">
                                Alunos cadastrados
                            </span>

                            <span class="stat-icon">♙</span>
                        </div>

                        <strong class="stat-number">
                            <?= number_format($totalAlunos, 0, ',', '.') ?>
                        </strong>

                        <span class="stat-description">
                            Contas de estudantes
                        </span>

                    </article>


                    <article class="stat-card stat-purple">

                        <div class="stat-top">
                            <span class="stat-label">
                                Partidas realizadas
                            </span>

                            <span class="stat-icon">▷</span>
                        </div>

                        <strong class="stat-number">
                            <?= number_format($totalPartidas, 0, ',', '.') ?>
                        </strong>

                        <span class="stat-description">
                            Registros de partidas
                        </span>

                    </article>


                    <article class="stat-card stat-pink">

                        <div class="stat-top">
                            <span class="stat-label">
                                Questões cadastradas
                            </span>

                            <span class="stat-icon">✎</span>
                        </div>

                        <strong class="stat-number">
                            <?= number_format($totalQuestoes, 0, ',', '.') ?>
                        </strong>

                        <span class="stat-description">
                            Questões disponíveis no banco
                        </span>

                    </article>


                    <article class="stat-card stat-orange">

                        <div class="stat-top">
                            <span class="stat-label">
                                Fases cadastradas
                            </span>

                            <span class="stat-icon">⚑</span>
                        </div>

                        <strong class="stat-number">
                            <?= number_format($totalFases, 0, ',', '.') ?>
                        </strong>

                        <span class="stat-description">
                            Fases de todos os jogos e séries
                        </span>

                    </article>

                </section>


                <!-- ==================================
                     RESUMO DA PLATAFORMA
                =================================== -->

                <section class="summary-grid">

                    <article class="summary-card">

                        <div class="summary-heading">
                            <div>
                                <span class="section-eyebrow">
                                    PLATAFORMA
                                </span>

                                <h3>Resumo geral</h3>
                            </div>

                            <span class="summary-icon">▦</span>
                        </div>

                        <div class="summary-list">

                            <div class="summary-row">
                                <span>Jogos cadastrados</span>
                                <strong>
                                    <?= number_format($totalJogos, 0, ',', '.') ?>
                                </strong>
                            </div>

                            <div class="summary-row">
                                <span>Medalhas cadastradas</span>
                                <strong>
                                    <?= number_format($totalMedalhas, 0, ',', '.') ?>
                                </strong>
                            </div>

                            <div class="summary-row">
                                <span>Total de respostas</span>
                                <strong>
                                    <?= number_format(
                                        $acertosGerais + $errosGerais,
                                        0,
                                        ',',
                                        '.'
                                    ) ?>
                                </strong>
                            </div>

                        </div>

                    </article>


                    <article class="summary-card accuracy-card">

                        <div class="summary-heading">
                            <div>
                                <span class="section-eyebrow">
                                    APRENDIZAGEM
                                </span>

                                <h3>Taxa geral de acertos</h3>
                            </div>

                            <span class="summary-icon">◎</span>
                        </div>

                        <div class="accuracy-content">

                            <div class="accuracy-number">
                                <?= number_format($taxaAcertos, 1, ',', '.') ?>%
                            </div>

                            <div
                                class="progress-track"
                                role="progressbar"
                                aria-label="Taxa geral de acertos"
                                aria-valuemin="0"
                                aria-valuemax="100"
                                aria-valuenow="<?= $taxaAcertos ?>">

                                <div
                                    class="progress-fill"
                                    style="width: <?= max(0, min(100, $taxaAcertos)) ?>%">
                                </div>

                            </div>

                            <p>
                                <?= number_format($acertosGerais, 0, ',', '.') ?>
                                acertos e
                                <?= number_format($errosGerais, 0, ',', '.') ?>
                                erros registrados.
                            </p>

                        </div>

                    </article>

                </section>


                <!-- ==================================
                     DESEMPENHO DOS JOGOS
                =================================== -->

                <section class="panel-card">

                    <div class="panel-heading">

                        <div>
                            <span class="section-eyebrow">
                                RESULTADOS
                            </span>

                            <h3>Desempenho por jogo</h3>

                            <p>
                                Comparativo dos resultados registrados
                                em cada jogo.
                            </p>
                        </div>

                        <a href="desempenho.php" class="text-link">
                            Ver desempenho →
                        </a>

                    </div>


                    <?php if (empty($desempenhoJogos)): ?>

                        <div class="empty-state">
                            <span class="empty-icon">▥</span>

                            <strong>Nenhum jogo cadastrado</strong>

                            <p>
                                Os jogos aparecerão aqui quando estiverem
                                cadastrados no banco de dados.
                            </p>
                        </div>

                    <?php else: ?>

                        <div class="table-responsive">

                            <table class="admin-table">

                                <thead>
                                    <tr>
                                        <th>Jogo</th>
                                        <th>Partidas</th>
                                        <th>Acertos</th>
                                        <th>Erros</th>
                                        <th>Taxa de acertos</th>
                                    </tr>
                                </thead>

                                <tbody>

                                    <?php foreach ($desempenhoJogos as $jogo): ?>

                                        <tr>

                                            <td>
                                                <div class="game-name">

                                                    <span class="game-dot">
                                                        <?= (int) $jogo["id"] === 1
                                                            ? "M"
                                                            : "S" ?>
                                                    </span>

                                                    <strong>
                                                        <?= adminEsc($jogo["nome"]) ?>
                                                    </strong>

                                                </div>
                                            </td>

                                            <td>
                                                <?= number_format(
                                                    (int) $jogo["partidas"],
                                                    0,
                                                    ',',
                                                    '.'
                                                ) ?>
                                            </td>

                                            <td class="number-success">
                                                <?= number_format(
                                                    (int) $jogo["acertos"],
                                                    0,
                                                    ',',
                                                    '.'
                                                ) ?>
                                            </td>

                                            <td class="number-error">
                                                <?= number_format(
                                                    (int) $jogo["erros"],
                                                    0,
                                                    ',',
                                                    '.'
                                                ) ?>
                                            </td>

                                            <td>

                                                <div class="table-progress">

                                                    <span>
                                                        <?= number_format(
                                                            (float) $jogo["taxa_acertos"],
                                                            1,
                                                            ',',
                                                            '.'
                                                        ) ?>%
                                                    </span>

                                                    <div class="mini-progress">

                                                        <div
                                                            style="width: <?= max(
                                                                                0,
                                                                                min(
                                                                                    100,
                                                                                    (float) $jogo["taxa_acertos"]
                                                                                )
                                                                            ) ?>%">
                                                        </div>

                                                    </div>

                                                </div>

                                            </td>

                                        </tr>

                                    <?php endforeach; ?>

                                </tbody>

                            </table>

                        </div>

                    <?php endif; ?>

                </section>


                <!-- ==================================
                     RANKING E PARTIDAS RECENTES
                =================================== -->

                <section class="bottom-grid">


                    <!-- RANKING -->

                    <article class="panel-card">

                        <div class="panel-heading">

                            <div>
                                <span class="section-eyebrow">
                                    DESTAQUES
                                </span>

                                <h3>Ranking de alunos</h3>
                            </div>

                            <span class="heading-icon">♛</span>

                        </div>


                        <?php if (empty($rankingAlunos)): ?>

                            <div class="empty-state">
                                <strong>Nenhum aluno cadastrado</strong>
                                <p>
                                    O ranking aparecerá quando houver
                                    alunos cadastrados.
                                </p>
                            </div>

                        <?php else: ?>

                            <div class="ranking-list" id="rankingLista">

                                <?php foreach ($rankingAlunos as $indice => $aluno): ?>

                                    <div class="ranking-item">

                                        <span class="ranking-position pos-<?= $indice + 1 ?>">
                                            <?= $indice + 1 ?>
                                        </span>

                                        <div class="ranking-avatar">
                                            <?= adminEsc(
                                                strtoupper(
                                                    mb_substr(
                                                        $aluno["nome"],
                                                        0,
                                                        1,
                                                        "UTF-8"
                                                    )
                                                )
                                            ) ?>
                                        </div>

                                        <div class="ranking-info">

                                            <strong>
                                                <?= adminEsc($aluno["nome"]) ?>
                                            </strong>

                                            <span>

                                                <?php if (!empty($aluno["serie"])): ?>

                                                    <?= (int) $aluno["serie"] ?>º ano

                                                    <?php if (!empty($aluno["turma"])): ?>
                                                        · <?= adminEsc($aluno["turma"]) ?>
                                                    <?php endif; ?>

                                                    ·

                                                <?php endif; ?>

                                                Nível
                                                <?= (int) $aluno["nivel"] ?>

                                            </span>

                                        </div>

                                        <div class="ranking-points">

                                            <strong>
                                                <?= number_format(
                                                    (int) $aluno["pontuacao_total"],
                                                    0,
                                                    ',',
                                                    '.'
                                                ) ?>
                                            </strong>

                                            <span>pontos</span>

                                        </div>

                                    </div>

                                <?php endforeach; ?>

                            </div>




                            <button
                                type="button"
                                class="list-toggle"
                                aria-expanded="false"
                                aria-controls="rankingLista"
                                onclick="alternarLista('rankingLista', this)">
                                <span class="toggle-label">Ver mais alunos</span>
                                <span class="toggle-arrow">⌄</span>
                            </button>
                        <?php endif; ?>

                    </article>


                    <!-- PARTIDAS RECENTES -->

                    <article class="panel-card">

                        <div class="panel-heading">

                            <div>
                                <span class="section-eyebrow">
                                    ATIVIDADE
                                </span>

                                <h3>Partidas recentes</h3>
                            </div>

                            <span class="heading-icon">◷</span>

                        </div>


                        <?php if (empty($partidasRecentes)): ?>

                            <div class="empty-state">
                                <strong>Nenhuma partida registrada</strong>
                                <p>
                                    As atividades dos alunos aparecerão
                                    aqui quando forem registradas.
                                </p>
                            </div>

                        <?php else: ?>

                            <div class="recent-list" id="partidasLista">

                                <?php foreach ($partidasRecentes as $partida): ?>

                                    <div class="recent-item">

                                        <div class="recent-icon">
                                            ▷
                                        </div>

                                        <div class="recent-info">

                                            <strong>
                                                <?= adminEsc($partida["aluno_nome"]) ?>
                                            </strong>

                                            <span>
                                                <?= adminEsc($partida["jogo_nome"]) ?>
                                                ·
                                                <?= adminEsc($partida["fase_nome"]) ?>
                                                ·
                                                <?= (int) $partida["fase_serie"] ?>º ano
                                            </span>

                                            <small>
                                                <?php
                                                $timestamp = strtotime(
                                                    $partida["data_partida"]
                                                );

                                                echo $timestamp
                                                    ? date("d/m/Y H:i", $timestamp)
                                                    : "Data indisponível";
                                                ?>
                                            </small>

                                        </div>

                                        <div class="recent-score">

                                            <strong>
                                                +<?= number_format(
                                                        (int) $partida["pontuacao"],
                                                        0,
                                                        ',',
                                                        '.'
                                                    ) ?>
                                            </strong>

                                            <span>pontos</span>

                                        </div>

                                    </div>

                                <?php endforeach; ?>

                            </div>


                            <button
                                type="button"
                                class="list-toggle"
                                aria-expanded="false"
                                aria-controls="partidasLista"
                                onclick="alternarLista('partidasLista', this)">
                                <span class="toggle-label">Ver mais partidas</span>
                                <span class="toggle-arrow">⌄</span>
                            </button>


                        <?php endif; ?>

                    </article>

                </section>


                <!-- ==================================
                     ATALHOS ADMINISTRATIVOS
                =================================== -->

                <section class="panel-card shortcuts-card">

                    <div class="panel-heading">

                        <div>
                            <span class="section-eyebrow">
                                ACESSO RÁPIDO
                            </span>

                            <h3>Gerenciar plataforma</h3>

                            <p>
                                Acesse diretamente as principais ferramentas.
                            </p>
                        </div>

                    </div>

                    <div class="shortcuts-grid">

                        <a href="alunos.php" class="shortcut-item">
                            <span class="shortcut-icon shortcut-blue">♙</span>

                            <span>
                                <strong>Alunos</strong>
                                <small>Consultar estudantes</small>
                            </span>

                            <span class="shortcut-arrow">→</span>
                        </a>


                        <a href="desempenho.php" class="shortcut-item">
                            <span class="shortcut-icon shortcut-purple">▥</span>

                            <span>
                                <strong>Desempenho</strong>
                                <small>Acompanhar resultados</small>
                            </span>

                            <span class="shortcut-arrow">→</span>
                        </a>


                        <a href="questoes.php" class="shortcut-item">
                            <span class="shortcut-icon shortcut-pink">✎</span>

                            <span>
                                <strong>Questões</strong>
                                <small>Gerenciar perguntas</small>
                            </span>

                            <span class="shortcut-arrow">→</span>
                        </a>


                        <a href="fases.php" class="shortcut-item">
                            <span class="shortcut-icon shortcut-orange">⚑</span>

                            <span>
                                <strong>Fases</strong>
                                <small>Gerenciar desafios</small>
                            </span>

                            <span class="shortcut-arrow">→</span>
                        </a>

                    </div>

                </section>


                <footer class="admin-footer">

                    <span>
                        MathRun · Painel administrativo
                    </span>

                    <span>
                        Aprender também é evoluir.
                    </span>

                </footer>

            </section>

        </main>

    </div>


    <script>
        function alternarLista(id, botao) {
            const lista = document.getElementById(id);

            if (!lista) return;

            const expandida = lista.classList.toggle("lista-expandida");

            botao.setAttribute("aria-expanded", expandida);

            const texto = botao.querySelector(".toggle-label");

            if (id === "rankingLista") {
                texto.textContent = expandida ?
                    "Ver menos alunos" :
                    "Ver mais alunos";
            } else {
                texto.textContent = expandida ?
                    "Ver menos partidas" :
                    "Ver mais partidas";
            }
        }
    </script>
    <script src="js/admin-layout.js" defer></script>
    <?php require_once __DIR__ . '/../includes/acessibilidade.php'; ?>
</body>

</html>