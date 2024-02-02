<?php

namespace Tests\Unit\ZeroGravity\Cms\Test;

use Exception;
use InvalidArgumentException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionProperty;
use Twig\Environment;
use Twig\Error\Error;
use Twig\Extension\ExtensionInterface;
use Twig\Loader\ArrayLoader;
use Twig\Loader\ChainLoader;
use Twig\Loader\LoaderInterface;
use Twig\RuntimeLoader\RuntimeLoaderInterface;
use Twig\TwigFilter;
use Twig\TwigFunction;
use Twig\TwigTest;

/**
 * This was copied and adapted from Twig\Test\IntegrationTestCase.
 *
 * @see IntegrationTestCase
 */
trait TwigExtensionTestTrait
{
    /**
     * @return string
     */
    abstract protected function getFixturesDir();

    /**
     * @return RuntimeLoaderInterface[]
     */
    protected function getRuntimeLoaders()
    {
        return [];
    }

    /**
     * @return ExtensionInterface[]
     */
    protected function getExtensions()
    {
        return [];
    }

    /**
     * @return TwigFilter[]
     */
    protected function getTwigFilters()
    {
        return [];
    }

    /**
     * @return TwigFunction[]
     */
    protected function getTwigFunctions()
    {
        return [];
    }

    /**
     * @return TwigTest[]
     */
    protected function getTwigTests()
    {
        return [];
    }

    /**
     * @return LoaderInterface[]
     */
    protected function getTwigLoaders()
    {
        return [];
    }

    /**
     * @dataProvider getTests
     */
    public function testIntegration($file, $message, $condition, $templates, $exception, $outputs)
    {
        $this->doIntegrationTest($file, $message, $condition, $templates, $exception, $outputs);
    }

    // /**
    //  * @dataProvider getLegacyTests
    //  * @group legacy
    //  */
    // public function testLegacyIntegration($file, $message, $condition, $templates, $exception, $outputs)
    // {
    //     $this->doIntegrationTest($file, $message, $condition, $templates, $exception, $outputs);
    // }

