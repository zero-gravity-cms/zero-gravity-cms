<?php

declare(strict_types=1);

use Rector\Core\Configuration\Option;
use Rector\DeadCode\Rector\Assign\RemoveUnusedVariableAssignRector;
use Rector\DeadCode\Rector\Class_\RemoveUnusedDoctrineEntityMethodAndPropertyRector;
use Rector\DeadCode\Rector\ClassConst\RemoveUnusedClassConstantRector;
use Rector\DeadCode\Rector\ClassMethod\RemoveDelegatingParentCallRector;
use Rector\DeadCode\Rector\ClassMethod\RemoveUnusedParameterRector;
use Rector\DeadCode\Rector\MethodCall\RemoveDefaultArgumentValueRector;
use Rector\DeadCode\Rector\Property\RemoveSetterOnlyPropertyAndMethodCallRector;
use Rector\DeadCode\Rector\Property\RemoveUnusedPrivatePropertyRector;
use Rector\EarlyReturn\Rector\Foreach_\ChangeNestedForeachIfsToEarlyContinueRector;
use Rector\EarlyReturn\Rector\If_\ChangeAndIfToEarlyReturnRector;
use Rector\EarlyReturn\Rector\If_\ChangeIfElseValueAssignToEarlyReturnRector;
use Rector\EarlyReturn\Rector\If_\ChangeNestedIfsToEarlyReturnRector;
use Rector\EarlyReturn\Rector\If_\RemoveAlwaysElseRector;
use Rector\Php56\Rector\FunctionLike\AddDefaultValueForUndefinedVariableRector;
use Rector\Set\ValueObject\SetList;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    // get parameters
    $parameters = $containerConfigurator->parameters();

    // paths to refactor; solid alternative to CLI arguments
    $parameters->set(Option::PATHS,
        [
            __DIR__.'/src',
            __DIR__.'/tests/unit',
        ]
    );

    $parameters->set(Option::AUTOLOAD_PATHS,
        []
    );

    // auto import fully qualified class names? [default: false]
    $parameters->set(Option::AUTO_IMPORT_NAMES, true);
    // skip root namespace classes, like \DateTime or \Exception [default: true]
    // $parameters->set(Option::IMPORT_SHORT_CLASSES, true);
    // skip classes used in PHP DocBlocks, like in /** @var \Some\Class */ [default: true]
    // $parameters->set(Option::IMPORT_DOC_BLOCKS, false);

    // $parameters->set(Option::ENABLE_CACHE, true);

    // Define what rule sets will be applied
    $parameters->set(Option::SETS, [
        // SetList::DEAD_CODE,
        // SetList::PHP_52,
        // SetList::PHP_53,
        // SetList::PHP_54,
        // SetList::PHP_55,
        // SetList::PHP_56,
        // SetList::PHP_70,
        // SetList::PHP_71,
        // SetList::PHP_72,
        // SetList::PHP_73,
        // SetList::PHP_74,
        // SetList::SYMFONY_28,
        // SetList::SYMFONY_30,
        // SetList::SYMFONY_31,
        // SetList::SYMFONY_32,
        // SetList::SYMFONY_33,
        // SetList::SYMFONY_34,
        // SetList::SYMFONY_40,
        // SetList::SYMFONY_41,
        // SetList::SYMFONY_42,
        // SetList::SYMFONY_43,
        // SetList::SYMFONY_44,
        // SetList::TWIG_112,
        // SetList::TWIG_127,
        // SetList::TWIG_134,
        // SetList::TWIG_140,
        // SetList::TWIG_20,
        // SetList::TWIG_240,
        // SetList::SYMFONY_CONSTRUCTOR_INJECTION,
    ]);

    // is your PHP version different from the one your refactor to? [default: your PHP version]
    // $parameters->set(Option::PHP_VERSION_FEATURES, '7.4');

    $parameters->set(Option::SKIP, [
        // skip new entities on demand to prevent rector from messing up our field trait imports
        // __DIR__.'/src/App/Entity',
        // __DIR__.'/src/App/Routing/EntityRoutingHelper.php',

        /**************** SetList::DEAD_CODE *****************/
        RemoveUnusedParameterRector::class,
        RemoveUnusedDoctrineEntityMethodAndPropertyRector::class,

        // happens not that often but if it does it's actually useful
        RemoveDefaultArgumentValueRector::class,
        // unsafe for now
        RemoveSetterOnlyPropertyAndMethodCallRector::class,
        // mainly targets commands, not that useful here
        RemoveDelegatingParentCallRector::class,
        // some constants are used in twig templates. needs manual checks
        RemoveUnusedClassConstantRector::class,
        // enable later, but requires service re-wiring
        RemoveUnusedPrivatePropertyRector::class,
    ]);

    // $parameters->set(Option::SYMFONY_CONTAINER_XML_PATH_PARAMETER, __DIR__.'/var/cache/dev/AppApp_KernelDevDebugContainer.xml');

    // get services (needed for register a single rule)
    $services = $containerConfigurator->services();

    // register a single rule
    // $services->set(ChangeAndIfToEarlyReturnRector::class);
    // $services->set(ChangeNestedForeachIfsToEarlyContinueRector::class);
    // $services->set(ChangeIfElseValueAssignToEarlyReturnRector::class);
    // $services->set(ChangeNestedIfsToEarlyReturnRector::class);
    // $services->set(RemoveAlwaysElseRector::class);
    // $services->set(RemoveUnusedVariableAssignRector::class);
    // $services->set(AddDefaultValueForUndefinedVariableRector::class);
};
