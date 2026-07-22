<?php

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__)
    ->exclude('.robo')
    ->exclude('bin')
    ->exclude('vendor')
    ->notPath('tests/_output')
    ->notPath('tests/Support/_data')
    ->notPath('tests/Support/_generated')
;

$cacheDir = __DIR__.'/.robo/cache';
if (!is_dir($cacheDir)) {
    mkdir($cacheDir, 0777, true);
}

return (new PhpCsFixer\Config())
//    ->setUsingCache(false)
    ->setCacheFile($cacheDir.'/.php-cs-fixer.cache')
    ->setParallelConfig(PhpCsFixer\Runner\Parallel\ParallelConfigFactory::detect())
    ->registerCustomFixers(new PhpCsFixerCustomFixers\Fixers())
    ->setRules([
        '@Symfony' => true,
        'global_namespace_import' => [
            'import_classes' => true,
            'import_constants' => true,
            'import_functions' => false,
        ],
        'multiline_promoted_properties' => [
            'keep_blank_lines' => true,
            'minimum_number_of_parameters' => 1,
        ],
        'no_superfluous_phpdoc_tags' => true,
        'no_superfluous_elseif' => true,
        'phpdoc_add_missing_param_annotation' => true,
        PhpCsFixerCustomFixers\Fixer\FunctionParameterSeparationFixer::name() => true,
    ])
    ->setFinder($finder)
;
