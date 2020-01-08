<?php

namespace Atom\Db;

interface DatabaseInterface
{
    public function delete();
    public function update(array $data);
    public function insert(array $data);
}
