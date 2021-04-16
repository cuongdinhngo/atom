<?php

namespace Atom\Http;

use Atom\Http\Globals;
use Atom\Http\Exception\RouterException;

class Router
{
    /**
     * Route path
     * @var string
     */
    protected $path;

    /**
     * Router construct
     */
    public function __construct()
    {
        $this->path = Globals::path();
    }

    /**
     * Dispatch Router
     * @return array
     */
    public function dispatchRouter()
    {
        $routeData = $this->identifyRouteData();
        if (empty($routeData)) {
            throw new RouterException(RouterException::ERR_MSG_INVALID_ROUTE);
        }

        return $routeData;
    }

    /**
     * Get Route Data By Path
     * @return mixed
     */
    public function getRouteDataByPath()
    {
        if (isApi()) {
            return route('api.' . $this->path);
        }
        return route('web.' . $this->path);
    }

    /**
     * Identify Route data
     *
     * @return array
     */
    public function identifyRouteData()
    {
        $patternCurrentUri = preg_replace("/[0-9]+/", '#', $this->path);
        $routers = isApi() ? route('api') : route('web');
        foreach ($routers as $route => $data) {
            $route = preg_replace('/\{[a-zA-Z]+\}+/', '#', $route);
            if ($route == $patternCurrentUri) {
                return $data;
            }
        }
    }
}
