<?php
/**
 * Carrega o CSS global do módulo de acessibilidade.
 * Inclua este arquivo dentro do <head> de cada página visual.
 */
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
?>
<link rel="stylesheet" href="<?= htmlspecialchars($mathrunUrlPrefix . '/assets/css/acessibilidade.css', ENT_QUOTES, 'UTF-8') ?>">
