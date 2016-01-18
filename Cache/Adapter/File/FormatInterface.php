<?php

namespace Tale\Cache\Adapter\File;

interface FormatInterface
{

    public function getExtension();
    public function load($path);
    public function save($path, $value);
}