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

function redirecionarQuestoes(string $mensagem, string $tipo = 'sucesso', array $filtros = []): never
{
    header('Location: questoes.php?' . http_build_query(array_merge([$tipo => $mensagem], $filtros)));
    exit;
}

$seriesValidas = [6, 7, 8, 9];
$jogos = $pdo->query('SELECT id, nome FROM jogos ORDER BY nome')->fetchAll();
$filtroJogo = filter_input(INPUT_GET, 'jogo_id', FILTER_VALIDATE_INT) ?: 0;
$filtroSerie = filter_input(INPUT_GET, 'serie', FILTER_VALIDATE_INT) ?: 0;
$filtroFase = filter_input(INPUT_GET, 'fase_id', FILTER_VALIDATE_INT) ?: 0;
$editarId = filter_input(INPUT_GET, 'editar', FILTER_VALIDATE_INT) ?: 0;

$fases = $pdo->query(
    'SELECT f.id, f.jogo_id, f.serie, f.nome, f.numero, j.nome AS jogo_nome
     FROM fases f INNER JOIN jogos j ON j.id = f.jogo_id
     ORDER BY j.nome, f.serie, f.numero'
)->fetchAll();

$faseEdicao = null;
$alternativasEdicao = [];
$dicasEdicao = [];
if ($editarId > 0) {
    $stmt = $pdo->prepare(
        'SELECT q.*, f.nome AS fase_nome, f.serie, f.jogo_id, f.numero AS fase_numero
         FROM questoes q INNER JOIN fases f ON f.id = q.fase_id WHERE q.id = :id'
    );
    $stmt->execute(['id' => $editarId]);
    $faseEdicao = $stmt->fetch() ?: null;
    if (!$faseEdicao) {
        redirecionarQuestoes('A questão selecionada não foi encontrada.', 'erro');
    }
    $stmt = $pdo->prepare('SELECT texto, correta FROM alternativas WHERE questao_id = :id ORDER BY id');
    $stmt->execute(['id' => $editarId]);
    $alternativasEdicao = $stmt->fetchAll();
    $stmt = $pdo->prepare('SELECT texto, custo_xp FROM dicas WHERE questao_id = :id ORDER BY ordem, id');
    $stmt->execute(['id' => $editarId]);
    $dicasEdicao = $stmt->fetchAll();
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
            $faseId = filter_input(INPUT_POST, 'fase_id', FILTER_VALIDATE_INT) ?: 0;
            $materia = trim((string)($_POST['materia'] ?? ''));
            $pergunta = trim((string)($_POST['pergunta'] ?? ''));
            $explicacao = trim((string)($_POST['explicacao'] ?? ''));
            $pontuacao = filter_input(INPUT_POST, 'pontuacao', FILTER_VALIDATE_INT);
            $opcoes = $_POST['alternativas'] ?? [];
            $indiceCorreta = filter_input(INPUT_POST, 'alternativa_correta', FILTER_VALIDATE_INT);
            $textosDicas = $_POST['dicas'] ?? [];
            $custosDicas = $_POST['custos_dicas'] ?? [];

            $faseValida = $pdo->prepare('SELECT id FROM fases WHERE id = :id');
            $faseValida->execute(['id' => $faseId]);
            if (!$faseValida->fetch()) {
                throw new RuntimeException('Selecione uma fase válida.');
            }
            if ($materia === '' || mb_strlen($materia) > 100) {
                throw new RuntimeException('Informe a matéria ou o assunto da questão (até 100 caracteres).');
            }
            if ($pergunta === '') {
                throw new RuntimeException('O enunciado da questão é obrigatório.');
            }
            if ($explicacao === '') {
                throw new RuntimeException('A explicação da resposta é obrigatória.');
            }
            if ($pontuacao === false || $pontuacao === null || $pontuacao < 0 || $pontuacao > 100000) {
                throw new RuntimeException('A pontuação deve ser um número entre 0 e 100000.');
            }
            if (!is_array($opcoes)) {
                throw new RuntimeException('As alternativas enviadas são inválidas.');
            }
            $alternativasValidas = [];
            foreach ($opcoes as $opcao) {
                $texto = trim((string)$opcao);
                if ($texto !== '') {
                    $alternativasValidas[] = $texto;
                }
            }
            if (count($alternativasValidas) < 2) {
                throw new RuntimeException('Cadastre pelo menos duas alternativas.');
            }
            if ($indiceCorreta === false || $indiceCorreta === null || !isset($opcoes[$indiceCorreta]) || trim((string)$opcoes[$indiceCorreta]) === '') {
                throw new RuntimeException('Marque qual alternativa é a correta.');
            }
            $respostaCorreta = trim((string)$opcoes[$indiceCorreta]);
            if (mb_strlen($respostaCorreta) > 255) {
                throw new RuntimeException('Cada alternativa deve ter no máximo 255 caracteres.');
            }
            foreach ($alternativasValidas as $texto) {
                if (mb_strlen($texto) > 255) {
                    throw new RuntimeException('Cada alternativa deve ter no máximo 255 caracteres.');
                }
            }

            $dicasValidas = [];
            if (is_array($textosDicas)) {
                foreach ($textosDicas as $i => $textoDica) {
                    $textoDica = trim((string)$textoDica);
                    if ($textoDica === '') {
                        continue;
                    }
                    $custo = isset($custosDicas[$i]) && is_numeric($custosDicas[$i]) ? (int)$custosDicas[$i] : 0;
                    if ($custo < 0 || $custo > 100000) {
                        throw new RuntimeException('O custo de cada dica deve ficar entre 0 e 100000 XP.');
                    }
                    $dicasValidas[] = ['texto' => $textoDica, 'custo_xp' => $custo];
                }
            }

            $pdo->beginTransaction();
            if ($id > 0) {
                $stmt = $pdo->prepare(
                    'UPDATE questoes SET fase_id = :fase_id, materia = :materia, pergunta = :pergunta,
                     resposta_correta = :resposta, explicacao = :explicacao, pontuacao = :pontuacao WHERE id = :id'
                );
                $stmt->execute([
                    'fase_id' => $faseId,
                    'materia' => $materia,
                    'pergunta' => $pergunta,
                    'resposta' => $respostaCorreta,
                    'explicacao' => $explicacao,
                    'pontuacao' => $pontuacao,
                    'id' => $id
                ]);
                $questaoId = $id;
                $stmt = $pdo->prepare('DELETE FROM alternativas WHERE questao_id = :id');
                $stmt->execute(['id' => $questaoId]);
                $stmt = $pdo->prepare('DELETE FROM dicas WHERE questao_id = :id');
                $stmt->execute(['id' => $questaoId]);
            } else {
                $stmt = $pdo->prepare(
                    'INSERT INTO questoes (fase_id, materia, pergunta, resposta_correta, explicacao, pontuacao)
                     VALUES (:fase_id, :materia, :pergunta, :resposta, :explicacao, :pontuacao)'
                );
                $stmt->execute([
                    'fase_id' => $faseId,
                    'materia' => $materia,
                    'pergunta' => $pergunta,
                    'resposta' => $respostaCorreta,
                    'explicacao' => $explicacao,
                    'pontuacao' => $pontuacao
                ]);
                $questaoId = (int)$pdo->lastInsertId();
            }

            $stmtAlternativa = $pdo->prepare(
                'INSERT INTO alternativas (questao_id, texto, correta) VALUES (:questao_id, :texto, :correta)'
            );
            foreach ($opcoes as $indice => $opcao) {
                $texto = trim((string)$opcao);
                if ($texto === '') {
                    continue;
                }
                $stmtAlternativa->execute([
                    'questao_id' => $questaoId,
                    'texto' => $texto,
                    'correta' => ((string)$indice === (string)$indiceCorreta) ? 1 : 0
                ]);
            }

            $stmtDica = $pdo->prepare(
                'INSERT INTO dicas (questao_id, ordem, texto, custo_xp) VALUES (:questao_id, :ordem, :texto, :custo_xp)'
            );
            foreach ($dicasValidas as $i => $dica) {
                $stmtDica->execute([
                    'questao_id' => $questaoId,
                    'ordem' => $i + 1,
                    'texto' => $dica['texto'],
                    'custo_xp' => $dica['custo_xp']
                ]);
            }
            $pdo->commit();
            redirecionarQuestoes($id > 0 ? 'Questão atualizada com sucesso.' : 'Questão cadastrada com sucesso.');
        }

        if ($acao === 'excluir') {
            $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT) ?: 0;
            if ($id < 1) {
                throw new RuntimeException('Questão inválida.');
            }
            $stmt = $pdo->prepare('DELETE FROM questoes WHERE id = :id');
            $stmt->execute(['id' => $id]);
            redirecionarQuestoes('Questão excluída. As alternativas e dicas vinculadas também foram removidas.');
        }

        throw new RuntimeException('Ação não reconhecida.');
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        redirecionarQuestoes($e instanceof PDOException ? 'Não foi possível salvar a questão. Confira os dados e tente novamente.' : $e->getMessage(), 'erro');
    }
}

