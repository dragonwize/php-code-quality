<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use Dragonwize\PhpCodeQuality\PhpCsFixer\Rule\DeclareStrictOnOpeningLineFixer;
use Dragonwize\PhpCodeQuality\PhpCsFixer\Rule\PhpDocGenericsSpaceAfterCommaFixer;
use Dragonwize\PhpCodeQuality\PhpCsFixer\Rule\PhpDocTypeUnionNoSpaceFixer;
use Dragonwize\PhpCodeQuality\PhpCsFixer\Rule\PhpDocSingleLineVarFixer;
use Dragonwize\PhpCodeQuality\PhpCsFixer\Rule\VariableNameCaseFixer;
use PhpCsFixer\Config;
use PhpCsFixer\Finder;

$finder = new Finder()
    ->in(__DIR__)
    ->exclude([
        'vendor',
    ]);

return new Config()
    ->setRiskyAllowed(true)
    ->registerCustomFixers([
        new DeclareStrictOnOpeningLineFixer(),
        new PhpDocGenericsSpaceAfterCommaFixer(),
        new PhpDocSingleLineVarFixer(),
        new PhpDocTypeUnionNoSpaceFixer(),
        new VariableNameCaseFixer(),
    ])
    ->setRules([
        '@Symfony'                                      => true,
        '@Symfony:risky'                                => true,
        '@PHP8x5Migration'                              => true,
        '@PHP8x5Migration:risky'                        => true,
        '@PHPUnit91Migration:risky'                     => true,
        'Dragonwize/declare_strict_on_opening_line'     => true,
        'Dragonwize/variable_name_case'                 => true,
        'Dragonwize/php_doc_generics_space_after_comma' => true,
        'Dragonwize/php_doc_single_line_var'            => true,
        'Dragonwize/php_doc_type_union_no_space'        => true,
        'binary_operator_spaces'                        => [
            'operators' => [
                '=>' => 'align_single_space_by_scope',
                '='  => 'align_single_space',
            ],
        ],
        'class_definition'                              => [
            'single_line'                  => false,
            'inline_constructor_arguments' => false,
            'space_before_parenthesis'     => true,
        ],
        'concat_space'                                  => ['spacing' => 'one'],
        'fully_qualified_strict_types'                  => ['import_symbols' => true],
        'function_declaration'                          => ['closure_fn_spacing' => 'one'],
        'method_argument_space'                         => ['on_multiline' => 'ensure_fully_multiline'],
        'single_line_empty_body'                        => true,
        'whitespace_after_comma_in_array'               => ['ensure_single_space' => true],
        'yoda_style'                                    => [
            'equal'            => false,
            'identical'        => false,
            'less_and_greater' => false,
        ],
    ])
    ->setFinder($finder);
