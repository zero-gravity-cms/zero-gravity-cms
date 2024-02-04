<?php

declare(strict_types=1);

use Rector\CodeQuality\Rector\Class_\InlineConstructorDefaultToPropertyRector;
use Rector\CodingStyle\Rector\Encapsed\EncapsedStringsToSprintfRector;
use Rector\CodingStyle\Rector\FuncCall\CountArrayToEmptyArrayComparisonRector;
use Rector\CodingStyle\Rector\Stmt\NewlineAfterStatementRector;
use Rector\Config\RectorConfig;
use Rector\PHPUnit\CodeQuality\Rector\Class_\PreferPHPUnitThisCallRector;
use Rector\PHPUnit\Rector\Class_\PreferPHPUnitSelfCallRector;
use Rector\PHPUnit\Set\PHPUnitSetList;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;
use Rector\Symfony\Set\SymfonySetList;
use Rector\ValueObject\PhpVersion;

return RectorConfig::configure()
    ->withPhpVersion(PhpVersion::PHP_82)
    ->withImportNames(
        importNames: true,
        importDocBlockNames: true,
        importShortClasses: true,
        removeUnusedImports: true,
    )
    ->withCache('.robo/cache/rector')

    ->withParallel(180, 16, 3)
    // ->withoutParallel()
    // ->withMemoryLimit('4096M')

    ->withPaths([
        __DIR__.'/RoboFile.php',
        __DIR__.'/src',
        __DIR__.'/tests/Unit',
    ])

    ->withSets([
        LevelSetList::UP_TO_PHP_82,
        // SymfonySetList::SYMFONY_28,
        // SymfonySetList::SYMFONY_30,
        // SymfonySetList::SYMFONY_31,
        // SymfonySetList::SYMFONY_32,
        // SymfonySetList::SYMFONY_33,
        // SymfonySetList::SYMFONY_34,
        // SymfonySetList::SYMFONY_40,
        // SymfonySetList::SYMFONY_41,
        // SymfonySetList::SYMFONY_42,
        // SymfonySetList::SYMFONY_43,
        // SymfonySetList::SYMFONY_44,
        // SymfonySetList::SYMFONY_50,
        // SymfonySetList::SYMFONY_51,
        // SymfonySetList::SYMFONY_52,
        // SymfonySetList::SYMFONY_53,
        // SymfonySetList::SYMFONY_54,
        // SymfonySetList::SYMFONY_60,
        // SymfonySetList::SYMFONY_61,
        // SymfonySetList::SYMFONY_62,
        // SymfonySetList::SYMFONY_63,
        SymfonySetList::SYMFONY_64,
        SymfonySetList::SYMFONY_CONSTRUCTOR_INJECTION,
        SymfonySetList::ANNOTATIONS_TO_ATTRIBUTES,
        // TwigSetList::TWIG_112,
        // TwigSetList::TWIG_127,
        // TwigSetList::TWIG_134,
        // TwigSetList::TWIG_140,
        // TwigSetList::TWIG_20,
        // TwigSetList::TWIG_240,
        // TwigSetList::TWIG_UNDERSCORE_TO_NAMESPACE,
        // PHPUnitSetList::PHPUNIT_40,
        // PHPUnitSetList::PHPUNIT_50,
        // PHPUnitSetList::PHPUNIT_60,
        // PHPUnitSetList::PHPUNIT_70,
        PHPUnitSetList::PHPUNIT_80,
        PHPUnitSetList::PHPUNIT_90,
        PHPUnitSetList::PHPUNIT_100,
        PHPUnitSetList::PHPUNIT_CODE_QUALITY,
        PHPUnitSetList::ANNOTATIONS_TO_ATTRIBUTES,

        SetList::CODE_QUALITY,
        SetList::CODING_STYLE,
        SetList::DEAD_CODE,
        SetList::EARLY_RETURN,
        SetList::INSTANCEOF,
        SetList::PRIVATIZATION,
        SetList::TYPE_DECLARATION,
    ])

    ->withRules([
        InlineConstructorDefaultToPropertyRector::class,
        PreferPHPUnitSelfCallRector::class,
    ])

    ->withSkip([
        CountArrayToEmptyArrayComparisonRector::class,
        EncapsedStringsToSprintfRector::class,
        NewlineAfterStatementRector::class,

        // using self instead of this
        PreferPHPUnitThisCallRector::class,
    ])
;
