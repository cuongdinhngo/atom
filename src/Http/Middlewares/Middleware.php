<?php

namespace Atom\Http\Middlewares;

use Atom\Http\Middlewares\Exception\MiddlewareException;

class Middleware
{
    /**
     * Available Middlewares
     * @var array
     */
    protected $middlewares;

    /**
     * Implemented Middlewares
     * @var array
     */
    protected $impMiddlewares;

    /**
     * Priority Middlewares
     * @var array
     */
    protected $priorityMiddlewares;

    /**
     * Middleware construct
     * @param array $middlewares Implemented middlwares
     */
    public function __construct($impMiddlewares)
    {
        $this->impMiddlewares = $impMiddlewares;
        $this->middlewares = config('middleware.routeMiddlewares');
        $this->priorityMiddlewares = config('middleware.priorityMiddlewares');
    }

    /**
     * Execute middlewares
     * @return void
     */
    public function execute()
    {
        $this->checkMiddlewares();
        $this->sortMiddlewares();
        foreach ($this->impMiddlewares as $middlewareAlias) {
            $middlewareClass = $this->middlewares[$middlewareAlias];
            $this->runMiddleware($middlewareClass);
        }
    }

    /**
     * Run middleware
     * @param  string $class Middleware class name
     * @return void
     */
    public function runMiddleware($class)
    {
        $middleware = $this->loadMiddlewares($class);
        $method = 'handle';

        if (!method_exists($middleware, $method)) {
            throw new MiddlewareException(MiddlewareException::ERR_MSG_MIDDLEWARE_NOT_EXISTS);
        }

        $result = $middleware->$method();

        if ($result === false) {
            throw new MiddlewareException(MiddlewareException::ERR_MSG_MIDDLEWARE_FAIL);
        }
    }

    /**
     * Load middleware class
     * @param  string $class Middleware class name
     * @return object
     */
    public function loadMiddlewares($class)
    {
        $file = MIDDLEWARE_PATH . $class . '.php';
        if (!file_exists($file)) {
            throw new MiddlewareException(MiddlewareException::ERR_MSG_MIDDLEWARE_NOT_EXISTS);
        }
        $class = str_replace("/", "\\", $class);

        require_once($file);

        $middlewareClass = env('APP_NAMESPACE').'\\Middlewares\\'.$class;
        $middleware = new $middlewareClass();

        if ($middleware) {
            return $middleware;
        }
        throw new MiddlewareException(MiddlewareException::ERR_MSG_MIDDLEWARE_FAIL);
    }

    /**
     * Check Middlewares
     * @return void
     */
    public function checkMiddlewares()
    {
        if (empty($this->middlewares)) {
            throw new MiddlewareException(MiddlewareException::ERR_MSG_NO_MIDDLEWARES);
        }

        $diff = array_diff($this->impMiddlewares, array_keys($this->middlewares));
        if (false === empty($diff)) {
            throw new MiddlewareException(MiddlewareException::ERR_MSG_INVALID_MIDDLEWARES);
        }
    }

    /**
     * Sort middleware
     * @return void
     */
    public function sortMiddlewares()
    {
        $highPriority = array_intersect(array_keys($this->priorityMiddlewares), $this->impMiddlewares);
        $tmp = array_diff($this->impMiddlewares, $highPriority);
        $this->impMiddlewares = array_merge($highPriority, $tmp);
        unset($highPriority);
        unset($tmp);
    }
}
