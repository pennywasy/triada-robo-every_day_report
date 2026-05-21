<?php

use App\Core\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateFooTable extends Migration
{
    public function up(): void
    {
        $this->schema->create('foo', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        $this->schema->dropIfExists('foo');
    }
}