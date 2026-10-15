<?php

declare(strict_types=1);

use PhpCsFixer\Config;
use PhpCsFixer\Finder;
use PhpCsFixer\Runner\Parallel\ParallelConfigFactory;

$finder = Finder::create()
    ->in('src')
    ->in('tests')
;

$config = new Config();

return $config
    ->setParallelConfig(ParallelConfigFactory::detect())
    ->setRules([
        '@PhpCsFixer' => true,
        '@PhpCsFixer:risky' => false,
        '@Symfony' => true,
        '@Symfony:risky' => false,
        '@DoctrineAnnotation' => false,
        'final_internal_class' => false,
        'native_constant_invocation' => false,
        'native_function_invocation' => false,
        'single_line_throw' => false,
        'global_namespace_import' => [
            'import_constants' => false,
            'import_functions' => false,
            'import_classes' => true,
        ],
        'php_unit_strict' => false,
        'php_unit_internal_class' => false,
        'php_unit_test_class_requires_covers' => false,
        'phpdoc_to_comment' => false,
        'return_assignment' => false,
    ])
    ->setRiskyAllowed(false)
    ->setFinder($finder)
;