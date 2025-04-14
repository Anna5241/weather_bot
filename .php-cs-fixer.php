<?php

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

$finder = Finder::create()
    ->in([
        __DIR__.'/app',
        __DIR__.'/config',
        __DIR__.'/database',
        __DIR__.'/routes',
        __DIR__.'/tests',
    ]);

return (new Config())
    ->setRules([
        '@PSR2' => true,
        '@PSR1' => true,
        'array_syntax' => ['syntax' => 'short'],
        'ordered_imports' => true,
        'no_unused_imports' => true,
        'phpdoc_order' => true,
        'phpdoc_types_order' => ['null_adjustment' => 'always_last'],
        'single_line_comment_style' => ['comment_types' => ['hash']],
        'method_argument_space' => ['on_multiline' => 'ensure_fully_multiline'],
        'no_superfluous_phpdoc_tags' => true,
        'no_useless_else' => true,
        'no_useless_return' => true,
        'return_type_declaration' => true,
        'yoda_style' => false,
        // Добавьте другие правила по вашему усмотрению
    ])
    ->setFinder($finder);
