<?php

namespace Tale\Cache\Adapter\File\Format;


class Export extends Json
{

    public function getExtension()
    {

        return '.php';
    }

    public function load($path)
    {

        return $this->deserializeObjects(include($path));
    }

    public function save($path, $value)
    {

        return file_put_contents($path, "<?php\nreturn ".var_export($this->serializeObjects($value), true).';', \LOCK_EX) !== false;
    }
}