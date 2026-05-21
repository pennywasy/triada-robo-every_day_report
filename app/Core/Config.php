<?php

namespace App\Core;

class Config
{
    /**
     * @var Config|null
     */
    private static $instance = null;

    /**
     * @var array
     */
    private $config = [];

    private function __construct()
    {
        $this->loadConfig();
    }

    private function __clone() {}

    /**
     * @return static
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * @return void
     */
    private function loadConfig(): void
    {
        $configFiles = glob(__DIR__  . '/../../config/*.php');
        foreach ($configFiles as $file) {
            $key = basename($file, '.php');
            $this->config[$key] = require $file;
        }
    }

    /**
     * @param string $key
     * @param mixed|null $default
     * @return mixed
     */
    public function get(string $key, $default = null)
    {
        $keys = explode('.', $key);
        $value = $this->config;

        foreach ($keys as $key) {
            if (!isset($value[$key])) {
                return $default;
            }

            $value = $value[$key];
        }

        return $value;
    }

    public function getConfig(){
        return $this->config;
    }
}
