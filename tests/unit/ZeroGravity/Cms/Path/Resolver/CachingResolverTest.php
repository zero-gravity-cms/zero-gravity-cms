<?php

namespace Tests\Unit\ZeroGravity\Cms\Path\Resolver;

use Cocur\Slugify\Slugify;
use Codeception\Util\Stub;
use Iterator;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Tests\Unit\ZeroGravity\Cms\Test\BaseUnit;
use ZeroGravity\Cms\Path\Path;
use ZeroGravity\Cms\Path\Resolver\CachingResolver;
use ZeroGravity\Cms\Path\Resolver\MultiPathResolver;
use ZeroGravity\Cms\Path\Resolver\SinglePathResolver;

/**
 * @group resolver
 */
class CachingResolverTest extends BaseUnit
{
    /**
     * @test
     *
     * @dataProvider provideMethods
     */
    public function methodIsCached(string $method, $expectedReturnValue, string $calledMethod = null)
    {
        if (null === $calledMethod) {
            $calledMethod = $method;
        }
        if ('file' === $expectedReturnValue) {
            $fileFactory = $this->getDefaultFileFactory();
            $expectedReturnValue = $fileFactory->createFile('');
        }

        $called = false;
        $callback = function () use (&$called, $expectedReturnValue) {
            $called = true;

            // this is required for php-level return checks
            return $expectedReturnValue;
        };

        $resolver = new CachingResolver(
            $this->getCache(),
            $this->getWrappedResolver($calledMethod, $callback),
            new Slugify()
        );

        $result = $resolver->$method(new Path('a'), new Path('b'));
        static::assertSame($expectedReturnValue, $result);
        static::assertTrue($called, 'Wrapped repo is called upon first request: '.$method.' :: '.$calledMethod);

        $called = false;

        $result = $resolver->$method(new Path('a'), new Path('b'));
        static::assertSame($expectedReturnValue, $result);
        static::assertFalse($called, 'Wrapped repo is not called upon second request: '.$method.' :: '.$calledMethod);

        $result = $resolver->$method(new Path('b'), new Path('c'));
        static::assertSame($expectedReturnValue, $result);
        static::assertTrue($called, 'Wrapped repo is called when arguments changed: '.$method.' :: '.$calledMethod);

        // try without parent path
        $result = $resolver->$method(new Path('d'));
        static::assertSame($expectedReturnValue, $result);
        static::assertTrue($called, 'Wrapped repo is called upon first request: '.$method.' :: '.$calledMethod);

        $called = false;

        $result = $resolver->$method(new Path('d'));
        static::assertSame($expectedReturnValue, $result);
        static::assertFalse($called, 'Wrapped repo is not called upon second request: '.$method.' :: '.$calledMethod);
    }

    public function provideMethods(): Iterator
    {
        yield 'get' => ['get', 'file'];
        yield 'find' => ['find', []];
        yield 'findOne' => ['findOne', 'file', 'get'];
    }

    /**
     * @param string $method
     *
     * @return MultiPathResolver
     */
    private function getWrappedResolver($method, callable $callback)
    {
        return Stub::makeEmpty(MultiPathResolver::class, [
            $method => $callback,
        ]);
    }

    /**
     * @test
     */
    public function findReturnsEmptyArrayIfWrappedResolverIsSinglePathResolver()
    {
        $wrappedResolver = Stub::makeEmpty(SinglePathResolver::class, []);
        $resolver = new CachingResolver(
            $this->getCache(),
            $wrappedResolver,
            new Slugify()
        );

        $result = $resolver->find(new Path('a'), new Path('b'));
        static::assertSame([], $result);
    }

    /**
     * @return ArrayAdapter
     */
    private function getCache()
    {
        return new ArrayAdapter(0, false);
    }
}
