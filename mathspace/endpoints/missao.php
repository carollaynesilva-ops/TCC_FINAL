
<?php

declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function responder(array $dados, int $status = 200): never
{
    http_response_code($status);

    echo json_encode(
        $dados,
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );

    exit;
}

function exigirMetodoPost(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        responder([
            'ok' => false,
            'message' => 'Método não permitido.'
        ], 405);
    }
}

function consultarUm(
    PDO $db,
    string $sql,
    array $params = []
): array|false {
    $stmt = $db->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function consultarTodos(
    PDO $db,
    string $sql,
    array $params = []
): array {
    $stmt = $db->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function executar(
    PDO $db,
    string $sql,
    array $params = []
): int {
    $stmt = $db->prepare($sql);
    $stmt->execute($params);

    return $stmt->rowCount();
}

/**
 * Busca as seis questões da fase por ordem de ID.
 */
function obterQuestao(
    PDO $db,
    int $faseId,
    int $indice
): array|false {
    if ($indice < 0 || $indice >= 6) {
        return false;
    }

    $questoes = consultarTodos(
        $db,
        'SELECT id, pergunta, explicacao, pontuacao
         FROM questoes
         WHERE fase_id = ?
         ORDER BY id ASC
         LIMIT 6',
        [$faseId]
    );

    if (!isset($questoes[$indice])) {
        return false;
    }

    return $questoes[$indice];
}

/**
 * Monta a questão que será enviada ao JavaScript.
 */
function montarQuestao(PDO $db, array $questao): array
{
    $alternativas = consultarTodos(
        $db,
        'SELECT id, texto
         FROM alternativas
         WHERE questao_id = ?
         ORDER BY id ASC',
        [(int) $questao['id']]
    );

    return [
        'id' => (int) $questao['id'],
        'pergunta' => $questao['pergunta'],
        'alternativas' => array_map(
            static fn(array $alternativa): array => [
                'id' => (int) $alternativa['id'],
                'texto' => $alternativa['texto']
            ],
            $alternativas
        )
    ];
}

/**
 * Obtém o identificador da questão enviado pelo JavaScript.
 * Aceita diferentes nomes para manter compatibilidade.
 */
function obterIdQuestaoEnviado(array $body): int
{
    $valor = $body['questao_id']
        ?? $body['question_id']
        ?? $body['questionId']
        ?? $body['questaoId']
        ?? $body['questao']
        ?? $body['question']
        ?? 0;

    return filter_var($valor, FILTER_VALIDATE_INT) ?: 0;
}

/**
 * Obtém o identificador da alternativa enviado pelo JavaScript.
 */
function obterIdAlternativaEnviado(array $body): int
{
    $valor = $body['alternativa_id']
        ?? $body['alternative_id']
        ?? $body['alternativeId']
        ?? $body['alternativaId']
        ?? $body['alternativa']
        ?? $body['alternative']
        ?? 0;

    return filter_var($valor, FILTER_VALIDATE_INT) ?: 0;
}

/**
 * Registra uma resposta e avança o índice da partida.
 */
function registrarResposta(
    PDO $db,
    int $usuarioId,
    array &$partida,
    int $questaoId,
    string $resposta,
    bool $correta
): void {
    executar(
        $db,
        'INSERT INTO respostas_usuario
            (usuario_id, questao_id, partida_id, resposta,
             correta, tempo_resposta, usou_dica)
         VALUES (?, ?, ?, ?, ?, ?, 0)',
        [
            $usuarioId,
            $questaoId,
            (int) $partida['partida'],
            $resposta,
            $correta ? 1 : 0,
            max(
                0,
                time() - (int) ($partida['question_started_at'] ?? time())
            )
        ]
    );

    $partida['answers'][] = $questaoId;

    if ($correta) {
        $partida['correct']++;
    } else {
        $partida['wrong']++;
    }

    $partida['question_index']++;

    // A próxima questão será definida quando for solicitada.
    $partida['current_question_id'] = null;
}

/**
 * Confere se a questão enviada é a questão atualmente entregue
 * pelo servidor. Usa o ID guardado na sessão.
 */
function validarQuestaoAtual(
    PDO $db,
    array $partida,
    int $questaoIdEnviado
): array|false {
    $questaoAtualId = (int) (
        $partida['current_question_id'] ?? 0
    );

    if ($questaoAtualId < 1 || $questaoIdEnviado < 1) {
        return false;
    }

    if ($questaoAtualId !== $questaoIdEnviado) {
        return false;
    }

    $questao = consultarUm(
        $db,
        'SELECT id, pergunta, explicacao, pontuacao
         FROM questoes
         WHERE id = ? AND fase_id = ?
         LIMIT 1',
        [
            $questaoAtualId,
            (int) $partida['phase']
        ]
    );

    if (!$questao) {
        return false;
    }

    return $questao;
}

// ---------------------------------------------------------
// 1. VALIDAÇÃO DA REQUISIÇÃO
// ---------------------------------------------------------

exigirMetodoPost();

$body = json_decode(
    file_get_contents('php://input'),
    true
);

if (!is_array($body)) {
    responder([
        'ok' => false,
        'message' => 'Requisição inválida.'
    ], 400);
}

$csrf = (string) ($body['csrf'] ?? '');

if (
    empty($_SESSION['mathspace_csrf']) ||
    !hash_equals(
        (string) $_SESSION['mathspace_csrf'],
        $csrf
    )
) {
    responder([
        'ok' => false,
        'message' => 'Sua sessão expirou. Recarregue a missão.'
    ], 419);
}

$usuarioId = (int) (
    $_SESSION['usuario_id']
    ?? $_SESSION['id_usuario']
    ?? $_SESSION['user_id']
    ?? $_SESSION['id']
    ?? 0
);

if ($usuarioId < 1) {
    responder([
        'ok' => false,
        'message' => 'Faça login novamente para continuar.'
    ], 401);
}

// ---------------------------------------------------------
// 2. CONEXÃO COM O BANCO
// ---------------------------------------------------------

$configPath = __DIR__ . '/../../config/config.php';

if (!is_file($configPath)) {
    responder([
        'ok' => false,
        'message' => 'Arquivo config.php não encontrado.'
    ], 500);
}

require_once $configPath;

$db = null;

foreach (['pdo', 'conn', 'conexao', 'conexaoPDO'] as $candidate) {
    if (isset($$candidate) && $$candidate instanceof PDO) {
        $db = $$candidate;
        break;
    }
}

if (!$db instanceof PDO) {
    responder([
        'ok' => false,
        'message' => 'A conexão PDO não foi encontrada no config.php.'
    ], 500);
}

$db->setAttribute(
    PDO::ATTR_ERRMODE,
    PDO::ERRMODE_EXCEPTION
);

$db->setAttribute(
    PDO::ATTR_DEFAULT_FETCH_MODE,
    PDO::FETCH_ASSOC
);

$acao = (string) ($body['action'] ?? '');

try {

    // =====================================================
    // 3. INICIAR A MISSÃO
    // =====================================================

    if ($acao === 'start') {
        $faseInformada = $body['fase']
            ?? $body['fase_id']
            ?? $body['faseId']
            ?? null;

        $faseId = filter_var(
            $faseInformada,
            FILTER_VALIDATE_INT
        );

        if (
            $faseId === false ||
            $faseId === null ||
            $faseId < 1
        ) {
            responder([
                'ok' => false,
                'message' => 'Identificador de fase inválido.'
            ], 400);
        }

        // Descarta uma missão anterior que ficou presa na sessão.
        // Isso não apaga o histórico que já foi registrado no banco.
        $partidaAtual = $_SESSION['mathspace_run'] ?? null;

        if (
            is_array($partidaAtual) &&
            (int) ($partidaAtual['user'] ?? 0) === $usuarioId
        ) {
            unset($_SESSION['mathspace_run']);
        }

        $fase = consultarUm(
            $db,
            'SELECT f.id, f.jogo_id, f.nome, f.numero,
                    f.serie, f.nivel_dificuldade
             FROM fases f
             INNER JOIN jogos j ON j.id = f.jogo_id
             WHERE f.id = ?
               AND j.nome = ?
             LIMIT 1',
            [$faseId, 'MathSpace']
        );

        if (!$fase) {
            responder([
                'ok' => false,
                'message' => 'Fase do MathSpace não encontrada.'
            ], 404);
        }

        // Confere a série do estudante, se a coluna existir.
        $colunasUsuario = consultarUm(
            $db,
            "SELECT COUNT(*) AS existe
             FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'usuarios'
               AND COLUMN_NAME = 'serie'"
        );

        if ((int) ($colunasUsuario['existe'] ?? 0) > 0) {
            $aluno = consultarUm(
                $db,
                'SELECT serie
                 FROM usuarios
                 WHERE id = ?
                 LIMIT 1',
                [$usuarioId]
            );

            if (
                $aluno &&
                !empty($aluno['serie']) &&
                (int) $aluno['serie'] !== (int) $fase['serie']
            ) {
                responder([
                    'ok' => false,
                    'message' => 'Esta missão não corresponde à sua série escolar.'
                ], 403);
            }
        }

        // A fase anterior precisa estar concluída.
        if ((int) $fase['numero'] > 1) {
            $anterior = consultarUm(
                $db,
                'SELECT id
                 FROM fases
                 WHERE jogo_id = ?
                   AND serie = ?
                   AND numero < ?
                 ORDER BY numero DESC
                 LIMIT 1',
                [
                    (int) $fase['jogo_id'],
                    (int) $fase['serie'],
                    (int) $fase['numero']
                ]
            );

            if ($anterior) {
                $progressoAnterior = consultarUm(
                    $db,
                    'SELECT concluida
                     FROM progresso_usuario
                     WHERE usuario_id = ?
                       AND fase_id = ?
                     LIMIT 1',
                    [
                        $usuarioId,
                        (int) $anterior['id']
                    ]
                );

                if (
                    !$progressoAnterior ||
                    !(int) $progressoAnterior['concluida']
                ) {
                    responder([
                        'ok' => false,
                        'message' => 'Conclua a missão anterior para desbloquear esta fase.'
                    ], 403);
                }
            }
        }

        // Verifica se a fase possui seis questões.
        $questoes = consultarTodos(
            $db,
            'SELECT id
             FROM questoes
             WHERE fase_id = ?
             ORDER BY id ASC
             LIMIT 6',
            [$faseId]
        );

        if (count($questoes) < 6) {
            responder([
                'ok' => false,
                'message' => 'Esta fase precisa ter 6 questões cadastradas.'
            ], 422);
        }

        // Cada questão precisa ter pelo menos duas alternativas.
        foreach ($questoes as $questao) {
            $quantidade = consultarUm(
                $db,
                'SELECT COUNT(*) AS total
                 FROM alternativas
                 WHERE questao_id = ?',
                [(int) $questao['id']]
            );

            if ((int) ($quantidade['total'] ?? 0) < 2) {
                responder([
                    'ok' => false,
                    'message' => 'Uma das questões não possui alternativas suficientes.'
                ], 422);
            }
        }

        // Busca o ID do jogo.
        $jogo = consultarUm(
            $db,
            'SELECT id
             FROM jogos
             WHERE nome = ?
             LIMIT 1',
            ['MathSpace']
        );

        if (!$jogo) {
            responder([
                'ok' => false,
                'message' => 'Jogo MathSpace não encontrado no banco.'
            ], 404);
        }

        // Cria o registro da partida.
        $db->beginTransaction();

        try {
            executar(
                $db,
                'INSERT INTO historico_partidas
                    (usuario_id, jogo_id, fase_id,
                     pontuacao, acertos, erros, dicas_usadas)
                 VALUES (?, ?, ?, 0, 0, 0, 0)',
                [
                    $usuarioId,
                    (int) $jogo['id'],
                    $faseId
                ]
            );

            $partidaId = (int) $db->lastInsertId();

            $db->commit();
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }

            throw $e;
        }

        $primeiraQuestao = obterQuestao(
            $db,
            $faseId,
            0
        );

        if (!$primeiraQuestao) {
            responder([
                'ok' => false,
                'message' => 'Não foi possível carregar a primeira questão.'
            ], 500);
        }

        // Guarda o ID exato da questão enviada ao jogo.
        $_SESSION['mathspace_run'] = [
            'user' => $usuarioId,
            'phase' => $faseId,
            'partida' => $partidaId,

            'question_index' => 0,
            'current_question_id' => (int) $primeiraQuestao['id'],
            'question_started_at' => time(),

            'lives' => 3,
            'shield' => 100,

            'score' => 0,
            'correct' => 0,
            'wrong' => 0,

            'answers' => [],
            'started_at' => time(),

            'finished' => false,
            'result' => null
        ];

        responder([
            'ok' => true,
            'phase' => $fase,
            'total' => 6,
            'question' => montarQuestao($db, $primeiraQuestao),
            'lives' => 3,
            'shield' => 100,
            'score' => 0
        ]);
    }

    // =====================================================
    // 4. VALIDAR A PARTIDA ATIVA
    // =====================================================

    $partida = $_SESSION['mathspace_run'] ?? null;

    if (
        !is_array($partida) ||
        (int) ($partida['user'] ?? 0) !== $usuarioId
    ) {
        responder([
            'ok' => false,
            'message' => 'Nenhuma partida ativa. Inicie a missão novamente.'
        ], 409);
    }

    // Compatibilidade com uma sessão criada pela versão anterior.
    if (!array_key_exists('current_question_id', $partida)) {
        $questaoAtual = obterQuestao(
            $db,
            (int) $partida['phase'],
            (int) $partida['question_index']
        );

        $partida['current_question_id'] = $questaoAtual
            ? (int) $questaoAtual['id']
            : null;

        $_SESSION['mathspace_run'] = $partida;
    }

    if (
        !empty($partida['finished']) &&
        $acao !== 'finish'
    ) {
        responder([
            'ok' => false,
            'message' => 'Esta partida já terminou.'
        ], 409);
    }

    // =====================================================
    // 5. BUSCAR A QUESTÃO ATUAL
    // =====================================================

    if ($acao === 'question') {
        if (
            (int) $partida['lives'] <= 0 ||
            (int) $partida['question_index'] >= 6
        ) {
            responder([
                'ok' => false,
                'message' => 'Não há mais questões disponíveis nesta partida.'
            ], 409);
        }

        $questao = obterQuestao(
            $db,
            (int) $partida['phase'],
            (int) $partida['question_index']
        );

        if (!$questao) {
            responder([
                'ok' => false,
                'message' => 'Questão não encontrada.'
            ], 404);
        }

        // Atualiza a questão atual antes de enviá-la ao navegador.
        $partida['current_question_id'] = (int) $questao['id'];
        $partida['question_started_at'] = time();

        $_SESSION['mathspace_run'] = $partida;

        responder([
            'ok' => true,
            'index' => (int) $partida['question_index'],
            'total' => 6,
            'question' => montarQuestao($db, $questao)
        ]);
    }

    // =====================================================
    // 6. RESPONDER UMA QUESTÃO
    // =====================================================

    if ($acao === 'answer') {
        $questaoId = obterIdQuestaoEnviado($body);
        $alternativaId = obterIdAlternativaEnviado($body);

        $questao = validarQuestaoAtual(
            $db,
            $partida,
            $questaoId
        );

        if (!$questao) {
            error_log(
                'MathSpace: questão divergente. ' .
                    'Usuário=' . $usuarioId .
                    ', questão enviada=' . $questaoId .
                    ', questão atual=' .
                    (int) ($partida['current_question_id'] ?? 0) .
                    ', índice=' .
                    (int) ($partida['question_index'] ?? -1)
            );

            responder([
                'ok' => false,
                'message' => 'A questão enviada não corresponde à questão atual. Atualize a missão e tente novamente.'
            ], 409);
        }

        if (
            in_array(
                $questaoId,
                $partida['answers'] ?? [],
                true
            )
        ) {
            responder([
                'ok' => false,
                'message' => 'Esta questão já foi respondida.'
            ], 409);
        }

        $alternativa = consultarUm(
            $db,
            'SELECT id, texto, correta
             FROM alternativas
             WHERE id = ?
               AND questao_id = ?
             LIMIT 1',
            [
                $alternativaId,
                $questaoId
            ]
        );

        if (!$alternativa) {
            responder([
                'ok' => false,
                'message' => 'Alternativa inválida para esta questão.'
            ], 400);
        }

        $questaoCompleta = consultarUm(
            $db,
            'SELECT resposta_correta
             FROM questoes
             WHERE id = ?
             LIMIT 1',
            [$questaoId]
        );

        $respostaCorreta =
            (int) $alternativa['correta'] === 1 ||
            (
                $questaoCompleta &&
                trim((string) $alternativa['texto']) ===
                trim((string) ($questaoCompleta['resposta_correta'] ?? ''))
            );

        $pontos = $respostaCorreta
            ? (int) $questao['pontuacao']
            : 0;

        $db->beginTransaction();

        try {
            registrarResposta(
                $db,
                $usuarioId,
                $partida,
                $questaoId,
                (string) $alternativa['texto'],
                $respostaCorreta
            );

            if ($respostaCorreta) {
                $partida['score'] += $pontos;
            } else {
                $partida['lives'] = max(
                    0,
                    (int) $partida['lives'] - 1
                );
            }

            $db->commit();
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }

            throw $e;
        }

        $_SESSION['mathspace_run'] = $partida;

        responder([
            'ok' => true,
            'correct' => $respostaCorreta,
            'points' => $pontos,
            'score' => (int) $partida['score'],
            'lives' => (int) $partida['lives'],
            'shield' => (int) $partida['shield'],
            'correctCount' => (int) $partida['correct'],
            'index' => (int) $partida['question_index'],
            'explanation' => $questao['explicacao'],
            'finished' => (
                (int) $partida['question_index'] >= 6 ||
                (int) $partida['lives'] <= 0
            )
        ]);
    }

    // =====================================================
    // 7. TEMPO ESGOTADO
    // =====================================================

    if ($acao === 'timeout') {
        $questaoId = obterIdQuestaoEnviado($body);

        $questao = validarQuestaoAtual(
            $db,
            $partida,
            $questaoId
        );

        if (!$questao) {
            responder([
                'ok' => false,
                'message' => 'A questão do temporizador não corresponde à questão atual.'
            ], 409);
        }

        if (
            in_array(
                $questaoId,
                $partida['answers'] ?? [],
                true
            )
        ) {
            responder([
                'ok' => false,
                'message' => 'Esta questão já foi registrada.'
            ], 409);
        }

        $db->beginTransaction();

        try {
            registrarResposta(
                $db,
                $usuarioId,
                $partida,
                $questaoId,
                'TEMPO ESGOTADO',
                false
            );

            $partida['lives'] = max(
                0,
                (int) $partida['lives'] - 1
            );

            $db->commit();
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }

            throw $e;
        }

        $_SESSION['mathspace_run'] = $partida;

        responder([
            'ok' => true,
            'correct' => false,
            'points' => 0,
            'score' => (int) $partida['score'],
            'lives' => (int) $partida['lives'],
            'shield' => (int) $partida['shield'],
            'correctCount' => (int) $partida['correct'],
            'index' => (int) $partida['question_index'],
            'explanation' => $questao['explicacao'],
            'finished' => (
                (int) $partida['question_index'] >= 6 ||
                (int) $partida['lives'] <= 0
            )
        ]);
    }

    // =====================================================
    // 8. DANO CAUSADO POR PROJÉTIL RIVAL
    // =====================================================

    if ($acao === 'damage') {
        $dano = 20;

        $partida['shield'] = max(
            0,
            (int) $partida['shield'] - $dano
        );

        $perdeuVida = false;

        if ((int) $partida['shield'] <= 0) {
            $partida['lives'] = max(
                0,
                (int) $partida['lives'] - 1
            );

            $partida['shield'] = 100;
            $perdeuVida = true;
        }

        $_SESSION['mathspace_run'] = $partida;

        responder([
            'ok' => true,
            'damage' => $dano,
            'shield' => (int) $partida['shield'],
            'lives' => (int) $partida['lives'],
            'lifeLost' => $perdeuVida,
            'score' => (int) $partida['score'],
            'correctCount' => (int) $partida['correct'],
            'finished' => (int) $partida['lives'] <= 0
        ]);
    }

    // =====================================================
    // 9. FINALIZAR E SALVAR A MISSÃO
    // =====================================================

    if ($acao === 'finish') {
        if (!empty($partida['finished'])) {
            responder([
                'ok' => true,
                'result' => $partida['result'] ?? []
            ]);
        }

        $score = (int) $partida['score'];
        $correct = (int) $partida['correct'];
        $wrong = (int) $partida['wrong'];
        $lives = (int) $partida['lives'];

        $success = (
            $lives > 0 &&
            (int) $partida['question_index'] >= 6
        );

        $xp = $success ? $correct * 20 : 0;

        $db->beginTransaction();

        try {
            executar(
                $db,
                'UPDATE historico_partidas
                 SET pontuacao = ?, acertos = ?, erros = ?
                 WHERE id = ?
                   AND usuario_id = ?',
                [
                    $score,
                    $correct,
                    $wrong,
                    (int) $partida['partida'],
                    $usuarioId
                ]
            );

            $progresso = consultarUm(
                $db,
                'SELECT id
                 FROM progresso_usuario
                 WHERE usuario_id = ?
                   AND fase_id = ?
                 LIMIT 1',
                [
                    $usuarioId,
                    (int) $partida['phase']
                ]
            );

            if ($progresso) {
                executar(
                    $db,
                    'UPDATE progresso_usuario
                     SET tentativas = tentativas + 1,
                         pontuacao = ?,
                         melhor_pontuacao = GREATEST(melhor_pontuacao, ?),
                         xp_conquistado = GREATEST(xp_conquistado, ?),
                         concluida = IF(concluida = 1 OR ? = 1, 1, 0),
                         data_conclusao = IF(
                             ? = 1 AND data_conclusao IS NULL,
                             NOW(),
                             data_conclusao
                         )
                     WHERE id = ?',
                    [
                        $score,
                        $score,
                        $xp,
                        $success ? 1 : 0,
                        $success ? 1 : 0,
                        (int) $progresso['id']
                    ]
                );
            } else {
                executar(
                    $db,
                    'INSERT INTO progresso_usuario
                        (usuario_id, fase_id, concluida, pontuacao,
                         tentativas, melhor_pontuacao, xp_conquistado,
                         data_conclusao)
                     VALUES (?, ?, ?, ?, 1, ?, ?, ?)',
                    [
                        $usuarioId,
                        (int) $partida['phase'],
                        $success ? 1 : 0,
                        $score,
                        $score,
                        $xp,
                        $success ? date('Y-m-d H:i:s') : null
                    ]
                );
            }

            if ($success) {
                executar(
                    $db,
                    'UPDATE usuarios
                     SET xp = COALESCE(xp, 0) + ?,
                         pontuacao_total = COALESCE(pontuacao_total, 0) + ?
                     WHERE id = ?',
                    [
                        $xp,
                        $score,
                        $usuarioId
                    ]
                );
            }

            $db->commit();
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }

            throw $e;
        }

        $resultado = [
            'success' => $success,
            'score' => $score,
            'correct' => $correct,
            'wrong' => $wrong,
            'xp' => $xp,
            'lives' => $lives,
            'shield' => (int) $partida['shield']
        ];

        $partida['finished'] = true;
        $partida['result'] = $resultado;

        $_SESSION['mathspace_run'] = $partida;

        responder([
            'ok' => true,
            'result' => $resultado
        ]);
    }

    // =====================================================
    // 10. AÇÃO DESCONHECIDA
    // =====================================================

    responder([
        'ok' => false,
        'message' => 'Ação desconhecida.'
    ], 400);
} catch (Throwable $e) {
    error_log(
        'MathSpace API: ' . $e->getMessage()
    );

    if (
        isset($db) &&
        $db instanceof PDO &&
        $db->inTransaction()
    ) {
        $db->rollBack();
    }

    responder([
        'ok' => false,
        'message' => 'Ocorreu um erro ao processar a missão. Confira o banco e o log do PHP.'
    ], 500);
}
