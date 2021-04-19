<?php

namespace Atom\Models;

use Atom\Db\Database;
use Atom\Traits\HasAttributes;

abstract class Model extends Database
{
    use HasAttributes;

    /**
     * Get table
     * @return string
     */
    public function getTable()
    {
        return $this->table;
    }

    /**
     * Convert data to array
     *
     * @return array
     */
    public function toArray()
    {
        return (array) $this->attributes;
    }
}
