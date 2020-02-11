<?php

namespace Atom\Http;

use Atom\Http\Globals;
use Atom\Http\Exception\RouteException;

class Route
{
    public $path;
    public $method;

    /**
     * Route construct
     */
    public function __construct()
    {
        $this->path = Globals::path();
        $this->method = Globals::method();
    }

    /**
     * Dispatch controller
     * @return array
     */
    public function dispatchController()
    {
        $call = $this->dispatchRoute();
        if (is_null($call)) {
            throw new \Exception(RouteException::ERR_MSG_INVALID_ROUTE);
        }

        if (isset($call['controller']) && !empty($call['controller'])) {
            $class = $call['controller'];
        }

        if (isset($call['actions']) && !empty($call['actions'])) {
            $actions = array_column($call['actions'], strtolower($this->method));
            list($class, $function) = explode('@', $actions[0]);
        }
        return [$class, $function];
    }

    /**
     * Dispatch route
     * @return mixed
     */
    public function dispatchRoute()
    {
        if (isApi()) {
            return route('api.' . $this->path);
        }
        return route('web.' . $this->path);
    }
}
