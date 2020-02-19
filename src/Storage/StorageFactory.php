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
        switch ($this->storageConfig["driver"]) {
            case 'local':
                return new LocalService($this->storageConfig["path"]);
                break;
            case 's3':
                # code...
                break;
            default:
                throw new StorageException(StorageException::ERR_MSG_NOT_FOUND);
        }
    }
}
