<?php

namespace Tale\Cache\Adapter\File\Format;

use Tale\Cache\Adapter\File\FormatInterface;

class Serialize implements FormatInterface
{

    public function getExtension()
    {

        return '.serialized';
    }

    public function load($path)
    {

        return unserialize(file_get_contents($path));
    }

    public function save($path, $value)
    {

        file_put_contents($path, serialize($value));
    }
}