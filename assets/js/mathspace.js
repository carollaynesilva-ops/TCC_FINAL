document.addEventListener('DOMContentLoaded', () => {
    const destinations = document.querySelectorAll('[data-destination]');

    const selectedVisual = document.getElementById('selectedVisual');
    const selectedPlanet = selectedVisual?.querySelector('.selected-planet');

    const selectedSymbol = document.getElementById('selectedSymbol');
    const selectedType = document.getElementById('selectedType');
    const selectedName = document.getElementById('selectedName');
    const selectedDescription = document.getElementById('selectedDescription');
    const selectedDifficulty = document.getElementById('selectedDifficulty');
    const selectedPoints = document.getElementById('selectedPoints');
    const selectedStatus = document.getElementById('selectedStatus');

    const launchButton = document.getElementById('launchButton');
    const launchText = document.getElementById('launchText');
    const launchCaption = document.getElementById('launchCaption');

    // Anima as barras de progresso da cabine.
    document.querySelectorAll('[data-progress]').forEach((bar) => {
        const progress = Number(bar.dataset.progress) || 0;

        requestAnimationFrame(() => {
            bar.style.width = `${Math.min(100, Math.max(0, progress))}%`;
        });
    });

    function selecionarDestino(destino) {
        destinations.forEach((item) => {
            const selecionado = item === destino;

            item.classList.toggle('selected', selecionado);
            item.setAttribute('aria-pressed', String(selecionado));
        });

        const nome = destino.dataset.name || 'Destino desconhecido';
        const descricao = destino.dataset.description || 'Nenhuma descrição disponível.';
        const tipo = destino.dataset.type || 'DESTINO DE EXPLORAÇÃO';
        const dificuldade = destino.dataset.difficulty || 'Não informada';
        const pontos = Number(destino.dataset.points) || 0;

        const concluida = destino.dataset.completed === '1';
        const desbloqueada = destino.dataset.unlocked === '1';
        const cor = getComputedStyle(destino)
            .getPropertyValue('--planet-color')
            .trim() || '#a9baff';

        selectedSymbol.textContent =
            destino.querySelector('.destination-symbol')?.textContent.trim() || '✦';

        selectedType.textContent = tipo;
        selectedName.textContent = nome;
        selectedDescription.textContent = descricao;
        selectedDifficulty.textContent = dificuldade;

        selectedPoints.textContent =
            `${pontos.toLocaleString('pt-BR')} PTS`;

        // Ajusta a aparência do planeta no painel de destino.
        if (selectedPlanet) {
            selectedPlanet.style.background = `
                radial-gradient(
                    circle at 30% 25%,
                    rgba(255,255,255,.7),
                    transparent 6%
                ),
                radial-gradient(
                    circle at 32% 28%,
                    color-mix(in srgb, ${cor} 75%, white),
                    ${cor} 46%,
                    #111833 100%
                )
            `;

            selectedPlanet.style.boxShadow = `
                inset -15px -14px 25px rgba(0,0,0,.55),
                inset 5px 5px 13px rgba(255,255,255,.13),
                0 0 33px color-mix(in srgb, ${cor} 35%, transparent)
            `;
        }

        if (selectedVisual) {
            selectedVisual.style.background = `
                radial-gradient(
                    ellipse at center,
                    color-mix(in srgb, ${cor} 22%, transparent),
                    transparent 70%
                ),
                rgba(8,13,32,.52)
            `;

            selectedVisual.querySelectorAll('.selected-orbit').forEach((orbit, index) => {
                orbit.style.borderColor = index === 0
                    ? `color-mix(in srgb, ${cor} 75%, white 10%)`
                    : `color-mix(in srgb, ${cor} 40%, transparent)`;
            });
        }

        // Atualiza o status e a possibilidade de iniciar a missão.
        launchButton.classList.remove('disabled');

        if (concluida) {
            selectedStatus.textContent = 'MISSÃO CONCLUÍDA';
            launchText.textContent = 'Jogar novamente';
            launchCaption.textContent =
                'Revise o desafio e tente superar sua pontuação anterior.';

            launchButton.href = destino.dataset.url;
            launchButton.setAttribute('aria-disabled', 'false');

        } else if (desbloqueada) {
            selectedStatus.textContent = 'MISSÃO DISPONÍVEL';
            launchText.textContent = 'Iniciar missão';
            launchCaption.textContent =
                'Sistemas prontos. Confirme sua próxima viagem.';

            launchButton.href = destino.dataset.url;
            launchButton.setAttribute('aria-disabled', 'false');

        } else {
            selectedStatus.textContent = 'ACESSO RESTRITO';
            launchText.textContent = 'Destino bloqueado';
            launchCaption.textContent =
                'Conclua a missão anterior para liberar esta região.';

            launchButton.href = '#';
            launchButton.classList.add('disabled');
            launchButton.setAttribute('aria-disabled', 'true');
        }
    }

    destinations.forEach((destino) => {
        destino.addEventListener('click', () => {
            selecionarDestino(destino);
        });
    });

    launchButton?.addEventListener('click', (event) => {
        if (launchButton.getAttribute('aria-disabled') === 'true') {
            event.preventDefault();
        }
    });

    const destinoInicial =
        document.querySelector('[data-destination].selected') ||
        document.querySelector('[data-destination][data-unlocked="1"]') ||
        destinations[0];

    if (destinoInicial) {
        selecionarDestino(destinoInicial);
    }
});