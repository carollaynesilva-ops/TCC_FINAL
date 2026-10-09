
document.addEventListener('DOMContentLoaded', () => {
    const cards = document.querySelectorAll('.mission-card');

    cards.forEach((card, index) => {
        card.style.animationDelay = `${index * 80}ms`;
        card.classList.add('mission-card-visible');
    });

    const progress = document.querySelector('.progress-fill');

    if (progress) {
        const width = progress.style.width;
        progress.style.width = '0';

        requestAnimationFrame(() => {
            progress.style.width = width;
        });
    }
});


document.addEventListener('DOMContentLoaded', () => {
    // Anima a barra de progresso quando a página aparece.
    const progressBar = document.querySelector('.progress-fill');

    if (progressBar) {
        const target = Number(progressBar.dataset.progress) || 0;

        requestAnimationFrame(() => {
            progressBar.style.width = `${Math.min(100, Math.max(0, target))}%`;
        });
    }

    // Revela os cartões com um pequeno atraso entre eles.
    const cards = document.querySelectorAll('.mission-card');

    cards.forEach((card, index) => {
        card.style.setProperty('--mission-index', index);
    });

    // Movimento discreto do planeta principal, sem interferir no celular.
    const heroPlanet = document.querySelector('.hero-planet');

    if (heroPlanet && window.matchMedia('(hover: hover)').matches) {
        document.addEventListener('mousemove', (event) => {
            const x = (event.clientX / window.innerWidth - 0.5) * 8;
            const y = (event.clientY / window.innerHeight - 0.5) * 8;

            heroPlanet.style.marginLeft = `${x}px`;
            heroPlanet.style.marginTop = `${y}px`;
        });
    }
});