<?php
/**
 * Tuhin Bepari <digitaldreams40@gmail.com>
 */
namespace DbReader;

use DbReader\Helpers\Connector;
use DbReader\Helpers\DatabaseHelper;

/**
 * Analyze and return database structure e.g. tables, views, relations etc
 *
 * @package LaraCrud\Reader
 */
class Database
{
    use DatabaseHelper;

    /**
     * Name of the Database Engine
     * @var string
     */
    public static $connection = 'mysql';

    /**
     * Database Host name. Default to localhost
     * @var string
     */
    public static $host = '127.0.0.1';

    /**
     * Database Port. Default to 3306 which is default of Mysql connection
     * @var int
     */
    public static $port = 3306;

    /**
     * Name of the database to be connected
     * @var
     */
    public static $database;

    /**
     * Database Username
     * @var string
     */
    public static $username = 'root';

    /**
     * Database Password
     * @var string
     */
    public static $password = '';

    /**
     * Connection Option
     *
     * @var array
     */
    public static $options = [];
    /**
     * Default PDO connection. Either PDO connection or DB info required
     * @var
     */
    public static $pdo;

    /**
     * @var \PDO
     */
    protected $db;

    /**
     * List of table names database has
     * @var array
     */

    protected $tables = [];

    /**
     * @var array
     */
    public static $manualRelations = [
        // tables.foreign_column=>foreign_table.column
    ];

    /**
     * @var array
     */
    public static $files = [
        //'tables.column'
    ];

    /**
     * All Relations
     * @var
     */
    protected $relations = [];


    public function __construct()
    {
        $this->db = (new Connector())->pdo();
        $this->fetchTables();
    }

    public function tables()
    {
        return $this->tables;
    }


    public function indexes()
    {
        return $this->indexes;
    }

    public function relations()
    {
        return $this->relations;
    }

    public function fetchRelations()
    {
        $dbName = $this->getDatabaseName();
        $sql = "SELECT TABLE_NAME,COLUMN_NAME,CONSTRAINT_NAME, REFERENCED_TABLE_NAME,REFERENCED_COLUMN_NAME
                                    FROM  INFORMATION_SCHEMA.KEY_COLUMN_USAGE
                                    WHERE TABLE_SCHEMA='$dbName' AND REFERENCED_TABLE_NAME IS NOT NULL";
        return $this->relations = $this->db->query($sql)->fetchAll(\PDO::FETCH_OBJ);

    }


    public function fetchTables()
    {
        $names = [];
        $result = $this->db->query("SHOW TABLES");

        foreach ($result as $tb) {
            $tb = (array)$tb;
            $name = array_values($tb);
            $names[] = array_shift($name);
        }
        $this->tables = $names;
        return $this;
    }

    public function __get($name)
    {
        if (in_array($name, $this->tables)) {
            return new Table($name);
        }
        throw new \Exception('Table ' . $name . ' you are trying to access is not exists');
    }

    /**
     * @param array $settings
     */
    public static function settings(array $settings)
    {
        foreach ($settings as $prop => $value) {
            if (property_exists(static::class, $prop)) {
                static::${$prop} = $value;
            }
        }
    }


}