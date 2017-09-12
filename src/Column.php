<?php
/**
 * Tuhin Bepari <digitaldreams40@gmail.com>
 */
namespace DbReader;


/**
 * Analyze and return table structure e.g. columns, indexes, foreign keys
 *
 * @package LaraCrud\Reader
 */
class Column
{
    const INDEX_UNIQUE = 'UNI';
    const INDEX_PRIMARY = 'PRI';

    /**
     * @var array
     */
    protected $data = [];

    /**
     * @var array
     */
    public $foreign = [];

    /**
     * Parent Table Instance
     * @var Table
     */
    public $table;

    public function __construct($data, $foreign = [], $table = '')
    {
        $this->data = $data;
        $this->foreign = $foreign;
        $this->table = is_object($table) ? $table : new Table($table);
    }

    /**
     * Column name
     * @return mixed
     */
    public function name()
    {
        return $this->data->Field;
    }

    /**
     * Column data type
     * @return mixed
     */
    public function type()
    {
        if (strpos($this->data->Type, "(")) {
            return trim(substr($this->data->Type, 0, strpos($this->data->Type, "(")));
        }
        return $this->data->Type;
    }

    /**
     * Column storage length
     * @return bool
     */
    public function length()
    {
        if ($this->type() == 'enum') {
            return false;
        }
        $values = substr($this->data->Type, strpos($this->data->Type, "("), strrpos($this->data->Type, ")"));

        return filter_var(str_replace(["(", ")", "'"], "", $values), FILTER_SANITIZE_NUMBER_INT);
    }

    /**
     * Return Options for enum
     * @return mixed
     */
    public function options()
    {
        $values = substr($this->data->Type, strpos($this->data->Type, "("), strrpos($this->data->Type, ")"));
        $cleanvalues = str_replace(["(", ")", "'"], "", $values);
        return explode(",", $cleanvalues);
    }

    /**
     * Default value of the column
     * @return mixed
     */
    public function defaultValue()
    {
        return $this->data->Default;
    }

    /**
     * Check if it is primary key
     * @return bool
     */
    public function isPk()
    {
        return $this->data->Key == static::INDEX_PRIMARY;

    }

    /**
     * Check if it value is unique
     * @return bool
     */
    public function isUnique()
    {
        return $this->data->Key == static::INDEX_UNIQUE;
    }

    /**
     * Check if it is nullable
     * @return bool
     */
    public function isNull()
    {
        return $this->data->Null == 'YES';
    }

    /**
     * Is it foreign key
     * @return bool
     */
    public function isForeign()
    {
        return isset($this->foreign->COLUMN_NAME) && $this->foreign->COLUMN_NAME == $this->name();
    }

    /**
     * Get the foreign table name
     * @return bool
     */
    public function foreignTable()
    {
        return isset($this->foreign->REFERENCED_TABLE_NAME) ? $this->foreign->REFERENCED_TABLE_NAME : false;
    }

    /**
     * Reference column name
     * @return bool
     */
    public function foreignColumn()
    {
        return isset($this->foreign->REFERENCED_COLUMN_NAME) ? $this->foreign->REFERENCED_COLUMN_NAME : false;
    }

    /**
     * Export as array
     * @return array
     */
    public function toArray()
    {
        return (array)$this->data;
    }

    /**
     * Make column name camel Case
     * @return mixed
     */
    public function camelCase()
    {
        return lcfirst(str_replace(" ", "", ucwords(str_replace("_", " ", $this->name()))));
    }

    /**
     * Make column name to label. E.g. first_name will be return First Name
     */
    public function label()
    {
        return ucwords(str_replace("_", " ", $this->name()));
    }

    /**
     * should this column ignore
     * @return mixed
     */
    public function isIgnore()
    {
        $columnName = $this->table->name() . "." . $this->name();
        return in_array($columnName, config('laracrud.view.ignore'));
    }

    /**
     * Does this column is protected
     * @return mixed
     */
    public function isProtected()
    {
        return in_array($this->name(), config('laracrud.model.protectedColumns'));
    }

    /**
     * @param $name
     * @return bool|mixed
     */
    public function __get($name)
    {
        return isset($this->data->{$name}) ? $this->data->{$name} : false;
    }

}