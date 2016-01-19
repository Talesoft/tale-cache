<?php

namespace Tale\Test;

use Tale\Cache;

class JsonFileCacheTest extends FileCacheTestBase
{

    public function getPath()
    {
        return __DIR__.'/cache/json';
    }

    public function addGateway(Cache $cache, $name)
    {

        $cache->addJsonGateway($name, $this->getPath(), 2);
    }
}