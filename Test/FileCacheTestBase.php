<?php

namespace Tale\Test;

use Tale\Cache;

abstract class FileCacheTestBase extends \PHPUnit_Framework_TestCase
{

    private $_cachePath;
    private $_cache;

    public function setUp()
    {

        $this->_cachePath = $this->getPath();
        $this->_cache = new Cache();
        $this->addGateway($this->_cache, 'poolName');
    }

    abstract public function getPath();
    abstract public function addGateway(Cache $cache, $name);

    public function testCloning()
    {

        $cache = clone $this->_cache;
        $this->assertNotSame($this->_cache->poolName->getPool(), $cache->poolName->getPool());
        $this->assertNotSame($this->_cache->poolName->getPool()->getAdapter(), $cache->poolName->getPool()->getAdapter());
        $this->assertNotSame($this->_cache->poolName->getPool()->getAdapter()->getFormat(), $cache->poolName->getPool()->getAdapter()->getFormat());
    }

    /**
     * @depends testCloning
     * @dataProvider cachableValueProvider
     */
    public function testStoringOfValues($index, $value)
    {

        $this->_cache->poolName[$index] = $value;
        $cache = clone $this->_cache;
        $this->assertEquals(
            $value,
            $cache->poolName->getPool()->getAdapter()->get($index)
        );
    }

    /**
     * @depends testStoringOfValues
     */
    public function testClearing()
    {

        $this->assertTrue($this->_cache->poolName->clear());
        $this->assertEquals(0, count(scandir($this->_cachePath)) - 2);
    }

    /**
     * @depends testClearing
     * @dataProvider cachableValueProvider
     */
    public function testRetrievalOfValues($index, $value)
    {

        $this->_cache->poolName->getPool()->getAdapter()->set($index, $value, 2);
        $this->assertTrue($this->_cache->poolName->getPool()->getItem($index)->isHit(), 'before clone');
        $cache = clone $this->_cache;
        $this->assertTrue($cache->poolName->getPool()->getItem($index)->isHit(), 'after clone');
        $this->assertEquals(
            $value,
            $cache->poolName[$index]
        );
    }

    public function testSecondClearing()
    {

        $this->testClearing();
    }

    /**
     * @depends testSecondClearing
     */
    public function testExpiration()
    {

        $this->_cache->poolName->set('test', 'some value', 3);

        sleep(1);
        $cache = clone $this->_cache;

        $this->assertTrue($cache->poolName->has('test'), 'after 1s');
        $this->assertEquals('some value', $cache->poolName['test']);

        sleep(1);
        $cache = clone $this->_cache;

        $this->assertTrue($cache->poolName->has('test'), 'after 2s');
        $this->assertEquals('some value', $cache->poolName['test']);

        sleep(2);
        $cache = clone $this->_cache;

        $this->assertFalse($cache->poolName->has('test'), 'after 4s');
        $this->assertEquals(null, $cache->poolName['test']);
    }

    public function testThirdClearing()
    {

        $this->testClearing();
    }

    /**
     * @depends testThirdClearing
     */
    public function testLoading()
    {

        $called = false;
        $cachedValue = $this->_cache->poolName->load('test', function() use (&$called) {

            $called = true;
            return 'some value';
        }, 2);

        $this->assertEquals('some value', $cachedValue);
        $this->assertTrue($called, 'first load');

        sleep(1);
        $called = false;
        $cachedValue = $this->_cache->poolName->load('test', function() use (&$called) {

            $called = true;
            return 'some value';
        }, 2);

        $this->assertEquals('some value', $cachedValue);
        $this->assertFalse($called, 'second load');

        sleep(2);

        $called = false;
        $cachedValue = $this->_cache->poolName->load('test', function() use (&$called) {

            $called = true;
            return 'some value';
        }, 2);

        $this->assertEquals('some value', $cachedValue);
        $this->assertTrue($called, 'third load');

    }

    public function cachableValueProvider()
    {

        return [
            ['null', null],
            ['true', true],
            ['false', false],
            ['array', ['a', 'b', 'c']],
            ['one', 1],
            ['float', 4.3],
            ['int', 2556],
            ['string', 'some string'],
            ['object', (object)['a' => 1, 'b' => 2, 'c' => 3]]
        ];
    }
}