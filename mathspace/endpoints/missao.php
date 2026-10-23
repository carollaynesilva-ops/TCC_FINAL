<?php
// API do MathSpace. Toda validação de resposta acontece no servidor.
if (session_status() !== PHP_SESSION_ACTIVE)
    session_start();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
function out(array $data, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST')
    out(['ok' => false, 'message' => 'Método não permitido.'], 405);
$body = json_decode(file_get_contents('php://input'), true) ?: [];
$csrf = (string) ($body['csrf'] ?? '');
if (empty($_SESSION['mathspace_csrf']) || !hash_equals($_SESSION['mathspace_csrf'], $csrf))
    out(['ok' => false, 'message' => 'Sua sessão expirou. Recarregue a missão.'], 419);
$userId = (int) ($_SESSION['usuario_id'] ?? $_SESSION['id_usuario'] ?? $_SESSION['user_id'] ?? $_SESSION['id'] ?? 0);
if ($userId < 1)
    out(['ok' => false, 'message' => 'Faça login novamente para continuar.'], 401);
$configPath = __DIR__ . '/../../config/config.php';
if (!is_file($configPath))
    out(['ok' => false, 'message' => 'Não encontrei config/config.php.'], 500);
require_once $configPath;
$db = null;
foreach (['pdo', 'conn', 'conexao', 'conexaoPDO', 'mysqli'] as $candidate)
    if (isset($$candidate) && ($$candidate instanceof PDO || $$candidate instanceof mysqli)) {
        $db = $$candidate;
        break;
    }
if (!$db)
    out(['ok' => false, 'message' => 'A conexão PDO/MySQLi não foi encontrada no config.'], 500);
function queryOne($db, string $sql, array $params = []): array|false
{
    if ($db instanceof PDO) {
        $s = $db->prepare($sql);
        $s->execute($params);
        return $s->fetch(PDO::FETCH_ASSOC);
    }
    $s = $db->prepare($sql);
    if (!$s)
        throw new RuntimeException($db->error);
    if ($params) {
        $types = '';
        $vals = [];
        foreach ($params as $p) {
            $types .= is_int($p) ? 'i' : (is_float($p) ? 'd' : 's');
            $vals[] = $p;
        }
        $s->bind_param($types, ...$vals);
    }
    $s->execute();
    $r = $s->get_result()->fetch_assoc();
    $s->close();
    return $r ?: false;
}
function queryAll($db, string $sql, array $params = []): array
{
    if ($db instanceof PDO) {
        $s = $db->prepare($sql);
        $s->execute($params);
        return $s->fetchAll(PDO::FETCH_ASSOC);
    }
    $s = $db->prepare($sql);
    if (!$s)
        throw new RuntimeException($db->error);
    if ($params) {
        $types = '';
        $vals = [];
        foreach ($params as $p) {
            $types .= is_int($p) ? 'i' : (is_float($p) ? 'd' : 's');
            $vals[] = $p;
        }
        $s->bind_param($types, ...$vals);
    }
    $s->execute();
    $r = $s->get_result();
    $rows = $r->fetch_all(MYSQLI_ASSOC);
    $s->close();
    return $rows;
}
function executeSql($db, string $sql, array $params = []): int
{
    if ($db instanceof PDO) {
        $s = $db->prepare($sql);
        $s->execute($params);
        return (int) $db->lastInsertId();
    }
    $s = $db->prepare($sql);
    if (!$s)
        throw new RuntimeException($db->error);
    if ($params) {
        $types = '';
        $vals = [];
        foreach ($params as $p) {
            $types .= is_int($p) ? 'i' : (is_float($p) ? 'd' : 's');
            $vals[] = $p;
        }
        $s->bind_param($types, ...$vals);
    }
    $s->execute();
    $id = (int) $db->insert_id;
    $s->close();
    return $id;
}
function runSql($db, string $sql, array $params = []): void
{
    if ($db instanceof PDO) {
        $s = $db->prepare($sql);
        $s->execute($params);
        return;
    }
    $s = $db->prepare($sql);
    if (!$s)
        throw new RuntimeException($db->error);
    if ($params) {
        $types = '';
        $vals = [];
        foreach ($params as $p) {
            $types .= is_int($p) ? 'i' : (is_float($p) ? 'd' : 's');
            $vals[] = $p;
        }
        $s->bind_param($types, ...$vals);
    }
    $s->execute();
    $s->close();
}
$action = (string) ($body['action'] ?? '');
try {
    if ($action === 'start') {
        $phaseId = (int) ($body['fase'] ?? 0);
        $phase = queryOne($db, 'SELECT f.id,f.jogo_id,f.nome,f.numero,f.serie,f.nivel_dificuldade FROM fases f JOIN jogos j ON j.id=f.jogo_id WHERE f.id=? AND j.nome="MathSpace" LIMIT 1', [$phaseId]);
        if (!$phase)
            out(['ok' => false, 'message' => 'Fase do MathSpace não encontrada.'], 404);
        $student = queryOne($db, 'SELECT serie FROM usuarios WHERE id=? LIMIT 1', [$userId]);
        if ($student && !empty($student['serie']) && (int) $student['serie'] !== (int) $phase['serie'])
            out(['ok' => false, 'message' => 'Esta missão não corresponde à sua série escolar.'], 403);
        if ((int) $phase['numero'] > 1) {
            $previous = queryOne($db, 'SELECT id FROM fases WHERE jogo_id=? AND serie=? AND numero<? ORDER BY numero DESC LIMIT 1', [(int) $phase['jogo_id'], (int) $phase['serie'], (int) $phase['numero']]);
            if ($previous) {
                $progress = queryOne($db, 'SELECT concluida FROM progresso_usuario WHERE usuario_id=? AND fase_id=?', [$userId, (int) $previous['id']]);
                if (!$progress || !(int) $progress['concluida'])
                    out(['ok' => false, 'message' => 'Conclua a missão anterior para desbloquear esta fase.'], 403);
            }
        }
        $questions = queryAll($db, 'SELECT id,pergunta,explicacao,pontuacao FROM questoes WHERE fase_id=? ORDER BY id ASC LIMIT 6', [$phaseId]);
        if (count($questions) < 6)
            out(['ok' => false, 'message' => 'Esta fase ainda não tem 6 questões cadastradas (5 questões + 1 desafio especial). Cadastre as perguntas e alternativas no banco antes de jogar.'], 422);
        $gameId = queryOne($db, 'SELECT id FROM jogos WHERE nome="MathSpace" LIMIT 1');
        $partidaId = executeSql($db, 'INSERT INTO historico_partidas (usuario_id,jogo_id,fase_id,pontuacao,acertos,erros,dicas_usadas) VALUES (?,?,?,0,0,0,0)', [$userId, (int) $gameId['id'], $phaseId]);
        $_SESSION['mathspace_run'] = ['user' => $userId, 'phase' => $phaseId, 'partida' => $partidaId, 'question_index' => 0, 'lives' => 3, 'integrity' => 100, 'score' => 0, 'correct' => 0, 'wrong' => 0, 'answers' => [], 'started_at' => time(), 'finished' => false];
        $q = $questions[0];
        $alternatives = queryAll($db, 'SELECT id,texto FROM alternativas WHERE questao_id=? ORDER BY id ASC', [(int) $q['id']]);
        if (count($alternatives) < 2)
            out(['ok' => false, 'message' => 'Uma questão desta fase não possui alternativas suficientes.'], 422);
        out(['ok' => true, 'phase' => $phase, 'total' => 6, 'question' => ['id' => (int) $q['id'], 'pergunta' => $q['pergunta'], 'alternativas' => array_map(fn($a) => ['id' => (int) $a['id'], 'texto' => $a['texto']], $alternatives)], 'lives' => 3, 'score' => 0]);
    }
    $run = $_SESSION['mathspace_run'] ?? null;
    if (!$run || (int) $run['user'] !== $userId)
        out(['ok' => false, 'message' => 'Nenhuma partida ativa. Inicie a missão novamente.'], 409);
    if ($action === 'damage') {
        if (!empty($run['finished']))
            out(['ok' => false, 'message' => 'Esta partida já terminou.'], 409);
        // O servidor controla a integridade para evitar perdas duplicadas ou estados inconsistentes no navegador.
        $integrity = max(0, (int) ($run['integrity'] ?? 100) - 20);
        $lives = (int) ($run['lives'] ?? 3);
        if ($integrity <= 0) {
            $integrity = 100;
            $lives = max(0, $lives - 1);
        }
        $run['integrity'] = $integrity;
        $run['lives'] = $lives;
        $_SESSION['mathspace_run'] = $run;
        out(['ok' => true, 'integrity' => $integrity, 'lives' => $lives, 'gameOver' => $lives <= 0]);
    }
    if ($action === 'question') {
        $idx = (int) $run['question_index'];
        if ($idx >= 6)
            out(['ok' => false, 'message' => 'Todas as questões já foram respondidas.'], 409);
        $q = queryOne($db, 'SELECT id,pergunta,explicacao,pontuacao FROM questoes WHERE fase_id=? ORDER BY id ASC LIMIT 1 OFFSET ' . max(0, $idx), [(int) $run['phase']]);
        if (!$q)
            out(['ok' => false, 'message' => 'Questão não encontrada.'], 404);
        $alts = queryAll($db, 'SELECT id,texto FROM alternativas WHERE questao_id=? ORDER BY id ASC', [(int) $q['id']]);
        out(['ok' => true, 'index' => $idx, 'total' => 6, 'question' => ['id' => (int) $q['id'], 'pergunta' => $q['pergunta'], 'alternativas' => array_map(fn($a) => ['id' => (int) $a['id'], 'texto' => $a['texto']], $alts)]]);
    }
    if ($action === 'answer') {
        if (!empty($run['finished']) || (int) ($run['lives'] ?? 0) <= 0)
            out(['ok' => false, 'message' => 'Esta partida já terminou.'], 409);
        $questionId = (int) ($body['questao'] ?? 0);
        $timedOut = !empty($body['tempo_esgotado']);
        $alternativeId = (int) ($body['alternativa'] ?? 0);
        $q = queryOne($db, 'SELECT id,pergunta,resposta_correta,explicacao,pontuacao FROM questoes WHERE id=? AND fase_id=?', [$questionId, (int) $run['phase']]);
        if (!$q)
            out(['ok' => false, 'message' => 'Questão inválida para esta missão.'], 400);
        if (in_array($questionId, $run['answers'], true))
            out(['ok' => false, 'message' => 'Esta questão já foi respondida.'], 409);
        $alt = false;
        if (!$timedOut) {
            $alt = queryOne($db, 'SELECT id,texto,correta FROM alternativas WHERE id=? AND questao_id=?', [$alternativeId, $questionId]);
            if (!$alt)
                out(['ok' => false, 'message' => 'Alternativa inválida.'], 400);
        }
        $correct = !$timedOut && ((int) $alt['correta'] === 1 || trim((string) $alt['texto']) === trim((string) $q['resposta_correta']));
        $points = $correct ? (int) $q['pontuacao'] : 0;
        if ($correct) {
            $run['score'] += $points;
            $run['correct']++;
        } else {
            $run['wrong']++;
            // A vida é descontada no servidor uma única vez, tanto no erro quanto no tempo esgotado.
            $run['lives'] = max(0, (int) $run['lives'] - 1);
        }
        $run['answers'][] = $questionId;
        $run['question_index']++;
        $answerText = $timedOut ? 'Tempo esgotado' : (string) $alt['texto'];
        runSql($db, 'INSERT INTO respostas_usuario (usuario_id,questao_id,partida_id,resposta,correta,tempo_resposta,usou_dica) VALUES (?,?,?,?,?,?,0)', [$userId, $questionId, (int) $run['partida'], $answerText, $correct ? 1 : 0, max(0, time() - (int) $run['started_at'])]);
        $_SESSION['mathspace_run'] = $run;
        out(['ok' => true, 'correct' => $correct, 'points' => $points, 'score' => (int) $run['score'], 'lives' => (int) $run['lives'], 'correctCount' => (int) $run['correct'], 'index' => (int) $run['question_index'], 'explanation' => $q['explicacao'], 'finished' => ((int) $run['question_index'] >= 6 || (int) $run['lives'] <= 0)]);
    }
    if ($action === 'finish') {
        if (!empty($run['finished']))
            out(['ok' => true, 'result' => $run['result'] ?? []]);
        $success = (int) $run['lives'] > 0 && (int) $run['question_index'] >= 6;
        $score = (int) $run['score'];
        $correct = (int) $run['correct'];
        $wrong = (int) $run['wrong'];
        $xp = $success ? $correct * 20 : 0;
        runSql($db, 'UPDATE historico_partidas SET pontuacao=?,acertos=?,erros=? WHERE id=? AND usuario_id=?', [$score, $correct, $wrong, (int) $run['partida'], $userId]);
        $existing = queryOne($db, 'SELECT id,melhor_pontuacao,xp_conquistado FROM progresso_usuario WHERE usuario_id=? AND fase_id=?', [$userId, (int) $run['phase']]);
        if ($existing) {
            runSql($db, 'UPDATE progresso_usuario SET tentativas=tentativas+1,pontuacao=?,melhor_pontuacao=GREATEST(melhor_pontuacao,?),xp_conquistado=GREATEST(xp_conquistado,?),concluida=IF(concluida=1 OR ?=1,1,0),data_conclusao=IF(?=1 AND data_conclusao IS NULL,NOW(),data_conclusao) WHERE id=?', [$score, $score, $xp, $success ? 1 : 0, $success ? 1 : 0, (int) $existing['id']]);
        } else {
            runSql($db, 'INSERT INTO progresso_usuario (usuario_id,fase_id,concluida,pontuacao,tentativas,melhor_pontuacao,xp_conquistado,data_conclusao) VALUES (?,?,?, ?,1,?,?,?)', [$userId, (int) $run['phase'], $success ? 1 : 0, $score, $score, $xp, $success ? date('Y-m-d H:i:s') : null]);
        }
        if ($success)
            runSql($db, 'UPDATE usuarios SET xp=COALESCE(xp,0)+?,pontuacao_total=COALESCE(pontuacao_total,0)+? WHERE id=?', [$xp, $score, $userId]);

        // Concede a medalha Explorador Espacial na primeira missão concluída.
        // O nome é consultado no banco, sem depender de um ID fixo.
        $medalAwarded = null;
        if ($success) {
            $phaseGame = queryOne($db, 'SELECT jogo_id FROM fases WHERE id=? LIMIT 1', [(int) $run['phase']]);
            if ($phaseGame) {
                $completed = queryOne($db, 'SELECT COUNT(*) AS total FROM progresso_usuario pu JOIN fases f ON f.id=pu.fase_id WHERE pu.usuario_id=? AND pu.concluida=1 AND f.jogo_id=?', [$userId, (int) $phaseGame['jogo_id']]);
                if ($completed && (int) $completed['total'] === 1) {
                    $medal = queryOne($db, 'SELECT id,nome FROM medalhas WHERE nome=? LIMIT 1', ['Explorador Espacial']);
                    if ($medal) {
                        $owned = queryOne($db, 'SELECT id FROM usuario_medalhas WHERE usuario_id=? AND medalha_id=? LIMIT 1', [$userId, (int) $medal['id']]);
                        if (!$owned) {
                            runSql($db, 'INSERT INTO usuario_medalhas (usuario_id,medalha_id) VALUES (?,?)', [$userId, (int) $medal['id']]);
                            $medalAwarded = (string) $medal['nome'];
                        }
                    }
                }
            }
        }
        $result = ['success' => $success, 'score' => $score, 'correct' => $correct, 'wrong' => $wrong, 'xp' => $xp, 'lives' => (int) $run['lives'], 'medal' => $medalAwarded];
        $run['finished'] = true;
        $run['result'] = $result;
        $_SESSION['mathspace_run'] = $run;
        out(['ok' => true, 'result' => $result]);
    }
    out(['ok' => false, 'message' => 'Ação desconhecida.'], 400);
} catch (Throwable $e) {
    error_log('MathSpace API: ' . $e->getMessage());
    out(['ok' => false, 'message' => 'Ocorreu um erro ao processar a missão. Verifique a conexão e a estrutura do banco.'], 500);
}
