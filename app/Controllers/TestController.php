<?php

namespace App\Controllers;

use App\Core\Logger;
use App\Models\Foo;

class TestController extends CRUD
{

    public function __construct()
    {
        parent::__construct();
    }

    public function create(): void {
        $request = $this->request->getInputHandler()->getOriginalPost();

        Logger::message(json_encode($request));

        $foo = new Foo();

        $foo->name = $request['name'];

        $foo->save();

        $this->response->json($foo->toArray());
    }

    public function read(int $id): void
    {
        $foo = Foo::query()->findOrFail($id);

        $this->response->json($foo->toArray());
    }

    public function update(int $id): void
    {
        $request = $this->request->getInputHandler()->getOriginalPost();

        $foo = Foo::query()->findOrFail($id);

        $foo->name = $request['name'];

        $foo->save();

        $this->response->json($foo->toArray());
    }

    public function delete(int $id): void
    {
        Foo::query()->findOrFail($id)->delete();

        $this->response->json(['deleted' => true]);
    }
}