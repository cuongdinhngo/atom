<?php

namespace Atom\Controllers;

use Atom\Http\Request;
use Atom\Controllers\Exception\ControllerException;
use Atom\Validation\Validator;

class Controller
{
    use Validator;

	const PREFIX = 'App\\Controllers\\';

    protected $request;

    /**
     * Controller construct
     * @param Request|null $request
     */
    public function __construct(Request $request = null)
    {
        $this->request = $request ?? new Request();
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

        $controllerClass = self::PREFIX.$class;
        $controller = new $controllerClass();
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

        $result = $this->$method();
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
