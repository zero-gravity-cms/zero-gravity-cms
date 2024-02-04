<?php

namespace Tests\Unit\ZeroGravity\Cms\Path\Resolver;

use Cocur\Slugify\Slugify;
use Codeception\Attribute\DataProvider;
use Codeception\Attribute\Group;
use Codeception\Stub;
use Iterator;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Tests\Unit\ZeroGravity\Cms\Test\BaseUnit;
use ZeroGravity\Cms\Path\Path;
use ZeroGravity\Cms\Path\Resolver\CachingResolver;
use ZeroGravity\Cms\Path\Resolver\MultiPathResolver;
use ZeroGravity\Cms\Path\Resolver\SinglePathResolver;

#[Group('resolver')]
class CachingResolverTest extends BaseUnit
{
    #[DataProvider('provideMethods')]
    #[Test]
    public function methodIsCached(string $method, $expectedReturnValue, string $calledMethod = null): void
    {
        if (null === $calledMethod) {
            $calledMethod = $method;
        }
        if ('file' === $expectedReturnValue) {
            $fileFactory = $this->getDefaultFileFactory();
            $expectedReturnValue = $fileFactory->createFile('');
        }

        $called = false;
        $callback = static function () use (&$called, $expectedReturnValue) {
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
        self::assertSame($expectedReturnValue, $result);
        self::assertTrue($called, 'Wrapped repo is called upon first request: '.$method.' :: '.$calledMethod);

        $called = false;

        $result = $resolver->$method(new Path('a'), new Path('b'));
        self::assertSame($expectedReturnValue, $result);
        self::assertFalse($called, 'Wrapped repo is not called upon second request: '.$method.' :: '.$calledMethod);

        $result = $resolver->$method(new Path('b'), new Path('c'));
        self::assertSame($expectedReturnValue, $result);
        self::assertTrue($called, 'Wrapped repo is called when arguments changed: '.$method.' :: '.$calledMethod);

        // try without parent path
        $result = $resolver->$method(new Path('d'));
        self::assertSame($expectedReturnValue, $result);
        self::assertTrue($called, 'Wrapped repo is called upon first request: '.$method.' :: '.$calledMethod);

        $called = false;

        $result = $resolver->$method(new Path('d'));
        self::assertSame($expectedReturnValue, $result);
        self::assertFalse($called, 'Wrapped repo is not called upon second request: '.$method.' :: '.$calledMethod);
    }

    public static function provideMethods(): Iterator
    {
        yield 'get' => ['get', 'file'];
        yield 'find' => ['find', []];
        yield 'findOne' => ['findOne', 'file', 'get'];
    }

    /**
     * @return MultiPathResolver
     */
    private function getWrappedResolver(string $method, callable $callback)
    {
        return Stub::makeEmpty(MultiPathResolver::class, [
            $method => $callback,
        ]);
    }

    #[Test]
    public function findReturnsEmptyArrayIfWrappedResolverIsSinglePathResolver(): void
    {
        $wrappedResolver = Stub::makeEmpty(SinglePathResolver::class, []);
        $resolver = new CachingResolver(
            $this->getCache(),
            $wrappedResolver,
            new Slugify()
        );

        $result = $resolver->find(new Path('a'), new Path('b'));
        self::assertSame([], $result);
    }

    private function getCache(): ArrayAdapter
    {
        return new ArrayAdapter(0, false);
    }
}
