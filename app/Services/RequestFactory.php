<?php
namespace App\Services;

use App\Http\Requests\BaseRequest;
use Pecee\Http\Request;
use JsonException;
use App\Exceptions\ValidationException;

class RequestFactory
{
    /**
     * @throws JsonException
     */
    public static function make(string $requestClass, ?Request $httpRequest = null): BaseRequest
    {
        $data = self::getRequestData($httpRequest);
        return new $requestClass($data);
    }

    /**
     * @throws JsonException
     */
    private static function getRequestData(?Request $request): array
    {
        if ($request === null) {
            $rawBody = file_get_contents('php://input');
            return $rawBody ? json_decode($rawBody, true, 512, JSON_THROW_ON_ERROR) : [];
        }

        // Поддержка разных типов запросов
        $contentType = $request->getHeader('content-type') ?? '';

        if (str_contains($contentType, 'application/json')) {
            $rawBody = file_get_contents('php://input');
            return $rawBody ? json_decode($rawBody, true, 512, JSON_THROW_ON_ERROR) : [];
        }

        if (str_contains($contentType, 'application/x-www-form-urlencoded') ||
            str_contains($contentType, 'multipart/form-data')) {
            return $request->getInputHandler()->all();
        }

        return [];
    }
}