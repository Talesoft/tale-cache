<?php declare(strict_types=1);

namespace Tale\Cache\Pool;

use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Tale\Cache\AbstractPool;

final class RoutingPool extends AbstractPool
{
    /** @var CacheItemPoolInterface[] */
    private $childPools;

    /** @var CacheItemPoolInterface[] */
    private $routedChildPools;

    public function __construct(array $childPools)
    {
        $this->childPools = array_map(function (CacheItemPoolInterface $pool) {
            return $pool;
        }, $childPools);
    }

    public function getChildPoolForKey(string $key): CacheItemPoolInterface
    {
        if (isset($this->routedChildPools[$key])) {
            return $this->routedChildPools[$key];
        }

        foreach ($this->childPools as $route => $pool) {
            if ($route === '*' || strncmp($key, $route, strlen($route)) === 0) {
                return $this->routedChildPools[$key] = $pool;
            }
        }

        throw new \RuntimeException(
            "Could not route cache key {$key}. You can add a routed pool to * to catch unrouted items."
        );
    }

    public function getItem($key): CacheItemInterface
    {
        $pool = $this->getChildPoolForKey($key);
        return $pool->getItem($key);
    }

    public function clear(): bool
    {
        $success = true;
        foreach ($this->childPools as $pool) {
            if (!$pool->clear()) {
                $success = false;
            }
        }
        return $success;
    }

    public function deleteItem($key): bool
    {
        $pool = $this->getChildPoolForKey($key);
        return $pool->deleteItem($key);
    }

    public function save(CacheItemInterface $item): bool
    {
        $pool = $this->getChildPoolForKey($item->getKey());
        return $pool->save($item);
    }
}