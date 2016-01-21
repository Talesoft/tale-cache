<?php

namespace Tale\Cache;

use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Tale\Cache;

class ItemPool implements CacheItemPoolInterface
{

    private $_adapter;
    private $_items;
    private $_deferredItems;
    private $_lifeTime;

    public function __construct(AdapterInterface $adapter, $lifeTime = null)
    {

        $this->_adapter = $adapter;
        $this->_items = [];
        $this->_deferredItems = [];
        $this->_lifeTime = $lifeTime !== null ? $lifeTime : 31622400; //One year
    }

    public function __destruct()
    {

        $this->commit();
    }

    public function __clone()
    {

        $this->_adapter = clone $this->_adapter;
        $this->_items = [];
        $this->_deferredItems = [];
    }

    /**
     * @return \Tale\Cache\AdapterInterface
     */
    public function getAdapter()
    {
        return $this->_adapter;
    }

    /**
     * @return array
     */
    public function getDeferredItems()
    {
        return $this->_deferredItems;
    }

    /**
     * @return int|null
     */
    public function getLifeTime()
    {
        return $this->_lifeTime;
    }

    public function isValidKey($key)
    {

        return preg_match('/[a-zA-Z0-9.\-_]+/', $key);
    }

    public function validateKey($key)
    {

        if (!$this->isValidKey($key))
            throw new InvalidArgumentException(
                "Passed key for cache item can consist of ".
                "a-z, A-Z, 0-9, . and - only and should not start or end ".
                "with a dot (.)"
            );
    }

    public function isValidItem(CacheItemInterface $item)
    {

        return $item instanceof Item && $item->getPool() === $this;
    }

    public function validateItem(CacheItemInterface $item)
    {

        if (!$this->isValidItem($item))
            throw new InvalidArgumentException(
                "Passed cache item doesn't originate from this cache pool"
            );
    }

    /**
     * Returns a Cache Item representing the specified key.
     *
     * This method must always return a CacheItemInterface object, even in case of
     * a cache miss. It MUST NOT return null.
     *
     * @param string $key
     *   The key for which to return the corresponding Cache Item.
     *
     * @throws InvalidArgumentException
     *   If the $key string is not a legal value a \Psr\Cache\InvalidArgumentException
     *   MUST be thrown.
     *
     * @return CacheItemInterface
     *   The corresponding Cache Item.
     */
    public function getItem($key)
    {

        if (!isset($this->_items[$key]))
            $this->_items[$key] = new Item($this, $key);

        return $this->_items[$key];
    }

    /**
     * Returns a traversable set of cache items.
     *
     * @param array $keys
     * An indexed array of keys of items to retrieve.
     *
     * @throws InvalidArgumentException
     *   If any of the keys in $keys are not a legal value a \Psr\Cache\InvalidArgumentException
     *   MUST be thrown.
     *
     * @return array|\Traversable
     *   A traversable collection of Cache Items keyed by the cache keys of
     *   each item. A Cache item will be returned for each key, even if that
     *   key is not found. However, if no keys are specified then an empty
     *   traversable MUST be returned instead.
     */
    public function getItems(array $keys = [])
    {

        $items = [];
        foreach ($keys as $key)
            $items[$key] = $this->getItem($key);

        return $items;
    }

    /**
     * Confirms if the cache contains specified cache item.
     *
     * Note: This method MAY avoid retrieving the cached value for performance reasons.
     * This could result in a race condition with CacheItemInterface::get(). To avoid
     * such situation use CacheItemInterface::isHit() instead.
     *
     * @param string $key
     *    The key for which to check existence.
     *
     * @throws InvalidArgumentException
     *   If the $key string is not a legal value a \Psr\Cache\InvalidArgumentException
     *   MUST be thrown.
     *
     * @return bool
     *  True if item exists in the cache, false otherwise.
     */
    public function hasItem($key)
    {

        return $this->getItem($key)->isHit();
    }

    /**
     * Removes the item from the pool.
     *
     * @param string $key
     *   The key for which to delete
     *
     * @throws InvalidArgumentException
     *   If the $key string is not a legal value a \Psr\Cache\InvalidArgumentException
     *   MUST be thrown.
     *
     * @return bool
     *   True if the item was successfully removed. False if there was an error.
     */
    public function deleteItem($key)
    {

        /** @var Item $item */
        $item = $this->getItem($key);

        $success = $item->delete();

        if (isset($this->_items[$key]))
            unset($this->_items[$key]);

        return $success;
    }

    /**
     * Removes multiple items from the pool.
     *
     * @param array $keys
     *   An array of keys that should be removed from the pool.
     *
     * @throws InvalidArgumentException
     *   If any of the keys in $keys are not a legal value a \Psr\Cache\InvalidArgumentException
     *   MUST be thrown.
     *
     * @return bool
     *   True if the items were successfully removed. False if there was an error.
     */
    public function deleteItems(array $keys)
    {

        $success = true;
        foreach ($keys as $key) {

            if (!$this->deleteItem($key))
                $success = false;
        }

        return $success;
    }

    /**
     * Deletes all items in the pool.
     *
     * @return bool
     *   True if the pool was successfully cleared. False if there was an error.
     */
    public function clear()
    {

        return $this->_adapter->clear();
    }

    /**
     * Persists a cache item immediately.
     *
     * @param CacheItemInterface $item
     *   The cache item to save.
     *
     * @throws InvalidArgumentException
     *
     * @return bool
     *   True if the item was successfully persisted. False if there was an error.
     */
    public function save(CacheItemInterface $item)
    {

        $this->validateItem($item);

        /** @var $item Item */
        return $item->save();
    }

    /**
     * Sets a cache item to be persisted later.
     *
     * @param CacheItemInterface $item
     *   The cache item to save.
     *
     * @return bool
     *   False if the item could not be queued or if a commit was attempted and failed. True otherwise.
     */
    public function saveDeferred(CacheItemInterface $item)
    {
        $this->validateItem($item);

        //check if this item is already deferred

        if (in_array($item, $this->_deferredItems, true))
            return false;

        $this->_deferredItems[] = $item;

        return true;
    }

    /**
     * Persists any deferred cache items.
     *
     * @return bool
     *   True if all not-yet-saved items were successfully saved or there were none. False otherwise.
     */
    public function commit()
    {

        if (count($this->_deferredItems) < 1)
            return true;

        $success = true;
        foreach ($this->_deferredItems as $item)
            if (!$this->save($item))
                $success = false;

        $this->_deferredItems = [];

        return $success;
    }
}