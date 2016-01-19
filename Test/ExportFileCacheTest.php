<?php

namespace Tale\Test;

use Tale\Cache;

class ExportFileCacheTest extends FileCacheTestBase
{

    public function getPath()
    {
        return __DIR__.'/cache/export';
    }

    public function addGateway(Cache $cache, $name)
    {

        $cache->addExportGateway($name, $this->getPath(), 2);
    }
}