<?php

session_start();

require_once __DIR__ . '/config/config.php';


// =========================================================
// VERIFICAR LOGIN
// =========================================================

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

$usuarioId = $_SESSION['usuario_id'];


// =========================================================
// BUSCAR USUÁRIO
// =========================================================

$stmt = $pdo->prepare("
    SELECT
        id,
        nome,
        email,
        tipo,
        nivel,
        xp,
        pontuacao_total,
        data_cadastro
    FROM usuarios
    WHERE id = ?
");

$stmt->execute([$usuarioId]);

$usuario = $stmt->fetch();


// Caso o usuário não exista mais no banco
if (!$usuario) {

    session_unset();
    session_destroy();

    header("Location: login.php");
    exit;
}


// =========================================================
// DADOS DO USUÁRIO
// =========================================================

$nome = $usuario['nome'];
$email = $usuario['email'];

$nivel = (int) $usuario['nivel'];
$xp = (int) $usuario['xp'];
$pontuacaoTotal = (int) $usuario['pontuacao_total'];


// =========================================================
// SISTEMA DE XP
// =========================================================

// Cada nível necessita de 500 XP
$xpPorNivel = 500;

$xpNoNivel = $xp % $xpPorNivel;

$proximoNivel = $nivel + 1;

$porcentagemXp = ($xpNoNivel / $xpPorNivel) * 100;

if ($porcentagemXp > 100) {
    $porcentagemXp = 100;
}


// =========================================================
// TOTAL DE CONQUISTAS
// =========================================================

$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM usuario_medalhas
    WHERE usuario_id = ?
");

$stmt->execute([$usuarioId]);

$totalConquistas = (int) $stmt->fetchColumn();


// =========================================================
// TOTAL DE PARTIDAS
// =========================================================

$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM historico_partidas
    WHERE usuario_id = ?
");

$stmt->execute([$usuarioId]);

$totalPartidas = (int) $stmt->fetchColumn();


// =========================================================
// MELHOR PONTUAÇÃO
// =========================================================

$stmt = $pdo->prepare("
    SELECT COALESCE(MAX(pontuacao), 0)
    FROM historico_partidas
    WHERE usuario_id = ?
");

$stmt->execute([$usuarioId]);

$melhorPontuacao = (int) $stmt->fetchColumn();


// =========================================================
// DATA DE CADASTRO
// =========================================================

$dataCadastro = date(
    'd/m/Y',
    strtotime($usuario['data_cadastro'])
);


// =========================================================
// PRIMEIRA LETRA DO NOME
// =========================================================

$inicial = strtoupper(
    mb_substr($nome, 0, 1, 'UTF-8')
);

?>

<!DOCTYPE html>

<html lang="pt-BR">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>Meu Perfil | MathRun</title>

    <link
        rel="stylesheet"
        href="assets/css/perfil.css">

</head>


<body>


    <!-- ======================================================
     NAVBAR
====================================================== -->

    <header class="navbar">

        <a href="inicio.php" class="brand">
            Math<span>Run</span>
        </a>


        <nav class="nav-links">

            <a href="inicio.php">
                Início
            </a>

            <a href="ranking.php">
                Ranking
            </a>

            <a href="conquistas.php">
                Conquistas
            </a>

        </nav>


        <div class="nav-user">


            <!-- SELETOR DE TEMA -->

            <div class="theme-switcher">

                <button
                    type="button"
                    data-theme-option="light"
                    title="Tema claro">
                    ☀
                </button>

                <button
                    type="button"
                    data-theme-option="dark"
                    title="Tema escuro">
                    ☾
                </button>

                <button
                    type="button"
                    data-theme-option="pink"
                    title="Tema rosa">
                    ♡
                </button>

            </div>


            <!-- AVATAR -->

            <div class="avatar">
                <?= htmlspecialchars($inicial) ?>
            </div>


            <!-- INFORMAÇÕES -->

            <div class="user-info">

                <strong>
                    <?= htmlspecialchars($nome) ?>
                </strong>

                <span>
                    LEVEL <?= $nivel ?>
                </span>

            </div>


            <!-- LOGOUT -->

            <a
                href="logout.php"
                class="logout"
                title="Sair">
                ↪
            </a>

        </div>

    </header>



    <!-- ======================================================
     CONTEÚDO
====================================================== -->

    <main class="profile-container">


        <!-- CABEÇALHO -->

        <section class="profile-header">

            <div>

                <span class="section-label">
                    MEU PERFIL
                </span>

                <h1>
                    Olá, <?= htmlspecialchars($nome) ?>.
                </h1>

                <p>
                    Veja seu progresso e sua jornada no MathRun.
                </p>

            </div>


            <a
                href="inicio.php"
                class="back-button">
                ← Voltar para início
            </a>

        </section>



        <!-- ==================================================
         CARD PRINCIPAL
    ================================================== -->

        <section class="profile-main-card">


            <!-- PERFIL -->

            <div class="profile-person">


                <div class="big-avatar">
                    <?= htmlspecialchars($inicial) ?>
                </div>


                <div class="person-info">

                    <h2>
                        <?= htmlspecialchars($nome) ?>
                    </h2>

                    <p>
                        <?= htmlspecialchars($email) ?>
                    </p>

                    <span class="user-type">
                        <?= $usuario['tipo'] === 'admin' ? 'Administrador' : 'Aluno' ?>
                    </span>

                </div>

            </div>



            <!-- LEVEL -->

            <div class="level-box">

                <div class="level-top">

                    <span>
                        LEVEL ATUAL
                    </span>

                    <strong>
                        <?= $nivel ?>
                    </strong>

                </div>


                <div class="xp-info">

                    <span>
                        <?= $xpNoNivel ?> / <?= $xpPorNivel ?> XP
                    </span>

                    <span>
                        <?= $proximoNivel ?>º nível
                    </span>

                </div>


                <div class="xp-bar">

                    <div
                        style="width: <?= $porcentagemXp ?>%;"></div>

                </div>

            </div>

        </section>



        <!-- ==================================================
         ESTATÍSTICAS
    ================================================== -->

        <section class="stats-grid">


            <!-- XP -->

            <div class="stat-card">

                <div class="stat-icon">
                    ⚡
                </div>

                <div>

                    <span>
                        EXPERIÊNCIA
                    </span>

                    <strong>
                        <?= number_format($xp, 0, ',', '.') ?>
                    </strong>

                    <small>
                        XP acumulado
                    </small>

                </div>

            </div>



            <!-- PONTOS -->

            <div class="stat-card">

                <div class="stat-icon">
                    ⭐
                </div>

                <div>

                    <span>
                        PONTUAÇÃO
                    </span>

                    <strong>
                        <?= number_format($pontuacaoTotal, 0, ',', '.') ?>
                    </strong>

                    <small>
                        pontos totais
                    </small>

                </div>

            </div>



            <!-- PARTIDAS -->

            <div class="stat-card">

                <div class="stat-icon">
                    🎮
                </div>

                <div>

                    <span>
                        PARTIDAS
                    </span>

                    <strong>
                        <?= $totalPartidas ?>
                    </strong>

                    <small>
                        partidas realizadas
                    </small>

                </div>

            </div>



            <!-- CONQUISTAS -->

            <div class="stat-card">

                <div class="stat-icon">
                    🏆
                </div>

                <div>

                    <span>
                        CONQUISTAS
                    </span>

                    <strong>
                        <?= $totalConquistas ?>
                    </strong>

                    <small>
                        medalhas desbloqueadas
                    </small>

                </div>

            </div>

        </section>



        <!-- ==================================================
         INFORMAÇÕES
    ================================================== -->

        <section class="profile-grid">


            <!-- DADOS DA CONTA -->

            <div class="info-card">

                <div class="card-heading">

                    <div>

                        <span>
                            CONTA
                        </span>

                        <h2>
                            Seus dados
                        </h2>

                    </div>

                </div>


                <div class="info-list">


                    <div class="info-item">

                        <div class="info-item-icon">
                            👤
                        </div>

                        <div>

                            <span>
                                Nome
                            </span>

                            <strong>
                                <?= htmlspecialchars($nome) ?>
                            </strong>

                        </div>

                    </div>



                    <div class="info-item">

                        <div class="info-item-icon">
                            ✉
                        </div>

                        <div>

                            <span>
                                E-mail
                            </span>

                            <strong>
                                <?= htmlspecialchars($email) ?>
                            </strong>

                        </div>

                    </div>



                    <div class="info-item">

                        <div class="info-item-icon">
                            📅
                        </div>

                        <div>

                            <span>
                                Membro desde
                            </span>

                            <strong>
                                <?= $dataCadastro ?>
                            </strong>

                        </div>

                    </div>
                    <a href="editar_perfil.php" class="edit-profile-button">
                    ✎ Editar perfil
                </a>
                </div>
                
            </div>



            <!-- DESEMPENHO -->

            <div class="info-card">

                <div class="card-heading">

                    <div>

                        <span>
                            DESEMPENHO
                        </span>

                        <h2>
                            Seus números
                        </h2>

                    </div>

                </div>


                <div class="performance-list">


                    <div class="performance-item">

                        <div>

                            <span>
                                Melhor pontuação
                            </span>

                            <strong>
                                <?= number_format($melhorPontuacao, 0, ',', '.') ?>
                            </strong>

                        </div>

                        <span class="performance-icon">
                            🏅
                        </span>

                    </div>



                    <div class="performance-item">

                        <div>

                            <span>
                                Nível atual
                            </span>

                            <strong>
                                Level <?= $nivel ?>
                            </strong>

                        </div>

                        <span class="performance-icon">
                            ⚡
                        </span>

                    </div>



                    <div class="performance-item">

                        <div>

                            <span>
                                Próximo nível
                            </span>

                            <strong>
                                <?= $xpPorNivel - $xpNoNivel ?> XP restantes
                            </strong>

                        </div>

                        <span class="performance-icon">
                            🚀
                        </span>

                    </div>

                </div>

            </div>

        </section>



        <!-- ==================================================
         CONQUISTAS
    ================================================== -->

        <section class="achievement-preview">


            <div>

                <span class="section-label">
                    SUA JORNADA
                </span>

                <h2>
                    Continue evoluindo.
                </h2>

                <p>
                    Cada desafio concluído aproxima você da próxima conquista.
                </p>

            </div>


            <div class="achievement-number">

                <strong>
                    <?= $totalConquistas ?>
                </strong>

                <span>
                    conquistas
                </span>

            </div>


            <a
                href="conquistas.php"
                class="achievement-button">
                Ver conquistas
                <span>→</span>
            </a>

        </section>


    </main>


    <!-- ======================================================
     TEMA
====================================================== -->

    <script src="assets/js/tema.js"></script>

</body>

</html>