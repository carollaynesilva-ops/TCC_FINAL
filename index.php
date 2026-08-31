<?php
session_start();

// Se o usuário já estiver logado,
// vai direto para a área principal.
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
        content="CoraTech - Plataforma educacional gamificada para aprender matemática."
    >

    <title>CoraTech | Sua aventura matemática</title>

    <link rel="stylesheet" href="assets/css/style.css">

</head>


<body>

    <!-- =================================================
         ELEMENTOS DECORATIVOS DO FUNDO
         ================================================= -->

    <div class="background-math" aria-hidden="true">

        <span class="math-symbol symbol-1">+</span>
        <span class="math-symbol symbol-2">÷</span>
        <span class="math-symbol symbol-3">×</span>
        <span class="math-symbol symbol-4">=</span>
        <span class="math-symbol symbol-5">π</span>
        <span class="math-symbol symbol-6">½</span>
        <span class="math-symbol symbol-7">%</span>

    </div>


    <!-- =================================================
         CABEÇALHO
         ================================================= -->

    <header class="topbar">

        <a href="index.php" class="logo-link">

            <img
                src="assets/img/logo.png"
                alt="CoraTech"
                class="logo"
            >

        </a>


        <div class="topbar-direita">

            <span class="tema-texto">
                Tema
            </span>

            <div class="tema-seletor">

                <button
                    type="button"
                    onclick="mudarTema('claro')"
                    title="Tema claro"
                    aria-label="Ativar tema claro"
                >
                    ☀
                </button>

                <button
                    type="button"
                    onclick="mudarTema('escuro')"
                    title="Tema escuro"
                    aria-label="Ativar tema escuro"
                >
                    ◐
                </button>

                <button
                    type="button"
                    onclick="mudarTema('rosa')"
                    title="Tema rosa"
                    aria-label="Ativar tema rosa"
                >
                    ♡
                </button>

            </div>

        </div>

    </header>


    <!-- =================================================
         CONTEÚDO PRINCIPAL
         ================================================= -->

    <main>


        <!-- =================================================
             HERO
             ================================================= -->

        <section class="hero">

            <div class="hero-conteudo">


                <div class="hero-tag">

                    <span class="tag-ponto"></span>

                    PLATAFORMA EDUCACIONAL GAMIFICADA

                </div>


                <h1>

                    Sua aventura
                    <br>

                    <span>matemática</span>
                    começa aqui.

                </h1>


                <p class="hero-descricao">

                    Aprenda matemática através de desafios,
                    aventuras e jogos que transformam cada
                    problema em uma nova conquista.

                </p>


                <div class="hero-acoes">

                    <a
                        href="cadastro.php"
                        class="btn-principal"
                    >

                        Começar agora

                        <span class="btn-seta">
                            →
                        </span>

                    </a>


                    <a
                        href="login.php"
                        class="btn-login"
                    >

                        Já tenho uma conta

                    </a>

                </div>


                <!-- Pequena indicação de gamificação -->

                <div class="hero-status">

                    <div class="status-item">

                        <span class="status-icone">
                            ★
                        </span>

                        <div>
                            <strong>XP</strong>
                            <small>Ganhe pontos</small>
                        </div>

                    </div>


                    <div class="status-linha"></div>


                    <div class="status-item">

                        <span class="status-icone">
                            ◆
                        </span>

                        <div>
                            <strong>MEDALHAS</strong>
                            <small>Desbloqueie conquistas</small>
                        </div>

                    </div>


                    <div class="status-linha"></div>


                    <div class="status-item">

                        <span class="status-icone">
                            ↑
                        </span>

                        <div>
                            <strong>EVOLUA</strong>
                            <small>Acompanhe seu progresso</small>
                        </div>

                    </div>

                </div>

            </div>


            <!-- =================================================
                 UNIVERSOS DOS JOGOS
                 ================================================= -->

            <div class="universos">


                <!-- MATHCHEF -->

                <a
                    href="#mathchef"
                    class="universo universo-chef"
                >

                    <div class="universo-topo">

                        <span class="universo-numero">
                            01
                        </span>

                        <span class="universo-status">
                            EM DESTAQUE
                        </span>

                    </div>


                    <div class="universo-icone">

                        <div class="icone-circulo">
                            🍰
                        </div>

                    </div>


                    <div class="universo-info">

                        <span class="universo-label">
                            COZINHA MATEMÁTICA
                        </span>

                        <h2>
                            MathChef
                        </h2>

                        <p>
                            Misture ingredientes,
                            prepare receitas e domine
                            as frações.
                        </p>

                    </div>


                    <div class="universo-rodape">

                        <span>
                            Frações
                        </span>

                        <span>
                            Explorar →
                        </span>

                    </div>

                </a>


                <!-- MATHSPACE -->

                <a
                    href="#mathspace"
                    class="universo universo-space"
                >

                    <div class="universo-topo">

                        <span class="universo-numero">
                            02
                        </span>

                        <span class="universo-status">
                            EM BREVE
                        </span>

                    </div>


                    <div class="universo-icone">

                        <div class="icone-circulo">
                            🚀
                        </div>

                    </div>


                    <div class="universo-info">

                        <span class="universo-label">
                            MISSÃO ESPACIAL
                        </span>

                        <h2>
                            MathSpace
                        </h2>

                        <p>
                            Viaje pelo espaço enquanto
                            resolve desafios e conquista
                            novos planetas.
                        </p>

                    </div>


                    <div class="universo-rodape">

                        <span>
                            Desafios
                        </span>

                        <span>
                            Explorar →
                        </span>

                    </div>

                </a>


                <!-- Elemento central decorativo -->

                <div
                    class="universos-centro"
                    aria-hidden="true"
                >

                    <span>+</span>

                </div>

            </div>

        </section>


        <!-- =================================================
             FRASE INFERIOR
             ================================================= -->

        <section class="frase">

            <span class="linha"></span>

            <p>
                Cada desafio é um passo a mais.
            </p>

            <span class="linha"></span>

        </section>

    </main>


    <!-- =================================================
         RODAPÉ
         ================================================= -->

    <footer>

        <p>
            © 2026 CoraTech
        </p>

        <span>
            Aprender • Jogar • Evoluir
        </span>

    </footer>


    <!-- =================================================
         JAVASCRIPT
         ================================================= -->

    <script src="assets/js/tema.js"></script>

</body>

</html>