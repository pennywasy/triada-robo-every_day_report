<?php

namespace App\Services;

use App\Core\HttpClientCore;
use App\Core\Logger;
use Exception;
use GuzzleHttp\Promise\PromiseInterface;

class HttpClient extends HttpClientCore
{
    /**
     * @throws Exception
     */
    public function get(string $url, array $options = []): string
    {
        return $this->execute('get', $url, $options);
    }

    /**
     * @throws Exception
     */
    public function post(string $url, array $options = []): string
    {
        return $this->execute('post', $url, $options);
    }

    /**
     * @throws Exception
     */
    public function put(string $url, array $options = []): string
    {
        return $this->execute('put', $url, $options);
    }

    /**
     * @throws Exception
     */
    public function delete(string $url, array $options = []): string
    {
        return $this->execute('delete', $url, $options);
    }

    /**
     * Асинхронный GET запрос
     * @throws Exception
     */
    public function getAsync(string $url, array $options = []): PromiseInterface
    {
        return $this->executeAsync('get', $url, $options);
    }

    /**
     * Асинхронный POST запрос
     * @throws Exception
     */
    public function postAsync(string $url, array $options = []): PromiseInterface
    {
        return $this->executeAsync('post', $url, $options);
    }

    /**
     * Асинхронный PUT запрос
     * @throws Exception
     */
    public function putAsync(string $url, array $options = []): PromiseInterface
    {
        return $this->executeAsync('put', $url, $options);
    }

    /**
     * Асинхронный DELETE запрос
     * @throws Exception
     */
    public function deleteAsync(string $url, array $options = []): PromiseInterface
    {
        return $this->executeAsync('delete', $url, $options);
    }

    /**
     * Выполнение нескольких асинхронных запросов
     *
     * @param array $requests Массив запросов с ключами:
     *                       - method (string): HTTP метод
     *                       - url (string): URL запроса
     *                       - options (array): Опции запроса
     * @return array Массив промисов
     * @throws Exception
     */
    public function multipleAsync(array $requests): array
    {
        return $this->executeMultipleAsync($requests);
    }

    /**
     * Ожидание завершения всех промисов и получение результатов
     *
     * @param array $promises Массив промисов
     * @return array Массив результатов ['key' => ['response' => mixed, 'error' => Exception|null]]
     */
    public function waitAll(array $promises): array
    {
        $results = [];

        foreach ($promises as $key => $promise) {
            try {
                $response = $promise->wait();
                $results[$key] = [
                    'response' => $response->getBody()->getContents(),
                    'error' => null
                ];
            } catch (Exception $e) {
                Logger::error("Async request failed: " . $e->getMessage());
                $results[$key] = [
                    'response' => null,
                    'error' => $e
                ];
            }
        }

        return $results;
    }
}