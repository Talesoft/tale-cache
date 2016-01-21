
# Tale Cache
**A Tale Framework Component**

# What is Tale Cache?

Tale Cache is a small caching utility library that is compatible to PSR-6 caches

# Installation

Install via Composer

```bash
composer require "talesoft/tale-cache:*"
composer install
```

# Usage

**Short form**
```php

$cache = new Tale\Cache();
$cache->addJsonGateway('views', __DIR__.'/cache/views');
$cache->addSerializeGateway('db', __DIR__.'/cache/db');

$html = $cache->views->load('my-view', function() {
    
    //Expensive view generation stuff
    return $html;
});


$users = $cache->db->load('users', function() {

    //Load users from database
    return $users;
});



//Working with the gateway
if (!isset($cache->db->users)) {
    
    $cache->db->users = [new User(), new User(), new User()];
}

var_dump($cache->db->users);


$cache->db->commit();
```

**Long form**
```php

//Create adapter
$adapter = new Tale\Cache\Adapter\File([
    'path' => __DIR__.'/cache/views',
    'format' => 'serialize'
]);

//Create item pool
$pool = new Tale\Cache\ItemPool($adapter);

//Create cache and add gateway for pool
$cache = new Tale\Cache();
$cache->addGateway('testPool', $pool);

//Work with the pool directly
$item = $pool->getItem('some-item');

if (!$item->isHit()) {
    
    $item->set('some value');
    $item->expireAt(new DateTimeImmutable('+2 years'));
    $pool->save($item);
}

var_dump($item->get());

```
