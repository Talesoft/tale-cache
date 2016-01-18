<?php

namespace Tale\Cache;

interface AdapterInterface
{

    public function has($key);
    public function get($key);
    public function set($key, $value, $lifeTime);
    public function remove($key);
    public function clear();
}