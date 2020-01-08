<?php

namespace Atom\Http;

use Atom\Http\Globals;
use Atom\Http\Exception\RouterException;

class Router
{
    public $path;
    public $method;

    /**
     * Router construct
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
        if (empty($call)) {
            throw new RouterException(RouterException::ERR_MSG_INVALID_ROUTE);
        }

        $actions = array_column($call, strtolower($this->method));
        list($class, $function) = explode('@', $actions[0]);
        if (empty($class)) {
            throw new RouterException(RouterException::ERR_MSG_INVALID_ROUTE);
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
