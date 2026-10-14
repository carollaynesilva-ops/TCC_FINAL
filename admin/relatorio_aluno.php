<?php
session_start();

if (($_SESSION["admin_autenticado"] ?? false) !== true) {
    header("Location: ../login.php");
    exit;
}

require_once "../config/config.php";

function relatorioEsc($valor): string
{
    return htmlspecialchars((string)($valor ?? ""), ENT_QUOTES, "UTF-8");
}

function relatorioData(?string $data, bool $comHora = false): string
{
    if (!$data) {
        return "Não informado";
    }
    try {
        $obj = new DateTime($data);
        return $obj->format($comHora ? "d/m/Y H:i" : "d/m/Y");
    } catch (Throwable $e) {
        return "Não informado";
    }
}

/** Gera um gráfico SVG sem depender de bibliotecas externas. */
function gerarGraficoLinha(array $partidas, string $titulo, string $cor): string
{
    $largura = 520;
    $altura = 230;
    $esquerda = 48;
    $direita = 18;
    $topo = 22;
    $base = 178;
    $areaLargura = $largura - $esquerda - $direita;
    $areaAltura = $base - $topo;

    $valores = array_map(static fn($p) => max(0, (float)$p["pontuacao"]), $partidas);
    $maximo = max(100, ...$valores);
    $maximo = ceil($maximo / 50) * 50;
    $maximo = max(100, $maximo);

    $svg = '<svg class="grafico-svg" viewBox="0 0 ' . $largura . ' ' . $altura . '" role="img" aria-label="' . relatorioEsc($titulo) . '">';

    for ($i = 0; $i <= 4; $i++) {
        $y = $topo + ($areaAltura / 4) * $i;
        $valorEixo = (int)round($maximo - ($maximo / 4) * $i);
        $svg .= '<line x1="' . $esquerda . '" y1="' . $y . '" x2="' . ($largura - $direita) . '" y2="' . $y . '" class="grade" />';
        $svg .= '<text x="' . ($esquerda - 8) . '" y="' . ($y + 4) . '" class="eixo-texto" text-anchor="end">' . $valorEixo . '</text>';
    }

    $svg .= '<line x1="' . $esquerda . '" y1="' . $topo . '" x2="' . $esquerda . '" y2="' . $base . '" class="eixo" />';
    $svg .= '<line x1="' . $esquerda . '" y1="' . $base . '" x2="' . ($largura - $direita) . '" y2="' . $base . '" class="eixo" />';

    if (count($partidas) === 0) {
        $svg .= '<text x="' . ($largura / 2) . '" y="108" class="sem-dados" text-anchor="middle">Sem partidas registradas</text>';
    } else {
        $pontos = [];
        $total = count($partidas);
        foreach ($partidas as $i => $partida) {
            $x = $total === 1 ? $esquerda + ($areaLargura / 2) : $esquerda + ($areaLargura * $i / ($total - 1));
            $y = $base - ((max(0, (float)$partida["pontuacao"]) / $maximo) * $areaAltura);
            $pontos[] = round($x, 2) . ',' . round($y, 2);
        }
        if (count($pontos) > 1) {
            $svg .= '<polyline points="' . implode(' ', $pontos) . '" fill="none" stroke="' . $cor . '" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" />';
        }
        foreach ($partidas as $i => $partida) {
            $xy = explode(',', $pontos[$i]);
            $x = (float)$xy[0];
            $y = (float)$xy[1];
            $svg .= '<circle cx="' . $x . '" cy="' . $y . '" r="4" fill="' . $cor . '" />';
            if ($total <= 8 || $i === 0 || $i === $total - 1) {
                $dataLabel = date('d/m', strtotime($partida["data_partida"]));
                $svg .= '<text x="' . $x . '" y="' . ($base + 19) . '" class="eixo-texto" text-anchor="middle">' . $dataLabel . '</text>';
            }
        }
    }

    $svg .= '<text x="' . ($largura / 2) . '" y="' . ($altura - 5) . '" class="rotulo-eixo" text-anchor="middle">Data da partida</text>';
    $svg .= '</svg>';
    return $svg;
}

