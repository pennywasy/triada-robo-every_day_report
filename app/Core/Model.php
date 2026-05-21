<?php

namespace App\Core;

use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Database\Capsule\Manager as Capsule;
use RuntimeException;

abstract class Model extends EloquentModel
{
    protected static ?Capsule $capsule = null;

    public function __construct(array $attributes = [])
    {
        static::initConnection();

        parent::__construct($attributes);
    }

    protected static function initConnection(): void
    {
        if (static::$capsule === null) {
            $config = Config::getInstance()->get('database');
            $dbDriver = $_ENV['DB_DRIVER'];
            $connection = $config['connections'][$dbDriver] ?? null;

            if (!$connection) {
                throw new RuntimeException('Database connection not configured');
            }

            static::$capsule = new Capsule;
            static::$capsule->addConnection($connection, 'default');
            static::$capsule->setAsGlobal();
            static::$capsule->bootEloquent();
        }
    }

    public static function connection(): \Illuminate\Database\Connection
    {
        static::initConnection();
        return static::$capsule->getConnection();
    }

    public function newQuery(): \Illuminate\Database\Eloquent\Builder
    {
        static::initConnection();
        return parent::newQuery();
    }
}