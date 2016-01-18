<?php

namespace Tale\Cache\Adapter;

use Tale\Cache\Adapter\File\Format\Export;
use Tale\Cache\Adapter\File\Format\Json;
use Tale\Cache\Adapter\File\Format\Serialize;
use Tale\Cache\Adapter\File\FormatInterface;
use Tale\Cache\AdapterInterface;

/**
 * Basic file system storage cache adapter
 *
 * Given a path it uses a directory and files as a cache storage
 * Sub-Cache keys are mapped to directories (e.g. some.sub.key => some/sub/key.cache.php)
 *
 * @package Tale\Cache\Adapter
 */
class File implements AdapterInterface
{

    private $_options;

    /**
     * The directory that will be used as a cache storage
     *
     * @var string
     */
    private $_path;

    /**
     * @var FormatInterface
     */
    private $_format;

    /**
     * The path to the file that contains the life-times for each key in this cache
     *
     * @var string
     */
    private $_lifeTimePath;

    /**
     * The life-times of specific items indexed by the cache-key used
     *
     * @var array
     */
    private $_lifeTimes;

    /**
     * Initializes the file cache adapter
     */
    public function __construct(array $options = null)
    {

        $this->_options = array_replace_recursive([
            'path' => './cache',
            'formats' => [
                'json' => Export::class,
                'serialize' => Json::class,
                'export' => Serialize::class
            ],
            'format' => 'json',
            'lifeTimeKey' => 'cache-lifetimes'
        ], $options ? $options : []);

        $formats = $this->_options['formats'];
        $format = $this->_options['format'];

        if (!isset($formats[$format]) || !is_subclass_of($formats[$format], FormatInterface::class))
            throw new \InvalidArgumentException(
                "The 'format' option should point to a valid ".FormatInterface::class."-class name in the 'formats' option"
            );

        $formatClassName = $formats[$format];
        $this->_format = new $formatClassName();
        $this->_lifeTimePath = $this->getKeyPath($this->_options['lifeTimeKey']);
        $this->_lifeTimes = [];

        if (file_exists($this->_lifeTimePath))
            $this->_lifeTimes = $this->_format->load($this->_lifeTimePath);
    }


    /**
     * Returns the current cache storage directory path
     *
     * @return string
     */
    public function getPath()
    {

        return $this->_path;
    }

    /**
     * Translates a cache key to the specific cache storage path
     * Dots (.) around the key will be trimmed
     *
     * @param $key string The key that needs to be translated
     *
     * @return string The path where the cache file resides
     */
    public function getKeyPath($key)
    {

        $key = str_replace('.', '/', trim($key, '.'));
        return implode('', [$this->_path, "/$key", $this->_format->getExtension()]);
    }

    /**
     * Checks if the given cache key has an existing cache file that didn't exceed the given life-time
     *
     * @param $key string The key that needs to be checked
     *
     * @return bool
     */
    public function has($key)
    {

        $path = $this->getKeyPath($key);

        if (!file_exists($path) || empty($this->_lifeTimes[$key]))
            return false;

        return !(time() - filemtime($path) > $this->_lifeTimes[$key]);
    }

    /**
     * Gets the content of a cache file by its key
     *
     * @param $key string The key that needs to be checked
     *
     * @return mixed The cached content value
     */
    public function get($key)
    {

        return $this->_format->load($this->getKeyPath($key));
    }

    /**
     * Sets the value of an cache item to the given value
     *
     * @param $key string The key that needs to be checked
     * @param $value mixed The value that needs to be cached
     * @param $lifeTime int The life-time of the cache item in seconds
     *
     * @return bool
     */
    public function set($key, $value, $lifeTime)
    {

        $path = $this->getKeyPath($key);
        $dir = dirname($path);

        if (!is_dir($dir))
            mkdir($dir, 0777, true);

        $this->_lifeTimes[$key] = intval($lifeTime);

        //Save the life times
        $this->_format->save($this->_lifeTimePath, $this->_lifeTimes);

        //Save the cache content
        $this->_format->save($path, $value);

        return true;
    }

    /**
     * @param $key
     *
     * @return $this
     */
    public function remove($key)
    {

        if (isset($this->_lifeTimes[$key])) {

            unset($this->_lifeTimes[$key]);
            $this->_format->save($this->_lifeTimePath, $this->_lifeTimes);
        }

        return unlink($this->getKeyPath($key));
    }

    public function clear()
    {

        $success = true;
        foreach (scandir($this->_options['path']) as $file)
            if (!unlink($file))
                $success = false;

        return $success;
    }
}