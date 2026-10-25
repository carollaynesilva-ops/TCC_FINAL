<?php

declare(strict_types=1);

session_start();

if (($_SESSION['admin_autenticado'] ?? false) !== true) {
    http_response_code(403);
    exit('Acesso negado. Entre com sua conta administrativa para acessar esta página.');
}

require_once __DIR__ . '/../config/config.php';

if (!isset($pdo) || !($pdo instanceof PDO)) {
    http_response_code(500);
    exit('A conexão PDO não está disponível em config/config.php.');
}

$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

if (empty($_SESSION['csrf_admin'])) {
    $_SESSION['csrf_admin'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_admin'];

function h($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function redirecionarFases(string $mensagem, string $tipo = 'sucesso'): never
{
    header('Location: fases.php?' . http_build_query([$tipo => $mensagem]));
    exit;
}

$jogos = $pdo->query('SELECT id, nome FROM jogos ORDER BY nome')->fetchAll();
$seriesValidas = [6, 7, 8, 9];
$dificuldadesValidas = ['facil', 'medio', 'dificil'];

$filtroJogo = filter_input(INPUT_GET, 'jogo_id', FILTER_VALIDATE_INT) ?: 0;
$filtroSerie = filter_input(INPUT_GET, 'serie', FILTER_VALIDATE_INT) ?: 0;
$editarId = filter_input(INPUT_GET, 'editar', FILTER_VALIDATE_INT) ?: 0;
$faseEdicao = null;

if ($editarId > 0) {
    $stmt = $pdo->prepare('SELECT * FROM fases WHERE id = :id');
    $stmt->execute(['id' => $editarId]);
    $faseEdicao = $stmt->fetch() ?: null;
    if (!$faseEdicao) {
        redirecionarFases('A fase selecionada não foi encontrada.', 'erro');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = (string)($_POST['csrf_token'] ?? '');
    if (!hash_equals($csrf, $token)) {
        http_response_code(419);
        exit('Sessão do formulário expirada. Atualize a página e tente novamente.');
    }

    $acao = (string)($_POST['acao'] ?? '');

    try {
        if ($acao === 'salvar') {
            $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT) ?: 0;
            $jogoId = filter_input(INPUT_POST, 'jogo_id', FILTER_VALIDATE_INT) ?: 0;
            $serie = filter_input(INPUT_POST, 'serie', FILTER_VALIDATE_INT) ?: 0;
            $nome = trim((string)($_POST['nome'] ?? ''));
            $descricao = trim((string)($_POST['descricao'] ?? ''));
            $dificuldade = (string)($_POST['nivel_dificuldade'] ?? '');
            $numero = filter_input(INPUT_POST, 'numero', FILTER_VALIDATE_INT);

            if ($jogoId < 1 || !in_array($jogoId, array_map('intval', array_column($jogos, 'id')), true)) {
                throw new RuntimeException('Selecione um jogo válido.');
            }
            if (!in_array($serie, $seriesValidas, true)) {
                throw new RuntimeException('Selecione uma série entre 6º e 9º ano.');
            }
            if ($nome === '' || mb_strlen($nome) > 100) {
                throw new RuntimeException('Informe o nome da fase (até 100 caracteres).');
            }
            if (!in_array($dificuldade, $dificuldadesValidas, true)) {
                throw new RuntimeException('Selecione uma dificuldade válida.');
            }
            if (!$numero || $numero < 1) {
                throw new RuntimeException('O número da fase deve ser um inteiro maior que zero.');
            }

            // A combinação jogo + série + número identifica a posição da fase.
            $duplicada = $pdo->prepare(
                'SELECT id FROM fases WHERE jogo_id = :jogo_id AND serie = :serie AND numero = :numero AND id <> :id LIMIT 1'
            );
            $duplicada->execute([
                'jogo_id' => $jogoId,
                'serie' => $serie,
                'numero' => $numero,
                'id' => $id
            ]);
            if ($duplicada->fetch()) {
                throw new RuntimeException('Já existe uma fase com esse número para esse jogo e essa série.');
            }

            if ($id > 0) {
                $stmt = $pdo->prepare(
                    'UPDATE fases SET jogo_id = :jogo_id, serie = :serie, nome = :nome, descricao = :descricao,
                     nivel_dificuldade = :dificuldade, numero = :numero WHERE id = :id'
                );
                $stmt->execute([
                    'jogo_id' => $jogoId,
                    'serie' => $serie,
                    'nome' => $nome,
                    'descricao' => $descricao,
                    'dificuldade' => $dificuldade,
                    'numero' => $numero,
                    'id' => $id
                ]);
                redirecionarFases('Fase atualizada com sucesso.');
            }

            $stmt = $pdo->prepare(
                'INSERT INTO fases (jogo_id, serie, nome, descricao, nivel_dificuldade, numero)
                 VALUES (:jogo_id, :serie, :nome, :descricao, :dificuldade, :numero)'
            );
            $stmt->execute([
                'jogo_id' => $jogoId,
                'serie' => $serie,
                'nome' => $nome,
                'descricao' => $descricao,
                'dificuldade' => $dificuldade,
                'numero' => $numero
            ]);
            redirecionarFases('Fase cadastrada com sucesso.');
        }

        if ($acao === 'excluir') {
            $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT) ?: 0;
            if ($id < 1) {
                throw new RuntimeException('Fase inválida.');
            }

            // O schema pode apagar progresso/histórico em cascata. Para preservar registros,
            // bloqueamos a exclusão se a fase já tiver sido usada por alunos.
            $usos = $pdo->prepare(
                'SELECT
                    (SELECT COUNT(*) FROM progresso_usuario WHERE fase_id = :fase_progresso) +
                    (SELECT COUNT(*) FROM historico_partidas WHERE fase_id = :fase_historico) AS total'
            );
            $usos->execute(['fase_progresso' => $id, 'fase_historico' => $id]);
            if ((int)$usos->fetchColumn() > 0) {
                throw new RuntimeException('Esta fase possui registros de progresso ou partidas. Ela não foi excluída para preservar o histórico dos alunos.');
            }

            $stmt = $pdo->prepare('DELETE FROM fases WHERE id = :id');
            $stmt->execute(['id' => $id]);
            redirecionarFases('Fase excluída. As questões, alternativas e dicas vinculadas também são removidas pelo banco.');
        }

        throw new RuntimeException('Ação não reconhecida.');
    } catch (Throwable $e) {
        redirecionarFases($e instanceof PDOException ? 'Não foi possível salvar a fase. Confira os dados e tente novamente.' : $e->getMessage(), 'erro');
    }
}

$sql = 'SELECT f.*, j.nome AS jogo_nome,
        (SELECT COUNT(*) FROM questoes q WHERE q.fase_id = f.id) AS total_questoes
        FROM fases f INNER JOIN jogos j ON j.id = f.jogo_id WHERE 1=1';
$params = [];
if ($filtroJogo > 0) {
    $sql .= ' AND f.jogo_id = :jogo';
    $params['jogo'] = $filtroJogo;
}
if (in_array($filtroSerie, $seriesValidas, true)) {
    $sql .= ' AND f.serie = :serie';
    $params['serie'] = $filtroSerie;
}
$sql .= ' ORDER BY j.nome, f.serie, f.numero, f.id';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$fases = $stmt->fetchAll();

$totalFases = (int)$pdo->query('SELECT COUNT(*) FROM fases')->fetchColumn();
$totalQuestoes = (int)$pdo->query('SELECT COUNT(*) FROM questoes')->fetchColumn();

$mensagem = (string)($_GET['sucesso'] ?? '');
$erro = (string)($_GET['erro'] ?? '');
$form = $faseEdicao ?: [
    'id' => 0,
    'jogo_id' => $filtroJogo ?: ($jogos[0]['id'] ?? ''),
    'serie' => $filtroSerie ?: 6,
    'nome' => '',
    'descricao' => '',
    'nivel_dificuldade' => 'facil',
    'numero' => ''
];
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar fases | MathRun</title>
    <link rel="stylesheet" href="css/admin.css">
    <link rel="stylesheet" href="css/admin-layout.css">
    <link rel="stylesheet" href="css/fases.css">
    <?php require_once __DIR__ . '/../includes/acessibilidade-head.php'; ?>
</head>

<body class="admin-page admin-fases-page">
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
                <a href="fases.php" class="is-active"><span class="nav-symbol">▤</span> Fases</a>
                <a href="questoes.php" class=""><span class="nav-symbol">∑</span> Questões</a>
                <a href="desempenho.php"><span class="nav-symbol">↗</span> Desempenho</a>
            </nav>
            <span class="topnav-status"><i></i> Painel</span>
        </header>

        <main class="admin-main">
            <header class="page-topbar">
                <div><span class="eyebrow">CONTEÚDO DO JOGO</span>
                    <h1>Gerenciar fases</h1>
                    <p>Organize as fases do MathChef e do MathSpace por série escolar.</p>
                </div>
                <a class="btn btn-secondary" href="admin.php">Voltar ao painel</a>
            </header>

            <?php if ($mensagem !== ''): ?><div class="alert alert-success" role="status"><?= h($mensagem) ?></div><?php endif; ?>
            <?php if ($erro !== ''): ?><div class="alert alert-error" role="alert"><?= h($erro) ?></div><?php endif; ?>

            <section class="stats-grid">
                <article class="stat-card"><span class="stat-icon purple">▤</span>
                    <div><span>Total de fases</span><strong><?= $totalFases ?></strong></div>
                </article>
                <article class="stat-card"><span class="stat-icon blue">☷</span>
                    <div><span>Total de questões</span><strong><?= $totalQuestoes ?></strong></div>
                </article>
                <article class="stat-card"><span class="stat-icon pink">◎</span>
                    <div><span>Fases exibidas</span><strong><?= count($fases) ?></strong></div>
                </article>
            </section>

            <section class="admin-panel">
                <div class="panel-heading">
                    <div>
                        <h2><?= $faseEdicao ? 'Editar fase' : 'Cadastrar fase' ?></h2>
                        <p>Defina o jogo, a série, a dificuldade e a ordem da fase.</p>
                    </div>
                    <?php if ($faseEdicao): ?><a class="text-link" href="fases.php">Cancelar edição</a><?php endif; ?>
                </div>
                <form method="post" class="admin-form" id="fase-form">
                    <input type="hidden" name="csrf_token" value="<?= h($csrf) ?>">
                    <input type="hidden" name="acao" value="salvar">
                    <input type="hidden" name="id" value="<?= (int)$form['id'] ?>">
                    <div class="form-grid">
                        <label>Jogo
                            <select name="jogo_id" required>
                                <option value="">Selecione um jogo</option>
                                <?php foreach ($jogos as $jogo): ?>
                                    <option value="<?= (int)$jogo['id'] ?>" <?= (int)$form['jogo_id'] === (int)$jogo['id'] ? 'selected' : '' ?>><?= h($jogo['nome']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label>Série escolar
                            <select name="serie" required>
                                <?php foreach ($seriesValidas as $serie): ?>
                                    <option value="<?= $serie ?>" <?= (int)$form['serie'] === $serie ? 'selected' : '' ?>><?= $serie ?>º ano</option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label class="field-wide">Nome da fase
                            <input type="text" name="nome" maxlength="100" required value="<?= h($form['nome']) ?>" placeholder="Ex.: Cozinha Básica">
                        </label>
                        <label>Número da fase
                            <input type="number" name="numero" min="1" step="1" required value="<?= h($form['numero']) ?>" placeholder="Ex.: 1">
                        </label>
                        <label>Dificuldade
                            <select name="nivel_dificuldade" required>
                                <option value="facil" <?= $form['nivel_dificuldade'] === 'facil' ? 'selected' : '' ?>>Fácil</option>
                                <option value="medio" <?= $form['nivel_dificuldade'] === 'medio' ? 'selected' : '' ?>>Médio</option>
                                <option value="dificil" <?= $form['nivel_dificuldade'] === 'dificil' ? 'selected' : '' ?>>Difícil</option>
                            </select>
                        </label>
                        <label class="field-wide">Descrição
                            <textarea name="descricao" rows="3" placeholder="Explique brevemente o objetivo da fase."><?= h($form['descricao']) ?></textarea>
                        </label>
                    </div>
                    <div class="form-actions"><button class="btn btn-primary" type="submit"><?= $faseEdicao ? 'Salvar alterações' : 'Cadastrar fase' ?></button><?php if ($faseEdicao): ?><a class="btn btn-secondary" href="fases.php">Cancelar</a><?php endif; ?></div>
                </form>
            </section>

            <section class="admin-panel">
                <div class="panel-heading">
                    <div>
                        <h2>Fases cadastradas</h2>
                        <p>Use os filtros para encontrar uma fase específica.</p>
                    </div><span class="result-count"><?= count($fases) ?> resultado(s)</span>
                </div>
                <form method="get" class="filter-bar" id="fases-filtros">
                    <label>Jogo
                        <select name="jogo_id">
                            <option value="">Todos os jogos</option>
                            <?php foreach ($jogos as $jogo): ?><option value="<?= (int)$jogo['id'] ?>" <?= $filtroJogo === (int)$jogo['id'] ? 'selected' : '' ?>><?= h($jogo['nome']) ?></option><?php endforeach; ?>
                        </select>
                    </label>
                    <label>Série
                        <select name="serie">
                            <option value="">Todas as séries</option>
                            <?php foreach ($seriesValidas as $serie): ?><option value="<?= $serie ?>" <?= $filtroSerie === $serie ? 'selected' : '' ?>><?= $serie ?>º ano</option><?php endforeach; ?>
                        </select>
                    </label>
                    <button class="btn btn-secondary" type="submit">Filtrar</button>
                    <a class="btn btn-ghost" href="fases.php">Limpar</a>
                </form>
                <div class="table-wrap">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Fase</th>
                                <th>Jogo</th>
                                <th>Série</th>
                                <th>Dificuldade</th>
                                <th>Questões</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!$fases): ?><tr>
                                    <td colspan="6" class="empty-state">Nenhuma fase encontrada com esses filtros.</td>
                                </tr><?php endif; ?>
                            <?php foreach ($fases as $fase): ?>
                                <tr>
                                    <td>
                                        <div class="table-primary"><span class="phase-number"><?= (int)$fase['numero'] ?></span>
                                            <div><strong><?= h($fase['nome']) ?></strong><small><?= h($fase['descricao'] ?: 'Sem descrição') ?></small></div>
                                        </div>
                                    </td>
                                    <td><?= h($fase['jogo_nome']) ?></td>
                                    <td><span class="grade-pill"><?= (int)$fase['serie'] ?>º ano</span></td>
                                    <td><span class="difficulty difficulty-<?= h($fase['nivel_dificuldade']) ?>"><?= ['facil' => 'Fácil', 'medio' => 'Médio', 'dificil' => 'Difícil'][$fase['nivel_dificuldade']] ?? h($fase['nivel_dificuldade']) ?></span></td>
                                    <td><?= (int)$fase['total_questoes'] ?></td>
                                    <td>
                                        <div class="row-actions"><a class="icon-btn" title="Editar fase" aria-label="Editar fase" href="fases.php?editar=<?= (int)$fase['id'] ?>">✎</a><a class="icon-btn" title="Gerenciar questões desta fase" aria-label="Gerenciar questões desta fase" href="questoes.php?fase_id=<?= (int)$fase['id'] ?>">☷</a>
                                            <form method="post" class="inline-form js-delete-form" data-confirm="Excluir esta fase? As questões, alternativas e dicas vinculadas serão removidas. Fases com histórico de alunos não podem ser excluídas.">
                                                <input type="hidden" name="csrf_token" value="<?= h($csrf) ?>"><input type="hidden" name="acao" value="excluir"><input type="hidden" name="id" value="<?= (int)$fase['id'] ?>">
                                                <button class="icon-btn danger" type="submit" title="Excluir fase" aria-label="Excluir fase">⌫</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
            <footer class="admin-footer">MathRun · Área administrativa</footer>
        </main>
    </div>
    <script src="js/admin-layout.js" defer></script>
    <script src="js/fases.js" defer></script>
    <?php require_once __DIR__ . '/../includes/acessibilidade.php'; ?>
</body>

</html>