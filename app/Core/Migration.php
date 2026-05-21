<?php

namespace App\Core;

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Builder;

abstract class Migration
{
    protected Builder $schema;

    public function __construct()
    {
        $this->schema = Capsule::schema();
    }

    abstract public function up(): void;
    abstract public function down(): void;
}