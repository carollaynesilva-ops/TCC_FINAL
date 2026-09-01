<?php
session_start();

if (isset($_SESSION['usuario_id'])) {
    header("Location: inicio.php");
    exit;
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

    <meta
        name="description"
        content="MathRun - Aprenda matemática jogando."
    >

    <title>MathRun | Aprenda jogando</title>

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

    <link
        rel="stylesheet"
        href="assets/css/style.css"
    >

</head>


<body data-theme="claro">


    <!-- ==================================================
         FUNDO
         ================================================== -->

    <div class="background">

        <div class="glow glow-1"></div>

        <div class="glow glow-2"></div>

        <div class="grid"></div>


        <div class="math-symbol symbol-1">+</div>

        <div class="math-symbol symbol-2">÷</div>

        <div class="math-symbol symbol-3">×</div>

        <div class="math-symbol symbol-4">=</div>

        <div class="math-symbol symbol-5">π</div>

        <div class="math-symbol symbol-6">√</div>

        <div class="math-symbol symbol-7">½</div>

        <div class="math-symbol symbol-8">%</div>

    </div>


    <!-- ==================================================
         HEADER
         ================================================== -->

    <header class="navbar">

        <a
            href="index.php"
            class="brand"
        >
            Math<span>Run</span>
        </a>


        <div class="navbar-right">

            <div class="theme-switch">

                <button
                    type="button"
                    onclick="mudarTema('claro')"
                    title="Tema claro"
                >
                    ☀
                </button>

                <button
                    type="button"
                    onclick="mudarTema('escuro')"
                    title="Tema escuro"
                >
                    ◐
                </button>

                <button
                    type="button"
                    onclick="mudarTema('rosa')"
                    title="Tema rosa"
                >
                    ♡
                </button>

            </div>

        </div>

    </header>


    <!-- ==================================================
         CONTEÚDO
         ================================================== -->

    <main class="hero">


        <div class="hero-content">


            <div class="badge">

                <span class="badge-dot"></span>

                MATEMÁTICA + DESAFIOS + DIVERSÃO

            </div>


            <h1>

                Quantos níveis você aguentaria 
                <span>antes de travar?</span>

            </h1>


            <p>

                Aceite o desafio no MathRun, conquiste XP a cada segundo e mostre do que você é capaz.

            </p>


            <div class="hero-buttons">

                <a
                    href="cadastro.php"
                    class="btn-primary"
                >

                    <span>Começar agora</span>

                    <strong>→</strong>

                </a>


                <a
                    href="login.php"
                    class="btn-secondary"
                >

                    Já tenho uma conta

                </a>

            </div>


            <div class="mini-stats">

                <div>

                    <strong>XP</strong>

                    <span>Ganhe pontos</span>

                </div>


                <div class="stats-line"></div>


                <div>

                    <strong>🏆</strong>

                    <span>Conquiste medalhas</span>

                </div>


                <div class="stats-line"></div>


                <div>

                    <strong>↗</strong>

                    <span>Suba de nível</span>

                </div>

            </div>


        </div>


        <!-- ==================================================
             ELEMENTO VISUAL
             ================================================== -->

        <div class="hero-visual">


            <div class="game-card">


                <div class="card-top">

                    <span class="card-label">
                        MATHRUN
                    </span>

                    <span class="card-level">
                        LVL 01
                    </span>

                </div>


                <div class="card-question">

                    <span>DESAFIO</span>

                    <strong>

                        1/4 de 1kg

                    </strong>

                </div>


                <div class="answers">

                    <div>25g</div>

                    <div class="correct">250g</div>

                    <div>400g</div>

                    <div>750g</div>

                </div>


                <div class="card-footer">

                    <span>+100 XP</span>

                    <span>● ● ● ○</span>

                </div>


            </div>


            <div class="floating-xp">

                <strong>+100</strong>

                <span>XP</span>

            </div>


            <div class="floating-level">

                <span>LEVEL</span>

                <strong>UP!</strong>

            </div>


        </div>

    </main>


    <!-- ==================================================
         FOOTER
         ================================================== -->

    <footer>

        <span>
            © 2026 MathRun
        </span>

        <span>
            Aprenda. Jogue. Evolua.
        </span>

    </footer>


    <script src="assets/js/tema.js"></script>

</body>

</html>