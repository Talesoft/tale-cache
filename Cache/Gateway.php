<?php

namespace Tale\Cache;

use ArrayAccess;
use Psr\Cache\CacheItemPoolInterface;

class Gateway implements ArrayAccess
{

    /**
     * @var ItemPool
     */
    private $_pool;

    public function __construct(CacheItemPoolInterface $pool)
    {

        $this->_pool = $pool;
    }

    /**
     * @return ItemPool
     */
    public function getPool()
    {
        return $this->_pool;
    }

    public function getItem($key)
    {

        return $this->_pool->getItem($key);
    }

    public function has($key)
    {

        return $this->_pool->hasItem($key);
    }

    public function get($key)
    {

        return $this->getItem($key)->get();
    }

    public function set($key, $value, $lifeTime = null)
    {

        $item = $this->getItem($key);
        $item->set($value);

        if ($lifeTime !== null)
            $item->expiresAfter($lifeTime);

        $this->_pool->saveDeferred($item);

        return $this;
    }

    public function delete($key)
    {

        return $this->_pool->deleteItem($key);
    }

    public function commit()
    {

        return $this->_pool->commit();
    }

    public function load($key, $callback, $lifeTime = null)
    {

        $item = $this->getItem($key);

        if ($item->isHit())
            return $item->get();

        $value = call_user_func($callback);
        $item->set($value);

        if ($lifeTime !== null)
            $item->expiresAfter($lifeTime);

        $this->_pool->saveDeferred($item);

        return $value;
    }

    public function __get($key)
    {

        return $this->get($key);
    }

    public function __set($key, $value)
    {

        $this->set($key, $value);
    }

    public function __isset($key)
    {

        return $this->has($key);
    }

    public function __unset($key)
    {

        $this->_pool->deleteItem($key);
    }

    public function offsetExists($offset)
    {

        return $this->has($offset);
    }

    public function offsetGet($offset)
    {

        return $this->get($offset);
    }

    public function offsetSet($offset, $value)
    {

        $this->set($offset, $value);
    }

    public function offsetUnset($offset)
    {

        $this->delete($offset);
    }
}