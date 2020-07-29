<?php

namespace Atom\Db;

use PDO;

abstract class PHPDataObjects
{
    public $db;
    public $sth;
    protected $table;
    protected $params = [];

    /**
     * Create a PDO instance
     */
    public function __construct()
    {
        $this->connect($host, $user, $password, $db, $port);
    }

    /**
     * Connect to Database
     *
     * @param string $host     DB Host
     * @param string $user     DB User
     * @param string $password DB User's password
     * @param string $db       DB Name
     * @param string $port     DB Port
     *
     * @return void
     */
    public function connect()
    {
        try {
            $this->db = new PDO(env('DB_CONNECTION') . ':dbname=' . env('DB_NAME') .';host=' . env('DB_HOST') . ';port=' . env('DB_PORT'), env('DB_USER'), env('DB_PASSWORD'));
        } catch (PDOException $e) {
            throw new \Exception(DatabaseException::ERR_MSG_CONNECTION_FAIL . ' => ' . $e->getMessage());
        }
    }

    /**
     * Set params
     *
     * @param array $data Params
     */
    public function setParams(array $data)
    {
        $this->params = $data;
    }

    /**
     * Execute query
     *
     * @param  string $query SQL query
     *
     * @return boolean
     */
    public function execute($query)
    {
        $this->sth = $this->db->prepare($query);
        foreach ($this->params as $key => $value) {
            $this->sth->bindParam(':'.$key, $value);
        }
        $this->sth->execute();
        return $this->sth;
    }

    public function update(array $data)
    {
        var_dump($keys = array_keys($data));
        $tmp = [];
        foreach ($data as $key => $value) {
            $tmp = "$key = :{$key}";
        }
        var_dump($tmp);
        $this->setParams($data);
        $query = "UPDATE {$this->table} SET ".implode(", ", $tmp);
        var_dump($query);
        $this->execute($query);
    }

    /**
     * Insert data
     *
     * @param  array  $data Request data
     *
     * @return array
     */
    public function insert(array $data)
    {
        $keys = array_keys($data);
        $callBack = function ($key) {
            return ":".$key;
        };
        $query = "INSERT INTO {$this->table}(". implode(', ', $keys). ") VALUES(". implode(',', array_map($callBack, $keys)) .")";
        $this->setParams($data);
        $this->execute($query);
        return ['id' => $this->getLastInsertId()];
    }

    /**
     * Get data
     *
     * @return array
     */
    public function get()
    {
        $query = "SELECT * FROM {$this->table}";
        return $this->execute($query)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get last insert id
     *
     * @return int
     */
    public function getLastInsertId()
    {
        return $this->db->lastInsertId();
    }
}
