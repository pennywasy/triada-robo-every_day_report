<?php

namespace App\Services;

use App\Core\HttpClientCore;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Promise\PromiseInterface;

class StaticHttpClient extends HttpClientCore
{
    private static ?Client $sharedClient = null;

    public function __construct(array $config = [])
    {
        if (self::$sharedClient === null) {
            self::$sharedClient = new Client($config);
        }
        $this->client = self::$sharedClient;
    }

    /**
     * @throws Exception
     */
    public static function get(string $url, array $options = [], array $config = []): string
    {
        return (new self($config))->execute('get', $url, $options);
    }

    /**
     * @throws Exception
     */
    public static function post(string $url, array $options = [], array $config = []): string
    {
        return (new self($config))->execute('post', $url, $options);
    }

    /**
     * @throws Exception
     */
    public static function put(string $url, array $options = [], array $config = []): string
    {
        return (new self($config))->execute('put', $url, $options);
    }

    /**
     * @throws Exception
     */
    public static function delete(string $url, array $options = [], array $config = []): string
    {
        return (new self($config))->execute('delete', $url, $options);
    }

    /**
     * Статический асинхронный GET запрос
     * @throws Exception
     */
    public static function getAsync(string $url, array $options = [], array $config = []): PromiseInterface
    {
        return (new self($config))->executeAsync('get', $url, $options);
    }

    /**
     * Статический асинхронный POST запрос
     * @throws Exception
     */
    public static function postAsync(string $url, array $options = [], array $config = []): PromiseInterface
    {
        return (new self($config))->executeAsync('post', $url, $options);
    }

    /**
     * Статический асинхронный PUT запрос
     * @throws Exception
     */
    public static function putAsync(string $url, array $options = [], array $config = []): PromiseInterface
    {
        return (new self($config))->executeAsync('put', $url, $options);
    }

    /**
     * Статический асинхронный DELETE запрос
     * @throws Exception
     */
    public static function deleteAsync(string $url, array $options = [], array $config = []): PromiseInterface
    {
        return (new self($config))->executeAsync('delete', $url, $options);
    }

    /**
     * Выполнение нескольких статических асинхронных запросов
     * @throws Exception
     */
    public static function multipleAsync(array $requests, array $config = []): array
    {
        return (new self($config))->executeMultipleAsync($requests);
    }

    /**
     * Ожидание завершения всех промисов (статический метод)
     */
    public static function waitAll(array $promises): array
    {
        return (new self())->waitAll($promises);
    }
}