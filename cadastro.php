<?php

session_start();

require_once 'config/config.php';

$erro = "";
$sucesso = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $nome = trim($_POST["nome"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $senha = $_POST["senha"] ?? "";
    $confirmarSenha = $_POST["confirmar_senha"] ?? "";


    // ==========================================
    // VALIDAÇÕES
    // ==========================================

    if ($nome === "" || $email === "" || $senha === "" || $confirmarSenha === "") {

        $erro = "Preencha todos os campos.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $erro = "Digite um e-mail válido.";

    } elseif (strlen($senha) < 6) {

        $erro = "A senha deve ter pelo menos 6 caracteres.";

    } elseif ($senha !== $confirmarSenha) {

        $erro = "As senhas não coincidem.";

    } else {

        // ==========================================
        // VERIFICAR SE O E-MAIL JÁ EXISTE
        // ==========================================

        $sql = "SELECT id FROM usuarios WHERE email = :email";

        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            ":email" => $email
        ]);

        $usuarioExistente = $stmt->fetch();


        if ($usuarioExistente) {

            $erro = "Este e-mail já está cadastrado.";

        } else {

            // ==========================================
            // CRIPTOGRAFAR SENHA
            // ==========================================

            $senhaHash = password_hash(
                $senha,
                PASSWORD_DEFAULT
            );


            // ==========================================
            // CADASTRAR USUÁRIO
            // ==========================================

            $sql = "
                INSERT INTO usuarios
                (
                    nome,
                    email,
                    senha,
                    tipo,
                    nivel,
                    xp,
                    pontuacao_total
                )
                VALUES
                (
                    :nome,
                    :email,
                    :senha,
                    'aluno',
                    1,
                    0,
                    0
                )
            ";

            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                ":nome" => $nome,
                ":email" => $email,
                ":senha" => $senhaHash
            ]);


            // ==========================================
            // CADASTRO REALIZADO
            // ==========================================

            $sucesso = "Cadastro realizado com sucesso!";

            header("refresh:2;url=login.php");
        }
    }
}

?>

<!DOCTYPE html>

<html lang="pt-BR">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Cadastro | MathRun</title>


    <!-- Fonte -->

    <link
        rel="preconnect"
        href="https://fonts.googleapis.com"
    >

    <link
        rel="preconnect"
        href="https://fonts.gstatic.com"
        crossorigin
    >

    <link
        href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&display=swap"
        rel="stylesheet"
    >


    <!-- CSS -->

    <link
        rel="stylesheet"
        href="assets/css/cadastro.css"
    >

</head>


<body>


    <!-- ==========================================
         FUNDO
         ========================================== -->

    <div class="background">

        <div class="glow glow-1"></div>

        <div class="glow glow-2"></div>

        <div class="math-symbol symbol-1">+</div>

        <div class="math-symbol symbol-2">÷</div>

        <div class="math-symbol symbol-3">×</div>

        <div class="math-symbol symbol-4">π</div>

        <div class="math-symbol symbol-5">√</div>

    </div>


    <!-- ==========================================
         HEADER
         ========================================== -->

    <header class="navbar">

        <a
            href="index.php"
            class="brand"
        >
            Math<span>Run</span>
        </a>


        <a
            href="login.php"
            class="login-link"
        >
            Já tenho uma conta
        </a>

    </header>


    <!-- ==========================================
         CADASTRO
         ========================================== -->

    <main class="cadastro-container">


        <section class="cadastro-card">


            <div class="card-header">

                <span class="tag">
                    BORA COMEÇAR?
                </span>

                <h1>
                    Crie sua conta.
                </h1>

                <p>
                    Entre no MathRun e comece
                    sua jornada matemática.
                </p>

            </div>


            <!-- ==================================
                 MENSAGENS
                 ================================== -->

            <?php if ($erro !== ""): ?>

                <div class="mensagem erro">
                    <?= htmlspecialchars($erro) ?>
                </div>

            <?php endif; ?>


            <?php if ($sucesso !== ""): ?>

                <div class="mensagem sucesso">
                    <?= htmlspecialchars($sucesso) ?>
                </div>

            <?php endif; ?>


            <!-- ==================================
                 FORMULÁRIO
                 ================================== -->

            <form
                method="POST"
                action=""
                class="formulario"
            >


                <!-- NOME -->

                <div class="campo">

                    <label for="nome">
                        Seu nome
                    </label>

                    <input
                        type="text"
                        id="nome"
                        name="nome"
                        placeholder="Como podemos te chamar?"
                        value="<?= htmlspecialchars($_POST['nome'] ?? '') ?>"
                        autocomplete="name"
                        required
                    >

                </div>


                <!-- EMAIL -->

                <div class="campo">

                    <label for="email">
                        E-mail
                    </label>

                    <input
                        type="email"
                        id="email"
                        name="email"
                        placeholder="seuemail@email.com"
                        value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                        autocomplete="email"
                        required
                    >

                </div>


                <!-- SENHA -->

                <div class="campo">

                    <label for="senha">
                        Senha
                    </label>

                    <input
                        type="password"
                        id="senha"
                        name="senha"
                        placeholder="Mínimo de 6 caracteres"
                        autocomplete="new-password"
                        required
                    >

                </div>


                <!-- CONFIRMAR SENHA -->

                <div class="campo">

                    <label for="confirmar_senha">
                        Confirmar senha
                    </label>

                    <input
                        type="password"
                        id="confirmar_senha"
                        name="confirmar_senha"
                        placeholder="Digite a senha novamente"
                        autocomplete="new-password"
                        required
                    >

                </div>


                <!-- BOTÃO -->

                <button
                    type="submit"
                    class="btn-cadastrar"
                >

                    <span>
                        Criar minha conta
                    </span>

                    <strong>
                        →
                    </strong>

                </button>


            </form>


            <p class="login-mobile">

                Já possui uma conta?

                <a href="login.php">
                    Entrar
                </a>

            </p>


        </section>


        <!-- ======================================
             LADO VISUAL
             ====================================== -->

        <section class="cadastro-visual">


            <div class="visual-content">

                <div class="xp-icon">
                    XP
                </div>

                <h2>
                    Sua jornada
                    começa aqui.
                </h2>

                <p>
                    Resolva desafios, conquiste
                    XP e desbloqueie novas fases.
                </p>


                <div class="progress-example">

                    <div class="progress-info">

                        <span>
                            LEVEL 01
                        </span>

                        <strong>
                            0 / 500 XP
                        </strong>

                    </div>


                    <div class="progress-bar">

                        <div></div>

                    </div>

                </div>

            </div>


        </section>


    </main>


</body>

</html>