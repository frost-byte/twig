<?php

//Do the bootstrap manually
//define('BPATH', __DIR__ . DIRECTORY_SEPARATOR);

$finder = PhpCsFixer\Finder::create();
//    ->in(__DIR__);

return PhpCsFixer\Config::create()
    ->setRules([
        '@PSR2' => true,
        'align_multiline_comment' => ['comment_type' => 'all_multiline'],
        'array_indentation' => true,
        'array_syntax' => ['syntax' => 'short'],
        'combine_consecutive_unsets' => true,
        'no_multiline_whitespace_before_semicolons' => true,
        'single_quote' => true,
        'binary_operator_spaces' => [
            'align_double_arrow' => true,
            'align_equals' => false,
        ],
        'blank_line_after_namespace' => true,
        'blank_line_after_opening_tag' => true,
        'blank_line_before_return' => true,
        'braces' => [
            'allow_single_line_closure' => true,
            'position_after_control_structures' => 'next',
            'position_after_functions_and_oop_constructs' => 'next',
        ],
        'cast_spaces' => ['space' => 'single'],
        'class_definition' => [
            'singleLine' => false,
            'singleItemSingleLine' => true,
            'multiLineExtendsEachSingleLine' => true,
        ],
        'concat_space' => ['spacing' => 'one'],
        'declare_equal_normalize' => ['space' => 'single'],
        'doctrine_annotation_braces' => ['syntax' => 'with_braces'],
        'doctrine_annotation_indentation' => ['indent_mixed_lines' => true],
        'function_declaration' => ['closure_function_spacing' => 'one'],
        'function_typehint_space' => true,
        'hash_to_slash_comment' => true,
        'include' => true,
        'lowercase_cast' => true,
        'method_argument_space' => [
            'on_multiline' => 'ensure_fully_multiline'
        ],
        'method_separation' => true,
        'no_extra_blank_lines' => [
            'curly_brace_block',
            'extra',
            'parenthesis_brace_block',
            'square_brace_block',
            'throw',
            'use',
        ],
        'no_multiline_whitespace_around_double_arrow' => true,
        'no_spaces_around_offset' => true,
        'no_whitespace_before_comma_in_array' => true,
        'no_whitespace_in_blank_line' => true,
        'object_operator_without_whitespace' => true,
        'single_blank_line_before_namespace' => true,
        'ternary_operator_spaces' => true,
        'trailing_comma_in_multiline_array' => true,
        'trim_array_spaces' => true,
        'unary_operator_spaces' => true,
        'whitespace_after_comma_in_array' => true,
    ])
    ->setLineEnding("\n")
    ->setFinder($finder);