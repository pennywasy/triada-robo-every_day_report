<?php

namespace App\Services;

use App\Core\Config;
use PDO;
use PDOException;

class DataBase
{


    /**
     * @var string
     */
    protected $dbPath;

    /**
     * @var PDO
     */
    protected $pdo;

    public function __construct()
    {
        $config = Config::getInstance()->get('database.sqlite');
        $this->dbPath = $config['dbPath'];

        $this->connectToDB();
    }

    /**
     * @return void
     */
    protected function connectToDB(): void
    {
        try {
            $this->pdo = new PDO("sqlite:" . $this->dbPath);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            http_response_code(404);
            echo json_encode(["error" => 'internal server Error', 'db error' => $e->getMessage()]);
            return;
        }
    }

}
