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
    SELECT id, nome, email, senha
    FROM usuarios
    WHERE id = ?
");

$stmt->execute([$usuarioId]);

$usuario = $stmt->fetch();

if (!$usuario) {

    session_unset();
    session_destroy();

    header("Location: login.php");
    exit;
}


// =========================================================
// VARIÁVEIS
// =========================================================

$nome = $usuario['nome'];
$email = $usuario['email'];

$erro = "";
$sucesso = "";


// =========================================================
// PROCESSAR FORMULÁRIO
// =========================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $novoNome = trim($_POST['nome'] ?? '');
    $novoEmail = trim($_POST['email'] ?? '');

    $senhaAtual = $_POST['senha_atual'] ?? '';
    $novaSenha = $_POST['nova_senha'] ?? '';
    $confirmarSenha = $_POST['confirmar_senha'] ?? '';


    // =====================================================
    // VALIDAÇÃO DO NOME
    // =====================================================

    if ($novoNome === '') {

        $erro = "O nome não pode ficar vazio.";

    }


    // =====================================================
    // VALIDAÇÃO DO E-MAIL
    // =====================================================

    elseif (!filter_var($novoEmail, FILTER_VALIDATE_EMAIL)) {

        $erro = "Digite um e-mail válido.";

    }


    // =====================================================
    // VERIFICAR E-MAIL DUPLICADO
    // =====================================================

    else {

        $stmt = $pdo->prepare("
            SELECT id
            FROM usuarios
            WHERE email = ?
            AND id != ?
        ");

        $stmt->execute([
            $novoEmail,
            $usuarioId
        ]);

        $emailExiste = $stmt->fetch();


        if ($emailExiste) {

            $erro = "Esse e-mail já está sendo utilizado por outra conta.";

        }
    }


    // =====================================================
    // ALTERAÇÃO DE SENHA
    // =====================================================

    if ($erro === '' && $novaSenha !== '') {

        if ($senhaAtual === '') {

            $erro = "Digite sua senha atual para alterar a senha.";

        }

        elseif (!password_verify($senhaAtual, $usuario['senha'])) {

            $erro = "A senha atual está incorreta.";

        }

        elseif (strlen($novaSenha) < 6) {

            $erro = "A nova senha deve ter pelo menos 6 caracteres.";

        }

        elseif ($novaSenha !== $confirmarSenha) {

            $erro = "A confirmação da nova senha não corresponde.";

        }
    }


    // =====================================================
    // SALVAR ALTERAÇÕES
    // =====================================================

    if ($erro === '') {

        try {

            $pdo->beginTransaction();


            // ---------------------------------------------
            // Atualizar nome e e-mail
            // ---------------------------------------------

            $stmt = $pdo->prepare("
                UPDATE usuarios
                SET nome = ?, email = ?
                WHERE id = ?
            ");

            $stmt->execute([
                $novoNome,
                $novoEmail,
                $usuarioId
            ]);


            // ---------------------------------------------
            // Atualizar senha, se foi preenchida
            // ---------------------------------------------

            if ($novaSenha !== '') {

                $senhaHash = password_hash(
                    $novaSenha,
                    PASSWORD_DEFAULT
                );

                $stmt = $pdo->prepare("
                    UPDATE usuarios
                    SET senha = ?
                    WHERE id = ?
                ");

                $stmt->execute([
                    $senhaHash,
                    $usuarioId
                ]);
            }


            $pdo->commit();


            // ---------------------------------------------
            // Atualizar sessão
            // ---------------------------------------------

            $_SESSION['usuario_nome'] = $novoNome;
            $_SESSION['usuario_email'] = $novoEmail;


            $nome = $novoNome;
            $email = $novoEmail;


            $sucesso = "Seus dados foram atualizados com sucesso.";


            // Limpar campos de senha
            $senhaAtual = "";
            $novaSenha = "";
            $confirmarSenha = "";

        } catch (PDOException $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $erro = "Não foi possível atualizar seus dados. Tente novamente.";
        }
    }
}


// =========================================================
// INICIAL DO NOME
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
        content="width=device-width, initial-scale=1.0"
    >

    <title>Editar Perfil | MathRun</title>

    <link
        rel="stylesheet"
        href="assets/css/editar_perfil.css"
    >

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
                title="Tema claro"
            >
                ☀
            </button>

            <button
                type="button"
                data-theme-option="dark"
                title="Tema escuro"
            >
                ☾
            </button>

            <button
                type="button"
                data-theme-option="pink"
                title="Tema rosa"
            >
                ♡
            </button>

        </div>


        <!-- AVATAR -->

        <div class="avatar">
            <?= htmlspecialchars($inicial) ?>
        </div>


        <div class="user-info">

            <strong>
                <?= htmlspecialchars($nome) ?>
            </strong>

            <span>
                LEVEL <?= (int) $_SESSION['usuario_nivel'] ?>
            </span>

        </div>


        <a
            href="logout.php"
            class="logout"
            title="Sair"
        >
            ↪
        </a>

    </div>

</header>



<!-- ======================================================
     CONTEÚDO
====================================================== -->

<main class="edit-container">


    <!-- CABEÇALHO -->

    <section class="edit-header">

        <span class="section-label">
            CONFIGURAÇÕES DA CONTA
        </span>

        <h1>
            Editar perfil.
        </h1>

        <p>
            Atualize suas informações e mantenha sua conta em dia.
        </p>

    </section>



    <!-- MENSAGENS -->

    <?php if ($erro !== ''): ?>

        <div class="message error">
            <span>!</span>
            <?= htmlspecialchars($erro) ?>
        </div>

    <?php endif; ?>


    <?php if ($sucesso !== ''): ?>

        <div class="message success">
            <span>✓</span>
            <?= htmlspecialchars($sucesso) ?>
        </div>

    <?php endif; ?>



    <!-- ==================================================
         FORMULÁRIO
    ================================================== -->

    <form
        method="POST"
        class="edit-form"
    >


        <!-- DADOS PESSOAIS -->

        <section class="edit-card">

            <div class="card-title">

                <div class="title-icon">
                    👤
                </div>

                <div>

                    <span>
                        INFORMAÇÕES PESSOAIS
                    </span>

                    <h2>
                        Seus dados
                    </h2>

                </div>

            </div>


            <!-- NOME -->

            <div class="form-group">

                <label for="nome">
                    Nome
                </label>

                <input
                    type="text"
                    id="nome"
                    name="nome"
                    value="<?= htmlspecialchars($nome) ?>"
                    maxlength="100"
                    required
                >

            </div>


            <!-- E-MAIL -->

            <div class="form-group">

                <label for="email">
                    E-mail
                </label>

                <input
                    type="email"
                    id="email"
                    name="email"
                    value="<?= htmlspecialchars($email) ?>"
                    maxlength="150"
                    required
                >

            </div>

        </section>



        <!-- SENHA -->

        <section class="edit-card">

            <div class="card-title">

                <div class="title-icon">
                    🔐
                </div>

                <div>

                    <span>
                        SEGURANÇA
                    </span>

                    <h2>
                        Alterar senha
                    </h2>

                </div>

            </div>


            <p class="password-info">
                Se não quiser alterar sua senha, deixe os campos abaixo vazios.
            </p>


            <!-- SENHA ATUAL -->

            <div class="form-group">

                <label for="senha_atual">
                    Senha atual
                </label>

                <input
                    type="password"
                    id="senha_atual"
                    name="senha_atual"
                    autocomplete="current-password"
                >

            </div>


            <!-- NOVA SENHA -->

            <div class="form-group">

                <label for="nova_senha">
                    Nova senha
                </label>

                <input
                    type="password"
                    id="nova_senha"
                    name="nova_senha"
                    minlength="6"
                    autocomplete="new-password"
                >

                <small>
                    Mínimo de 6 caracteres.
                </small>

            </div>


            <!-- CONFIRMAR -->

            <div class="form-group">

                <label for="confirmar_senha">
                    Confirmar nova senha
                </label>

                <input
                    type="password"
                    id="confirmar_senha"
                    name="confirmar_senha"
                    minlength="6"
                    autocomplete="new-password"
                >

            </div>

        </section>



        <!-- BOTÕES -->

        <div class="form-actions">

            <a
                href="perfil.php"
                class="cancel-button"
            >
                Cancelar
            </a>

            <button
                type="submit"
                class="save-button"
            >
                Salvar alterações
                <span>→</span>
            </button>

        </div>


    </form>


</main>


<script src="assets/js/tema.js"></script>

</body>

</html>