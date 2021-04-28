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

    /**
     * Save (insert or dupdate)
     *
     * @return void
     */
    public function save()
    {
        $this->insertDuplicate($this->getAttributes());
    }

    /**
     * Create new
     *
     * @param  array  $data [description]
     *
     * @return [type]       [description]
     */
    public function create(array $data)
    {
        $id = $this->insert($data);
        $this->mapAttributes(array_merge($data, ["id" => $id]));
        return $this;
    }

    /**
     * Find all records by id
     *
     * @param  [type] $id [description]
     *
     * @return [type]     [description]
     */
    public function find($id)
    {
        if (is_array($id)) {
            return $this->whereIn("id", $id)->get();
        } else {
            return $this->where(["id", $id])->first();
        }
    }

    /**
     * Delete multi records
     *
     * @param  [type] $id [description]
     *
     * @return [type]     [description]
     */
    public function destroy($id)
    {
        if (is_array($id)) {
            array_walk($id, function ($item) {
                $this->where(["id", $item])->delete();
            });
        } else {
            $this->where(["id", $id])->delete();
        }
    }
}
