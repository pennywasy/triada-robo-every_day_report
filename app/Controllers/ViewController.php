<?php

namespace App\Controllers;

use App\Core\View;
use Exception;

class  ViewController
{
    public function __construct() {
    }

    /**
     * @throws Exception
     */
    public function handle() {
        return View::renderAngular();
    }
}