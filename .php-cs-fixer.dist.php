<?php

$finder = (new PhpCsFixer\Finder())->in(__DIR__.'/src');

return (new PhpCsFixer\Config())
    ->setRules([
        '@Symfony' => true,
        'array_syntax' => ['syntax' => 'short'],
        'ordered_imports' => [
            'imports_order' => ['class', 'const', 'function'],
            'sort_algorithm' => 'alpha',
        ],
    ])
    ->setFinder($finder);
