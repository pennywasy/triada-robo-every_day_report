<?php


namespace App\Middlewares;

use App\Core\Response;
use Pecee\Http\Middleware\IMiddleware;
use Pecee\Http\Request;
use App\Core\Config;

class AuthVerification implements IMiddleware
{
    public function handle(Request $request): void
    {
        $token = Config::getInstance()->get('app.app_token');

        $headers = getallheaders();
        $authHeader = $headers["Authorization"] ?? "";

        if (!preg_match('/^Bearer\s+(.+)$/', $authHeader, $matches)) {
            Response::response()->httpCode(401);
            die();
        }

        $authToken = $matches[1];

        if ($token !== $authToken){
            Response::response()->httpCode(401);
            die();
        }
    }
}
