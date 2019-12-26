<?php

namespace Atom\Http;

class Globals
{
    /**
     * Route path
     * @return string
     */
    public static function path()
    {
        $uri = static::uri();
        $path = parse_url($uri, PHP_URL_PATH);
        $explode = explode('/', $path);

        if (is_null(parse_url($uri, PHP_URL_QUERY))) {
            $last = end($explode);
            if (strval($last) === strval(intval($last))) {
                array_pop($explode);
            }
        }

        $path = implode('/', $explode);

        return $path;
    }

    /**
     * URI
     * @return string
     */
    public static function uri()
    {
        if (isApi()) {
            $uri = static::server('REQUEST_URI');
            return (bool) strpos($uri, 'api') ? substr($uri, 4) : $uri;
        }
        return static::server('REQUEST_URI');
    }

    /**
     * Http Method
     * @return string
     */
    public static function method()
    {
        return static::server('REQUEST_METHOD');
    }

    /**
     * Server data
     * @param  string|null $element
     * @return string|array
     */
    public static function server(string $element = null)
    {
        return is_null($element) ? $_SERVER : $_SERVER[$element];
    }

    /**
     * Http GET
     * @return array
     */
    public static function get()
    {
        return $_GET;
    }

    /**
     * Http POST
     * @return array
     */
    public static function post()
    {
        return $_POST;
    }

    /**
     * Http FILES
     * @return array
     */
    public static function files()
    {
        return $_FILES;
    }

    /**
     * Globals __callStatic
     * @param  string $method
     * @param  mixed $args
     * @return void
     */
    public static function __callStatic($method, $args) {
        $called = get_called_class();
        $class = new $called();
        return call_user_func_array([$class, $method], $args);
    }

    /**
     * Start session
     */
    public static function sessionStart()
    {
        session_start();
    }

    /**
     * Get session id
     * @return string
     */
    public static function sessionID()
    {
        static::checkSession();

        return session_id();
    }

    /**
     * Check is session status
     */
    private static function checkSession()
    {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * @param $name
     * @param $val
     */
    public static function setSession($name = null, $val = null)
    {
        if ($name) {
            static::checkSession();
            $_SESSION[$name] = $val;
        }
    }

    /**
     * @param string $name
     * @return bool|null
     */
    public static function session($name = null)
    {
        static::checkSession();
        if (!$name) {
            return $_SESSION;
        }
        if (isset($_SESSION[$name])) {
            return $_SESSION[$name];
        }

        return false;
	}
}
