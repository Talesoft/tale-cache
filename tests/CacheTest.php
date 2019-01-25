<?php
declare(strict_types=1);

namespace Tale\Test;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Tale\Cache;
use function Tale\cache_pool_runtime;

/**
 * @coversDefaultClass \Tale\Cache
 */
class CacheTest extends TestCase
{
    /**
     * @covers ::__construct
     * @covers ::has
     * @throws \Psr\Cache\InvalidArgumentException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function testHas(): void
    {
        $cache = new Cache(cache_pool_runtime());
        self::assertFalse($cache->has('some.key'));
    }

    /**
     * @covers ::__construct
     * @covers ::get
     * @throws \Psr\Cache\InvalidArgumentException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function testGet(): void
    {
        $cache = new Cache(cache_pool_runtime());
        self::assertNull($cache->get('some.key'));
        self::assertSame('default value', $cache->get('some.key', 'default value'));
        $cache->set('some.key', 'some value');
        self::assertSame('some value', $cache->get('some.key', 'default value'));
    }

    /**
     * @covers ::__construct
     * @covers ::set
     * @throws \Psr\Cache\InvalidArgumentException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function testSet(): void
    {
        $cache = new Cache(cache_pool_runtime());
        self::assertTrue($cache->set('some.key', 'some value', 3306));
    }

    /**
     * @covers ::__construct
     * @covers ::delete
     * @throws \Psr\Cache\InvalidArgumentException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function testDelete(): void
    {
        $cache = new Cache(cache_pool_runtime());
        self::assertFalse($cache->delete('some.key'));
        $cache->set('some.key', 'some value');
        self::assertTrue($cache->delete('some.key'));
    }

    /**
     * @covers ::__construct
     * @covers ::clear()
     */
    public function testClear(): void
    {
        $cache = new Cache(cache_pool_runtime());
        self::assertTrue($cache->clear());
    }

    /**
     * @covers ::__construct
     * @covers ::getMultiple
     * @covers ::setMultiple
     * @throws \Psr\Cache\InvalidArgumentException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function testGetMultiple(): void
    {
        $cache = new Cache(cache_pool_runtime());
        self::assertSame([null, null, null], iterator_to_array($cache->getMultiple(
            ['first.key', 'second.key', 'third.key']
        )));
        $cache->setMultiple(['first.key' => 'first value', 'third.key' => 'third value']);
        self::assertSame(['first value', 'default value', 'third value'], iterator_to_array($cache->getMultiple(
            ['first.key', 'second.key', 'third.key'],
            'default value'
        )));
    }

    /**
     * @covers ::__construct
     * @covers ::getMultiple
     * @covers ::setMultiple
     * @throws \Psr\Cache\InvalidArgumentException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function testGetMultipleWithKeyIterator(): void
    {
        $cache = new Cache(cache_pool_runtime());
        self::assertSame([null, null, null], iterator_to_array($cache->getMultiple(
            new \ArrayIterator(['first.key', 'second.key', 'third.key'])
        )));
        $cache->setMultiple(['first.key' => 'first value', 'third.key' => 'third value']);
        self::assertSame(['first value', 'default value', 'third value'], iterator_to_array($cache->getMultiple(
            new \ArrayIterator(['first.key', 'second.key', 'third.key']),
            'default value'
        )));
    }

    /**
     * @covers ::__construct
     * @covers ::getMultiple
     * @expectedException InvalidArgumentException
     * @dataProvider provideKeyMultiples
     * @param $arg
     * @throws \Psr\Cache\InvalidArgumentException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function testGetMultipleThrowsExceptionOnInvalidArgument($arg): void
    {
        $cache = new Cache(cache_pool_runtime());
        iterator_to_array($cache->getMultiple($arg));
    }

    public function provideKeyMultiples(): array
    {
        return [
            [null],
            [false],
            [1],
            [5.4],
            ['test'],
            [new class {
            }],
            [stream_context_create()]
        ];
    }

    /**
     * @covers ::__construct
     * @covers ::setMultiple
     * @covers ::getMultiple
     * @throws \Psr\Cache\InvalidArgumentException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function testSetMultiple(): void
    {
        $cache = new Cache(cache_pool_runtime());
        self::assertTrue($cache->setMultiple([
            'first.key' => 'first value',
            'second.key' => 'second value',
            'third.key' => 'third value'
        ]));
        self::assertSame(['first value', 'second value', 'third value'], iterator_to_array($cache->getMultiple(
            ['first.key', 'second.key', 'third.key']
        )));
    }

    /**
     * @covers ::__construct
     * @covers ::deleteMultiple
     * @throws \Psr\Cache\InvalidArgumentException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function testDeleteMultiple(): void
    {
        $cache = new Cache(cache_pool_runtime());
        $cache->set('first.key', 'a');
        $cache->set('third.key', 'c');
        self::assertFalse($cache->deleteMultiple(['first.key', 'second.key', 'third.key']));
        $cache->set('first.key', 'a');
        $cache->set('second.key', 'b');
        $cache->set('third.key', 'c');
        self::assertTrue($cache->deleteMultiple(['first.key', 'second.key', 'third.key']));
    }
}
