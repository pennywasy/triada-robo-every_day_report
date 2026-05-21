<?php

namespace App\Core;

class Logger
{
    /**
     * @var string
     */
    protected static $directory = "logs";

    /**
     * @var string
     */
    protected static $absPath = __DIR__ . '/../../';

    /**
     * @param string|null $directory
     * @param string|null $fileName
     * @return string
     */
    protected static function getFilePath(?string $directory = null, ?string $fileName = null): string
    {
        $logDirectory = $directory ?? self::$directory;

        if (!is_dir(self::$absPath . $logDirectory)) {
            mkdir(self::$absPath . $logDirectory, 0777, true);
        }

        $logFileName = $fileName ?? "log_" . date("Y-m-d") . ".log";
        return self::$absPath . $logDirectory . DIRECTORY_SEPARATOR . $logFileName;
    }

    /**
     * @param string $message
     * @param string|null $directory
     * @param string|null $fileName
     * @return void
     */
    public static function message(string $message, ?string $directory = null, ?string $fileName = null): void
    {
        $logFilePath = self::getFilePath($directory, $fileName);

        $formattedMessage = "MESSAGE [" . date("Y-m-d H:i:s") . "] " . $message . PHP_EOL;

        file_put_contents($logFilePath, $formattedMessage, FILE_APPEND);
    }

    /**
     * Экземплярный метод для info-логов
     * @param string $message
     * @return void
     */
    public function info(string $message): void
    {
        self::message('[INFO] ' . $message);
    }

    /**
     * Экземплярный метод для warning-логов
     * @param string $message
     * @return void
     */
    public function warning(string $message): void
    {
        self::message('[WARNING] ' . $message);
    }

    /**
     * Статический метод для error-логов
     * @param string $message
     * @param string|null $directory
     * @param string|null $fileName
     * @return void
     */
    public static function error(string $message, ?string $directory = null, ?string $fileName = null): void
    {
        $logFilePath = self::getFilePath($directory, $fileName);

        $formattedMessage = "ERROR [" . date("Y-m-d H:i:s") . "] " . $message . PHP_EOL;

        file_put_contents($logFilePath, $formattedMessage, FILE_APPEND);
    }

    /**
     * Экземплярный метод для error-логов
     * @param string $message
     * @return void
     */
    public function logError(string $message): void
    {
        self::error('[ERROR] ' . $message);
    }


}