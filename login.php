<?php

session_start();

require_once 'config/config.php';

$erro = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST["email"] ?? "");
    $senha = $_POST["senha"] ?? "";

    if ($email === "" || $senha === "") {

        $erro = "Preencha todos os campos.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $erro = "Digite um e-mail válido.";

    } else {

        $sql = "
            SELECT
                id,
                nome,
                email,
                senha,
                tipo,
                nivel,
                xp,
                pontuacao_total
            FROM usuarios
            WHERE email = :email
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ":email" => $email
        ]);

        $usuario = $stmt->fetch();

        if (!$usuario) {

            $erro = "E-mail ou senha incorretos.";

        } elseif (!password_verify($senha, $usuario["senha"])) {

            $erro = "E-mail ou senha incorretos.";

        } else {

            // Criando a sessão do usuário
            $_SESSION["usuario_id"] = $usuario["id"];
            $_SESSION["usuario_nome"] = $usuario["nome"];
            $_SESSION["usuario_email"] = $usuario["email"];
            $_SESSION["usuario_tipo"] = $usuario["tipo"];
            $_SESSION["usuario_nivel"] = $usuario["nivel"];
            $_SESSION["usuario_xp"] = $usuario["xp"];
            $_SESSION["usuario_pontuacao"] = $usuario["pontuacao_total"];

            // Evita problemas de segurança com a sessão
            session_regenerate_id(true);

            // Redireciona para a página inicial do aluno
            header("Location: inicio.php");
            exit;
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

    <title>Login | MathRun</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">

    <link
        rel="preconnect"
        href="https://fonts.gstatic.com"
        crossorigin
    >

    <link
        href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&display=swap"
        rel="stylesheet"
    >

    <link
        rel="stylesheet"
        href="assets/css/login.css"
    >

</head>

<body>

    <div class="background">

        <div class="glow glow-1"></div>
        <div class="glow glow-2"></div>

        <div class="math-symbol symbol-1">+</div>
        <div class="math-symbol symbol-2">÷</div>
        <div class="math-symbol symbol-3">×</div>
        <div class="math-symbol symbol-4">π</div>
        <div class="math-symbol symbol-5">√</div>

    </div>

    <header class="navbar">

        <a href="index.php" class="brand">
            Math<span>Run</span>
        </a>

        <a href="cadastro.php" class="cadastro-link">
            Criar conta
        </a>

    </header>


    <main class="login-container">

        <section class="login-card">

            <div class="card-header">

                <span class="tag">
                    BEM-VINDO DE VOLTA
                </span>

                <h1>
                    Entre na sua conta.
                </h1>

                <p>
                    Continue sua jornada no MathRun.
                </p>

            </div>


            <?php if ($erro !== ""): ?>

                <div class="mensagem erro">
                    <?= htmlspecialchars($erro) ?>
                </div>

            <?php endif; ?>


            <form
                method="POST"
                action=""
                class="formulario"
            >

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


                <div class="campo">

                    <label for="senha">
                        Senha
                    </label>

                    <input
                        type="password"
                        id="senha"
                        name="senha"
                        placeholder="Digite sua senha"
                        autocomplete="current-password"
                        required
                    >

                </div>


                <button
                    type="submit"
                    class="btn-entrar"
                >

                    <span>
                        Entrar no MathRun
                    </span>

                    <strong>
                        →
                    </strong>

                </button>

            </form>


            <p class="cadastro-mobile">

                Ainda não possui uma conta?

                <a href="cadastro.php">
                    Criar conta
                </a>

            </p>

        </section>


        <section class="login-visual">

            <div class="visual-content">

                <div class="xp-icon">
                    XP
                </div>

                <h2>
                    Continue de onde parou.
                </h2>

                <p>
                    Seu progresso, suas conquistas e seus desafios estão esperando por você.
                </p>


                <div class="stats-example">

                    <div class="stat">

                        <span>
                            LEVEL
                        </span>

                        <strong>
                            01
                        </strong>

                    </div>


                    <div class="stat">

                        <span>
                            XP
                        </span>

                        <strong>
                            0
                        </strong>

                    </div>


                    <div class="stat">

                        <span>
                            PONTOS
                        </span>

                        <strong>
                            0
                        </strong>

                    </div>

                </div>

            </div>

        </section>

    </main>

</body>

</html>