<?php

namespace Atom\Http;

use Atom\Http\Globals;

class Request
{
	public $request;
	public $uri;
	public $method;
	public $get;
	public $post;
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

		return $params;
	}


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
