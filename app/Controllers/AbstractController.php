<?php

namespace App\Controllers;

use App\Exceptions\ValidationException;
use App\Http\Requests\BaseRequest;
use App\Services\RequestFactory;
use JsonException;
use Pecee\Http\Request;
use Pecee\Http\Response;
use Pecee\SimpleRouter\SimpleRouter as Router;

abstract class AbstractController
{
    /**
     * @var Response
     */
    protected Response $response;
    /**
     * @var Request
     */
    protected Request $request;

    protected static array $messages = [
        '500' => 'Внутренняя ошибка сервера',
        '401' => 'Не авторизован'
    ];

    public function __construct() {
        $this->request = Router::router()->getRequest();
        $this->response =  new Response($this->request);
    }

    protected function validatedRequest(string $requestClass): BaseRequest|string
    {
        try {
            return RequestFactory::make($requestClass, $this->request);
        } catch (JsonException $e) {
            $this->sendError(400, 'Invalid JSON format');
            exit;
        } catch (ValidationException $e) {
            $this->sendValidationError($e->getErrors());
            exit;
        }
    }

    protected function sendValidationError(array $errors, ?string $message = null): void
    {
        $this->response->httpCode(422)->json([
            'success' => false,
            'message' => $message ?? 'Validation failed',
            'errors' => $errors
        ]);
    }

    protected function sendError(int $statusCode, ?string $message = null): void {
        $this->response->httpCode($statusCode)->json([
            'success' => false,
            'error' => $message ?? self::$messages[$statusCode]
        ]);
    }

    protected function sendSuccess(?int $code = null, ?array $data = null): void {
        if ($code !== null) {
            $this->response->httpCode($code);
        }

        $this->response->json([
            'success' => true,
            'data' => $data
        ]);
    }
}