<?php
/**
 * Botão e painel global de acessibilidade.
 * Inclua este arquivo antes de </body> de cada página visual.
 */
if (!isset($mathrunUrlPrefix)) {
    $mathrunProjectRoot = realpath(__DIR__ . '/..');
    $mathrunDocumentRoot = realpath($_SERVER['DOCUMENT_ROOT'] ?? '');
    $mathrunUrlPrefix = '';
    if ($mathrunProjectRoot && $mathrunDocumentRoot && strpos($mathrunProjectRoot, $mathrunDocumentRoot) === 0) {
        $mathrunRelativeRoot = trim(str_replace('\\', '/', substr($mathrunProjectRoot, strlen($mathrunDocumentRoot))), '/');
        if ($mathrunRelativeRoot !== '') {
            $mathrunUrlPrefix = '/' . $mathrunRelativeRoot;
        }
    }
    $mathrunUrlPrefix = rtrim($mathrunUrlPrefix, '/');
}
?>
<div class="a11y-widget" id="a11y-widget">
    <button
        class="a11y-trigger"
        id="a11y-trigger"
        type="button"
        aria-expanded="false"
        aria-controls="a11y-panel"
        aria-label="Abrir opções de acessibilidade"
        title="Acessibilidade">
        <svg aria-hidden="true" viewBox="0 0 24 24" focusable="false">
            <circle cx="12" cy="4.5" r="2"></circle>
            <path d="M4 8.5h16M12 8.5v11M12 12l-5 7M12 12l5 7"></path>
        </svg>
    </button>

    <section class="a11y-panel" id="a11y-panel" aria-labelledby="a11y-title" hidden>
        <div class="a11y-panel-header">
            <div>
                <h2 id="a11y-title">Acessibilidade</h2>
                <p>Personalize sua experiência</p>
            </div>
            <button class="a11y-close" id="a11y-close" type="button" aria-label="Fechar opções de acessibilidade">&times;</button>
        </div>

        <div class="a11y-group" aria-labelledby="a11y-text-label">
            <span class="a11y-label" id="a11y-text-label">Tamanho do texto</span>
            <div class="a11y-button-row">
                <button type="button" data-a11y-font="-1" aria-label="Diminuir tamanho do texto">A−</button>
                <button type="button" data-a11y-font="0" aria-label="Tamanho normal do texto">A</button>
                <button type="button" data-a11y-font="1" aria-label="Aumentar tamanho do texto">A+</button>
            </div>
        </div>

        <div class="a11y-options">
            <label class="a11y-option" for="a11y-contrast">
                <span><strong>Alto contraste</strong><small>Reforça a diferença entre texto e fundo.</small></span>
                <input type="checkbox" id="a11y-contrast">
            </label>
            <label class="a11y-option" for="a11y-links">
                <span><strong>Destacar links</strong><small>Sublinha links para facilitar a identificação.</small></span>
                <input type="checkbox" id="a11y-links">
            </label>
            <label class="a11y-option" for="a11y-motion">
                <span><strong>Reduzir animações</strong><small>Reduz movimentos e transições decorativas.</small></span>
                <input type="checkbox" id="a11y-motion">
            </label>
        </div>

        <button class="a11y-reset" id="a11y-reset" type="button">Restaurar padrão</button>
        <p class="a11y-note" id="a11y-status" role="status" aria-live="polite"></p>
    </section>
</div>
<script src="<?= htmlspecialchars($mathrunUrlPrefix . '/assets/js/acessibilidade.js', ENT_QUOTES, 'UTF-8') ?>" defer></script>
