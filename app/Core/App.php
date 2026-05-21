<?php

namespace App\Core;

use Pecee\SimpleRouter\SimpleRouter;

class App
{
    public function __construct()
    {
        $this->loadConfig();

        $this->loadRoutes();

        $this->initializeComponents();
    }

    /**
     * @return void
     */
    private function loadConfig(): void
    {
        $configFiles = glob(__DIR__ . '/../../config/*.php');
        foreach ($configFiles as $file) {
            require $file;
        }
    }

    /**
     * @return void
     */
    private function loadRoutes(): void
    {
        $routeFiles = glob(__DIR__ . '/../../routes/*.php');
        foreach ($routeFiles as $file){
            require $file;
        }
    }

    /**
     * @return void
     */
    private function initializeComponents(): void
    {
//        $this->db = new DataBase();
    }

    /**
     * @return void
     * @throws \Pecee\Http\Middleware\Exceptions\TokenMismatchException
     * @throws \Pecee\SimpleRouter\Exceptions\HttpException
     * @throws \Pecee\SimpleRouter\Exceptions\NotFoundHttpException
     */
    public function run(): void
    {
        SimpleRouter::enableMultiRouteRendering(false);

        SimpleRouter::start();
    }
}