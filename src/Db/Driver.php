<?php

namespace Atom\Db;

class Driver
{
    protected $driver;
    protected $db;

    /**
     * Driver construct
     */
    public function __construct()
    {
        $this->driver = env('DB_CONNECTION');
    }

    /**
     * Create connection
     * @return void
     */
    public function createConnection()
    {
        switch ($this->driver) {
            case 'mysql':
                return new MySQL(env('DB_HOST'), env('DB_USER'), env('DB_PASSWORD'), env('DB_NAME'), env('DB_PORT'));
            break;
        }
    }
}
