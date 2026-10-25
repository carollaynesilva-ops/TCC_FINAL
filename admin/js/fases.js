document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.js-delete-form').forEach((form) => {
        form.addEventListener('submit', (event) => {
            const mensagem = form.dataset.confirm || 'Deseja realmente excluir este registro?';
            if (!window.confirm(mensagem)) event.preventDefault();
        });
    });
});
