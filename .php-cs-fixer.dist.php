<?php

$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__)
    ->exclude('var')
;

return (new PhpCsFixer\Config())
    ->setRules([
        '@Symfony' => true,
        '@Symfony:risky' => true,
        'array_syntax' => ['syntax' => 'short'],
        'blank_line_before_statement' => false,
        'braces' => ['allow_single_line_closure' => true],
        'heredoc_to_nowdoc' => false,
        'no_unreachable_default_argument_value' => false,
        'ordered_imports' => ['imports_order' => ['class', 'function', 'const']],
        'phpdoc_annotation_without_dot' => false,
        'phpdoc_order' => true,
        'phpdoc_separation' => false,
        'phpdoc_summary' => false,
        'trailing_comma_in_multiline' => false,
        'yoda_style' => false,
        'no_superfluous_phpdoc_tags' => true,
        'native_constant_invocation' => false,
        'native_function_invocation' => false,
        'declare_strict_types' => false,

        '@DoctrineAnnotation' => true,
        'doctrine_annotation_indentation' => [
            'indent_mixed_lines' => true,
        ],
        'single_line_throw' => false,
        'phpdoc_trim_consecutive_blank_line_separation' => false,
        'global_namespace_import' => [
            'import_constants' => false,
            'import_functions' => false,
            'import_classes' => true,
        ],
        'nullable_type_declaration_for_default_null_value' => false,
    ])
    ->setRiskyAllowed(true)
    ->setFinder($finder)
;
