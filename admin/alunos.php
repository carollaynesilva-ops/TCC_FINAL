<?php
session_start();

if (($_SESSION["admin_autenticado"] ?? false) !== true) {
    header("Location: ../login.php");
    exit;
}

require_once "../config/config.php";

$erro = "";
$sucesso = "";

/* =========================================
   FUNÇÕES AUXILIARES
========================================= */

function alunoEsc($valor): string
{
    return htmlspecialchars(
        (string) ($valor ?? ""),
        ENT_QUOTES,
        "UTF-8"
    );
}

function alunoIniciais(string $nome): string
{
    $partes = preg_split('/\s+/', trim($nome));
    $iniciais = "";

    foreach (array_slice($partes ?: [], 0, 2) as $parte) {
        if ($parte !== "") {
            $iniciais .= mb_substr($parte, 0, 1, "UTF-8");
        }
    }

    return mb_strtoupper($iniciais ?: "A", "UTF-8");
}

/* =========================================
   TOKEN DE SEGURANÇA DO FORMULÁRIO
========================================= */

if (empty($_SESSION["csrf_alunos"])) {
    $_SESSION["csrf_alunos"] = bin2hex(random_bytes(32));
}

$csrf = $_SESSION["csrf_alunos"];

/* =========================================
   EDITAR DADOS DO ALUNO
========================================= */

