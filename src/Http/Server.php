<?php

namespace Atom\Http;

use Atom\Http\Globals;
use Atom\Controllers\Controller as ControllerMaster;
use Atom\Http\Resquest;

class Server
{
    protected $route;
    protected $server;
    protected $controller;
    protected $controllerMaster;
    protected $request;

    /**
     * Server construct
     * @param [type] $files [description]
     */
    public function __construct($files = null)
    {
        $this->loadConfig($files);
        $this->route = new Route();
        $this->controllerMaster = new ControllerMaster();
        date_default_timezone_set(env('TIMEZONE'));
    }

    /**
     * Handle progress
     * @return void
     */
    public function handle()
    {
        try {
            list($class, $method) = $this->route->dispatchController();
            $this->loadController($class, $method);
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
    }

    /**
     * Load controller
     * @param  string $class
     * @param  string $method
     * @return void
     */
    public function loadController($class, $method)
    {
        try {
            $this->controller = $this->controllerMaster->init($class);
            $this->controller->callMethod($method);
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
    }

    /**
     * Load system configuration
     * @param  array $files
     * @return void
     */
    public function loadConfig($files)
    {
        foreach ($files as $file) {
            config($file);
        }
    }
}
