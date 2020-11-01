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
        $params = parse_url($this->uri, PHP_URL_QUERY);
        $path = parse_url($this->uri, PHP_URL_PATH);

        if (is_null($params)) {
            $explode = explode('/', $path);
            $last = end($explode);

            if (strval($last) === strval(intval($last))) {
                $params = (int) $last;
            }
        }

        return $this->compileParameters($params);
    }

    /**
     * Compile parameters
     * @param  mixed $params
     * @return mixed
     */
    public function compileParameters($params)
    {
        if (is_null($params)) {
            return $params;
        }

        if (is_int($params)) {
            return ['id' => $params];
        }

        parse_str($params, $compileParams);
        return $compileParams;
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
