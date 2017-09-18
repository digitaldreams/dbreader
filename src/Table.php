<?php
/**
 * Tuhin Bepari <digitaldreams40@gmail.com>
 */
namespace DbReader;

use DbReader\Helpers\Connector;
use DbReader\Helpers\DatabaseHelper;

/**
 * Analyze and return table structure e.g. columns, indexes, foreign keys
 *
 * @package LaraCrud\Reader
 */
class Table
{
    use DatabaseHelper;
    /**
     * Database Connection
     * @var
     */
    protected $db;

    /**
     * Table columns
     * @var array
     */
    protected $columns = [];

    /**
     * Table name
     * @var string
     */
    protected $name;

    /**
     * Details about The table.
     * This will store result return by EXPLAIN {table} query
     *
     * @var array
     */
    protected $details = [];

    /**
     * Table Indexes
     *
     * This will store result return by SHOW INDEXES FROM {table} query
     * @var array
     */
    protected $indexes = [];

    /**
     * Foreign Keys relation in table
     * @var
     */
    protected $relations = [];

    /**
     * Table primary key used in other tables as foreign key
     * @var
     */
    protected $references = [];

    /**
     * Table constructor.
     * @param $table
     */
    public function __construct($table)
    {
        $this->db = (new Connector())->pdo();
        $this->name = $table;
        $this->fetchColumns()->fetchIndexes()->fetchRelations()->fetchReferences();
    }

    /**
     * Get table name
     * @return string
     */
    public function name()
    {
        return $this->name;
    }

    /**
     * get columns
     * @return array
     */
    public function columns()
    {
        return $this->columns;
    }

    /**
     * Get indexes
     * @return array
     */
    public function indexes()
    {
        return $this->indexes;
    }

    /**
     * get relations
     * @return array
     */
    public function relations()
    {
        return $this->relations;
    }

    /**
     * get references of the table used in other tables
     * @return array
     */
    public function references()
    {
        return $this->references;
    }

    /**
     * Fetch Columns details from database
     * @return $this
     */
    public function fetchColumns()
    {
        $columns = $this->db->query("EXPLAIN {$this->name}")->fetchAll();

        foreach ($columns as $column) {
            $this->columns[$column->Field] = $column;
        }
        return $this;
    }

    /**
     * Fetch indexes of the table
     * @return $this
     */
    public function fetchIndexes()
    {
        $this->indexes = $this->db->query("SHOW INDEXES FROM {$this->name}")->fetchAll();
        return $this;
    }


    /**
     * Fetch Foreign key relation of the table
     * @return self
     */
    public function fetchRelations()
    {
        $dbName = $this->getDatabaseName();
        $tableName = $this->name;

        $sql = "SELECT TABLE_NAME,COLUMN_NAME,CONSTRAINT_NAME, REFERENCED_TABLE_NAME,REFERENCED_COLUMN_NAME
                                    FROM  INFORMATION_SCHEMA.KEY_COLUMN_USAGE
                                    WHERE TABLE_SCHEMA='$dbName' AND TABLE_NAME='$tableName' AND REFERENCED_TABLE_NAME IS NOT NULL";
        $relations = $this->db->query($sql)->fetchAll();
        $relations = array_merge($relations, $this->makeManualRelation());

        foreach ($relations as $rel) {
            $this->relations[$rel->COLUMN_NAME] = $rel;
        }
        return $this;
    }

    /**
     * Get other table's foreign key that references to this table
     * @return self
     */
    public function fetchReferences()
    {
        $dbName = $this->getDatabaseName();
        $tableName = $this->name;

        $sql = "SELECT TABLE_NAME,COLUMN_NAME,CONSTRAINT_NAME, REFERENCED_TABLE_NAME,REFERENCED_COLUMN_NAME
                                    FROM  INFORMATION_SCHEMA.KEY_COLUMN_USAGE
                                    WHERE TABLE_SCHEMA='$dbName' AND REFERENCED_TABLE_NAME='$tableName' AND TABLE_NAME IS NOT NULL";
        $relations = $this->db->query($sql)->fetchAll();
        foreach ($relations as $rel) {
            $this->references[] = $rel;
        }
        return $this;
    }

    /**
     * Make array of Column class instance
     * @return arrays
     */
    public function columnClasses()
    {
        $columns = [];
        foreach ($this->columns() as $name => $column) {
            $foreign = isset($this->relations[$name]) ? $this->relations[$name] : [];
            $columns[] = new Column($column, $foreign, $this);
        }
        return $columns;
    }

    /**
     * Get columns from manual array of this table
     * @param $manualArr
     * @return array
     */
    protected function manualArr($manualArr)
    {
        $retArr = [];
        $relations = $manualArr;
        if (!empty($relations)) {
            $retArr = array_filter($relations, function ($v, $k) {
                if (substr_compare($k, $this->name(), 0, strlen($this->name())) === 0) {
                    return true;
                }
            }, ARRAY_FILTER_USE_BOTH);
        }
        return $retArr;
    }

    /**
     * Make Foreign Key for Custom/Manual Column
     * @return array
     */
    protected function makeManualRelation()
    {
        $relArr = [];
        $relations = $this->manualArr(Database::$manualRelations);
        foreach ($relations as $key => $value) {
            $foreign = new \stdClass();
            $fkKeys = explode(".", $key);
            $fkValues = explode(".", $value);

            if (count($fkKeys) < 2) {
                continue;
            }
            $foreign->TABLE_NAME = $this->name();
            $foreign->COLUMN_NAME = $fkKeys[1];
            $foreign->CONSTRAINT_NAME = '';
            $foreign->REFERENCED_TABLE_NAME = $fkValues[0];
            $foreign->REFERENCED_COLUMN_NAME = isset($fkValues[1]) ? $fkValues[1] : 'id';

            $relArr[] = $foreign;
        }
        return $relArr;
    }

    /**
     * @return array
     */
    public function fileColumns()
    {
        $retArr = [];
        $filesColumn = $this->manualArr(array_flip(Database::$files));
        foreach (array_flip($filesColumn) as $value) {

            $fileArr = explode(".", $value);
            if (count($fileArr) < 2) {
                continue;
            }
            $retArr[$fileArr[1]] = $fileArr[1];
        }

        return $retArr;
    }

    /**
     *
     */
    public function hasFile()
    {
        return count($this->fileColumns()) > 0 ? true : false;
    }

    /**
     * Dynamically assign Column class  to column name and return that class. So each column will be dynamic property
     *
     * @param $name name of the column
     * @return Column
     * @throws \Exception
     */
    public function __get($name)
    {
        if (isset($this->columns[$name])) {
            $foreign = isset($this->relations[$name]) ? $this->relations[$name] : [];
            $files = $this->fileColumns();
            $file = isset($files[$name]) ? $files[$name] : '';

            $columnObj = new Column($this->columns[$name], $foreign, $this);
            $columnObj->setFile($file);
            return $columnObj;
        }
        throw new \Exception('Column ' . $name . ' does not exists in table ' . $this->name);
    }


}