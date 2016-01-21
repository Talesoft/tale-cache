<?php

namespace Tale\Test;

use Tale\Cache;

class SerializeFileCacheTest extends FileCacheTestBase
{

    public function getPath()
    {
        return __DIR__.'/cache/serialize';
    }

    public function addGateway(Cache $cache, $name)
    {

        $cache->addSerializeGateway($name, $this->getPath(), 2);
    }
}