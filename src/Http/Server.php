<?php

namespace Atom\Http;

use Atom\Controllers\Controller as ControllerMaster;
use Atom\Http\Middlewares\Middleware;

class Server
{
    /**
     * Router
     * @var Object
     */
    protected $router;

    /**
     * Controller Master
     * @var Object
     */
    protected $controllerMaster;

    /**
     * Server construct
     * @param [type] $files [description]
     */
    public function __construct($files = null)
    {
        $this->loadConfig($files);
        $this->router = new Router();
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
            $routeData = $this->router->dispatchRouter();

            $this->handleMiddlewares($routeData);

            $this->controllerMaster->loadController($routeData);
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

    /**
     * Handle Middlewares
     * @param  array $routeData Route Data
     * @return void
     */
    public function handleMiddlewares($routeData)
    {
        if (false === isset($routeData['middleware']) && empty($routeData['middleware'])) {
            return;
        }
        (new Middleware($routeData['middleware']))->execute();
    }
}
