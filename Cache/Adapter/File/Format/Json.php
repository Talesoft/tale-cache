<?php

namespace Tale\Cache\Adapter\File\Format;

use Tale\Cache\Adapter\File\FormatInterface;

class Json implements FormatInterface
{

    public function getExtension()
    {

        return '.json';
    }

    public function load($path)
    {

        return $this->deserializeObjects(
            json_decode(file_get_contents($path), true)
        );
    }

    public function save($path, $value)
    {

        return file_put_contents($path, json_encode(
            $this->serializeObjects($value),
            \JSON_HEX_TAG | \JSON_HEX_AMP | \JSON_HEX_APOS | \JSON_HEX_QUOT
        ), \LOCK_EX) !== false;
    }

    private function serializeObjects($value)
    {

        if (is_array($value)) {

            foreach ($value as $i => $item)
                $value[$i] = $this->serializeObjects($item);

            return $value;
        }

        if (is_object($value)) {

            $serialized = serialize($value);
            return ['#!'.sha1($serialized) => $serialized];
        }

        return $value;
    }

    private function deserializeObjects($value)
    {

        if (!is_array($value))
            return $value;

        if (count($value) !== 1) {

            foreach ($value as $key => $val)
                $value[$key] = $this->deserializeObjects($val);

            return $value;
        }

        $key = key($value);
        if (!is_string($key) || strlen($key) !== 42 && strncmp($key, '#!', 2) !== 0)
            return $value;

        $hash = substr($key, 2);
        if (sha1($value[$key]) !== $hash)
            return $value;

        return unserialize($value[$key]);
    }
}