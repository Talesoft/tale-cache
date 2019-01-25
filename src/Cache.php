<?php declare(strict_types=1);

namespace Tale;

use Psr\Cache\CacheItemPoolInterface;
use Psr\SimpleCache\CacheInterface;

final class Cache implements CacheInterface
{
    /** @var CacheInterface */
    private $innerCache;

    public function __construct(CacheItemPoolInterface $innerPool)
    {
        //Right now Tale Cache is basically just a wrapper around the PoolCache of tale-cache-core
        $this->innerCache = cache_pool_cache($innerPool);
    }

    /**
     * @param string $key
     * @return bool
     * @throws \Psr\Cache\InvalidArgumentException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function has($key): bool
    {
        return $this->innerCache->has($key);
    }

    /**
     * @param string $key
     * @param null $default
     * @return mixed|null
     * @throws \Psr\Cache\InvalidArgumentException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function get($key, $default = null)
    {
        return $this->innerCache->get($key, $default);
    }

    /**
     * @param string $key
     * @param mixed $value
     * @param null $ttl
     * @return bool
     * @throws \Psr\Cache\InvalidArgumentException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function set($key, $value, $ttl = null): bool
    {
        return $this->innerCache->set($key, $value, $ttl);
    }

    /**
     * @param string $key
     * @return bool
     * @throws \Psr\Cache\InvalidArgumentException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function delete($key): bool
    {
        return $this->innerCache->delete($key);
    }

    /**
     * @return bool
     */
    public function clear(): bool
    {
        return $this->innerCache->clear();
    }

    /**
     * @param iterable $keys
     * @param null $default
     * @return \Generator|iterable
     * @throws \Psr\Cache\InvalidArgumentException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function getMultiple($keys, $default = null)
    {
        yield from $this->innerCache->getMultiple($keys, $default);
    }

    /**
     * @param iterable $values
     * @param null $ttl
     * @return bool
     * @throws \Psr\Cache\InvalidArgumentException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function setMultiple($values, $ttl = null): bool
    {
        return $this->innerCache->setMultiple($values, $ttl);
    }

    /**
     * @param iterable $keys
     * @return bool
     * @throws \Psr\Cache\InvalidArgumentException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function deleteMultiple($keys): bool
    {
        return $this->innerCache->deleteMultiple($keys);
    }
}