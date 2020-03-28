<?php

namespace Atom\Db;

use Atom\Db\Exception\DatabaseException;

class Driver
{
    /**
     * Database Driver
     * @var string
     */
    protected $driver;

    /**
     * Database Host
     * @var string
     */
    protected $host;

    /**
     * Database User
     * @var string
     */
    protected $user;

    /**
     * User password
     * @var string
     */
    protected $password;

    /**
     * Database Name
     * @var string
     */
    protected $database;

    /**
     * Database Port
     * @var string
     */
    protected $port;

    /**
     * Driver construct
     *
     * @param string|null $driver   Database Driver
     * @param string|null $host     Database Host
     * @param string|null $user     Database User
     * @param string|null $password User Password
     * @param string|null $database Database Name
     * @param string|null $port     Database Port
     */
    public function __construct(string $driver = null, string $host = null, string $user = null, string $password = null, string $database = null, string $port = null)
    {
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
        switch ($this->driver) {
            case 'mysql':
                return new MySQL($this->host, $this->user, $this->password, $this->database, $this->port);
            default:
                throw new DatabaseException(DatabaseException::ERR_MSG_INVALID_DRIVER);
            break;
        }
    }
}
