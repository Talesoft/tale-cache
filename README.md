
Tale Cache
==========

What is Tale Cache?
-------------------

Tale Cache is a complete PSR-6 and PSR-16 implementation
that provides different cache pools and simple cache interfaces.

Installation
------------

**Don't use in production yet**

```bash
composer req talesoft/tale-cache
```

Usage
-----

```php
use function Tale\cache;
use function Tale\cache_pool_routing;
use function Tale\cache_pool_serialized_file;
use function Tale\cache_pool_redis; //Doesn't actually exist yet

$cache = cache(cache_pool_routing([
    'app.' => cache_pool_serialized_file(__DIR__.'/var/cache/app'),
    'db.' => cache_pool_redis('redis://localhost')
]);

$value = $cache->get('app.my_namespace.my_value');
if ($value === null) {
    $value = do_some_heavy_work()
    $cache->set('app.my_namespace.my_value', $value);
}

//$value is now cached to var/cache/app/my_namespace/my_value.cache
```

//TODO: more docs, more tests