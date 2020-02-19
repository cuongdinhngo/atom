<?php

namespace Atom\IMDB;

use Predis;
use Atom\IMDB\Exception\ImdbException;

class Redis extends Predis\Client
{
    /**
     * Redis construct
     */
    public function __construct()
    {
        try {
            $connection = [
                'scheme' => env('REDIS_SCHEMA'),
                'host' => env('REDIS_HOST'),
                'port' => env('REDIS_PORT'),
                'database' => env('REDIS_DATABASE') ? env('REDIS_DATABASE') : 0
            ];
            parent::__construct($connection);
            $this->connect();
        } catch (Predis\Connection\ConnectionException $e) {
            throw new ImdbException(ImdbException::ERR_REDIS_CONNECTION_FAIL.': '.$e->getMessage());
        }
    }

    /**
     * Filter existed keys
     * @param  array  $keys
     * @return array
     */
    public function keyFilter(array $keys)
    {
        foreach ($keys as $key => $value) {
            if (false === (bool) $this->exists($value)) {
                unset($keys[$key]);
            }
        }
        return $keys;
    }

    /**
     * Scan and list all keys by type
     * @return array
     */
    public function scanKeys()
    {
        $result = [];
        $keys = $this->keys('*');
        foreach ($keys as $key) {
            $type = $this->type($key);
            $result[(string) $type][] = $key;
        }
        return $result;
    }

}
