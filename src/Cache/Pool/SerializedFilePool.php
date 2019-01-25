<?php declare(strict_types=1);

namespace Tale\Cache\Pool;

use DateTimeImmutable;
use Psr\Cache\CacheItemInterface;
use Tale\Cache\AbstractPool;
use Tale\Cache\Item;
use Tale\Cache\ItemInterface;
use function Tale\cache_item_hit;
use function Tale\cache_item_miss;

final class SerializedFilePool extends AbstractPool
{
    /** @var string */
    private $directory;

    /** @var int */
    private $createMode;

    /**
     * SerializedFilePool constructor.
     * @param string $directory
     * @param int $createMode
     */
    public function __construct(string $directory, int $createMode = 0775)
    {
        $this->directory = rtrim($directory, '\\/');
        $this->createMode = $createMode;
    }

    /**
     * @param string $key
     * @return CacheItemInterface|Item
     * @throws \Exception
     */
    public function getItem($key)
    {
        $path = $this->getPathFromKey($key);
        if (file_exists($path) && is_file($path)) {
            /** @var DateTimeImmutable|null $expirationTime
             */
            [$expirationTime, $value] = unserialize(file_get_contents($path));
            if ($expirationTime === null
                || date_create_immutable()->getTimestamp() <= $expirationTime->getTimestamp()) {
                return cache_item_hit($key, $value, $expirationTime);
            }
            //Remove file that timed out
            unlink($path);
        }
        return cache_item_miss($key);
    }

    public function clear(): bool
    {
        return is_dir($this->directory) && $this->removeDirectoryContents($this->directory);
    }

    private function removeDirectoryContents(string $directory): bool
    {
        $success = true;
        $files = scandir($directory, SCANDIR_SORT_NONE);
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            $fullPath = "{$directory}/{$file}";
            if (is_dir($fullPath)) {
                if (!$this->removeDirectoryContents($fullPath)) {
                    $success = false;
                }
                rmdir($fullPath);
                continue;
            }

            if (!unlink($fullPath)) {
                $success = false;
            }
        }
        return $success;
    }

    public function deleteItem($key): bool
    {
        $path = $this->getPathFromKey($key);
        return file_exists($path) && unlink($path);
    }

    public function save(CacheItemInterface $item)
    {
        $this->filterItem($item);
        /** @var ItemInterface $item */
        $path = $this->getPathFromKey($item->getKey());
        $this->ensureDirectoryExists();
        return file_put_contents($path, serialize([$item->getExpirationTime(), $item->get()]));
    }

    private function ensureDirectoryExists(): void
    {
        if (!is_dir($this->directory)
            && !mkdir($this->directory, $this->createMode, true)
            && !is_dir($this->directory)) {
            throw new \RuntimeException("Failed to create cache directory {$this->directory}");
        }
    }

    private function getPathFromKey(string $key): string
    {
        $subPath = str_replace('.', '/', $key);
        return "{$this->directory}/{$subPath}.cache";
    }
}