
document.addEventListener('DOMContentLoaded', () => {
    const mapa = document.getElementById('galaxyMap');
    const foguete = document.getElementById('rocketCursor');
    const destinos = document.querySelectorAll('[data-destination]');

    const visualSelecionado = document.getElementById('selectedVisual');
    const planetaSelecionado = document.getElementById('selectedPlanet');
    const simboloSelecionado = document.getElementById('selectedSymbol');

    const tipoSelecionado = document.getElementById('selectedType');
    const nomeSelecionado = document.getElementById('selectedName');
    const descricaoSelecionada = document.getElementById('selectedDescription');
    const dificuldadeSelecionada = document.getElementById('selectedDifficulty');
    const pontosSelecionados = document.getElementById('selectedPoints');
    const estadoSelecionado = document.getElementById('selectedStatus');

    const botaoLancamento = document.getElementById('launchButton');
    const textoLancamento = document.getElementById('launchText');
    const legendaLancamento = document.getElementById('launchCaption');

    let ultimoX = 0;
    let ultimoY = 0;

    /*
     * FOGUETE DO PONTEIRO
     * Ele aparece apenas dentro da área do mapa.
     */

    if (mapa && foguete) {
        mapa.addEventListener('pointerenter', (evento) => {
            if (evento.pointerType === 'touch') return;

            foguete.classList.add('active');
        });

        mapa.addEventListener('pointermove', (evento) => {
            if (evento.pointerType === 'touch') return;

            const x = evento.clientX;
            const y = evento.clientY;

            const diferencaX = x - ultimoX;
            const diferencaY = y - ultimoY;

            if (ultimoX !== 0 || ultimoY !== 0) {
                const angulo = Math.atan2(diferencaY, diferencaX);
                const graus = angulo * (180 / Math.PI);

                foguete.style.setProperty(
                    '--rocket-angle',
                    `${graus + 45}deg`
                );

                foguete.querySelector('.rocket-body').style.transform =
                    `rotate(${graus + 45}deg)`;
            }

            foguete.style.left = `${x}px`;
            foguete.style.top = `${y}px`;

            ultimoX = x;
            ultimoY = y;
        });

        mapa.addEventListener('pointerleave', () => {
            foguete.classList.remove('active');
            ultimoX = 0;
            ultimoY = 0;
        });
    }

    /*
     * BARRA DE PROGRESSO
     */

    document.querySelectorAll('[data-progress]').forEach((barra) => {
        const progresso = Number(barra.dataset.progress) || 0;

        barra.style.width = '0%';

        requestAnimationFrame(() => {
            barra.style.width =
                `${Math.min(100, Math.max(0, progresso))}%`;
        });
    });

    /*
     * SELEÇÃO DE PLANETAS E ATUALIZAÇÃO DO PAINEL
     */

    function selecionarDestino(destino) {
        if (
            !destino ||
            !nomeSelecionado ||
            !descricaoSelecionada ||
            !botaoLancamento
        ) {
            return;
        }

        destinos.forEach((item) => {
            const selecionado = item === destino;

            item.classList.toggle('selected', selecionado);
            item.setAttribute('aria-pressed', String(selecionado));
        });

        const nome = destino.dataset.name || 'Destino desconhecido';
        const descricao =
            destino.dataset.description || 'Nenhuma descrição disponível.';

        const tipo = destino.dataset.type || 'DESTINO DE EXPLORAÇÃO';
        const dificuldade = destino.dataset.difficulty || 'Não informada';

        const pontos = Number(destino.dataset.points) || 0;
        const concluida = destino.dataset.completed === '1';
        const desbloqueada = destino.dataset.unlocked === '1';

        const cor = getComputedStyle(destino)
            .getPropertyValue('--planet-color')
            .trim() || '#a9baff';

        const simbolo = destino
            .querySelector('.destination-marker')
            ?.textContent.trim() || '✦';

        if (simboloSelecionado) {
            simboloSelecionado.textContent = simbolo;
        }

        if (tipoSelecionado) {
            tipoSelecionado.textContent = tipo;
        }

        nomeSelecionado.textContent = nome;
        descricaoSelecionada.textContent = descricao;

        if (dificuldadeSelecionada) {
            dificuldadeSelecionada.textContent = dificuldade;
        }

        if (pontosSelecionados) {
            pontosSelecionados.textContent =
                `${pontos.toLocaleString('pt-BR')} PTS`;
        }

        /*
         * A aparência do planeta do painel muda de acordo
         * com o planeta escolhido no mapa.
         */

        if (planetaSelecionado) {
            planetaSelecionado.style.background = `
                radial-gradient(
                    ellipse at 28% 20%,
                    rgba(255,255,255,.9),
                    transparent 7%
                ),
                radial-gradient(
                    ellipse at 32% 28%,
                    color-mix(in srgb, ${cor} 75%, white),
                    ${cor} 42%,
                    #11152f 100%
                )
            `;

            planetaSelecionado.style.boxShadow = `
                inset -22px -15px 25px rgba(0,0,0,.75),
                inset 7px 6px 12px rgba(255,255,255,.16),
                0 0 32px color-mix(in srgb, ${cor} 35%, transparent)
            `;
        }

        if (visualSelecionado) {
            visualSelecionado.style.background = `
                radial-gradient(
                    ellipse at center,
                    color-mix(in srgb, ${cor} 22%, transparent),
                    transparent 70%
                ),
                rgba(8,13,32,.65)
            `;

            visualSelecionado
                .querySelectorAll('.selected-orbit')
                .forEach((orbita, indice) => {
                    orbita.style.borderColor = indice === 0
                        ? `color-mix(in srgb, ${cor} 75%, white 10%)`
                        : `color-mix(in srgb, ${cor} 40%, transparent)`;
                });
        }

        botaoLancamento.classList.remove('disabled');
        botaoLancamento.setAttribute('aria-disabled', 'false');

        if (concluida) {
            if (estadoSelecionado) {
                estadoSelecionado.textContent = 'MISSÃO CONCLUÍDA';
            }

            textoLancamento.textContent = 'Jogar novamente';

            legendaLancamento.textContent =
                'Revise o desafio e tente superar sua pontuação anterior.';

            botaoLancamento.href = destino.dataset.url || '#';

        } else if (desbloqueada) {
            if (estadoSelecionado) {
                estadoSelecionado.textContent = 'MISSÃO DISPONÍVEL';
            }

            textoLancamento.textContent = 'Iniciar missão';

            legendaLancamento.textContent =
                'Sistemas prontos. Prepare-se para a próxima viagem.';

            botaoLancamento.href = destino.dataset.url || '#';

        } else {
            if (estadoSelecionado) {
                estadoSelecionado.textContent = 'ACESSO RESTRITO';
            }

            textoLancamento.textContent = 'Destino bloqueado';

            legendaLancamento.textContent =
                'Conclua a missão anterior para liberar esta região.';

            botaoLancamento.href = '#';
            botaoLancamento.classList.add('disabled');
            botaoLancamento.setAttribute('aria-disabled', 'true');
        }
    }

    destinos.forEach((destino) => {
        destino.addEventListener('click', () => {
            selecionarDestino(destino);
        });

        /*
         * Permite navegar pelos planetas com teclado.
         */

        destino.addEventListener('keydown', (evento) => {
            if (evento.key === 'Enter' || evento.key === ' ') {
                evento.preventDefault();
                selecionarDestino(destino);
            }
        });
    });

    if (botaoLancamento) {
        botaoLancamento.addEventListener('click', (evento) => {
            if (
                botaoLancamento.getAttribute('aria-disabled') === 'true' ||
                botaoLancamento.getAttribute('href') === '#'
            ) {
                evento.preventDefault();
            }
        });
    }

    const destinoInicial =
        document.querySelector('[data-destination].selected') ||
        document.querySelector('[data-destination][data-unlocked="1"]') ||
        destinos[0];

    if (destinoInicial) {
        selecionarDestino(destinoInicial);
    }
});