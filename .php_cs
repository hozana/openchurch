<?php

$finder = PhpCsFixer\Finder::create()
    ->exclude('.dev')
    ->exclude('bin')
    ->exclude('data')
    ->exclude('node_modules')
    ->exclude('tools')
    ->exclude('var')
    ->exclude('vendor')
    ->exclude('web')
    ->in(__DIR__);

return PhpCsFixer\Config::create()
    ->setRules(array(
        '@Symfony' => true,
        '@Symfony:risky' => true,
        'array_syntax' => array('syntax' => 'short'),
        'braces' => array('allow_single_line_closure' => true),
        'heredoc_to_nowdoc' => false,
        'no_unreachable_default_argument_value' => false,
        'ordered_imports' => true,
        'phpdoc_annotation_without_dot' => false,
        'yoda_style' => null,
        'no_superfluous_phpdoc_tags' => true,
        'native_constant_invocation' => false,
    ))
    ->setRiskyAllowed(true)
    ->setUsingCache(false)
    ->setFinder($finder);
