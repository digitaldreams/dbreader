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
            $columns[] = new Column($column, $foreign,$this);
        }
        return $columns;
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
            return new Column($this->columns[$name], $foreign, $this);
        }
        throw new \Exception('Column ' . $name . ' does not exists in table ' . $this->name);
    }


}