<?php

namespace App\Models;


use App\Core\Model;

class Foo extends Model
{
    protected $table = 'foo';
    protected $primaryKey = 'id';
    public $timestamps = true;

    protected $fillable = [
        "name"
    ];

    protected $casts = [
        // Add your field casts here
    ];
}