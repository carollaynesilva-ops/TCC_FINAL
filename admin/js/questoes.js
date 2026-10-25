document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('questao-form');
    const alternativasLista = document.getElementById('alternativas-lista');
    const dicasLista = document.getElementById('dicas-lista');
    const addAlternativa = document.getElementById('adicionar-alternativa');
    const addDica = document.getElementById('adicionar-dica');

    document.querySelectorAll('.js-delete-form').forEach((deleteForm) => {
        deleteForm.addEventListener('submit', (event) => {
            if (!window.confirm(deleteForm.dataset.confirm || 'Deseja realmente excluir?')) {
                event.preventDefault();
            }
        });
    });

    function reindexAlternativas() {
        if (!alternativasLista) return;
        const rows = [...alternativasLista.querySelectorAll('.alternative-row')];
        rows.forEach((row, index) => {
            const radio = row.querySelector('input[type="radio"]');
            const text = row.querySelector('input[type="text"]');
            const remove = row.querySelector('.remove-row');
            radio.value = String(index);
            radio.required = true;
            text.setAttribute('aria-label', `Texto da alternativa ${index + 1}`);
            if (remove) remove.disabled = rows.length <= 2;
        });
        if (rows.length && !rows.some(row => row.querySelector('input[type="radio"]').checked)) {
            rows[0].querySelector('input[type="radio"]').checked = true;
        }
    }

    function reindexDicas() {
        if (!dicasLista) return;
        [...dicasLista.querySelectorAll('.tip-row')].forEach((row, index) => {
            const order = row.querySelector('.tip-order');
            if (order) order.textContent = String(index + 1);
        });
    }

    function addAlternative(text = '', correct = false) {
        if (!alternativasLista) return;
        const row = document.createElement('div');
        row.className = 'dynamic-row alternative-row';
        row.innerHTML = `
            <label class="correct-choice"><input type="radio" name="alternativa_correta" value="0" ${correct ? 'checked' : ''} required><span>Correta</span></label>
            <input type="text" name="alternativas[]" maxlength="255" placeholder="Texto da alternativa" aria-label="Texto da alternativa">
            <button class="remove-row" type="button" aria-label="Remover alternativa" title="Remover alternativa">×</button>
        `;
        row.querySelector('input[type="text"]').value = text;
        alternativasLista.appendChild(row);
        reindexAlternativas();
        row.querySelector('input[type="text"]').focus();
    }

    function addTip(text = '', cost = 0) {
        if (!dicasLista) return;
        const row = document.createElement('div');
        row.className = 'dynamic-row tip-row';
        row.innerHTML = `
            <span class="tip-order"></span>
            <input type="text" name="dicas[]" placeholder="Texto da dica">
            <label class="cost-field"><span>XP</span><input type="number" name="custos_dicas[]" min="0" max="100000" value="0"></label>
            <button class="remove-row" type="button" aria-label="Remover dica" title="Remover dica">×</button>
        `;
        row.querySelector('input[type="text"]').value = text;
        row.querySelector('input[type="number"]').value = String(cost);
        dicasLista.appendChild(row);
        reindexDicas();
        row.querySelector('input[type="text"]').focus();
    }

    if (addAlternativa) addAlternativa.addEventListener('click', () => addAlternative());
    if (addDica) addDica.addEventListener('click', () => addTip());

    document.addEventListener('click', (event) => {
        const removeButton = event.target.closest('.remove-row');
        if (!removeButton) return;
        const row = removeButton.closest('.dynamic-row');
        if (!row) return;
        if (row.classList.contains('alternative-row')) {
            const rows = alternativasLista.querySelectorAll('.alternative-row');
            if (rows.length <= 2) {
                window.alert('Uma questão precisa ter pelo menos duas alternativas.');
                return;
            }
            const wasCorrect = row.querySelector('input[type="radio"]').checked;
            row.remove();
            reindexAlternativas();
            if (wasCorrect) {
                const first = alternativasLista.querySelector('.alternative-row input[type="radio"]');
                if (first) first.checked = true;
            }
        } else if (row.classList.contains('tip-row')) {
            row.remove();
            reindexDicas();
        }
    });

    if (form) {
        form.addEventListener('submit', (event) => {
            const alternatives = [...form.querySelectorAll('.alternative-row input[name="alternativas[]"]')];
            const filled = alternatives.filter(input => input.value.trim() !== '');
            const checked = form.querySelector('.alternative-row input[name="alternativa_correta"]:checked');
            if (filled.length < 2) {
                event.preventDefault();
                window.alert('Preencha pelo menos duas alternativas.');
                return;
            }
            if (!checked || !checked.closest('.alternative-row').querySelector('input[name="alternativas[]"]').value.trim()) {
                event.preventDefault();
                window.alert('Marque como correta uma alternativa que tenha texto preenchido.');
            }
        });
    }

    reindexAlternativas();
    reindexDicas();

    const gameFilter = document.getElementById('filtro-jogo');
    const gradeFilter = document.getElementById('filtro-serie');
    const phaseFilter = document.getElementById('filtro-fase');
    function filterPhaseOptions() {
        if (!phaseFilter) return;
        const game = gameFilter?.value || '';
        const grade = gradeFilter?.value || '';
        [...phaseFilter.options].forEach((option, index) => {
            if (index === 0) return;
            const matches = (!game || option.dataset.jogo === game) && (!grade || option.dataset.serie === grade);
            option.hidden = !matches;
            if (!matches && option.selected) phaseFilter.value = '';
        });
    }
    gameFilter?.addEventListener('change', filterPhaseOptions);
    gradeFilter?.addEventListener('change', filterPhaseOptions);
    filterPhaseOptions();
});
