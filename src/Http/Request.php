<?php

namespace Atom\Http;

use Atom\Http\Globals;
use ArrayAccess;

class Request implements ArrayAccess
{
    /**
     * Request
     * @var array
     */
    public $request = [];

    /**
     * URI
     * @var string
     */
    public $uri;

    /**
     * Request method
     * @var string
     */
    public $method;

    /**
     * Get Request by GET method
     * @var array
     */
    public $get;

    /**
     * Get Request by POST method
     * @var array
     */
    public $post;

    /**
     * Get Request by File
     * @var array
     */
    public $files;

    /**
     * Request construct
     */
    public function __construct()
    {
        $this->uri = Globals::uri();
        $this->method = Globals::method();
        $this->get = Globals::get();
        $this->post = Globals::post();
        $this->files = Globals::files();
        $this->request = (array) $this->collectParameters();
    }

    /**
     * Convert request to array
     * @return array
     */
    public function all()
    {
        return $this->request;
    }

    /**
     * Get headers
     * @param  string|null $key
     * @return string|array
     */
    public function headers(string $key = null)
    {
        $headers = getHeaders();
        return is_null($key) ? $headers : $headers[$key];
    }

    /**
     * Collect parameters
     * @return object
     */
    public function collectParameters()
    {
        $params = [];

        if (!empty($tmpParams = $this->extractUriParameters())) {
            $params = array_merge($params, $tmpParams);
        }

        if (!empty($tmpParams = $this->getParametersByMethod())) {
            $params = array_merge($params, $tmpParams);
        }

        if (!empty($tmpParams = $this->files)) {
            $params = array_merge($params, $tmpParams);
        }

        if (!empty($tmpParams = $this->getRawData())) {
            $params = array_merge($params, $tmpParams);
        }

        return $params;
    }

    /**
     * Get raw data
     * @return array
     */
    public function getRawData()
    {
        if (empty($content = file_get_contents('php://input'))){
            return [];
        }
        $data = json_decode($content, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $data;
        }
        parse_str($content, $data);
        return $data;
    }

    /**
     * Get parameters by method
     */
    public function getParametersByMethod()
    {
        switch ($this->method) {
            case 'GET':
                return $this->get;
                break;
            case 'POST':
            case 'PUT':
            case 'PATCH':
                return $this->post;
                break;
        }
    }

    /**
     * Extract uri parameters
     * @return mixed
     */
    public function extractUriParameters()
    {
        parse_str(parse_url($this->uri, PHP_URL_QUERY), $params);
        return array_merge($params, $this->parseUriParams());
    }

    /**
     * Parse parameters in URI
     *
     * @return array
     */
    public function parseUriParams()
    {
        $path = Globals::path();
        $patternCurrentUri = preg_replace("/[0-9]+/", '#', $path);
        $routers = isApi() ? route('api') : route('web');
        foreach ($routers as $route => $data) {
            $tmp = preg_replace('/\{[a-zA-Z]+\}+/', '#', $route);
            if ($tmp == $patternCurrentUri) {
                return $this->mapParams($path, $route);
            }
        }
    }

    /**
     * Map key and value for Parameters
     *
     * @param  [type] $current [description]
     * @param  [type] $expect  [description]
     *
     * @return [type]          [description]
     */
    public function mapParams($current, $expect)
    {
        $arrUri = explode('/', $current);
        $arrRoute = explode('/', $expect);
        $tmp = array_combine($arrRoute, $arrUri);
        $params = [];
        foreach($tmp as $key => $value) {
            if (preg_match('/^\{|\}$/', $key)) {
                $key = preg_replace('/^.|.$/','',$key);
                $params[$key] = $value;
            }
        }
        return $params;
    }

    /**
     * Get a data by key
     *
     * @param string The key data to retrieve
     * @access public
     */
    public function &__get ($key) {
        return $this->request[$key];
    }

    /**
     * Assigns a value to the specified data
     *
     * @param string The data key to assign the value to
     * @param mixed  The value to set
     * @access public
     */
    public function __set($key,$value) {
        $this->request[$key] = $value;
    }

    /**
     * Whether or not an data exists by key
     *
     * @param string An data key to check for
     * @access public
     * @return boolean
     * @abstracting ArrayAccess
     */
    public function __isset ($key) {
        return isset($this->request[$key]);
    }

    /**
     * Unsets an data by key
     *
     * @param string The key to unset
     * @access public
     */
    public function __unset($key) {
        unset($this->request[$key]);
    }

    /**
     * Assigns a value to the specified offset
     *
     * @param string The offset to assign the value to
     * @param mixed  The value to set
     * @access public
     * @abstracting ArrayAccess
     */
    public function offsetSet($offset,$value) {
        if (is_null($offset)) {
            $this->request[] = $value;
        } else {
            $this->request[$offset] = $value;
        }
    }

    /**
     * Whether or not an offset exists
     *
     * @param string An offset to check for
     * @access public
     * @return boolean
     * @abstracting ArrayAccess
     */
    public function offsetExists($offset) {
        return isset($this->request[$offset]);
    }

    /**
     * Unsets an offset
     *
     * @param string The offset to unset
     * @access public
     * @abstracting ArrayAccess
     */
    public function offsetUnset($offset) {
        if ($this->offsetExists($offset)) {
            unset($this->request[$offset]);
        }
    }

    /**
     * Returns the value at specified offset
     *
     * @param string The offset to retrieve
     * @access public
     * @return mixed
     * @abstracting ArrayAccess
     */
    public function offsetGet($offset) {
        return $this->offsetExists($offset) ? $this->request[$offset] : null;
    }
}
