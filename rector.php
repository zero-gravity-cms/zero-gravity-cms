<?php

declare(strict_types=1);

use Rector\CodingStyle\Rector\Encapsed\EncapsedStringsToSprintfRector;
use Rector\CodingStyle\Rector\FuncCall\CountArrayToEmptyArrayComparisonRector;
use Rector\CodingStyle\Rector\Stmt\NewlineAfterStatementRector;
use Rector\Config\RectorConfig;
use Rector\PHPUnit\CodeQuality\Rector\Class_\PreferPHPUnitSelfCallRector;

return RectorConfig::configure()
    ->withPhpSets(
        php84: true,
    )
    ->withImportNames(
        importNames: true,
        importDocBlockNames: true,
        importShortClasses: true,
        removeUnusedImports: true,
    )
    ->withCache('.robo/cache/rector')
    ->withParallel(180, 16, 3)
    ->withPaths([
        __DIR__.'/RoboFile.php',
        __DIR__.'/src',
        __DIR__.'/tests/Unit',
    ])

    ->withPreparedSets(
        deadCode: true,
        codeQuality: true,
        codingStyle: true,
        typeDeclarations: true,
        privatization: true,
        instanceOf: true,
        earlyReturn: true,
    )
    ->withComposerBased(
        twig: true,
        doctrine: true,
        phpunit: true,
        symfony: true,
    )
    ->withAttributesSets(all: true)
    ->withRules([
        PreferPHPUnitSelfCallRector::class,
    ])

    ->withSkip([
        CountArrayToEmptyArrayComparisonRector::class,
        EncapsedStringsToSprintfRector::class,
        NewlineAfterStatementRector::class,
    ])
;
