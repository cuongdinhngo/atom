<?php

namespace Atom\Http;

use Atom\Http\Globals;

class Request
{
    /**
     * Request
     * @var array
     */
    public $request;

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
    }

    /**
     * Create request
     * @return $this
     */
    public function create()
    {
        $this->request = $this->collectParameters();
        return $this;
    }

    /**
     * Convert request to array
     * @return array
     */
    public function all()
    {
        return (array) $this->collectParameters();
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
        $content = file_get_contents('php://input');
        $data = json_decode($content, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $data;
        }
        return [$content];
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
}
