<?php

define('DOC_ROOT', $_SERVER["DOCUMENT_ROOT"]);
define('CONFIG_PATH', DOC_ROOT.'/../config/');
define('ROUTE_PATH', DOC_ROOT.'/../app/Routes/');
define('CONTROLLER_PATH', DOC_ROOT.'/../app/Controllers/');
define('VIEW_PATH', DOC_ROOT.'/../resources/views/');
define('STORAGE_PATH', DOC_ROOT.'/../storage/');
define('LOG_PATH', DOC_ROOT.'/../storage/logs/');

if (!function_exists('config')) {
    /**
     * Load configured files
     * @param  string $params
     * @return mixed
     */
	function config($params = null) 
	{
		if (is_null($params)) {
			return;
		}

		$keys = explode('.', $params);
		$fileName = array_shift($keys);
		$searchFile = searchFile(CONFIG_PATH . $fileName . '.*');

        if (strpos($searchFile, '.ini')) {
        	$envs = parse_ini_file($searchFile, false);
            foreach ($envs as $env => $value) {
                putenv("{$env}={$value}");
            }
        } else {
        	return obtainValue($searchFile, $keys);	
        }
	}
}

if (!function_exists('route')) {
    /**
     * Load route file
     * @param  string $params
     * @return mixed
     */
	function route($params = null)
	{
		if (is_null($params)) {
			return;
		}

		$keys = explode('.', $params);
		$fileName = array_shift($keys);
		$filePath = ROUTE_PATH . $fileName . '.php';

        return obtainValue($filePath, $keys);
	}
}

if (!function_exists('searchFile')) {

	function searchFile(string $path)
	{
		$searchFile = glob($path);
		if (empty($searchFile)) {
            throw new \Exception('File Is Not Existed');
        }
		return $searchFile[0];
	}
}

if (!function_exists('obtainValue')) {

	function obtainValue(string $file, array $keys)
	{
        if (!file_exists($file)) {
            throw new \Exception('File Is Not Existed');
        }

		$data = require_once($file);
    	foreach ($keys as $key) {
    		$data = $data[$key];
    	}
    	return $data;
	}
}

if (!function_exists('getHeaders'))
{
    /**
     * Get HTTP Headers
     * @return array
     */
    function getHeaders()
    {
        $headers = [];
       	foreach ($_SERVER as $name => $value)
       	{
           	if (substr($name, 0, 5) == 'HTTP_')
           	{
            	$headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
           	}
       	}
       	return $headers;
    }
}

if (!function_exists('isApi'))
{
    function isApi()
    {
        $headers = getHeaders();
        return (bool) strpos($_SERVER['REQUEST_URI'], 'api') || $headers['Content-Type'] == 'application/json';
    }
}

if (!function_exists('env'))
{
    /**
     * Get variable's value
     * @param  string|null $varName [description]
     * @return mixed
     */
    function env(string $varName = null)
    {
        return getenv($varName);
    }
}

if (!function_exists('view'))
{
    /**
     * Render view
     * @param  string $directory [description]
     * @param  array  $data      [description]
     * @return void
     */
    function view(string $directory, array $data = [])
    {
        if (!is_array($data)) {
            throw new \Exception('Invalid Arguments');
        }
        $file = VIEW_PATH . $directory . '.php';
        if (!file_exists($file)) {
            throw new \Exception('Invalid Directory');
        }

        extract($data);
        include $file;
    }
}

if (!function_exists('stripSpace'))
{
    /**
     * Strip whitespace
     * @param  string|null $string
     * @return string
     */
    function stripSpace(string $string = null)
    {
        return str_replace(' ', '', $string);
    }
}

if (!function_exists('now'))
{
    /**
     * Now
     * @return string
     */
    function now()
    {
        return date("Y-m-d H:i:s");
    }
}

if (!function_exists('today'))
{
    /**
     * Today
     * @return string
     */
    function today()
    {
        return date("Y-m-d");
    }
}