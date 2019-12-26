<?php

namespace Atom\Db;

use mysqli;
use Atom\Db\Exception\DatabaseException;

class MySQL
{
    protected $mysqli;
    protected $table;
    protected $result;

    /**
     * MySQL Construct
     * @param string $host     MySQL Host
     * @param string $user     MySQL User
     * @param string $password MySQL Password
     * @param string $db       MySQL Database
     * @param string $port     MySQL Port
     */
    public function __construct($host, $user, $password, $db, $port)
    {
        if (!$this->mysqli = new mysqli($host, $user, $password, $db, $port)) {
            throw new \Exception(DatabaseException::ERR_MSG_CONNECTION_FAIL);
        }
    }

    /**
     * Execute MySQL query
     * @param  string $query
     * @return void
     */
    public function query(string $query)
    {
        return $this->mysqli->query($query);
    }

    /**
     * MySQL escapse
     * @param  mixed $sql
     * @return mixed
     */
    public function escape($sql)
    {
        return $this->mysqli->real_escape_string($sql);
    }

    /**
     * Return MySQL request to Array
     * @param  mysql_result $result
     * @return array
     */
    public function resultToArray($result)
    {
        $arr = [];
        while ($row = $result->fetch_assoc()) {
            $arr[] = $row;
        }

        return $arr;
    }

    /**
     * MySQL error
     * @return string
     */
    public function error()
    {
        return $this->mysqli->error;
    }

}
