<?php

$finder = PhpCsFixer\Finder::create()
    ->exclude('plugins/MessageStatisticsPlugin/lan')
    ->notPath(['.tpl.php'])
    ->in(__DIR__)
;

$config = new PhpCsFixer\Config();

return $config->setRules([
        '@PSR1' => true,
        '@PSR2' => true,
        '@Symfony' => true,
        'concat_space' => false,
        'phpdoc_no_alias_tag' => false,
        'yoda_style' => false,
        'array_syntax' => false,
        'no_superfluous_phpdoc_tags' => false,
        'ordered_imports' => [
            'sort_algorithm' => 'alpha',
            'imports_order' => ['class', 'function', 'const']
        ],
        'blank_line_after_namespace' => true,
        'visibility_required' => false,
        'phpdoc_to_comment' => false,
        'global_namespace_import' => false,
        'nullable_type_declaration_for_default_null_value' => false,
        'no_null_property_initialization' => false,
    ])
    ->setFinder($finder)
;
