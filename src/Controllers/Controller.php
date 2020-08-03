<?php

namespace Atom\Controllers;

use ReflectionObject;
use Atom\Http\Request;
use Atom\Validation\Validator;
use Atom\Container\Container;
use Atom\Http\Globals;
use Atom\Controllers\Exception\ControllerException;
use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\EntityManager;

class Controller
{
    use Validator;

    /**
     * Request
     * @var Request
     */
    protected $request;

    /**
     * Container
     * @var Container
     */
    protected $container;

    /**
     * Request method
     * @var string
     */
    protected $requestMethod;

    /**
     * Doctrine Entity Manager
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * Controller construct
     * @param Request|null $request
     */
    public function __construct(Request $request = null)
    {
        $this->request = $request ?? new Request();
        $this->container = new Container();
        $this->requestMethod = Globals::method();
    }

    /**
     * Load controller
     * @param  array $routeData Route data
     * @return void
     */
    public function loadController($routeData)
    {
        try {
            list($class, $method) = $this->dispatchController($routeData);
            $this->controller = $this->init($class);
            $this->controller->callMethod($method);
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
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
            throw new ControllerException(ControllerException::ERR_MSG_INVALID_CONTROLLER);
        }
        $class = str_replace("/", "\\", $class);

        return [$file, $class];
    }

    /**
     * Dispatch Controller
     * @param  array $routeData Data is gained from route file
     * @return array
     * @throws ControllerException
     */
    public function dispatchController($routeData)
    {
        $actions = array_column($routeData, strtolower($this->requestMethod));
        list($class, $function) = explode('@', $actions[0]);
        if (empty($class)) {
            throw new ControllerException(ControllerException::ERR_MSG_INVALID_CONTROLLER);
        }

        return [$class, $function];
    }

    /**
     * Get Doctrine Entity Manager
     *
     * @return EntityManager | Exception
     */
    public function getDoctrineEntityManager()
    {
        if (false === boolval(env('DBAL_IN_USE'))) {
            throw new ControllerException(ControllerException::ERR_MSG_DOCTRINE_NOT_USE);
        }
        // Create a simple "default" Doctrine ORM configuration for Annotations
        $config = Setup::createAnnotationMetadataConfiguration(
            [DOC_ROOT.env('DBAL_PATH_CONFIG')],
            (bool) env('DBAL_DEV_MODE'),
            env('DBAL_PROXY_DIR') ? env('DBAL_PROXY_DIR') : null,
            env('DBAL_CACHE') ? env('DBAL_CACHE') : null,
            (bool) env('DBAL_USE_SIMPLE_ANNO_READER')
        );

        // database configuration parameters
        $conn = array(
            'driver' => env('DB_DRIVER'),
            'dbname' => env('DB_NAME'),
            'user' => env('DB_USER'),
            'password' => env('DB_PASSWORD'),
            'host' => env('DB_HOST').':'.env('DB_PORT'),
        );

        // obtaining the entity manager
        return EntityManager::create($conn, $config);
    }
}
