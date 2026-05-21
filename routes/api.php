<?php

use App\Controllers\TestController;
use Pecee\SimpleRouter\SimpleRouter as Router;
use App\Middlewares\ProccessRawBody;


Router::group([
    'prefix' => 'api/v1',
    'middleware' => [
        ProccessRawBody::class
    ]
], function () {


});