$sql = 'SELECT q.id, q.materia, q.pergunta, q.pontuacao, f.id AS fase_id, f.nome AS fase_nome,
        f.numero AS fase_numero, f.serie, j.nome AS jogo_nome,
        (SELECT COUNT(*) FROM alternativas a WHERE a.questao_id = q.id) AS total_alternativas,
        (SELECT COUNT(*) FROM dicas d WHERE d.questao_id = q.id) AS total_dicas
        FROM questoes q INNER JOIN fases f ON f.id = q.fase_id INNER JOIN jogos j ON j.id = f.jogo_id
        WHERE 1=1';
$params = [];
if ($filtroJogo > 0) {
    $sql .= ' AND f.jogo_id = :jogo';
    $params['jogo'] = $filtroJogo;
}
if (in_array($filtroSerie, $seriesValidas, true)) {
    $sql .= ' AND f.serie = :serie';
    $params['serie'] = $filtroSerie;
}
if ($filtroFase > 0) {
    $sql .= ' AND f.id = :fase';
    $params['fase'] = $filtroFase;
}
$sql .= ' ORDER BY j.nome, f.serie, f.numero, q.id DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$questoes = $stmt->fetchAll();

$totalQuestoes = (int)$pdo->query('SELECT COUNT(*) FROM questoes')->fetchColumn();
$totalAlternativas = (int)$pdo->query('SELECT COUNT(*) FROM alternativas')->fetchColumn();
$totalDicas = (int)$pdo->query('SELECT COUNT(*) FROM dicas')->fetchColumn();

