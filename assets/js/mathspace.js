
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