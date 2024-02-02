<?php
$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__)
    ->exclude('.robo')
    ->exclude('bin')
    ->exclude('vendor')
    ->notPath('tests/_data')
    ->notPath('tests/_output')
    ->notPath('tests/_support/_generated')
;

$cacheDir = __DIR__.'/.robo/cache';
if (!is_dir($cacheDir)) {
    mkdir($cacheDir, 0777, true);
}
return (new PhpCsFixer\Config())
//    ->setUsingCache(false)
    ->setCacheFile($cacheDir.'/.php-cs-fixer.cache')
    ->setRules([
        '@Symfony' => true,
        'global_namespace_import' => [
            'import_classes' => true,
            'import_constants' => true,
            'import_functions' => false,
        ],
        'no_superfluous_phpdoc_tags' => true,
        'no_superfluous_elseif' => true,
        'phpdoc_add_missing_param_annotation' => true,
    ])
    ->setFinder($finder)
;
