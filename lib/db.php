<?php
namespace webservices;

/**
 *
 * @package  lib.db
 * @author  mrlin <714480119@qq.com>
 */
class Db
{
    /**
     *
     * errors
     *
     * @var array
     */
    protected $errors = array();

    /**
     *
     * table
     *
     * @var string
     */
    protected $table = '';

    /**
     *
     * table fields
     *
     * @var string
     */
    protected $fields = '*';

    /**
     *
     * where condition
     *
     * @var string
     */
    protected $where = '';

    /**
     *
     * joins statement
     *
     * @var string
     */
    protected $joins = '';

    /**
     *
     * order statement
     *
     * @var string
     */
    protected $order = '';

    /**
     *
     * group by
     *
     * @var string
     */
    protected $groups = '';

    /**
     *
     * having statement
     *
     * @var string
     */
    protected $having = '';

    /**
     *
     * limit
     *
     * @var string
     */
    protected $limit = '';


    /**
     *
     * sql statement
     *
     * @var string
     */
    protected $sql = "";

    /**
     *
     * name => value pairs
     *
     * @var array
     */
    protected $values = array();

    /**
     *
     * The pdo
     *
     * @var null
     */
    protected $db = null;

    /**
     *
     * database type mysql ? pgsql ? mmsql ?
     *
     * @var string
     */
    protected $dbtype = '';

// ------------------------------------------------------------------------
    /**
     * @construct
     *
     * @param array $dsn
     *
     */
    public function __construct(array $dsn = array())
    {
        if (!empty($dsn))
        {
            $this->setDb($dsn);
        }
    }

// ------------------------------------------------------------------------
    /**
     * init db config
     *
     * @param array $dsn
     */
    public function setDb(array $dsn = array())
    {
        $this->dbtype = $db_type = @strtolower($dsn['db_type']);

        switch($db_type)
        {
            case 'pgsql':
            case 'postgresql':
            case 'mysql':
                $db_type  = isset($dsn['db_type'])  ? $dsn['db_type']: '';
                $hostname = isset($dsn['hostname']) ? $dsn['hostname']: '';
                $database = isset($dsn['database']) ? $dsn['database']: '';
                $username = isset($dsn['username']) ? $dsn['username']: '';
                $password = isset($dsn['password']) ? $dsn['password']: '';
                $db_port  = isset($dsn['db_port'])  ? $dsn['db_port']: '';
                $charset  = isset($dsn['charset'])  ? $dsn['charset']: 'utf8';
                try
                {
                    $this->db = new \PDO("$db_type:host=$hostname;port=$db_port;dbname=$database;charset=$charset", $username, $password);
                    $this->db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
                }
                catch(\PDOException $e)
                {
                    print "database configuration:\n";
                    die($e->getMessage());
                }
                break;
            case 'sqlite':
                try
                {
                    $path = isset($dsn['sqlite_path']) ? $dsn['sqlite_path']: '';
                    $this->db = new \PDO("sqlite:$path");
                    $this->db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
                }
                catch(\PDOException $e)
                {
                    print "sqlite path: $path\n";
                    die($e->getMessage());
                }
                break;
            default:
                throw new \Exception("Database type not supported");
        }
    }

// ------------------------------------------------------------------------
    /**
     *
     * singlon method
     *
     * @return \Db
     */
    public static function getDb()
    {
        static $instance = null;

        if (!$instance instanceof Db)
        {
            $instance = new self(Config::getConfig());
        }

        return $instance;
    }

// ------------------------------------------------------------------------
    /**
     *
     * @add a value to the values array
     *
     * @access public
     *
     * @param string $key the array key
     *
     * @param string $value The value
     *
     */
    public function addValue($key, $value)
    {
        $this->values[$key] = $value;
    }

// ------------------------------------------------------------------------
    /**
     *
     * @set the values
     *
     * @access public
     *
     * @param array
     *
     */
    public function setValues($array)
    {
        $this->values = $array;
    }

// ------------------------------------------------------------------------
    /**
     *
     * @delete a recored from a table
     *
     * @access public
     *
     * @param string $table The table name
     *
     * @param int ID
     *
     */
    public function delete($table, $id)
    {
        try
        {
            // get the primary key name
            $pk   = $this->getPrimaryKey($table);
            $sql  = "DELETE FROM $table WHERE $pk=:$pk";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(":$pk", $id);
            $stmt->execute();
        }
        catch(\Exception $e)
        {
            $this->errors[] = $e->getMessage();
        }
    }

// ------------------------------------------------------------------------
    /**
     *
     * @insert a record into a table (only mysql)
     *
     * @access public
     *
     * @param string $table The table name
     *
     * @param array $values An array of fieldnames and values
     *
     * @return int The last insert ID
     *
     */
    public function insert($table, $values = null)
    {
        $values = is_null($values) ? $this->values : $values;

        if (empty($values))
        {
            throw new \Exception("No values input", 1);
        }

        $keys = implode(',', array_keys($values));
        $vals = implode(',', array_map( function($item) { return ":$item"; },
            array_keys($values)
        ));

        $sql = sprintf("INSERT INTO $table (%s) VALUES(%s)", $keys, $vals);

        try
        {
            $stmt = $this->db->prepare($sql);
            // bind the params
            foreach ($values as $k => $v)
            {
                $stmt->bindParam(':'.$k, $v);
            }
            $stmt->execute($values);
            // return the last insert id
            return $this->db->lastInsertId();
        }
        catch (\Exception $e)
        {
            $this->errors[] = $e->getMessage();
        }
    }

// ------------------------------------------------------------------------
    /**
     * @update a table
     *
     * @access public
     *
     * @param string $table The table name
     *
     * @param int $id
     *
     */
    public function update($table, $id, $values=null)
    {
        $values = is_null($values) ? $this->values : $values;

        if (empty($values))
        {
            throw new \Exception("Error Processing values", 1);
        }

        try
        {
            // get the primary key/
            $pk = $this->getPrimaryKey($table);

            // set the primary key in the values array
            $values[$pk] = $id;
            $obj = new \CachingIterator(new \ArrayIterator($values));
            $sql = "UPDATE $table SET \n";
            foreach( $obj as $field=>$val)
            {
                $sql .= "$field = :$field";
                $sql .= $obj->hasNext() ? ',' : '';
                $sql .= "\n";
            }
            $sql .= " WHERE $pk=$id";
            $stmt = $this->db->prepare($sql);
            // bind the params
            foreach($values as $k=>$v)
            {
                $stmt->bindParam(':'.$k, $v);
            }
            // bind the primary key and the id
            $stmt->bindParam($pk, $id);
            $stmt->execute($values);
            // return the affected rows
            return $stmt->rowCount();
        }
        catch(\Exception $e)
        {
            $this->errors[] = $e->getMessage();
        }
    }

// ------------------------------------------------------------------------
    /**
     * get the name of the field that is the primary key
     *
     * only in mysql
     *
     * @access private
     *
     * @param string $table The name of the table
     *
     * @return string
     *
     */
    private function getPrimaryKey($table)
    {
        $pk = '';

        switch ($this->dbtype)
        {
            case 'sqlite':
                $sql = "PRAGMA table_info($table)";

                foreach ($this->db->query($sql) as $rows)
                {
                    if ($rows['pk'] == 1)
                    {
                        $pk = $rows['name'];
                        break;
                    }
                }

                break;

            case 'mysql':
                $sql = "SHOW COLUMNS FROM $table";

                foreach ($this->db->query($sql) as $rows)
                {
                    if ($rows['Key'] == 'PRI' && $rows['Extra'] == 'auto_increment')
                    {
                        $pk = $rows['Key'];
                        break;
                    }
                }

                break;

            default:

                throw new \Exception("Database type not supported", 1);
                break;
        }

        return $pk;
    }

// ------------------------------------------------------------------------
    /**
     *
     * Wraps quotes around a string and escapes the content for a string parameter.
     *
     * @param  string $value
     *
     * @return string
     *
     */
    public function escape($value)
    {
        if ($this->db !== null)
        {
            return $this->db->quote($value);
        }

        $value = str_replace(
            array('\\', "\0", "\n", "\r", "'", '"', "\x1a"),
            array('\\\\', '\\0', '\\n', '\\r', "\\'", '\\"', '\\Z'),
            $value
        );

        return $value;
    }

// ------------------------------------------------------------------------
    /**
     *
     * build sql statement
     *
     * @return string
     *
     */
    public function buildSql()
    {
        $sql = array_reduce( array(
                'SELECT',
                $this->fields,
                'FROM',
                "`$this->table`",
                $this->joins,
                $this->where,
                $this->groups,
                $this->having,
                $this->order,
                $this->limit,
            ),
            function ($sql, $input)
            {
                return strlen($input) > 0 ? $sql . ' ' . $input : $sql;
            }
        );

        // clear class properties
        $this->where  = '';
        $this->joins  = '';
        $this->fields = '*';
        $this->groups = '';
        $this->order  = '';
        $this->having = '';
        $this->limit  = '';

        return trim($sql);
    }

// ------------------------------------------------------------------------
    /**
     *
     * get last query sql
     *
     * @return string
     *
     */
    public function getLastSql()
    {
        return $this->sql;
    }

// ------------------------------------------------------------------------
    /**
     *
     * get errors
     *
     * @return array
     *
     */
    public function getErrors()
    {
        return $this->errors;
    }

// ------------------------------------------------------------------------
    /**
     *
     * Fetch all records from table
     *
     * @access public
     *
     * @param $table The table name
     *
     * @return PDOStatement 
     *
     */
    public function query()
    {
        $this->sql = $this->buildSql();
        $pdostatement = $this->db->query( $this->sql );

        return $pdostatement;
    }

// ------------------------------------------------------------------------
    /**
     *
     * @select statement
     *
     * @access public
     *
     * @param string $table
     *
     */
    public function select($table)
    {
        $this->table = $table;

        return $this;
    }

// ------------------------------------------------------------------------
    /**
     *
     * join clauss
     *
     * @param  string  $table
     *
     * @param  string $fields
     *
     * @param  string $type
     *
     * @return this
     *
     */
    public function join($table, $fields, $type = 'INNER')
    {
        $this->joins .= sprintf(" %s JOIN `%s` ON %s", $type, $table, $fields);

        return $this;
    }


// ------------------------------------------------------------------------
    /**
     * @where clause
     *
     * @access public
     *
     * @param string $field
     *
     * @param string $value
     *
     * @return this
     *
     */
    public function where($field, $value, $operation = "=")
    {
        $this->where .= sprintf(" WHERE `%s` %s '%s'", $field, $operation, $value);

        return $this;
    }

// ------------------------------------------------------------------------
    /**
     *
     * @set limit
     *
     * @access public
     *
     * @param int $offset
     *
     * @param int $limit
     *
     * @return this
     *
     */
    public function limit($offset, $limit = false)
    {
        $this->limit .= ($limit === false) ? sprintf(" LIMIT %s", $offset) : sprintf(" LIMIT %s, %s", $offset, $limit);

        return $this;
    }

// ------------------------------------------------------------------------
    /**
     *
     * @add an AND clause
     *
     * @access public
     *
     * @param string $field
     *
     * @param string $value
     *
     */
    public function andClause($field, $value, $operation = '=')
    {
        $this->where .= sprintf(" AND `%s` %s '%s'", $field, $operation, $value);

        return $this;
    }

// ------------------------------------------------------------------------
    /**
     *
     * add or OR clause
     *
     * @param string $field
     *
     * @param string $value
     *
     * @return this
     *
     */
    public function orClause($field, $value, $operation = '=')
    {
        $this->where .= sprintf(" OR `%s` %s '%s'", $field, $operation, $value);

        return $this;
    }

// ------------------------------------------------------------------------
    /**
     *
     * having clause
     *
     * @param  string $field
     *
     * @param  string $value
     *
     * @return this
     *
     */
    public function having($field, $value)
    {
        $this->having .= empty($this->having) ? ' HAVING' : '';
        $this->having .= sprintf(" `%s` %s", $field, $value);

        return $this;
    }

// ------------------------------------------------------------------------
    /**
     *
     * Add and order by
     *
     * @param string $fieldname
     *
     * @param string $order
     *
     * @return this
     *
     */
    public function orderBy($fieldname, $order='ASC')
    {
        $this->order .= sprintf(" ORDER BY `%s` %s", $fieldname, $order);

        return $this;
    }

// end of class
}
