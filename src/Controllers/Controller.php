<?php

namespace Atom\Controllers;

use ReflectionObject;
use Atom\Http\Request;
use Atom\Controllers\Exception\ControllerException;
use Atom\Validation\Validator;
use Atom\Container\Container;

class Controller
{
    use Validator;

    /**
     * Request
     * @var $request
     */
    protected $request;

    /**
     * Container
     * @var $container
     */
    protected $container;

    /**
     * Controller construct
     * @param Request|null $request
     */
    public function __construct(Request $request = null)
    {
        $this->request = $request ?? new Request();
        $this->container = new Container();
    }

    /**
     * Initiate controller class
     * @param  string $class
     * @return object
     */
    public function init(string $class)
    {
        list($file, $class) = $this->parseController($class);

        require_once($file);

        $controllerClass = env('APP_NAMESPACE').'\\Controllers\\'.$class;
        $controller = $this->container->resolve($controllerClass);

        if ($controller) {
            return $controller;
        }
        throw new ControllerException(ControllerException::ERR_MSG_INVALID_CONTROLLER);
    }

    /**
     * Call method
     * @param  string $method
     * @return void
     */
    public function callMethod($method)
    {
        if (!method_exists($this, $method)) {
            throw new ControllerException(ControllerException::ERR_MSG_ACTION_FAIL);
        }

        $methodReflection = (new ReflectionObject($this))->getMethod($method);
        $result = $methodReflection->invoke($this);

        if ($result === false) {
            throw new ControllerException(ControllerException::ERR_MSG_ACTION_FAIL);
        }
    }

    /**
     * Parse controller
     * @param  string $class
     * @return array
     */
    public function parseController(string $class)
    {
        $file = CONTROLLER_PATH . $class . '.php';
        if (!file_exists($file)) {
            throw new \Exception(ControllerException::ERR_MSG_INVALID_CONTROLLER);
        }
        $class = str_replace("/", "\\", $class);

        return [$file, $class];
    }

}
