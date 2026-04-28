<?php

/**
 * This file is part of the xAI PHP SDK.
 *
 * (c) 2026 Displace Technologies, LLC
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 *
 * This work was inspired by X.AI LLC's Python SDK.
 */

declare(strict_types=1);

$finder = PhpCsFixer\Finder::create()
    ->in([
        __DIR__ . '/src',
        __DIR__ . '/tests',
        __DIR__ . '/examples',
    ])
    ->name('*.php')
    ->notName('*.blade.php')
    ->ignoreDotFiles(true)
    ->ignoreVCS(true);

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(true)
    ->setParallelConfig(PhpCsFixer\Runner\Parallel\ParallelConfigFactory::sequential())
    ->setFinder($finder)
    ->setRules([
        // PSR-12 base ruleset
        '@PSR12' => true,
        '@PSR12:risky' => true,

        // PHP 8.4+ specific rules
        '@PHP84Migration' => true,

        // Strict types
        'declare_strict_types' => true,
        'strict_param' => true,
        'strict_comparison' => true,

        // Array syntax
        'array_syntax' => ['syntax' => 'short'],
        'list_syntax' => ['syntax' => 'short'],
        'trim_array_spaces' => true,
        'no_whitespace_before_comma_in_array' => true,
        'whitespace_after_comma_in_array' => ['ensure_single_space' => true],
        'array_indentation' => true,

        // Blank lines
        'blank_line_after_namespace' => true,
        'blank_line_after_opening_tag' => true,
        'blank_line_before_statement' => [
            'statements' => ['return', 'throw', 'try', 'if', 'switch', 'foreach', 'for', 'while', 'do'],
        ],
        'no_extra_blank_lines' => [
            'tokens' => ['curly_brace_block', 'extra', 'parenthesis_brace_block', 'square_brace_block', 'throw', 'use'],
        ],

        // Class/method rules
        'class_attributes_separation' => [
            'elements' => [
                'const' => 'one',
                'method' => 'one',
                'property' => 'one',
            ],
        ],
        'class_definition' => [
            'single_line' => true,
            'single_item_single_line' => true,
        ],
        'ordered_class_elements' => [
            'order' => [
                'use_trait',
                'case',
                'constant_public',
                'constant_protected',
                'constant_private',
                'property_public_static',
                'property_protected_static',
                'property_private_static',
                'property_public',
                'property_protected',
                'property_private',
                'construct',
                'destruct',
                'magic',
                'phpunit',
                'method_public_abstract',
                'method_protected_abstract',
                'method_public_static',
                'method_protected_static',
                'method_private_static',
                'method_public',
                'method_protected',
                'method_private',
            ],
        ],

        // Imports
        'no_unused_imports' => true,
        'ordered_imports' => [
            'sort_algorithm' => 'alpha',
            'imports_order' => ['class', 'function', 'const'],
        ],
        'global_namespace_import' => [
            'import_classes' => true,
            'import_constants' => true,
            'import_functions' => true,
        ],

        // Comments and PHPDoc
        'single_line_comment_style' => ['comment_types' => ['hash']],
        'multiline_comment_opening_closing' => true,
        'no_empty_comment' => true,
        'phpdoc_align' => ['align' => 'left'],
        'phpdoc_indent' => true,
        'phpdoc_no_empty_return' => true,
        'phpdoc_order' => true,
        'phpdoc_scalar' => true,
        'phpdoc_separation' => true,
        'phpdoc_single_line_var_spacing' => true,
        'phpdoc_summary' => true,
        'phpdoc_trim' => true,
        'phpdoc_trim_consecutive_blank_line_separation' => true,
        'phpdoc_types' => true,
        'phpdoc_types_order' => ['null_adjustment' => 'always_last', 'sort_algorithm' => 'none'],
        'phpdoc_var_without_name' => true,

        // Operators
        'binary_operator_spaces' => ['default' => 'single_space'],
        'concat_space' => ['spacing' => 'one'],
        'not_operator_with_successor_space' => true,
        'ternary_operator_spaces' => true,
        'ternary_to_null_coalescing' => true,
        'object_operator_without_whitespace' => true,

        // Control structures
        'no_superfluous_elseif' => true,
        'no_useless_else' => true,
        'simplified_if_return' => true,
        'trailing_comma_in_multiline' => [
            'elements' => ['arguments', 'arrays', 'match', 'parameters'],
        ],

        // Type declarations
        'fully_qualified_strict_types' => true,
        'no_null_property_initialization' => true,
        'nullable_type_declaration_for_default_null_value' => true,
        'return_type_declaration' => ['space_before' => 'none'],
        'void_return' => true,

        // Misc
        'final_class' => false, // Allow extension
        'no_empty_statement' => true,
        'no_leading_import_slash' => true,
        'no_trailing_comma_in_singleline' => true,
        'no_unneeded_control_parentheses' => true,
        'no_useless_return' => true,
        'semicolon_after_instruction' => true,
        'single_quote' => true,
        'yoda_style' => false, // Disable Yoda conditions
    ])
    ->setCacheFile(__DIR__ . '/.php-cs-fixer.cache');
