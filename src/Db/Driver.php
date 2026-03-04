<?php

namespace Atom\Db;

use Atom\Db\Exception\DatabaseException;

class Driver
{
    public function __construct(
        protected ?string $driver = null,
        protected ?string $host = null,
        protected ?string $user = null,
        protected ?string $password = null,
        protected ?string $database = null,
        protected ?string $port = null,
    ) {
        $this->driver   = $driver ?? env('DB_CONNECTION');
        $this->host     = $host ?? env('DB_HOST');
        $this->user     = $user ?? env('DB_USER');
        $this->password = $password ?? env('DB_PASSWORD');
        $this->database = $database ?? env('DB_NAME');
        $this->port     = $port ?? env('DB_PORT');
    }

    /**
     * Create connection
     *
     * @return Object
     * @throws Exception
     */
    public function createConnection()
    {
        return match ($this->driver) {
            'mysql' => new MySQL($this->host, $this->user, $this->password, $this->database, $this->port),
            default => throw new DatabaseException(DatabaseException::ERR_MSG_INVALID_DRIVER),
        };
    }
}
