<?php

use App\Controllers\ViewController;
use Pecee\SimpleRouter\SimpleRouter as Router;

Router::get('/', function () {
   echo "Is working correctly";
});