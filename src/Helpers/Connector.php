<?php
/**
 * Tuhin Bepari <digitaldreams40@gmail.com>
 */

namespace DbReader\Helpers;


use DbReader\Database;

/**
 * Class Connector
 * @package DbReader\Helpers
 */
class Connector
{
    /**
     * PHP DATABASE OBJECT
     * @var \PDO
     */
    protected $pdo;

    /**
     * Connector constructor.
     */
    public function __construct()
    {
        $dsn = "mysql:host=" . Database::$host . ";port=" . Database::$port . ";dbname=" . Database::$database;
        if (Database::$pdo instanceof \PDO) {
            $this->pdo = Database::$pdo;
        } else {
            $this->pdo = new \PDO($dsn, Database::$username, Database::$password, Database::$options);
        }
    }

    /**
     * @return \PDO
     * @throws \Exception
     */
    public function pdo()
    {
        if ($this->pdo instanceof \PDO) {
            $this->pdo->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_OBJ);
            return $this->pdo;
        }
        throw new \Exception(' PDO connection is not defined');
    }


}