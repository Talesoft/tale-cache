<?php declare(strict_types=1);

namespace Tale;

use Psr\Cache\CacheItemPoolInterface;
use Psr\SimpleCache\CacheInterface;
use Tale\Cache\Pool\RoutingPool;
use Tale\Cache\Pool\SerializedFilePool;

function cache(CacheItemPoolInterface $pool): CacheInterface
{
    return new Cache($pool);
}

function cache_pool_routing(array $childPools): CacheItemPoolInterface
{
    return new RoutingPool($childPools);
}

function cache_pool_serialized_file(string $directory, int $createMask = 0775): CacheItemPoolInterface
{
    return new SerializedFilePool($directory, $createMask);
}