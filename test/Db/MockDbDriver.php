<?php

namespace Atom\Test\Db;

/**
 * Minimal mock of the MySQL driver interface for testing
 */
class MockDbDriver
{
    public function escape($data)
    {
        return addslashes($data);
    }

    public function query($sql)
    {
        return true;
    }

    public function error()
    {
        return '';
    }

    public function resultToArray($result)
    {
        return [];
    }

    public function lastestInsertId()
    {
        return 1;
    }

    public function beginTransaction()
    {
        return true;
    }

    public function commit()
    {
        return true;
    }

    public function rollback()
    {
        return true;
    }
}
