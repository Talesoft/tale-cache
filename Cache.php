<?php

namespace Tale;

use Psr\Cache\CacheItemPoolInterface;
use Tale\Cache\Adapter\File;
use Tale\Cache\AdapterInterface;
use Tale\Cache\Gateway;
use Tale\Cache\InvalidArgumentException;
use Tale\Cache\ItemPool;

final class Cache
{

    private $_gateways;

    public function __construct(array $pools = null)
    {

        $this->_gateways = [];

        if ($pools)
            foreach ($pools as $name => $pool)
                $this->addGateway($name, $pool);
    }

    /**
     * @return array
     */
    public function getGateways()
    {
        return $this->_gateways;
    }

    public function getGateway($name)
    {

        return $this->_gateways[$name];
    }

    public function addGateway($name, CacheItemPoolInterface $pool)
    {

        if (isset($this->_gateways[$name]))
            throw new InvalidArgumentException(
                "An item pool with the name $name is already registered"
            );

        $this->_gateways[$name] = new Gateway($pool);

        return $this;
    }

    public function addAdapterGateway($name, AdapterInterface $adapter, $lifeTime = null)
    {

        return $this->addGateway($name, new ItemPool($adapter, $lifeTime));
    }

    public function addFileGateway($name, $path = null, $lifeTime = null, $format = null)
    {

        $options = [];

        if ($path)
            $options['path'] = $path;

        if ($format)
            $options['format'] = $format;

        return $this->addAdapterGateway($name, new File($options), $lifeTime);
    }

    public function addJsonGateway($name, $path = null, $lifeTime = null)
    {

        return $this->addFileGateway($name, $path, $lifeTime, 'json');
    }

    public function addSerializeGateway($name, $path = null, $lifeTime = null)
    {

        return $this->addFileGateway($name, $path, $lifeTime, 'serialize');
    }

    public function addExportGateway($name, $path = null, $lifeTime = null)
    {

        return $this->addFileGateway($name, $path, $lifeTime, 'export');
    }

    public function __get($key)
    {

        return $this->getGateway($key);
    }
}