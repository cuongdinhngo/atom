<?php

namespace Atom\Views;

use Atom\Views\Exception\ViewException;

class ViewFactory
{
	static $data;
	static $directory;

	/**
	 * View Factory construct
	 * @param string $directory
	 * @param array  $data
	 */
	public function __construct(string $directory, array $data)
	{
		static::$data = $data;
		static::$directory = $directory;
	}

	/**
	 * Create View
	 * @return void
	 */
	public static function createView()
	{
		$file = VIEW_PATH . static::$directory . '.php';
        if (!file_exists($file)) {
            throw new ViewException(ViewException::ERR_MSG_INVALID_DIR);
        }

        extract(static::$data);
        include $file;
	}
}