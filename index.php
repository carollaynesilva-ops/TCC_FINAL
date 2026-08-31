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
        content="CoraTech - Plataforma educacional gamificada."
    >

    <title>CoraTech | Aprenda de um jeito diferente</title>

    <link
        rel="stylesheet"
        href="assets/css/style.css"
    >

</head>


<body data-theme="claro">


    <!-- ================================================
         FUNDO MATEMÁTICO
         ================================================ -->

    <div
        class="background-math"
        aria-hidden="true"
    >

        <span class="math-symbol symbol-1">+</span>

        <span class="math-symbol symbol-2">÷</span>

        <span class="math-symbol symbol-3">×</span>

        <span class="math-symbol symbol-4">=</span>

        <span class="math-symbol symbol-5">π</span>

        <span class="math-symbol symbol-6">½</span>

        <span class="math-symbol symbol-7">%</span>

        <span class="math-symbol symbol-8">√</span>

        <span class="math-symbol symbol-9">∑</span>

    </div>


    <!-- ================================================
         HEADER
         ================================================ -->

    <header class="topbar">


        <a
            href="index.php"
            class="logo-link"
            aria-label="CoraTech - Página inicial"
        >

            <img
                src="assets/img/logo.png"
                alt="CoraTech"
                class="logo"
            >

        </a>


        <div class="topbar-direita">


            <div
                class="tema-seletor"
                aria-label="Selecionar tema"
            >

                <button
                    type="button"
                    onclick="mudarTema('claro')"
                    aria-label="Tema claro"
                    title="Tema claro"
                >
                    ☀
                </button>


                <button
                    type="button"
                    onclick="mudarTema('escuro')"
                    aria-label="Tema escuro"
                    title="Tema escuro"
                >
                    ◐
                </button>


                <button
                    type="button"
                    onclick="mudarTema('rosa')"
                    aria-label="Tema rosa"
                    title="Tema rosa"
                >
                    ♡
                </button>

            </div>

        </div>

    </header>


    <!-- ================================================
         CONTEÚDO PRINCIPAL
         ================================================ -->

    <main>


        <section class="hero-minimal">


            <!-- Pequeno detalhe de identificação -->

            <div class="hero-overline">

                <span></span>

                CORATECH

                <span></span>

            </div>


            <!-- Título principal -->

            <h1>

                Matemática

                <br>

                <span>de um jeito diferente.</span>

            </h1>


            <!-- Descrição -->

            <p class="hero-descricao">

                Uma nova forma de aprender, praticar
                e evoluir através de experiências
                interativas.

            </p>


            <!-- Ações -->

            <div class="hero-acoes">


                <a
                    href="cadastro.php"
                    class="btn-principal"
                >

                    Começar agora

                    <span>→</span>

                </a>


                <a
                    href="login.php"
                    class="btn-login"
                >

                    Já tenho uma conta

                </a>


            </div>


            <!-- Pequeno indicador -->

            <div class="hero-indicador">

                <span class="indicador-linha"></span>

                <span>
                    Aprender • Jogar • Evoluir
                </span>

                <span class="indicador-linha"></span>

            </div>


        </section>


    </main>


    <!-- ================================================
         FOOTER
         ================================================ -->

    <footer>

        <span>
            © 2026 CoraTech
        </span>

        <span>
            Educação • Tecnologia • Inovação
        </span>

    </footer>


    <script src="assets/js/tema.js"></script>

</body>

</html>