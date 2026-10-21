<?php
/*
 * MathSpace - página inicial
 * Local: TCC_FINAL/mathspace/paginas/mathspace.php
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/*
 * A página usa a configuração existente do MathRun.
 * O caminho é relativo a mathspace/paginas/.
 * A conexão com o banco será utilizada nas próximas etapas do jogo.
 */
$configPath = __DIR__ . '/../../config/config.php';
if (file_exists($configPath)) {
    require_once $configPath;
}

/* Ajuste o destino de login caso o projeto use outra rota. */
if (empty($_SESSION['usuario_id']) && empty($_SESSION['id_usuario']) && empty($_SESSION['id'])) {
    // Mantém a página acessível durante a montagem visual; ative o redirecionamento
    // depois de confirmar qual variável de sessão o login atual utiliza.
    // header('Location: ../../login.php');
    // exit;
}

$nomeUsuario = $_SESSION['nome'] ?? $_SESSION['nome_usuario'] ?? 'Explorador(a)';
$cssPath = '../css/mathspace.css';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#11152e">
    <title>MathSpace | MathRun</title>
    <link rel="stylesheet" href="<?= htmlspecialchars($cssPath, ENT_QUOTES, 'UTF-8') ?>">
</head>
<body>
    <a class="skip-link" href="#conteudo">Pular para o conteúdo principal</a>

    <header class="site-header">
        <a class="brand" href="../../inicio.php" aria-label="MathRun, página inicial">
            <span class="brand-mark" aria-hidden="true">M</span>
            <span>MathRun<span class="brand-dot">.</span></span>
        </a>

        <nav class="main-nav" aria-label="Navegação principal">
            <a href="../../inicio.php">Início</a>
            <a href="#sobre">Sobre o jogo</a>
            <a href="#acessibilidade">Acessibilidade</a>
        </nav>

        <div class="theme-switcher" role="group" aria-label="Escolher tema visual">
            <button type="button" data-theme-option="light" title="Tema claro" aria-label="Ativar tema claro" aria-pressed="false">☀</button>
            <button type="button" data-theme-option="dark" title="Tema escuro" aria-label="Ativar tema escuro" aria-pressed="true">☾</button>
            <button type="button" data-theme-option="pink" title="Tema rosa" aria-label="Ativar tema rosa" aria-pressed="false">♡</button>
        </div>
    </header>

    <main id="conteudo">
        <section class="hero" aria-labelledby="hero-title">
            <div class="hero-copy">
                <p class="eyebrow"><span class="status-dot" aria-hidden="true"></span> MISSÃO DISPONÍVEL · MATEMÁTICA + AVENTURA</p>
                <h1 id="hero-title">O universo precisa de <span>uma mente brilhante.</span></h1>
                <p class="hero-description">
                    Prepare sua nave, resolva desafios matemáticos e atravesse setores desconhecidos.
                    Cada resposta certa aproxima você da próxima missão.
                </p>

                <div class="hero-actions">
                    <a class="button button-primary" href="mapa_mathspace.php">
                        Explorar missões <span aria-hidden="true">↗</span>
                    </a>
                    <a class="button button-secondary" href="#sobre">Conhecer o jogo</a>
                </div>

                <ul class="feature-list" aria-label="Características do MathSpace">
                    <li><span aria-hidden="true">✦</span> Missões por série</li>
                    <li><span aria-hidden="true">⌁</span> Desafios matemáticos</li>
                    <li><span aria-hidden="true">♡</span> Seu progresso salvo</li>
                </ul>
            </div>

            <div class="space-art" role="img" aria-label="Ilustração decorativa de uma nave espacial roxa e azul viajando entre planetas e estrelas.">
                <div class="orbit orbit-one"></div>
                <div class="orbit orbit-two"></div>
                <div class="planet planet-large"><span></span></div>
                <div class="planet planet-small"></div>
                <div class="moon"></div>
                <div class="star star-one">✦</div>
                <div class="star star-two">✧</div>
                <div class="star star-three">·</div>
                <div class="ship">
                    <span class="ship-window"></span>
                    <span class="ship-wing ship-wing-left"></span>
                    <span class="ship-wing ship-wing-right"></span>
                    <span class="ship-flame"></span>
                </div>
                <div class="art-label label-top"><span aria-hidden="true">◎</span> SETOR 01</div>
                <div class="art-label label-bottom"><span aria-hidden="true">✦</span> PRONTO PARA DECOLAR</div>
            </div>
        </section>

        <section class="mission-strip" aria-label="Resumo da aventura">
            <div class="strip-item">
                <span class="strip-icon" aria-hidden="true">⌖</span>
                <div><strong>Explore</strong><span>Novos setores espaciais</span></div>
            </div>
            <div class="strip-item">
                <span class="strip-icon" aria-hidden="true">∑</span>
                <div><strong>Pense</strong><span>Use a matemática a seu favor</span></div>
            </div>
            <div class="strip-item">
                <span class="strip-icon" aria-hidden="true">♜</span>
                <div><strong>Evolua</strong><span>Conquiste pontos e progresso</span></div>
            </div>
        </section>

        <section class="content-section" id="sobre" aria-labelledby="about-title">
            <div class="section-heading">
                <p class="eyebrow">SUA JORNADA COMEÇA AQUI</p>
                <h2 id="about-title">Não é só chegar ao destino.<br><span>É aprender no caminho.</span></h2>
                <p>O MathSpace transforma cada missão em uma oportunidade de raciocinar, testar estratégias e avançar no seu ritmo.</p>
            </div>

            <div class="steps-grid">
                <article class="step-card">
                    <span class="step-number">01</span>
                    <span class="step-symbol" aria-hidden="true">⌘</span>
                    <h3>Escolha uma missão</h3>
                    <p>Encontre o setor adequado à sua série e avance fase por fase.</p>
                </article>
                <article class="step-card">
                    <span class="step-number">02</span>
                    <span class="step-symbol" aria-hidden="true">∑</span>
                    <h3>Enfrente os desafios</h3>
                    <p>Resolva questões matemáticas para se defender e superar os inimigos.</p>
                </article>
                <article class="step-card">
                    <span class="step-number">03</span>
                    <span class="step-symbol" aria-hidden="true">✧</span>
                    <h3>Avance no universo</h3>
                    <p>Ganhe pontos, acompanhe seu progresso e desbloqueie novas etapas.</p>
                </article>
            </div>
        </section>

        <section class="accessibility-panel" id="acessibilidade" aria-labelledby="accessibility-title">
            <div class="accessibility-icon" aria-hidden="true">◉</div>
            <div class="accessibility-copy">
                <p class="eyebrow">JOGAR TAMBÉM É PERTENCER</p>
                <h2 id="accessibility-title">Uma experiência mais acessível.</h2>
                <p>
                    A interface foi pensada para pessoas com baixa visão e para quem navega sem mouse:
                    contraste reforçado, texto redimensionável, foco de teclado visível e opção de reduzir animações.
                </p>
                <p class="accessibility-note">
                    <strong>Importante:</strong> estes recursos ajudam na navegação desta página. As próximas etapas
                    também precisarão aplicar acessibilidade às perguntas e à ação do jogo, incluindo controles de teclado
                    e alternativas à informação transmitida apenas por cores ou sons.
                </p>
            </div>
            <div class="accessibility-actions">
                <button class="button button-secondary" type="button" id="font-toggle" aria-pressed="false">Aumentar texto <span aria-hidden="true">A+</span></button>
                <button class="button button-secondary" type="button" id="motion-toggle" aria-pressed="false">Reduzir animações <span aria-hidden="true">◌</span></button>
            </div>
        </section>
    </main>

    <footer class="site-footer">
        <a class="brand footer-brand" href="../../inicio.php"><span class="brand-mark" aria-hidden="true">M</span><span>MathRun<span class="brand-dot">.</span></span></a>
        <p>Aprender também é uma forma de explorar o universo.</p>
        <span>MathSpace · Projeto educacional</span>
    </footer>

    <script>
    (() => {
        const root = document.documentElement;
        const themeButtons = [...document.querySelectorAll('[data-theme-option]')];
        const validThemes = ['light', 'dark', 'pink'];

        function applyTheme(theme) {
            if (!validThemes.includes(theme)) return;
            root.dataset.theme = theme;
            themeButtons.forEach((button) => {
                const active = button.dataset.themeOption === theme;
                button.setAttribute('aria-pressed', String(active));
            });
        }

        themeButtons.forEach((button) => {
            button.addEventListener('click', () => applyTheme(button.dataset.themeOption));
        });
        applyTheme('dark');

        const fontToggle = document.getElementById('font-toggle');
        fontToggle.addEventListener('click', () => {
            const enabled = fontToggle.getAttribute('aria-pressed') !== 'true';
            root.classList.toggle('large-text', enabled);
            fontToggle.setAttribute('aria-pressed', String(enabled));
            fontToggle.innerHTML = enabled
                ? 'Texto ampliado <span aria-hidden="true">A−</span>'
                : 'Aumentar texto <span aria-hidden="true">A+</span>';
        });

        const motionToggle = document.getElementById('motion-toggle');
        motionToggle.addEventListener('click', () => {
            const enabled = motionToggle.getAttribute('aria-pressed') !== 'true';
            root.classList.toggle('reduce-motion', enabled);
            motionToggle.setAttribute('aria-pressed', String(enabled));
            motionToggle.innerHTML = enabled
                ? 'Animações reduzidas <span aria-hidden="true">✓</span>'
                : 'Reduzir animações <span aria-hidden="true">◌</span>';
        });
    })();
    </script>
</body>
</html>
