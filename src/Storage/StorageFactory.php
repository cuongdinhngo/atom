<?php

namespace Atom\Storage;

use Atom\Storage\Exception\StorageException;

class StorageFactory
{
    public $storageConfig;

    /**
     * Storage Factory construct
     * @param string $type
     */
    public function __construct(string $type)
    {
        $this->storageConfig = config('app.storage.'.$type);
    }

    /**
     * Open Storage Drive
     * @return mixed
     */
    public function init()
    {
        return match ($this->storageConfig["driver"]) {
            'local' => new LocalService($this->storageConfig["path"]),
            default => throw new StorageException(StorageException::ERR_MSG_NOT_FOUND),
        };
    }
}
