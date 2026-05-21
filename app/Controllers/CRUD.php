<?php

namespace App\Controllers;

abstract class CRUD extends AbstractController
{
    public function __construct()
    {
        parent::__construct();
    }

    abstract public function create(): void;

    abstract public function read(int $id): void;

    abstract public function update(int $id): void;

    abstract public function delete(int $id): void;

}