<?php

namespace Atom\File;

use Atom\File\Exception\LogException;

class Log
{
	static $file;
	static $msg;
	static $datetime;

	const INFO = "INFO";
	const DEBUG = "DEBUG";
	const ERROR = "ERROR";

	/**
	 * Log is used
	 * @return boolean
	 */
	public static function isUse()
	{
		return env('DEV_LOG');
	}

	/**
	 * Get Log file
	 * @return string
	 */
	public static function logFile()
	{
		if (!static::isUse()) {
			throw new LogException(LogException::ERR_MSG_DEV_OFF);			
		}

		$logFile = LOG_PATH . env('DEV_LOG_FILE');

		if (!file_exists($logFile))
		{
			mkdir(LOG_PATH, 0777, true);
		}

		return $logFile;
	}

	/**
	 * Record log
	 * @return void
	 */
	protected static function record()
	{
		$file = static::logFile();
		$datetime = date('Y-m-d H:i:s');
		file_put_contents($file, PHP_EOL . "[{$datetime}]\t" .static::$msg , FILE_APPEND);
	}

	/**
	 * ERROR log
	 * @param  mixed $msg
	 * @return void
	 */
	public static function error($msg)
	{
		static::$msg = self::ERROR . "\t" .print_r($msg, true);
		static::record();
	}

	/**
	 * INFO log
	 * @param  mixed $msg
	 * @return void
	 */
	public static function info($msg)
	{
		static::$msg = self::INFO . "\t" .print_r($msg, true);
		static::record();
	}

	/**
	 * DEBUG log
	 * @param  mixed $msg
	 * @return void
	 */
	public static function debug($msg)
	{
		static::$msg = self::DEBUG . "\t" .print_r($msg, true);
		static::record();
	}
}
