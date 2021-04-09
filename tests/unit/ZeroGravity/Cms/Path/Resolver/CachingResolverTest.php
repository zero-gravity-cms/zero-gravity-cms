<?php

namespace Tests\Unit\ZeroGravity\Cms\Path\Resolver;

use Cocur\Slugify\Slugify;
use Codeception\Util\Stub;
use Symfony\Component\Cache\Simple\ArrayCache;
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
        $this->assertSame($expectedReturnValue, $result);
        $this->assertTrue($called, 'Wrapped repo is called upon first request: '.$method.' :: '.$calledMethod);

        $called = false;

        $result = $resolver->$method(new Path('a'), new Path('b'));
        $this->assertSame($expectedReturnValue, $result);
        $this->assertFalse($called, 'Wrapped repo is not called upon second request: '.$method.' :: '.$calledMethod);

        $result = $resolver->$method(new Path('b'), new Path('c'));
        $this->assertSame($expectedReturnValue, $result);
        $this->assertTrue($called, 'Wrapped repo is called when arguments changed: '.$method.' :: '.$calledMethod);

        // try without parent path
        $result = $resolver->$method(new Path('d'));
        $this->assertSame($expectedReturnValue, $result);
        $this->assertTrue($called, 'Wrapped repo is called upon first request: '.$method.' :: '.$calledMethod);

        $called = false;

        $result = $resolver->$method(new Path('d'));
        $this->assertSame($expectedReturnValue, $result);
        $this->assertFalse($called, 'Wrapped repo is not called upon second request: '.$method.' :: '.$calledMethod);
    }

    public function provideMethods()
    {
        return [
            'get' => ['get', 'file'],
            'find' => ['find', []],
            'findOne' => ['findOne', 'file', 'get'],
        ];
    }

    /**
     * @param string $method
     *
     * @return MultiPathResolver
     */
    private function getWrappedResolver($method, callable $callback)
    {
        $resolver = Stub::makeEmpty(MultiPathResolver::class, [
            $method => $callback,
        ]);

        return $resolver;
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
        $this->assertSame([], $result);
    }

    /**
     * @return ArrayCache
     */
    private function getCache()
    {
        return new ArrayCache(0, false);
    }
}