    public function getTests($name, $legacyTests = false)
    {
        $fixturesDir = realpath($this->getFixturesDir());
        $tests = [];

        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($fixturesDir), RecursiveIteratorIterator::LEAVES_ONLY) as $file) {
            if (!preg_match('/\.test$/', $file)) {
                continue;
            }

            if ($legacyTests xor false !== strpos($file->getRealpath(), '.legacy.test')) {
                continue;
            }

            $test = file_get_contents($file->getRealpath());

            if (preg_match('/--TEST--\s*(.*?)\s*(?:--CONDITION--\s*(.*))?\s*((?:--TEMPLATE(?:\(.*?\))?--(?:.*?))+)\s*(?:--DATA--\s*(.*))?\s*--EXCEPTION--\s*(.*)/sx', $test, $match)) {
                $message = $match[1];
                $condition = $match[2];
                $templates = self::parseTemplates($match[3]);
                $exception = $match[5];
                $outputs = [[null, $match[4], null, '']];
            } elseif (preg_match('/--TEST--\s*(.*?)\s*(?:--CONDITION--\s*(.*))?\s*((?:--TEMPLATE(?:\(.*?\))?--(?:.*?))+)--DATA--.*?--EXPECT--.*/s', $test, $match)) {
                $message = $match[1];
                $condition = $match[2];
                $templates = self::parseTemplates($match[3]);
                $exception = false;
                preg_match_all('/--DATA--(.*?)(?:--CONFIG--(.*?))?--EXPECT--(.*?)(?=\-\-DATA\-\-|$)/s', $test, $outputs, PREG_SET_ORDER);
            } else {
                throw new InvalidArgumentException(sprintf('Test "%s" is not valid.', str_replace($fixturesDir.'/', '', $file)));
            }

            while (isset($tests[$message])) {
                $message .= '.';
            }
            $tests[$message] = [str_replace($fixturesDir.'/', '', $file), $message, $condition, $templates, $exception, $outputs];
        }
        if (!$legacyTests) {
            return $tests;
        }
        if (!empty($tests)) {
            return $tests;
        }

        // add a dummy test to avoid a PHPUnit message
        return [['not', '-', '', [], '', []]];
    }

    // public function getLegacyTests()
    // {
    //     return $this->getTests('testLegacyIntegration', true);
    // }

    protected function doIntegrationTest($file, $message, $condition, $templates, $exception, $outputs)
    {
        if (!$outputs) {
            self::markTestSkipped('no legacy tests to run');
        }

        if ($condition) {
            eval('$ret = '.$condition.';');
            if (!$ret) {
                self::markTestSkipped($condition);
            }
        }

        $loader = new ChainLoader();
        foreach ($this->getTwigLoaders() as $customLoader) {
            $loader->addLoader($customLoader);
        }
        $loader->addLoader(new ArrayLoader($templates));

        foreach ($outputs as $i => $match) {
            $config = array_merge([
                'cache' => false,
                'strict_variables' => true,
            ], $match[2] ? eval($match[2].';') : []);
            $twig = new Environment($loader, $config);
            $twig->addGlobal('global', 'global');
            foreach ($this->getRuntimeLoaders() as $runtimeLoader) {
                $twig->addRuntimeLoader($runtimeLoader);
            }

            foreach ($this->getExtensions() as $extension) {
                $twig->addExtension($extension);
            }

            foreach ($this->getTwigFilters() as $filter) {
                $twig->addFilter($filter);
            }

            foreach ($this->getTwigTests() as $test) {
                $twig->addTest($test);
            }

            foreach ($this->getTwigFunctions() as $function) {
                $twig->addFunction($function);
            }

            // avoid using the same PHP class name for different cases
            $p = new ReflectionProperty($twig, 'templateClassPrefix');
            $p->setAccessible(true);
            $p->setValue($twig, '__TwigTemplate_'.hash('sha256', uniqid(random_int(0, mt_getrandmax()), true), false).'_');

            try {
                $template = $twig->load('index.twig');
            } catch (Exception $e) {
                if (false !== $exception) {
                    $message = $e->getMessage();
                    static::assertSame(trim($exception), trim(sprintf('%s: %s', get_class($e), $message)));
                    $last = substr($message, strlen($message) - 1);
                    static::assertTrue('.' === $last || '?' === $last, $message, 'Exception message must end with a dot or a question mark.');

                    return;
                }

                throw new Error(sprintf('%s: %s', get_class($e), $e->getMessage()), -1, $file, $e);
            }

            try {
                $output = trim($template->render(eval($match[1].';')), "\n ");
            } catch (Exception $e) {
                if (false !== $exception) {
                    static::assertSame(trim($exception), trim(sprintf('%s: %s', get_class($e), $e->getMessage())));

                    return;
                }

                $e = new Error(sprintf('%s: %s', get_class($e), $e->getMessage()), -1, $file, $e);

                $output = trim(sprintf('%s: %s', get_class($e), $e->getMessage()));
            }

            if (false !== $exception) {
                [$class] = explode(':', $exception);
                $constraintClass = class_exists(\PHPUnit\Framework\Constraint\Exception::class) ? \PHPUnit\Framework\Constraint\Exception::class : 'PHPUnit_Framework_Constraint_Exception';
                static::assertThat(null, new $constraintClass($class));
            }

            $expected = trim($match[3], "\n ");

            if ($expected !== $output) {
                printf("Compiled templates that failed on case %d:\n", $i + 1);

                foreach (array_keys($templates) as $name) {
                    echo "Template: $name\n";
                    echo $twig->compile($twig->parse($twig->tokenize($twig->getLoader()->getSourceContext($name))));
                }
            }
            static::assertEquals($expected, $output, $message.' (in '.$file.')');
        }
    }

    protected static function parseTemplates($test)
    {
        $templates = [];
        preg_match_all('/--TEMPLATE(?:\((.*?)\))?--(.*?)(?=\-\-TEMPLATE|$)/s', $test, $matches, PREG_SET_ORDER);
        foreach ($matches as $match) {
            $templates[$match[1] ?: 'index.twig'] = $match[2];
        }

        return $templates;
    }
}
