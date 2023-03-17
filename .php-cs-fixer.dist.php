<?php

if (!file_exists(__DIR__.'/src')) {
    exit(0);
}

$finder = (new \PhpCsFixer\Finder())
    ->in(__DIR__.'/src')
;

return (new \PhpCsFixer\Config())
    ->setRules(array(
        '@PSR12' => true,
        '@PSR12:risky' => true,
        'yoda_style' => false,
    ))
    ->setRiskyAllowed(true)
    ->setFinder($finder)
;
