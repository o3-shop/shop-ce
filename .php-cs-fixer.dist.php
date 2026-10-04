<?php

$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__)
    ->exclude([
        'vendor',
        'var',
        'tmp',
        'cache',
        'logs',
        'node_modules',
        'coverage',
        'out',
    ])
    ->name('*.php')
    ->notName('*.phtml')
    ->ignoreDotFiles(true)
    ->ignoreVCS(true)
    // Skip what .gitignore lists, above all the nested module/theme clones
    // (tinymce-editor, o3-theme, wave, testing-library, …): separate repos
    // with their own code style (o3-shop/o3-shop#242). New nested clones
    // MUST be added to .gitignore, or this run will rewrite them.
    ->ignoreVCSIgnored(true);

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(true)
    ->setRules([
        '@PSR12' => true,

        // Basic rules for better code quality
        'array_syntax' => ['syntax' => 'short'],
        'binary_operator_spaces' => [
            'default' => 'single_space',
            'operators' => ['=>' => null]
        ],
        'blank_line_after_opening_tag' => true,
        'concat_space' => ['spacing' => 'one'],
        'method_argument_space' => [
            'on_multiline' => 'ensure_fully_multiline'
        ],
        'no_extra_blank_lines' => [
            'tokens' => [
                'curly_brace_block',
                'extra',
                'parenthesis_brace_block',
                'square_brace_block',
                'throw',
                'use'
            ]
        ],
        'no_unused_imports' => true,
        'ordered_imports' => [
            'sort_algorithm' => 'alpha'
        ],
        'single_quote' => true,
        'trailing_comma_in_multiline' => true,
    ])
    ->setFinder($finder);