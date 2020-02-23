<?php

namespace Atom\Storage;

use Atom\Storage\StorageInterface;
use Atom\Storage\Exception\StorageException;

class LocalService implements StorageInterface
{
    public $storage;

    /**
     * Local Storage Service
     * @param string $path
     */
    public function __construct(string $path)
    {
        $this->storage = $path;
    }

    /**
     * Upload file
     * @param  string $directory /storage/images/test.jpg
     * @param  string  $file
     * @return void
     */
    public function upload(string $directory, string $file)
    {
        try {
            $fileData = file_get_contents($file);
            file_put_contents($directory, $fileData);
        } catch (\Exception $e) {
            throw new StorageException(StorageException::ERR_MSG_UPLOAD_FAIL);
        }
    }

    /**
     * Get Full URL
     * @param  string|null $directory
     * @return string
     */
    public function getFullUrl(string $directory = null)
    {
        $child = array_filter(explode('/', $directory));
        $parent = array_shift($child);
        if ($parent == 'public') {
            return public_path('/'.implode('/', $child));
        }
        return $directory ? storage_path($directory) : storage_path();
    }

    /**
     * [remove description]
     * @return [type] [description]
     */
    public function remove(string $fileName)
    {
        unlink($fileName);
    }

    /**
     * Check Directory is existed
     * @param  string $directory
     * @return mixed
     */
    public function checkDirectory(string $directory)
    {
        if (false === file_exists($directory)) {
            throw new StorageException(StorageException::ERR_MSG_NOT_FOUND);
        }

        return;
    }
}
