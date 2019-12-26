<?php

namespace Atom\Models;

use Atom\Db\Database;

abstract class Model extends Database
{
	/**
	 * Get table
	 * @return string
	 */
	public function getTable()
	{
		return $this->table;
	}
}