if (
    $_SERVER["REQUEST_METHOD"] === "POST"
    && ($_POST["acao"] ?? "") === "editar"
) {

    $token = $_POST["csrf"] ?? "";

    if (!hash_equals($csrf, $token)) {
        $erro = "A sessão do formulário expirou. Atualize a página.";
    } else {
        $id = filter_input(INPUT_POST, "id", FILTER_VALIDATE_INT);
        $nome = trim($_POST["nome"] ?? "");
        $email = trim($_POST["email"] ?? "");
        $serie = $_POST["serie"] ?? "";
        $turma = strtoupper(trim($_POST["turma"] ?? ""));

        $seriesPermitidas = ["6", "7", "8", "9", ""];
        $turmasPermitidas = ["A", "B", ""];

        if (!$id || $nome === "" || mb_strlen($nome) > 100) {
            $erro = "Informe um nome válido para o aluno.";
        } elseif (
            !filter_var($email, FILTER_VALIDATE_EMAIL)
            || mb_strlen($email) > 150
        ) {
            $erro = "Informe um endereço de e-mail válido.";
        } elseif (
            !in_array($serie, $seriesPermitidas, true)
            || !in_array($turma, $turmasPermitidas, true)
        ) {
            $erro = "A série ou a turma selecionada é inválida.";
        } elseif ($serie === "" && $turma !== "") {
            $erro = "Selecione uma série antes de definir a turma.";
        } else {
            try {
                $stmt = $pdo->prepare("
                    UPDATE usuarios
                    SET nome = :nome,
                        email = :email,
                        serie = :serie,
                        turma = :turma
                    WHERE id = :id
                      AND tipo = 'aluno'
                ");

                $stmt->execute([
                    ":nome" => $nome,
                    ":email" => $email,
                    ":serie" => $serie === "" ? null : (int) $serie,
                    ":turma" => $turma === "" ? null : $turma,
                    ":id" => $id
                ]);

                header("Location: alunos.php?atualizado=1");
                exit;
            } catch (PDOException $e) {
                error_log("Erro ao editar aluno: " . $e->getMessage());

                if ($e->getCode() === "23000") {
                    $erro = "Esse e-mail já está sendo utilizado por outra conta.";
                } else {
                    $erro = "Não foi possível atualizar os dados do aluno.";
                }
            }
        }
    }
}

if (isset($_GET["atualizado"])) {
    $sucesso = "Dados do aluno atualizados com sucesso.";
}

/* =========================================
   FILTROS
========================================= */

$filtroSerie = $_GET["serie"] ?? "";
$filtroTurma = strtoupper($_GET["turma"] ?? "");

if (!in_array($filtroSerie, ["", "6", "7", "8", "9"], true)) {
    $filtroSerie = "";
}

if (!in_array($filtroTurma, ["", "A", "B", "sem"], true)) {
    $filtroTurma = "";
}

/* =========================================
   BUSCAR ALUNOS E CALCULAR DESEMPENHO
========================================= */

$alunos = [];
$totalAlunos = 0;

try {
    $sql = "
        SELECT
            u.id,
            u.nome,
            u.email,
            u.foto,
            u.serie,
            u.turma,
            u.nivel,
            u.xp,
            u.pontuacao_total,
            u.data_cadastro,

            COUNT(h.id) AS partidas,

            COALESCE(SUM(h.acertos), 0) AS acertos,
            COALESCE(SUM(h.erros), 0) AS erros,

            CASE
                WHEN SUM(h.acertos + h.erros) > 0
                THEN ROUND(
                    SUM(h.acertos) * 100.0 /
                    SUM(h.acertos + h.erros),
                    1
                )
                ELSE 0
            END AS taxa_acertos

        FROM usuarios u

        LEFT JOIN historico_partidas h
            ON h.usuario_id = u.id

        WHERE u.tipo = 'aluno'
    ";

    $parametros = [];

    if ($filtroSerie !== "") {
        $sql .= " AND u.serie = :serie";
        $parametros[":serie"] = (int) $filtroSerie;
    }

    if ($filtroTurma === "A" || $filtroTurma === "B") {
        $sql .= " AND u.turma = :turma";
        $parametros[":turma"] = $filtroTurma;
    } elseif ($filtroTurma === "sem") {
        $sql .= " AND (u.turma IS NULL OR u.turma = '')";
    }

    $sql .= "
        GROUP BY
            u.id,
            u.nome,
            u.email,
            u.foto,
            u.serie,
            u.turma,
            u.nivel,
            u.xp,
            u.pontuacao_total,
            u.data_cadastro

        ORDER BY u.nome ASC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($parametros);

    $alunos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $totalAlunos = count($alunos);
} catch (PDOException $e) {
    error_log("Erro ao listar alunos: " . $e->getMessage());
    $erro = "Não foi possível carregar os alunos. Verifique a estrutura do banco.";
}

/* =========================================
   EXPORTAR ALUNOS SELECIONADOS EM CSV
========================================= */

if (
    $_SERVER["REQUEST_METHOD"] === "POST"
    && ($_POST["acao"] ?? "") === "exportar"
) {

    $token = $_POST["csrf"] ?? "";

    if (!hash_equals($csrf, $token)) {
        exit("Sessão expirada. Atualize a página.");
    }

    $ids = $_POST["alunos"] ?? [];

    if (!is_array($ids) || empty($ids)) {
        header("Location: alunos.php?exportacao=vazia");
        exit;
    }

    $ids = array_values(array_unique(array_filter(
        array_map("intval", $ids),
        fn($id) => $id > 0
    )));

    if (empty($ids)) {
        header("Location: alunos.php?exportacao=vazia");
        exit;
    }

    $marcadores = implode(",", array_fill(0, count($ids), "?"));

    $stmt = $pdo->prepare("
        SELECT id, nome, email, serie, turma, nivel, xp, pontuacao_total
        FROM usuarios
        WHERE tipo = 'aluno'
          AND id IN ($marcadores)
        ORDER BY nome ASC
    ");

    $stmt->execute($ids);

    header("Content-Type: text/csv; charset=UTF-8");
    header("Content-Disposition: attachment; filename=alunos-mathrun.csv");

    $saida = fopen("php://output", "w");

    // BOM para o Excel reconhecer acentos corretamente.
    fwrite($saida, "\xEF\xBB\xBF");

    fputcsv($saida, [
        "ID",
        "Nome",
        "E-mail",
        "Série",
        "Turma",
        "Nível",
        "XP",
        "Pontuação"
    ], ";");

    while ($aluno = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($saida, [
            $aluno["id"],
            $aluno["nome"],
            $aluno["email"],
            $aluno["serie"] ? $aluno["serie"] . "º ano" : "Sem série",
            $aluno["turma"] ?: "Sem turma",
            $aluno["nivel"],
            $aluno["xp"],
            $aluno["pontuacao_total"]
        ], ";");
    }

    fclose($saida);
    exit;
}

/* =========================================
   CONTADORES DAS TURMAS
========================================= */

$contagemTurmas = [
    "todos" => 0,
    "sem" => 0,
    "6A" => 0,
    "6B" => 0,
    "7A" => 0,
    "7B" => 0,
    "8A" => 0,
    "8B" => 0,
    "9A" => 0,
    "9B" => 0
];

try {
    $stmt = $pdo->query("
        SELECT serie, turma, COUNT(*) AS total
        FROM usuarios
        WHERE tipo = 'aluno'
        GROUP BY serie, turma
    ");

    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $grupo) {
        $serie = (string) ($grupo["serie"] ?? "");
        $turma = strtoupper((string) ($grupo["turma"] ?? ""));
        $total = (int) $grupo["total"];

        $contagemTurmas["todos"] += $total;

        if ($serie === "" || $turma === "") {
            $contagemTurmas["sem"] += $total;
        } elseif (isset($contagemTurmas[$serie . $turma])) {
            $contagemTurmas[$serie . $turma] += $total;
        }
    }
} catch (PDOException $e) {
    error_log("Erro nos contadores de turma: " . $e->getMessage());
}

/* =========================================
   SAUDAÇÃO
========================================= */

$hora = (int) date("H");

$saudacao = $hora < 12
    ? "Bom dia"
    : ($hora < 18 ? "Boa tarde" : "Boa noite");

?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Alunos | MathRun Admin</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="css/admin.css">
    <link rel="stylesheet" href="css/alunos.css">
    <link rel="stylesheet" href="css/admin-layout.css">
    <link rel="stylesheet" href="css/admin-legacy-pages.css">
    <?php require_once __DIR__ . '/../includes/acessibilidade-head.php'; ?>
</head>

<body class="admin-page admin-alunos-page">
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
                <a href="alunos.php" class="is-active"><span class="nav-symbol">♙</span> Alunos</a>
                <a href="fases.php"><span class="nav-symbol">▤</span> Fases</a>
                <a href="questoes.php"><span class="nav-symbol">∑</span> Questões</a>
                <a href="desempenho.php"><span class="nav-symbol">↗</span> Desempenho</a>
            </nav>
            <span class="topnav-status"><i></i> Painel</span>
        </header>

        <main class="admin-main alunos-main">

            <header class="page-topbar">
                <div>
                    <span class="breadcrumb">Painel / Gerenciamento</span>
                    <h1>Alunos</h1>
                    <p class="page-subtitle">
                        Gerencie cadastros e acompanhe a evolução dos estudantes.
                    </p>
                </div>

                <div class="topbar-right">
                    <span class="status-indicator"></span>
                    <span class="status-text">Área administrativa</span>
                </div>
            </header>

            <section class="dashboard-content alunos-content">

                <div class="alunos-intro">
                    <div>
                        <span class="section-eyebrow">GESTÃO DE ESTUDANTES</span>
                        <h2><?= alunoEsc($saudacao) ?>! Vamos acompanhar os alunos?</h2>
                        <p>Consulte cadastros, filtre turmas e acompanhe o desempenho no MathRun.</p>
                    </div>

                    <div class="intro-symbol" aria-hidden="true">∑</div>
                </div>

                <?php if ($erro !== ""): ?>
                    <div class="alunos-alerta alerta-erro">
                        <?= alunoEsc($erro) ?>
                    </div>
                <?php endif; ?>

                <?php if ($sucesso !== ""): ?>
                    <div class="alunos-alerta alerta-sucesso">
                        <?= alunoEsc($sucesso) ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($_GET["exportacao"]) && $_GET["exportacao"] === "vazia"): ?>
                    <div class="alunos-alerta">
                        Selecione pelo menos um aluno antes de exportar.
                    </div>
                <?php endif; ?>

                <section class="alunos-metrics">
                    <article class="aluno-metric">
                        <span class="metric-icon">♙</span>
                        <span class="metric-label">Alunos cadastrados</span>
                        <strong><?= number_format($contagemTurmas["todos"], 0, ",", ".") ?></strong>
                        <small>Contas de estudantes</small>
                    </article>

                    <article class="aluno-metric">
                        <span class="metric-icon metric-purple">▦</span>
                        <span class="metric-label">Turmas cadastradas</span>
                        <strong>
                            <?php
                            $turmasAtivas = 0;
                            foreach (["6A", "6B", "7A", "7B", "8A", "8B", "9A", "9B"] as $chave) {
                                if ($contagemTurmas[$chave] > 0) {
                                    $turmasAtivas++;
                                }
                            }
                            echo $turmasAtivas;
                            ?>
                        </strong>
                        <small>Com alunos matriculados no sistema</small>
                    </article>

                    <article class="aluno-metric">
                        <span class="metric-icon metric-cyan">◎</span>
                        <span class="metric-label">Sem turma definida</span>
                        <strong><?= number_format($contagemTurmas["sem"], 0, ",", ".") ?></strong>
                        <small>Cadastros que precisam ser conferidos</small>
                    </article>
                </section>

                <section class="alunos-panel">

                    <div class="alunos-panel-heading">
                        <div>
                            <span class="section-eyebrow">BASE DE ALUNOS</span>
                            <h3>Todos os estudantes</h3>
                            <p>Use as turmas ou a busca para encontrar um cadastro.</p>
                        </div>

                        <span class="total-pill">
                            <strong id="contadorVisiveis"><?= count($alunos) ?></strong>
                            <span>resultados</span>
                        </span>
                    </div>

                    <div class="turma-chips" aria-label="Filtrar por turma">

                        <a class="turma-chip <?= $filtroSerie === "" && $filtroTurma === "" ? "selecionado" : "" ?>"
                            href="alunos.php">
                            Todos
                            <span><?= $contagemTurmas["todos"] ?></span>
                        </a>

                        <?php foreach ([6, 7, 8, 9] as $serie): ?>
                            <?php foreach (["A", "B"] as $turma): ?>
                                <?php $chave = $serie . $turma; ?>
                                <a class="turma-chip <?= (string)$filtroSerie === (string)$serie && $filtroTurma === $turma ? "selecionado" : "" ?>"
                                    href="alunos.php?serie=<?= $serie ?>&turma=<?= $turma ?>">
                                    <?= $serie ?>º <?= $turma ?>
                                    <span><?= $contagemTurmas[$chave] ?></span>
                                </a>
                            <?php endforeach; ?>
                        <?php endforeach; ?>

                        <a class="turma-chip chip-sem-turma <?= $filtroTurma === "sem" ? "selecionado" : "" ?>"
                            href="alunos.php?turma=sem">
                            Sem turma
                            <span><?= $contagemTurmas["sem"] ?></span>
                        </a>
                    </div>

                    <div class="alunos-toolbar">
                        <label class="alunos-search">
                            <span aria-hidden="true">⌕</span>
                            <input
                                type="search"
                                id="buscaAluno"
                                placeholder="Buscar por nome ou e-mail..."
                                autocomplete="off"
                                aria-label="Buscar alunos por nome ou e-mail">
                            <kbd>⌘ K</kbd>
                        </label>

                        <div class="toolbar-actions">
                            <label class="filtro-select-wrap">
                                <span>Série</span>
                                <select id="filtroSerie">
                                    <option value="">Todas</option>
                                    <option value="6">6º ano</option>
                                    <option value="7">7º ano</option>
                                    <option value="8">8º ano</option>
                                    <option value="9">9º ano</option>
                                </select>
                            </label>

                            <label class="filtro-select-wrap">
                                <span>Turma</span>
                                <select id="filtroTurma">
                                    <option value="">Todas</option>
                                    <option value="A">Turma A</option>
                                    <option value="B">Turma B</option>
                                    <option value="sem">Sem turma</option>
                                </select>
                            </label>

                            <div class="view-switch" aria-label="Modo de visualização">
                                <button type="button" class="view-button ativo" data-view="table" aria-label="Visualização em tabela" title="Tabela">☷</button>
                                <button type="button" class="view-button" data-view="cards" aria-label="Visualização em cards" title="Cards">▦</button>
                            </div>
                        </div>
                    </div>

                    <form method="POST" id="formExportar">
                        <input type="hidden" name="acao" value="exportar">
                        <input type="hidden" name="csrf" value="<?= alunoEsc($csrf) ?>">

                        <div class="selection-bar" id="selectionBar">
                            <span><strong id="contadorSelecionados">0</strong> aluno(s) selecionado(s)</span>

                            <div>
                                <button type="button" class="selection-clear" id="limparSelecao">Limpar seleção</button>
                                <button type="submit" class="button-exportar">↓ Exportar CSV</button>
                            </div>
                        </div>

                        <div class="table-responsive" id="tabelaView">
                            <table class="alunos-table">
                                <thead>
                                    <tr>
                                        <th class="check-column">
                                            <input type="checkbox" id="selecionarTodos" aria-label="Selecionar todos os alunos visíveis">
                                        </th>
                                        <th>Aluno</th>
                                        <th>Turma</th>
                                        <th>Desempenho</th>
                                        <th>Progressão</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>

                                <tbody id="tabelaAlunos">
                                    <?php foreach ($alunos as $aluno): ?>
                                        <?php
                                        $serie = (string) ($aluno["serie"] ?? "");
                                        $turma = strtoupper((string) ($aluno["turma"] ?? ""));
                                        $semTurma = $serie === "" || $turma === "";
                                        $percentual = max(0, min(100, (float) $aluno["taxa_acertos"]));
                                        $foto = trim((string) ($aluno["foto"] ?? ""));
                                        ?>
                                        <tr class="aluno-row"
                                            data-id="<?= (int) $aluno["id"] ?>"
                                            data-nome="<?= alunoEsc(mb_strtolower($aluno["nome"], "UTF-8")) ?>"
                                            data-email="<?= alunoEsc(mb_strtolower($aluno["email"], "UTF-8")) ?>"
                                            data-serie="<?= alunoEsc($serie) ?>"
                                            data-turma="<?= alunoEsc($turma) ?>"
                                            data-sem-turma="<?= $semTurma ? "1" : "0" ?>">

                                            <td class="check-column">
                                                <input type="checkbox" class="aluno-checkbox" name="alunos[]" value="<?= (int) $aluno["id"] ?>" aria-label="Selecionar <?= alunoEsc($aluno["nome"]) ?>">
                                            </td>

                                            <td>
                                                <div class="aluno-identidade">
                                                    <?php if ($foto !== ""): ?>
                                                        <img class="aluno-avatar" src="../<?= alunoEsc(ltrim($foto, "/")) ?>" alt="Foto de <?= alunoEsc($aluno["nome"]) ?>" loading="lazy">
                                                    <?php else: ?>
                                                        <span class="aluno-avatar avatar-iniciais"><?= alunoEsc(alunoIniciais($aluno["nome"])) ?></span>
                                                    <?php endif; ?>

                                                    <span class="aluno-nome-bloco">
                                                        <strong><?= alunoEsc($aluno["nome"]) ?></strong>
                                                        <span><?= alunoEsc($aluno["email"]) ?></span>
                                                    </span>
                                                </div>
                                            </td>

                                            <td>
                                                <?php if (!$semTurma): ?>
                                                    <span class="turma-badge"><?= (int) $serie ?>º ano · <?= alunoEsc($turma) ?></span>
                                                <?php else: ?>
                                                    <span class="turma-badge badge-pendente">Não definida</span>
                                                <?php endif; ?>
                                            </td>

                                            <td>
                                                <div class="desempenho-mini">
                                                    <div class="desempenho-texto">
                                                        <strong><?= number_format($percentual, 1, ",", ".") ?>%</strong>
                                                        <span><?= (int)$aluno["partidas"] ?> partidas</span>
                                                    </div>
                                                    <div class="barra-progresso">
                                                        <span style="width: <?= $percentual ?>%"></span>
                                                    </div>
                                                </div>
                                            </td>

                                            <td>
                                                <div class="progressao-aluno">
                                                    <span class="nivel-badge">Nível <?= (int)$aluno["nivel"] ?></span>
                                                    <span><?= number_format((int)$aluno["xp"], 0, ",", ".") ?> XP</span>
                                                    <small><?= number_format((int)$aluno["pontuacao_total"], 0, ",", ".") ?> pontos</small>
                                                </div>
                                            </td>

                                            <td>
                                                <div class="acoes-aluno">
                                                    <a class="acao-icone" href="mailto:<?= alunoEsc($aluno["email"]) ?>" title="Enviar e-mail" aria-label="Enviar e-mail para <?= alunoEsc($aluno["nome"]) ?>">✉</a>

                                                    <button type="button"
                                                        class="acao-icone editar-aluno"
                                                        title="Editar cadastro"
                                                        aria-label="Editar <?= alunoEsc($aluno["nome"]) ?>"
                                                        data-id="<?= (int)$aluno["id"] ?>"
                                                        data-nome="<?= alunoEsc($aluno["nome"]) ?>"
                                                        data-email="<?= alunoEsc($aluno["email"]) ?>"
                                                        data-serie="<?= alunoEsc($serie) ?>"
                                                        data-turma="<?= alunoEsc($turma) ?>">✎</button>

                                                    <button type="button"
                                                        class="acao-icone copiar-email"
                                                        title="Copiar e-mail"
                                                        aria-label="Copiar e-mail de <?= alunoEsc($aluno["nome"]) ?>"
                                                        data-email="<?= alunoEsc($aluno["email"]) ?>">⧉
                                                    </button>

                                                    <a class="botao-secundario"
                                                        href="relatorio_aluno.php?id=<?= (int)$aluno["id"] ?>">
                                                        📄 Relatório escolar
                                                    </a>

                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="alunos-cards" id="cardsView">
                            <?php foreach ($alunos as $aluno): ?>
                                <?php
                                $serie = (string) ($aluno["serie"] ?? "");
                                $turma = strtoupper((string) ($aluno["turma"] ?? ""));
                                $semTurma = $serie === "" || $turma === "";
                                $percentual = max(0, min(100, (float) $aluno["taxa_acertos"]));
                                $foto = trim((string) ($aluno["foto"] ?? ""));
                                ?>
                                <article class="aluno-card"
                                    data-id="<?= (int)$aluno["id"] ?>"
                                    data-nome="<?= alunoEsc(mb_strtolower($aluno["nome"], "UTF-8")) ?>"
                                    data-email="<?= alunoEsc(mb_strtolower($aluno["email"], "UTF-8")) ?>"
                                    data-serie="<?= alunoEsc($serie) ?>"
                                    data-turma="<?= alunoEsc($turma) ?>"
                                    data-sem-turma="<?= $semTurma ? "1" : "0" ?>">

                                    <div class="card-aluno-topo">
                                        <label class="card-selecao">
                                            <input type="checkbox" class="aluno-checkbox" name="alunos[]" value="<?= (int)$aluno["id"] ?>" aria-label="Selecionar <?= alunoEsc($aluno["nome"]) ?>">
                                            <span>Selecionar</span>
                                        </label>

                                        <span class="nivel-badge">Nível <?= (int)$aluno["nivel"] ?></span>
                                    </div>

                                    <div class="card-identidade">
                                        <?php if ($foto !== ""): ?>
                                            <img class="aluno-avatar avatar-grande" src="../<?= alunoEsc(ltrim($foto, "/")) ?>" alt="Foto de <?= alunoEsc($aluno["nome"]) ?>" loading="lazy">
                                        <?php else: ?>
                                            <span class="aluno-avatar avatar-grande avatar-iniciais"><?= alunoEsc(alunoIniciais($aluno["nome"])) ?></span>
                                        <?php endif; ?>

                                        <h3><?= alunoEsc($aluno["nome"]) ?></h3>
                                        <p><?= alunoEsc($aluno["email"]) ?></p>

                                        <?php if (!$semTurma): ?>
                                            <span class="turma-badge"><?= (int)$serie ?>º ano · <?= alunoEsc($turma) ?></span>
                                        <?php else: ?>
                                            <span class="turma-badge badge-pendente">Turma não definida</span>
                                        <?php endif; ?>
                                    </div>

                                    <div class="card-indicadores">
                                        <div>
                                            <span>Taxa de acertos</span>
                                            <strong><?= number_format($percentual, 1, ",", ".") ?>%</strong>
                                            <div class="barra-progresso"><span style="width: <?= $percentual ?>%"></span></div>
                                        </div>
                                        <div>
                                            <span>Experiência</span>
                                            <strong><?= number_format((int)$aluno["xp"], 0, ",", ".") ?> XP</strong>
                                            <small><?= (int)$aluno["partidas"] ?> partidas</small>
                                        </div>
                                    </div>


                                    <div class="card-acoes">
                                        <a class="botao-secundario"
                                            href="mailto:<?= alunoEsc($aluno["email"]) ?>">
                                            ✉ E-mail
                                        </a>

                                        <a class="botao-secundario"
                                            href="relatorio_aluno.php?id=<?= (int)$aluno["id"] ?>">
                                            📄 Relatório escolar
                                        </a>

                                        <button type="button"
                                            class="botao-primario editar-aluno"
                                            data-id="<?= (int)$aluno["id"] ?>"
                                            data-nome="<?= alunoEsc($aluno["nome"]) ?>"
                                            data-email="<?= alunoEsc($aluno["email"]) ?>"
                                            data-serie="<?= alunoEsc($serie) ?>"
                                            data-turma="<?= alunoEsc($turma) ?>">
                                            Editar cadastro
                                        </button>
                                    </div>

                                </article>
                            <?php endforeach; ?>
                        </div>

                    </form>

                    <div class="alunos-empty" id="semResultados" hidden>
                        <span>⌕</span>
                        <strong>Nenhum aluno encontrado</strong>
                        <p>Tente mudar a busca ou os filtros selecionados.</p>
                        <button type="button" id="limparFiltros" class="botao-secundario">Limpar filtros</button>
                    </div>

                </section>

                <footer class="admin-footer">
                    <span>MathRun · Gerenciamento de alunos</span>
                    <span>Aprender também é evoluir.</span>
                </footer>

            </section>
        </main>
    </div>

    <!-- MODAL DE EDIÇÃO -->
    <div class="modal-overlay" id="modalEditar" hidden>
        <section class="modal-editar" role="dialog" aria-modal="true" aria-labelledby="modalTitulo">
            <div class="modal-cabecalho">
                <div>
                    <span class="section-eyebrow">DADOS CADASTRAIS</span>
                    <h2 id="modalTitulo">Editar aluno</h2>
                    <p>Atualize as informações do cadastro.</p>
                </div>
                <button type="button" class="modal-fechar" id="fecharModal" aria-label="Fechar">×</button>
            </div>

            <form method="POST" class="form-editar">
                <input type="hidden" name="acao" value="editar">
                <input type="hidden" name="csrf" value="<?= alunoEsc($csrf) ?>">
                <input type="hidden" name="id" id="editarId">

                <label>
                    Nome completo
                    <input type="text" name="nome" id="editarNome" maxlength="100" required>
                </label>

                <label>
                    E-mail
                    <input type="email" name="email" id="editarEmail" maxlength="150" required>
                </label>

                <div class="form-linha">
                    <label>
                        Série
                        <select name="serie" id="editarSerie">
                            <option value="">Sem série</option>
                            <option value="6">6º ano</option>
                            <option value="7">7º ano</option>
                            <option value="8">8º ano</option>
                            <option value="9">9º ano</option>
                        </select>
                    </label>

                    <label>
                        Turma
                        <select name="turma" id="editarTurma">
                            <option value="">Sem turma</option>
                            <option value="A">Turma A</option>
                            <option value="B">Turma B</option>
                        </select>
                    </label>
                </div>

                <p class="modal-nota">
                    A alteração de série ou turma será aplicada ao cadastro do aluno.
                </p>

                <div class="modal-acoes">
                    <button type="button" class="botao-secundario" id="cancelarModal">Cancelar</button>
                    <button type="submit" class="botao-primario">Salvar alterações</button>
                </div>
            </form>
        </section>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", () => {
            const busca = document.getElementById("buscaAluno");
            const filtroSerie = document.getElementById("filtroSerie");
            const filtroTurma = document.getElementById("filtroTurma");
            const tabelaView = document.getElementById("tabelaView");
            const cardsView = document.getElementById("cardsView");
            const botoesView = document.querySelectorAll("[data-view]");
            const contador = document.getElementById("contadorVisiveis");
            const semResultados = document.getElementById("semResultados");
            const selecionarTodos = document.getElementById("selecionarTodos");
            const selectionBar = document.getElementById("selectionBar");
            const contadorSelecionados = document.getElementById("contadorSelecionados");
            const formExportar = document.getElementById("formExportar");

            const linhas = [...document.querySelectorAll(".aluno-row")];
            const cards = [...document.querySelectorAll(".aluno-card")];
            const checkboxes = [...document.querySelectorAll(".aluno-checkbox")];

            // Preserva os filtros selecionados na URL.
            const params = new URLSearchParams(window.location.search);
            filtroSerie.value = params.get("serie") || "";
            filtroTurma.value = params.get("turma") || "";

            function aplicarFiltros() {
                const termo = busca.value.trim().toLocaleLowerCase("pt-BR");
                const serie = filtroSerie.value;
                const turma = filtroTurma.value;

                let visiveis = 0;

                [...linhas, ...cards].forEach(elemento => {
                    const correspondeBusca =
                        elemento.dataset.nome.includes(termo) ||
                        elemento.dataset.email.includes(termo);

                    const correspondeSerie =
                        serie === "" || elemento.dataset.serie === serie;

                    let correspondeTurma = true;

                    if (turma === "sem") {
                        correspondeTurma = elemento.dataset.semTurma === "1";
                    } else if (turma !== "") {
                        correspondeTurma = elemento.dataset.turma === turma;
                    }

                    const mostrar = correspondeBusca && correspondeSerie && correspondeTurma;

                    elemento.hidden = !mostrar;

                    // Conta somente a tabela, não duplica os cards.
                    if (elemento.classList.contains("aluno-row") && mostrar) {
                        visiveis++;
                    }
                });

                contador.textContent = visiveis;
                semResultados.hidden = visiveis !== 0;

                atualizarSelecao();
            }


            function atualizarSelecao() {
                const visiveis = linhas.filter(linha => !linha.hidden);

                // Cada aluno aparece duas vezes no HTML:
                // uma na tabela e outra nos cards.
                // Por isso, contamos IDs únicos.
                const idsSelecionados = new Set(
                    checkboxes
                    .filter(checkbox => checkbox.checked)
                    .map(checkbox => checkbox.value)
                );

                contadorSelecionados.textContent = idsSelecionados.size;
                selectionBar.classList.toggle("visivel", idsSelecionados.size > 0);

                selecionarTodos.checked =
                    visiveis.length > 0 &&
                    visiveis.every(linha => {
                        const checkbox = linha.querySelector(".aluno-checkbox");
                        return checkbox && idsSelecionados.has(checkbox.value);
                    });

                selecionarTodos.indeterminate =
                    idsSelecionados.size > 0 &&
                    !selecionarTodos.checked;
            }


            function selecionarPorId(id, marcado) {
                checkboxes.forEach(checkbox => {
                    if (checkbox.value === id) {
                        checkbox.checked = marcado;
                    }
                });
            }

            busca.addEventListener("input", aplicarFiltros);
            filtroSerie.addEventListener("change", aplicarFiltros);
            filtroTurma.addEventListener("change", aplicarFiltros);

            checkboxes.forEach(checkbox => {
                checkbox.addEventListener("change", () => {
                    selecionarPorId(checkbox.value, checkbox.checked);
                    atualizarSelecao();
                });
            });

            selecionarTodos.addEventListener("change", () => {
                linhas.forEach(linha => {
                    if (!linha.hidden) {
                        const checkbox = linha.querySelector(".aluno-checkbox");
                        if (checkbox) {
                            selecionarPorId(checkbox.value, selecionarTodos.checked);
                        }
                    }
                });

                atualizarSelecao();
            });

            document.getElementById("limparSelecao").addEventListener("click", () => {
                checkboxes.forEach(checkbox => checkbox.checked = false);
                atualizarSelecao();
            });

            document.getElementById("limparFiltros").addEventListener("click", () => {
                busca.value = "";
                filtroSerie.value = "";
                filtroTurma.value = "";
                aplicarFiltros();
            });

            botoesView.forEach(botao => {
                botao.addEventListener("click", () => {
                    const cardsAtivos = botao.dataset.view === "cards";

                    tabelaView.hidden = cardsAtivos;
                    cardsView.classList.toggle("ativo", cardsAtivos);

                    botoesView.forEach(item => {
                        item.classList.toggle("ativo", item === botao);
                    });
                });
            });

            // A seleção é sincronizada entre tabela e cards.
            cardsView.addEventListener("change", evento => {
                if (evento.target.classList.contains("aluno-checkbox")) {
                    selecionarPorId(evento.target.value, evento.target.checked);
                    atualizarSelecao();
                }
            });

            // Impede exportação vazia.
            formExportar.addEventListener("submit", evento => {
                const selecionados = checkboxes.filter(c => c.checked);

                if (selecionados.length === 0) {
                    evento.preventDefault();
                    alert("Selecione pelo menos um aluno para exportar.");
                }
            });

            // Copiar e-mail.
            document.querySelectorAll(".copiar-email").forEach(botao => {
                botao.addEventListener("click", async () => {
                    try {
                        await navigator.clipboard.writeText(botao.dataset.email);
                        const textoOriginal = botao.textContent;
                        botao.textContent = "✓";

                        setTimeout(() => {
                            botao.textContent = textoOriginal;
                        }, 1200);
                    } catch (erro) {
                        alert("Não foi possível copiar automaticamente. E-mail: " + botao.dataset.email);
                    }
                });
            });

            // Modal de edição.
            const modal = document.getElementById("modalEditar");
            const editarId = document.getElementById("editarId");
            const editarNome = document.getElementById("editarNome");
            const editarEmail = document.getElementById("editarEmail");
            const editarSerie = document.getElementById("editarSerie");
            const editarTurma = document.getElementById("editarTurma");

            function abrirModal(botao) {
                editarId.value = botao.dataset.id;
                editarNome.value = botao.dataset.nome;
                editarEmail.value = botao.dataset.email;
                editarSerie.value = botao.dataset.serie;
                editarTurma.value = botao.dataset.turma;

                modal.hidden = false;
                document.body.classList.add("modal-aberto");
                editarNome.focus();
            }

            function fecharModal() {
                modal.hidden = true;
                document.body.classList.remove("modal-aberto");
            }

            document.querySelectorAll(".editar-aluno").forEach(botao => {
                botao.addEventListener("click", () => abrirModal(botao));
            });

            document.getElementById("fecharModal").addEventListener("click", fecharModal);
            document.getElementById("cancelarModal").addEventListener("click", fecharModal);

            modal.addEventListener("click", evento => {
                if (evento.target === modal) fecharModal();
            });

            document.addEventListener("keydown", evento => {
                if (evento.key === "Escape" && !modal.hidden) fecharModal();

                if ((evento.metaKey || evento.ctrlKey) && evento.key.toLowerCase() === "k") {
                    evento.preventDefault();
                    busca.focus();
                }
            });

            // Mantém filtros da página sincronizados com as abas rápidas.
            document.querySelectorAll(".turma-chip").forEach(chip => {
                chip.addEventListener("click", evento => {
                    const url = new URL(chip.href);

                    filtroSerie.value = url.searchParams.get("serie") || "";
                    filtroTurma.value = url.searchParams.get("turma") || "";

                    // A navegação por abas também aplica o filtro no servidor.
                });
            });

            aplicarFiltros();
        });
    </script>

    <script src="js/admin-layout.js" defer></script>
    <?php require_once __DIR__ . '/../includes/acessibilidade.php'; ?>
</body>

</html>