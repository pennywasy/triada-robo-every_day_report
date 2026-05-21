<?php

namespace App\Core;

use App\Core\Logger;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Promise\PromiseInterface;

abstract class HttpClientCore
{
    protected Client $client;

    public function __construct(array $config = [])
    {
        $this->client = new Client($config);
    }

    /**
     * @throws Exception
     */
    protected function execute(string $method, string $url, array $options = []): string
    {
        try {
            $response = $this->client->$method($url, $options);
            return $response->getBody()->getContents();
        } catch (GuzzleException $e) {
            $error = "HTTP {$method} request failed: " . $e->getMessage();
            Logger::error($error);
            throw new Exception($error);
        }
    }

    /**
     * @throws Exception
     */
    protected function executeAsync(string $method, string $url, array $options = []): PromiseInterface
    {
        try {
            return $this->client->{$method . 'Async'}($url, $options);
        } catch (GuzzleException $e) {
            $error = "HTTP async {$method} request failed: " . $e->getMessage();
            Logger::error($error);
            // Для асинхронных запросов бросаем исключение, которое можно будет поймать при обработке промиса
            throw new Exception($error);
        }
    }

    /**
     * Выполнение нескольких асинхронных запросов одновременно
     *
     * @param array $requests Массив запросов ['method' => string, 'url' => string, 'options' => array]
     * @return array Массив промисов
     * @throws Exception
     */
    protected function executeMultipleAsync(array $requests): array
    {
        $promises = [];

        foreach ($requests as $key => $request) {
            $method = $request['method'] ?? 'get';
            $url = $request['url'] ?? '';
            $options = $request['options'] ?? [];

            if (!empty($url)) {
                $promises[$key] = $this->executeAsync($method, $url, $options);
            }
        }

        return $promises;
    }
}