$mensagem = (string)($_GET['sucesso'] ?? '');
$erro = (string)($_GET['erro'] ?? '');

$form = $faseEdicao ?: [
    'id' => 0,
    'fase_id' => $filtroFase ?: '',
    'materia' => '',
    'pergunta' => '',
    'resposta_correta' => '',
    'explicacao' => '',
    'pontuacao' => 100
];
if (!$faseEdicao) {
    $alternativasEdicao = [['texto' => '', 'correta' => 1], ['texto' => '', 'correta' => 0], ['texto' => '', 'correta' => 0], ['texto' => '', 'correta' => 0]];
    $dicasEdicao = [['texto' => '', 'custo_xp' => 0]];
}
$indiceCorretaAtual = 0;
foreach ($alternativasEdicao as $i => $alt) {
    if (!empty($alt['correta'])) {
        $indiceCorretaAtual = $i;
        break;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar questões | MathRun</title>
    <link rel="stylesheet" href="css/admin.css">
    <link rel="stylesheet" href="css/admin-layout.css">
    <link rel="stylesheet" href="css/questoes.css">
</head>

<body class="admin-page admin-questoes-page">
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
                <a href="fases.php" class=""><span class="nav-symbol">▤</span> Fases</a>
                <a href="questoes.php" class="is-active"><span class="nav-symbol">∑</span> Questões</a>
                <a href="desempenho.php"><span class="nav-symbol">↗</span> Desempenho</a>
            </nav>
            <span class="topnav-status"><i></i> Painel</span>
        </header>

        <main class="admin-main">
            <header class="page-topbar">
                <div><span class="eyebrow">CONTEÚDO DO JOGO</span>
                    <h1>Gerenciar questões</h1>
                    <p>Crie desafios, alternativas e dicas para cada fase.</p>
                </div>
                <a class="btn btn-secondary" href="fases.php">Gerenciar fases</a>
            </header>

            <?php if ($mensagem !== ''): ?><div class="alert alert-success" role="status"><?= h($mensagem) ?></div><?php endif; ?>
            <?php if ($erro !== ''): ?><div class="alert alert-error" role="alert"><?= h($erro) ?></div><?php endif; ?>

            <section class="stats-grid">
                <article class="stat-card"><span class="stat-icon purple">☷</span>
                    <div><span>Total de questões</span><strong><?= $totalQuestoes ?></strong></div>
                </article>
                <article class="stat-card"><span class="stat-icon blue">☑</span>
                    <div><span>Alternativas cadastradas</span><strong><?= $totalAlternativas ?></strong></div>
                </article>
                <article class="stat-card"><span class="stat-icon pink">✦</span>
                    <div><span>Dicas cadastradas</span><strong><?= $totalDicas ?></strong></div>
                </article>
            </section>

            <section class="admin-panel">
                <div class="panel-heading">
                    <div>
                        <h2><?= $faseEdicao ? 'Editar questão' : 'Cadastrar questão' ?></h2>
                        <p>Marque uma única alternativa como correta. As dicas são opcionais.</p>
                    </div>
                    <?php if ($faseEdicao): ?><a class="text-link" href="questoes.php">Cancelar edição</a><?php endif; ?>
                </div>
                <?php if (!$fases): ?>
                    <div class="notice-box">Você ainda não cadastrou fases. <a href="fases.php">Cadastre uma fase primeiro</a> para vincular questões a ela.</div>
                <?php else: ?>
                    <form method="post" class="admin-form" id="questao-form">
                        <input type="hidden" name="csrf_token" value="<?= h($csrf) ?>">
                        <input type="hidden" name="acao" value="salvar">
                        <input type="hidden" name="id" value="<?= (int)$form['id'] ?>">
                        <div class="form-grid">
                            <label class="field-wide">Fase
                                <select name="fase_id" required>
                                    <option value="">Selecione a fase</option>
                                    <?php foreach ($fases as $fase): ?>
                                        <option value="<?= (int)$fase['id'] ?>" <?= (int)$form['fase_id'] === (int)$fase['id'] ? 'selected' : '' ?>>
                                            <?= h($fase['jogo_nome']) ?> · <?= (int)$fase['serie'] ?>º ano · Fase <?= (int)$fase['numero'] ?>: <?= h($fase['nome']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                            <label>Matéria / assunto
                                <input type="text" name="materia" maxlength="100" required value="<?= h($form['materia']) ?>" placeholder="Ex.: Frações">
                            </label>
                            <label>Pontuação (XP)
                                <input type="number" name="pontuacao" min="0" max="100000" required value="<?= h($form['pontuacao']) ?>">
                            </label>
                            <label class="field-wide">Enunciado da questão
                                <textarea name="pergunta" rows="3" required placeholder="Escreva o desafio que o aluno deverá resolver."><?= h($form['pergunta']) ?></textarea>
                            </label>
                            <label class="field-wide">Explicação da resposta
                                <textarea name="explicacao" rows="3" required placeholder="Explique como chegar à resposta correta."><?= h($form['explicacao']) ?></textarea>
                            </label>
                        </div>

                        <div class="subsection-heading">
                            <div>
                                <h3>Alternativas</h3>
                                <p>Adicione pelo menos duas opções e selecione a correta.</p>
                            </div><button class="btn btn-secondary btn-small" type="button" id="adicionar-alternativa">+ Adicionar alternativa</button>
                        </div>
                        <div id="alternativas-lista" class="dynamic-list">
                            <?php foreach ($alternativasEdicao as $i => $alternativa): ?>
                                <div class="dynamic-row alternative-row">
                                    <label class="correct-choice"><input type="radio" name="alternativa_correta" value="<?= $i ?>" <?= $i === $indiceCorretaAtual ? 'checked' : '' ?> required><span>Correta</span></label>
                                    <input type="text" name="alternativas[]" maxlength="255" value="<?= h($alternativa['texto']) ?>" placeholder="Texto da alternativa" aria-label="Texto da alternativa <?= $i + 1 ?>">
                                    <button class="remove-row" type="button" aria-label="Remover alternativa" title="Remover alternativa">×</button>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="subsection-heading tips-heading">
                            <div>
                                <h3>Dicas progressivas</h3>
                                <p>Opcional. A ordem será definida automaticamente de cima para baixo.</p>
                            </div><button class="btn btn-secondary btn-small" type="button" id="adicionar-dica">+ Adicionar dica</button>
                        </div>
                        <div id="dicas-lista" class="dynamic-list">
                            <?php foreach ($dicasEdicao as $i => $dica): ?>
                                <div class="dynamic-row tip-row">
                                    <span class="tip-order"><?= $i + 1 ?></span>
                                    <input type="text" name="dicas[]" value="<?= h($dica['texto']) ?>" placeholder="Texto da dica">
                                    <label class="cost-field"><span>XP</span><input type="number" name="custos_dicas[]" min="0" max="100000" value="<?= h($dica['custo_xp']) ?>"></label>
                                    <button class="remove-row" type="button" aria-label="Remover dica" title="Remover dica">×</button>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="form-actions"><button class="btn btn-primary" type="submit"><?= $faseEdicao ? 'Salvar alterações' : 'Cadastrar questão' ?></button><?php if ($faseEdicao): ?><a class="btn btn-secondary" href="questoes.php">Cancelar</a><?php endif; ?></div>
                    </form>
                <?php endif; ?>
            </section>

            <section class="admin-panel">
                <div class="panel-heading">
                    <div>
                        <h2>Questões cadastradas</h2>
                        <p>Filtre por jogo, série ou fase para encontrar um desafio.</p>
                    </div><span class="result-count"><?= count($questoes) ?> resultado(s)</span>
                </div>
                <form method="get" class="filter-bar" id="questoes-filtros">
                    <label>Jogo
                        <select name="jogo_id" id="filtro-jogo">
                            <option value="">Todos os jogos</option><?php foreach ($jogos as $jogo): ?><option value="<?= (int)$jogo['id'] ?>" <?= $filtroJogo === (int)$jogo['id'] ? 'selected' : '' ?>><?= h($jogo['nome']) ?></option><?php endforeach; ?>
                        </select>
                    </label>
                    <label>Série
                        <select name="serie" id="filtro-serie">
                            <option value="">Todas as séries</option><?php foreach ($seriesValidas as $serie): ?><option value="<?= $serie ?>" <?= $filtroSerie === $serie ? 'selected' : '' ?>><?= $serie ?>º ano</option><?php endforeach; ?>
                        </select>
                    </label>
                    <label>Fase
                        <select name="fase_id" id="filtro-fase">
                            <option value="">Todas as fases</option><?php foreach ($fases as $fase): ?><option data-jogo="<?= (int)$fase['jogo_id'] ?>" data-serie="<?= (int)$fase['serie'] ?>" value="<?= (int)$fase['id'] ?>" <?= $filtroFase === (int)$fase['id'] ? 'selected' : '' ?>><?= h($fase['jogo_nome']) ?> · <?= (int)$fase['serie'] ?>º · <?= (int)$fase['numero'] ?> - <?= h($fase['nome']) ?></option><?php endforeach; ?>
                        </select>
                    </label>
                    <button class="btn btn-secondary" type="submit">Filtrar</button><a class="btn btn-ghost" href="questoes.php">Limpar</a>
                </form>
                <div class="table-wrap">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Questão</th>
                                <th>Jogo / série</th>
                                <th>Fase</th>
                                <th>Conteúdo</th>
                                <th>XP</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!$questoes): ?><tr>
                                    <td colspan="6" class="empty-state">Nenhuma questão encontrada com esses filtros.</td>
                                </tr><?php endif; ?>
                            <?php foreach ($questoes as $questao): ?>
                                <tr>
                                    <td>
                                        <div class="question-title"><span class="question-mark">?</span>
                                            <div><strong><?= h(mb_strimwidth($questao['pergunta'], 0, 95, '…', 'UTF-8')) ?></strong><small><?= h($questao['materia']) ?></small></div>
                                        </div>
                                    </td>
                                    <td><?= h($questao['jogo_nome']) ?><br><span class="muted"><?= (int)$questao['serie'] ?>º ano</span></td>
                                    <td>Fase <?= (int)$questao['fase_numero'] ?><br><span class="muted"><?= h($questao['fase_nome']) ?></span></td>
                                    <td><span class="mini-count"><?= (int)$questao['total_alternativas'] ?> alternativas</span><span class="mini-count"><?= (int)$questao['total_dicas'] ?> dicas</span></td>
                                    <td><strong><?= (int)$questao['pontuacao'] ?> XP</strong></td>
                                    <td>
                                        <div class="row-actions"><a class="icon-btn" title="Editar questão" aria-label="Editar questão" href="questoes.php?editar=<?= (int)$questao['id'] ?>">✎</a>
                                            <form method="post" class="inline-form js-delete-form" data-confirm="Excluir esta questão? Todas as alternativas e dicas vinculadas também serão removidas.">
                                                <input type="hidden" name="csrf_token" value="<?= h($csrf) ?>"><input type="hidden" name="acao" value="excluir"><input type="hidden" name="id" value="<?= (int)$questao['id'] ?>">
                                                <button class="icon-btn danger" type="submit" title="Excluir questão" aria-label="Excluir questão">⌫</button>
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
    <script src="js/questoes.js" defer></script>
</body>

</html>