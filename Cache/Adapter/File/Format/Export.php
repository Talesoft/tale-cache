<?php

namespace Tale\Cache\Adapter\File\Format;

use Tale\Cache\Adapter\File\FormatInterface;

class Export implements FormatInterface
{

    public function getExtension()
    {

        return '.php';
    }

    public function load($path)
    {

        return include($path);
    }

    public function save($path, $value)
    {

        file_put_contents($path, "<?php\nreturn ".var_export($value, true).';');
    }
}