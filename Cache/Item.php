<?php

namespace Tale\Cache;

use DateInterval;
use DateTimeImmutable;
use DateTimeInterface;
use Psr\Cache\CacheItemInterface;
use Tale\Cache;

class Item implements CacheItemInterface
{

    private $pool;
    private $adapter;
    private $key;
    private $loaded;
    private $value;
    private $lifeTime;

    public function __construct(ItemPool $pool, $key)
    {

        $pool->validateKey($key);

        $this->pool = $pool;
        $this->adapter = $pool->getAdapter();
        $this->key = $key;
        $this->loaded = false;
        $this->value = null;
        $this->lifeTime = null;
    }

    /**
     * @return ItemPool
     */
    public function getPool()
    {

        return $this->pool;
    }

    /**
     * @return \Tale\Cache\AdapterInterface
     */
    public function getAdapter()
    {

        return $this->adapter;
    }

    public function getValue()
    {

        return $this->value;
    }

    /**
     * @return int|null
     */
    public function getLifeTime()
    {

        return $this->lifeTime;
    }

    public function isLoaded()
    {

        return $this->loaded;
    }

    /**
     * Returns the key for the current cache item.
     *
     * The key is loaded by the Implementing Library, but should be available to
     * the higher level callers when needed.
     *
     * @return string
     *   The key string for this cache item.
     */
    public function getKey()
    {

        return $this->key;
    }

    /**
     * Retrieves the value of the item from the cache associated with this object's key.
     *
     * The value returned must be identical to the value originally stored by set().
     *
     * If isHit() returns false, this method MUST return null. Note that null
     * is a legitimate cached value, so the isHit() method SHOULD be used to
     * differentiate between "null value was found" and "no value was found."
     *
     * @return mixed
     *   The value corresponding to this cache item's key, or null if not found.
     */
    public function get()
    {

        if (!$this->isHit())
            return null;

        if ($this->loaded)
            return $this->value;

        $this->value = $this->adapter->get($this->key);
        $this->loaded = true;

        return $this->value;
    }

    /**
     * Confirms if the cache item lookup resulted in a cache hit.
     *
     * Note: This method MUST NOT have a race condition between calling isHit()
     * and calling get().
     *
     * @return bool
     *   True if the request resulted in a cache hit. False otherwise.
     */
    public function isHit()
    {

        return $this->adapter->has($this->key);
    }

    /**
     * Sets the value represented by this cache item.
     *
     * The $value argument may be any item that can be serialized by PHP,
     * although the method of serialization is left up to the Implementing
     * Library.
     *
     * @param mixed $value
     *   The serializable value to be stored.
     *
     * @return static
     *   The invoked object.
     */
    public function set($value)
    {

        $this->value = $value;

        return $this;
    }


    /**
     * Sets the expiration time for this cache item.
     *
     * @param DateTimeInterface $expiration
     *   The point in time after which the item MUST be considered expired.
     *   If null is passed explicitly, a default value MAY be used. If none is set,
     *   the value should be stored permanently or for as long as the
     *   implementation allows.
     *
     * @throws InvalidArgumentException
     *
     * @return static
     *   The called object.
     */
    public function expiresAt($expiration)
    {

        if ($expiration === null) {

            $this->lifeTime = null;
            return $this;
        }

        if (!($expiration instanceof DateTimeInterface))
            throw new InvalidArgumentException(
                "Argument 1 passed to Item->expiresAt needs to be ".
                "instance of DateTimeInterface"
            );

        $this->lifeTime = max(0, time() - $expiration->getTimestamp());

        return $this;
    }

    /**
     * Sets the expiration time for this cache item.
     *
     * @param int|\DateInterval $time
     *   The period of time from the present after which the item MUST be considered
     *   expired. An integer parameter is understood to be the time in seconds until
     *   expiration. If null is passed explicitly, a default value MAY be used.
     *   If none is set, the value should be stored permanently or for as long as the
     *   implementation allows.
     *
     * @throws InvalidArgumentException
     *
     * @return static
     *   The called object.
     */
    public function expiresAfter($time)
    {

        if ($time === null) {

            $this->lifeTime = null;
            return $this;
        }

        if (is_int($time)) {

            $this->lifeTime = $time;
            return $this;
        }

        if (!($time instanceof DateInterval))
            throw new InvalidArgumentException(
                "Argument 1 passed to Item->expiresAfter needs to be integer ".
                "or DateInterval instance"
            );

        return $this->expiresAt((new DateTimeImmutable())->add($time));
    }

    public function save()
    {

        $lifeTime = $this->lifeTime;
        if ($lifeTime === null)
            $lifeTime = $this->pool->getLifeTime();

        return $this->adapter->set($this->key, $this->value, $lifeTime);
    }

    public function delete()
    {

        return $this->adapter->remove($this->key);
    }
}