<?php

declare(strict_types=1);
use PhpCsFixer\Config;
use PhpCsFixer\Finder;

$basePath = __DIR__.'/../../';

$finder = (new Finder())
    ->in([
        $basePath.'src',
        $basePath.'tests',
        $basePath.'tools',
    ]);

return (new Config())
    ->setCacheFile(__DIR__.'/tools/php-cs-fixer/.cache')
    ->setRiskyAllowed(true)
    ->setRules([
        '@DoctrineAnnotation' => true,
        '@PHP80Migration' => true,
        '@PHP80Migration:risky' => true,
        '@Symfony' => true,
        '@Symfony:risky' => true,
        '@PSR2' => true,
        '@PhpCsFixer' => true,
        '@PhpCsFixer:risky' => true,
        'random_api_migration' => true,
        'dir_constant' => true,
        'modernize_types_casting' => true,
        'final_internal_class' => true,
        'php_unit_strict' => [
            'assertions' => [
                'assertAttributeEquals',
                'assertAttributeNotEquals',
                //                    'assertEquals', // This will replace all assertEquals with assertSame that can affect array comparisons
                'assertNotEquals',
            ],
        ],
        'php_unit_test_case_static_method_calls' => ['call_type' => 'this'],
        'strict_comparison' => true,
        'strict_param' => true,
        'date_time_immutable' => true,
        'mb_str_functions' => true,
        'not_operator_with_space' => false,
        'not_operator_with_successor_space' => false,
        'ordered_interfaces' => true,
        'php_unit_size_class' => true,
        'phpdoc_order' => ['order' => ['param', 'return', 'throws']],
        'phpdoc_to_return_type' => true,
        'static_lambda' => true,

        'yoda_style' => ['equal' => false, 'identical' => false, 'less_and_greater' => false],
        'php_unit_method_casing' => ['case' => 'snake_case'],
        'ordered_class_elements' => false,
        'multiline_whitespace_before_semicolons' => ['strategy' => 'no_multi_line'],
        'phpdoc_align' => ['align' => 'left'],
        'phpdoc_to_comment' => false,
    ])
    ->setFinder($finder);
