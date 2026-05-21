<?php

namespace App\Middlewares;

use JsonException;
use Pecee\Http\Middleware\IMiddleware;
use Pecee\Http\Request;

class ProccessRawBody implements IMiddleware
{

    /**
     * @inheritDoc
     * @throws JsonException
     */
    public function handle(Request $request): void
    {
        $rawBody = file_get_contents('php://input');

        if ($rawBody) {
            $body = json_decode($rawBody, true, 512);
            foreach ($body as $key => $value) {
                $request->$key = $value;
            }
        }
    }
}