$idAluno = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);
if (!$idAluno || $idAluno < 1) {
    http_response_code(400);
    exit("Identificador de aluno inválido.");
}

try {
    $stmt = $pdo->prepare("SELECT id, nome, email, serie, turma, nivel, xp, pontuacao_total, data_cadastro FROM usuarios WHERE id = :id AND tipo = 'aluno' LIMIT 1");
    $stmt->execute([":id" => $idAluno]);
    $aluno = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$aluno) {
        http_response_code(404);
        exit("Aluno não encontrado.");
    }

    $stmt = $pdo->prepare("
        SELECT h.id, h.jogo_id, j.nome AS jogo_nome, h.fase_id, f.nome AS fase_nome,
               h.pontuacao, h.acertos, h.erros, h.dicas_usadas, h.data_partida
        FROM historico_partidas h
        INNER JOIN jogos j ON j.id = h.jogo_id
        LEFT JOIN fases f ON f.id = h.fase_id
        WHERE h.usuario_id = :id
        ORDER BY h.data_partida ASC, h.id ASC
    ");
    $stmt->execute([":id" => $idAluno]);
    $partidas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("
        SELECT j.id AS jogo_id, j.nome AS jogo_nome,
               COUNT(h.id) AS partidas,
               COALESCE(SUM(h.pontuacao), 0) AS pontuacao,
               COALESCE(SUM(h.acertos), 0) AS acertos,
               COALESCE(SUM(h.erros), 0) AS erros,
               COALESCE(SUM(h.dicas_usadas), 0) AS dicas
        FROM jogos j
        LEFT JOIN historico_partidas h ON h.jogo_id = j.id AND h.usuario_id = :id
        GROUP BY j.id, j.nome
        ORDER BY j.id
    ");
    $stmt->execute([":id" => $idAluno]);
    $resumoJogos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("SELECT COUNT(DISTINCT fase_id) FROM progresso_usuario WHERE usuario_id = :id AND concluida = 1");
    $stmt->execute([":id" => $idAluno]);
    $fasesConcluidas = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare("
        SELECT m.nome, m.descricao, m.imagem, um.data_conquista
        FROM usuario_medalhas um
        INNER JOIN medalhas m ON m.id = um.medalha_id
        WHERE um.usuario_id = :id
        ORDER BY um.data_conquista ASC, m.nome ASC
    ");
    $stmt->execute([":id" => $idAluno]);
    $medalhas = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Erro ao gerar relatório do aluno: " . $e->getMessage());
    http_response_code(500);
    exit("Não foi possível montar o relatório neste momento. Verifique a conexão com o banco de dados e os nomes das tabelas.");
}

$totalPartidas = count($partidas);
$totalPontos = 0;
$totalAcertos = 0;
$totalErros = 0;
$totalDicas = 0;
$partidasPorJogo = [];
foreach ($partidas as $partida) {
    $totalPontos += (int)$partida["pontuacao"];
    $totalAcertos += (int)$partida["acertos"];
    $totalErros += (int)$partida["erros"];
    $totalDicas += (int)$partida["dicas_usadas"];
    $partidasPorJogo[(int)$partida["jogo_id"]][] = $partida;
}
$totalRespostas = $totalAcertos + $totalErros;
$taxaAcertos = $totalRespostas > 0 ? round(($totalAcertos / $totalRespostas) * 100, 1) : 0;
$serie = trim((string)($aluno["serie"] ?? ""));
$serieTexto = $serie !== "" ? $serie . "º ano" : "Não informada";
$turma = trim((string)($aluno["turma"] ?? ""));
$turmaTexto = ($serie !== "" && $turma !== "") ? $serie . $turma : ($turma !== "" ? $turma : "Não informada");
$dataRelatorio = date("d/m/Y");
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatório escolar - <?= relatorioEsc($aluno["nome"]) ?> | MathRun</title>
    <link rel="stylesheet" href="css/relatorio.css">
</head>
<body>
    <div class="barra-acoes">
        <a class="acao-voltar" href="alunos.php">← Voltar para alunos</a>
        <span class="dica-impressao">Para salvar em PDF, selecione “Salvar como PDF” na janela de impressão.</span>
        <button type="button" class="botao-imprimir" onclick="window.print()">Imprimir / Salvar PDF</button>
    </div>

    <main class="folha">
        <header class="cabecalho-institucional">
            <div class="marca">
                <div class="marca-simbolo">M</div>
                <div>
                    <p class="nome-sistema">MathRun</p>
                    <p class="subnome-sistema">Plataforma educacional de matemática</p>
                </div>
            </div>
            <div class="identificacao-documento">
                <p class="tipo-documento">RELATÓRIO INDIVIDUAL</p>
                <p class="data-documento">Emitido em <?= $dataRelatorio ?></p>
            </div>
        </header>

        <section class="introducao">
            <h1>Relatório de desempenho escolar</h1>
            <p>Registro individual de participação, resultados e progresso nas atividades gamificadas de matemática.</p>
        </section>

        <section class="secao">
            <h2><span>01</span> Identificação do estudante</h2>
            <div class="dados-aluno">
                <div class="campo-dado campo-nome"><span>Nome completo</span><strong><?= relatorioEsc($aluno["nome"]) ?></strong></div>
                <div class="campo-dado"><span>E-mail</span><strong><?= relatorioEsc($aluno["email"]) ?></strong></div>
                <div class="campo-dado"><span>Série</span><strong><?= relatorioEsc($serieTexto) ?></strong></div>
                <div class="campo-dado"><span>Sala / turma</span><strong><?= relatorioEsc($turmaTexto) ?></strong></div>
                <div class="campo-dado"><span>Nível atual</span><strong><?= (int)($aluno["nivel"] ?? 1) ?></strong></div>
                <div class="campo-dado"><span>Cadastro</span><strong><?= relatorioEsc(relatorioData($aluno["data_cadastro"] ?? null)) ?></strong></div>
            </div>
        </section>

        <section class="secao">
            <h2><span>02</span> Síntese do desempenho</h2>
            <div class="indicadores">
                <div class="indicador"><span>Pontuação registrada</span><strong><?= number_format($totalPontos, 0, ',', '.') ?></strong><small>pontos nas partidas</small></div>
                <div class="indicador"><span>Partidas realizadas</span><strong><?= number_format($totalPartidas, 0, ',', '.') ?></strong><small>registros no histórico</small></div>
                <div class="indicador"><span>Respostas corretas</span><strong><?= number_format($totalAcertos, 0, ',', '.') ?></strong><small><?= number_format($taxaAcertos, 1, ',', '.') ?>% de acerto</small></div>
                <div class="indicador"><span>Respostas incorretas</span><strong><?= number_format($totalErros, 0, ',', '.') ?></strong><small>total registrado</small></div>
                <div class="indicador"><span>Fases concluídas</span><strong><?= number_format($fasesConcluidas, 0, ',', '.') ?></strong><small>fases distintas</small></div>
                <div class="indicador"><span>Medalhas conquistadas</span><strong><?= number_format(count($medalhas), 0, ',', '.') ?></strong><small>conquistas registradas</small></div>
            </div>
        </section>

        <section class="secao secao-graficos">
            <h2><span>03</span> Evolução da pontuação por jogo</h2>
            <p class="texto-apoio">Cada ponto representa a pontuação registrada em uma partida, apresentada em ordem cronológica.</p>
            <div class="graficos">
                <?php
                $idsJogosConhecidos = [];
                foreach ($resumoJogos as $jogo):
                    $jogoId = (int)$jogo["jogo_id"];
                    $nomeJogo = (string)$jogo["jogo_nome"];
                    $idsJogosConhecidos[] = $jogoId;
                    $corGrafico = stripos($nomeJogo, "Chef") !== false ? "#2563a6" : "#6b5ca5";
                ?>
                    <article class="cartao-grafico">
                        <h3><?= relatorioEsc($nomeJogo) ?></h3>
                        <p><?= (int)$jogo["partidas"] ?> partida(s) registrada(s)</p>
                        <?= gerarGraficoLinha($partidasPorJogo[$jogoId] ?? [], $nomeJogo, $corGrafico) ?>
                    </article>
                <?php endforeach; ?>
                <?php if (count($resumoJogos) === 0): ?>
                    <p>Não há jogos cadastrados para apresentar.</p>
                <?php endif; ?>
            </div>
        </section>

        <section class="secao">
            <h2><span>04</span> Comparativo por jogo</h2>
            <div class="tabela-responsiva">
                <table>
                    <thead><tr><th>Jogo</th><th>Partidas</th><th>Pontuação</th><th>Acertos</th><th>Erros</th><th>Taxa de acerto</th><th>Dicas usadas</th></tr></thead>
                    <tbody>
                    <?php foreach ($resumoJogos as $jogo):
                        $respostasJogo = (int)$jogo["acertos"] + (int)$jogo["erros"];
                        $taxaJogo = $respostasJogo > 0 ? round(((int)$jogo["acertos"] / $respostasJogo) * 100, 1) : 0;
                    ?>
                        <tr>
                            <td><?= relatorioEsc($jogo["jogo_nome"]) ?></td>
                            <td><?= (int)$jogo["partidas"] ?></td>
                            <td><?= number_format((int)$jogo["pontuacao"], 0, ',', '.') ?></td>
                            <td><?= (int)$jogo["acertos"] ?></td>
                            <td><?= (int)$jogo["erros"] ?></td>
                            <td><?= number_format($taxaJogo, 1, ',', '.') ?>%</td>
                            <td><?= (int)$jogo["dicas"] ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$resumoJogos): ?><tr><td colspan="7" class="linha-vazia">Não há dados de jogos para este estudante.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="secao">
            <h2><span>05</span> Medalhas e conquistas</h2>
            <?php if ($medalhas): ?>
                <div class="lista-medalhas">
                    <?php foreach ($medalhas as $medalha): ?>
                        <article class="medalha">
                            <div class="medalha-selo" aria-hidden="true">★</div>
                            <div class="medalha-conteudo">
                                <h3><?= relatorioEsc($medalha["nome"]) ?></h3>
                                <p><?= relatorioEsc($medalha["descricao"] ?: "Descrição não cadastrada.") ?></p>
                                <small>Conquistada em <?= relatorioEsc(relatorioData($medalha["data_conquista"] ?? null)) ?></small>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="estado-vazio">Nenhuma medalha foi registrada para este estudante até o momento.</p>
            <?php endif; ?>
        </section>

        <section class="secao secao-observacoes">
            <h2><span>06</span> Observações pedagógicas</h2>
            <p class="texto-apoio">Espaço reservado para registro e acompanhamento pela equipe pedagógica.</p>
            <div class="linhas-observacao"><div></div><div></div><div></div><div></div></div>
        </section>

        <footer class="rodape-relatorio">
            <p>Documento gerado pela plataforma MathRun com base nos dados registrados no sistema.</p>
            <p>Relatório individual • <?= $dataRelatorio ?></p>
        </footer>
    </main>

    <script>
        // Abre a impressão do navegador, que permite imprimir ou escolher "Salvar como PDF".
        // Para abrir automaticamente a janela, acrescente ?imprimir=1 ao endereço do relatório.
        if (new URLSearchParams(window.location.search).get('imprimir') === '1') {
            window.addEventListener('load', () => window.print());
        }
    </script>
</body>
</html>
