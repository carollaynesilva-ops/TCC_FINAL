(() => {
    'use strict';

    const STORAGE_KEY = 'mathrun-acessibilidade-v1';
    const root = document.documentElement;
    const body = document.body;
    const trigger = document.getElementById('a11y-trigger');
    const panel = document.getElementById('a11y-panel');
    const closeButton = document.getElementById('a11y-close');
    const contrastInput = document.getElementById('a11y-contrast');
    const linksInput = document.getElementById('a11y-links');
    const motionInput = document.getElementById('a11y-motion');
    const resetButton = document.getElementById('a11y-reset');
    const status = document.getElementById('a11y-status');
    const fontButtons = [...document.querySelectorAll('[data-a11y-font]')];
    const textSelector = 'h1, h2, h3, h4, h5, h6, p, span, a, button, input, select, textarea, label, li, td, th, small, strong, em, legend, summary, figcaption, dt, dd, blockquote, caption';

    if (!trigger || !panel || !body) return;

    const defaults = { font: 0, contrast: false, links: false, reducedMotion: false };
    let settings = { ...defaults };

    function readSettings() {
        try {
            const saved = localStorage.getItem(STORAGE_KEY);
            if (saved) settings = { ...defaults, ...JSON.parse(saved) };
        } catch (_) {
            settings = { ...defaults };
        }
        settings.font = [-1, 0, 1].includes(Number(settings.font)) ? Number(settings.font) : 0;
        settings.contrast = Boolean(settings.contrast);
        settings.links = Boolean(settings.links);
        settings.reducedMotion = Boolean(settings.reducedMotion);
    }

    function saveSettings() {
        try { localStorage.setItem(STORAGE_KEY, JSON.stringify(settings)); } catch (_) { /* Armazenamento pode estar bloqueado. */ }
    }

    function applyTextScale() {
        const scale = settings.font === -1 ? 0.92 : settings.font === 1 ? 1.12 : 1;
        const elements = [...document.querySelectorAll(textSelector)];
        // Captura os tamanhos originais em uma primeira passada, antes de alterar qualquer elemento.
        elements.forEach(element => {
            if (!element.hasAttribute('data-a11y-base-font')) {
                element.dataset.a11yBaseFont = window.getComputedStyle(element).fontSize;
                element.dataset.a11yInlineFont = element.style.getPropertyValue('font-size');
                element.dataset.a11yInlinePriority = element.style.getPropertyPriority('font-size');
            }
        });
        elements.forEach(element => {
            if (scale === 1) {
                if (element.dataset.a11yInlineFont) {
                    element.style.setProperty('font-size', element.dataset.a11yInlineFont, element.dataset.a11yInlinePriority);
                } else {
                    element.style.removeProperty('font-size');
                }
            } else {
                const baseSize = Number.parseFloat(element.dataset.a11yBaseFont);
                if (Number.isFinite(baseSize)) element.style.setProperty('font-size', `${baseSize * scale}px`);
            }
        });
    }

    function applySettings(announce = false) {
        applyTextScale();
        root.classList.toggle('a11y-high-contrast', settings.contrast);
        body.classList.toggle('a11y-high-contrast', settings.contrast);
        body.classList.toggle('a11y-links-on', settings.links);
        body.classList.toggle('a11y-reduced-motion', settings.reducedMotion);
        contrastInput.checked = settings.contrast;
        linksInput.checked = settings.links;
        motionInput.checked = settings.reducedMotion;
        fontButtons.forEach(button => {
            button.setAttribute('aria-pressed', String(Number(button.dataset.a11yFont) === settings.font));
        });
        if (announce && status) status.textContent = 'Preferências de acessibilidade atualizadas.';
    }

    function setPanel(open) {
        panel.hidden = !open;
        trigger.setAttribute('aria-expanded', String(open));
        trigger.setAttribute('aria-label', open ? 'Fechar opções de acessibilidade' : 'Abrir opções de acessibilidade');
        if (open) closeButton.focus();
        else trigger.focus();
    }

    trigger.addEventListener('click', () => setPanel(panel.hidden));
    closeButton.addEventListener('click', () => setPanel(false));
    document.addEventListener('keydown', event => {
        if (event.key === 'Escape' && !panel.hidden) setPanel(false);
    });

    fontButtons.forEach(button => {
        button.addEventListener('click', () => {
            settings.font = Number(button.dataset.a11yFont);
            saveSettings();
            applySettings(true);
        });
    });
    contrastInput.addEventListener('change', () => { settings.contrast = contrastInput.checked; saveSettings(); applySettings(true); });
    linksInput.addEventListener('change', () => { settings.links = linksInput.checked; saveSettings(); applySettings(true); });
    motionInput.addEventListener('change', () => { settings.reducedMotion = motionInput.checked; saveSettings(); applySettings(true); });
    resetButton.addEventListener('click', () => {
        settings = { ...defaults };
        saveSettings();
        applySettings();
        if (status) status.textContent = 'Preferências restauradas.';
    });

    readSettings();
    applySettings();

    // Aplica a escala também a conteúdos que forem inseridos dinamicamente.
    const observer = new MutationObserver(mutations => {
        if (settings.font === 0) return;
        if (mutations.some(mutation => mutation.addedNodes.length > 0)) applyTextScale();
    });
    observer.observe(body, { childList: true, subtree: true });
})();
