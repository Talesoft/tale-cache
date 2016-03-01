<?php

namespace Tale\Cache\Adapter;

use Tale\Cache\Adapter\File\Format\Json;
use Tale\Cache\Adapter\File\Format\Serialize;
use Tale\Cache\Adapter\File\FormatInterface;
use Tale\Cache\AdapterInterface;
use Tale\ConfigurableTrait;

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
    use ConfigurableTrait;

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

        $this->defineOptions([
            'path' => getcwd().'/cache',
            'formats' => [
                'json' => Json::class,
                'serialize' => Serialize::class
            ],
            'format' => 'json',
            'ignoredFiles' => ['.gitignore', '.htaccess'],
            'lifeTimeKey' => 'cache-lifetimes'
        ], $options);

        $formats = $this->getOption('formats');
        $format = $this->getOption('format');

        if (!isset($formats[$format]) || !is_subclass_of($formats[$format], FormatInterface::class))
            throw new \InvalidArgumentException(
                "The 'format' option should point to a valid ".FormatInterface::class."-class name in the 'formats' option"
            );

        $formatClassName = $formats[$format];
        $this->_format = new $formatClassName();
        $this->_lifeTimePath = $this->getKeyPath($this->getOption('lifeTimeKey'));
        $this->_lifeTimes = [];

        $this->loadLifeTimes();
    }

    public function __clone()
    {

        $this->_format = clone $this->_format;
        $this->loadLifeTimes();
    }

    /**
     * Returns the current cache storage directory path
     *
     * @return string
     */
    public function getPath()
    {

        return $this->getOption('path');
    }

    /**
     * @return FormatInterface
     */
    public function getFormat()
    {
        return $this->_format;
    }

    /**
     * @return string
     */
    public function getLifeTimePath()
    {
        return $this->_lifeTimePath;
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
        return implode('', [$this->getPath(), "/$key", $this->_format->getExtension()]);
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
            if (!mkdir($dir, 0777, true))
                return false;

        $this->_lifeTimes[$key] = intval($lifeTime);

        $this->saveLifeTimes();

        //Save the cache content
        return $this->_format->save($path, $value);
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
            $this->saveLifeTimes();
        }

        $path = $this->getKeyPath($key);

        return !file_exists($path) || unlink($path);
    }

    public function clear()
    {

        $ignoredFiles = array_merge(['.', '..'], $this->_options['ignoredFiles']);

        $success = true;
        foreach (scandir($this->_options['path']) as $file)
            if (!in_array($file, $ignoredFiles, true))
                if (!unlink($this->_options['path']."/$file"))
                    $success = false;

        return $success;
    }

    protected function loadLifeTimes()
    {

        $this->_lifeTimes = [];
        if (file_exists($this->_lifeTimePath))
            $this->_lifeTimes = $this->_format->load($this->_lifeTimePath);

        return $this;
    }

    protected function saveLifeTimes()
    {

        if (count($this->_lifeTimes) > 0)
            $this->_format->save($this->_lifeTimePath, $this->_lifeTimes);

        return $this;
    }
}