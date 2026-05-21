<?php

namespace App\Core;

use Illuminate\Database\Capsule\Manager as Capsule;
use RuntimeException;

class Database
{
    private static ?Capsule $capsule = null;

    public static function init(array $config): void
    {
        if (!self::$capsule) {
            self::$capsule = new Capsule;
            self::$capsule->addConnection($config);
            self::$capsule->setAsGlobal();
            self::$capsule->bootEloquent();
        }
    }

    public static function schema(): \Illuminate\Database\Schema\Builder
    {
        if (!self::$capsule) {
            throw new RuntimeException('Database not initialized. Call Database::init() first.');
        }
        return self::$capsule->schema();
    }

    public static function table(string $table): \Illuminate\Database\Query\Builder
    {
        if (!self::$capsule) {
            throw new RuntimeException('Database not initialized. Call Database::init() first.');
        }
        return self::$capsule->table($table);
    }

    public static function getDriverName(): string
    {
        if (!self::$capsule) {
            throw new RuntimeException('Database not initialized. Call Database::init() first.');
        }
        return self::$capsule->getConnection()->getDriverName();
    }
}