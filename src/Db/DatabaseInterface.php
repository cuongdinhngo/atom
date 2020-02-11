<?php

namespace Atom\Db;

interface DatabaseInterface
{
    public function delete();
    public function update();
    public function insert();
}
