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
        senha,
        tipo
    FROM usuarios
    WHERE id = ?
");

$stmt->execute([$usuarioId]);

$usuario = $stmt->fetch();


// Caso o usuário não exista mais
if (!$usuario) {

    session_unset();
    session_destroy();

    header("Location: login.php");
    exit;
}


// =========================================================
// IMPEDIR ADMIN DE EXCLUIR A CONTA POR ESTA PÁGINA
// =========================================================

if ($usuario['tipo'] === 'admin') {
    header("Location: perfil.php");
    exit;
}


// =========================================================
// EXCLUSÃO DA CONTA
// =========================================================

$erro = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $senha = $_POST["senha"] ?? "";

    if ($senha === "") {

        $erro = "Digite sua senha para continuar.";

    } elseif (!password_verify($senha, $usuario['senha'])) {

        $erro = "Senha incorreta.";

    } else {

        try {

            // Inicia uma transação para garantir
            // que a exclusão aconteça corretamente.
            $pdo->beginTransaction();

            // Exclui o usuário.
            // As tabelas relacionadas possuem ON DELETE CASCADE.
            $stmt = $pdo->prepare("
                DELETE FROM usuarios
                WHERE id = ?
            ");

            $stmt->execute([$usuarioId]);

            // Confirma a exclusão
            $pdo->commit();


            // =================================================
            // ENCERRAR SESSÃO
            // =================================================

            $_SESSION = [];

            if (ini_get("session.use_cookies")) {

                $params = session_get_cookie_params();

                setcookie(
                    session_name(),
                    '',
                    time() - 42000,
                    $params["path"],
                    $params["domain"],
                    $params["secure"],
                    $params["httponly"]
                );
            }

            session_destroy();


            // =================================================
            // REDIRECIONAR PARA LOGIN
            // =================================================

            header("Location: login.php");
            exit;

        } catch (PDOException $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $erro = "Não foi possível excluir sua conta. Tente novamente.";
        }
    }
}


// =========================================================
// PRIMEIRA LETRA DO NOME
// =========================================================

$inicial = strtoupper(
    mb_substr($usuario['nome'], 0, 1, 'UTF-8')
);

?>

<!DOCTYPE html>

<html lang="pt-BR">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>Excluir perfil | MathRun</title>

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

            <!-- TEMA -->

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


            <!-- USUÁRIO -->

            <div class="user-info">

                <strong>
                    <?= htmlspecialchars($usuario['nome']) ?>
                </strong>

                <span>
                    LEVEL <?= $_SESSION['usuario_nivel'] ?? 1 ?>
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


        <section class="profile-header">

            <div>

                <span class="section-label">
                    SEGURANÇA DA CONTA
                </span>

                <h1>
                    Excluir perfil
                </h1>

                <p>
                    Esta ação é permanente e não poderá ser desfeita.
                </p>

            </div>


            <a
                href="perfil.php"
                class="back-button">
                ← Voltar para o perfil
            </a>

        </section>



        <!-- ==================================================
             CARD DE EXCLUSÃO
        ================================================== -->

        <section class="profile-main-card">


            <div>

                <div class="person-info">

                    <h2>
                        Tem certeza?
                    </h2>

                    <p>
                        Ao excluir sua conta, seu progresso, XP,
                        pontuação, partidas e conquistas serão removidos.
                    </p>

                </div>


                <?php if ($erro !== ""): ?>

                    <div class="delete-error">
                        <?= htmlspecialchars($erro) ?>
                    </div>

                <?php endif; ?>


                <form
                    method="POST"
                    class="delete-form">


                    <label for="senha">
                        Confirme sua senha
                    </label>


                    <input
                        type="password"
                        id="senha"
                        name="senha"
                        placeholder="Digite sua senha"
                        autocomplete="current-password"
                        required>


                    <div class="delete-actions">

                        <a
                            href="perfil.php"
                            class="back-button">
                            Cancelar
                        </a>


                        <button
                            type="submit"
                            class="delete-button">
                            Excluir minha conta
                        </button>

                    </div>

                </form>

            </div>


        </section>


    </main>


    <!-- ======================================================
         TEMA
    ====================================================== -->

    <script src="assets/js/tema.js"></script>

</body>

</html>