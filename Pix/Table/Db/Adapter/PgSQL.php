<?php

/**
 * Pix_Table_Db_Adapter_PgSQL
 * 
 * @uses Pix_Table_Db_Adapter
 * @package Table
 * @copyright 2003-2012 PIXNET Digital Media Corporation
 * @license http://framework.pixnet.net/license BSD License
 */
class Pix_Table_Db_Adapter_PgSQL extends Pix_Table_Db_Adapter_SQL
{
    protected $_pdo;
    protected $_path;
    protected $_name = null;

    public function __construct($options)
    {
        $this->_path = $options['host'];
        $this->_options = $options;
        $config = array();
        foreach ($options as $key => $value) {
            if (in_array($key, array('host', 'port', 'user', 'password', 'dbname'))) {
                $config[] = $key . '=' . $value;
            }
        }
        $this->_pdo = new PDO("pgsql:" . implode(';', $config));
    }

    public function getSQLConditionByTerm(Pix_Table_Search_Term $term, $table = null)
    {
        switch ($term->getType()) {
        case 'location/distance-with-in':
            $arguments = $term->getArguments();
            $column = $arguments[0];
            $latlon = $arguments[1];
            $distance = $arguments[2];

            return "ST_DWithin(" . $this->column_quote($column) . ", ST_GeographyFromText('POINT(" . floatval($latlon[1]) . " " . floatval($latlon[0]) . ")'), " . intval($distance) . ")";
        }

        throw new Pix_Table_Exception('Unsupport Pix_Table_Search_Term: ' . $term->getType());
    }

    public function getSupportFeatures()
    {
        return array('immediate_consistency');
    }


    public function setName($name)
    {
        $this->_name = $name;
    }

    public function query($sql)
    {
        if (Pix_Table::$_log_groups[Pix_Table::LOG_QUERY]) {
            Pix_Table::debug(sprintf("[%s]\t%40s", $this->_path . $this->_name, $sql));
        }

        $starttime = microtime(true);
        $statement = $this->_pdo->prepare($sql);
        if (!$statement) {
            if ($errno = $this->_pdo->errorCode()) {
                $errorInfo = $this->_pdo->errorInfo();
            }
            if ($errorInfo[2] == 'PRIMARY KEY must be unique' or
                    preg_match('/duplicate key value violates unique constraint/', $errorInfo[2])) {
                throw new Pix_Table_DuplicateException();
            }
            throw new Exception("SQL Error: ({$errorInfo[0]}:{$errorInfo[1]}) {$errorInfo[2]} (SQL: {$sql})");
        }
        $res = $statement->execute();
	if (($t = Pix_Table::getLongQueryTime()) and ($delta = (microtime(true) - $starttime)) > $t) {
            Pix_Table::debug(sprintf("[%s]\t%s\t%40s", $this->_pdo->getAttribute(PDO::ATTR_SERVER_INFO), $delta, $sql));
	}

	if ($res === false) {
            if ($errno = $this->_pdo->errorCode()) {
                $errorInfo = $this->_pdo->errorInfo();
            }
            if ($errorInfo[2] == 'PRIMARY KEY must be unique' or
                    preg_match('/duplicate key value violates unique constraint/', $errorInfo[2])) {
                throw new Pix_Table_DuplicateException();
            }
            throw new Exception("SQL Error: ({$errorInfo[0]}:{$errorInfo[1]}) {$errorInfo[2]} (SQL: {$sql})");
	}
        
        return new Pix_Table_Db_Adapter_PDO_Result($statement);
    }

    /**
     * createTable 將 $table 建立進資料庫內
     * 
     * @param Pix_Table $table 
     * @access public
     * @return void
     */
    public function createTable($table)
    {
        $sql = "CREATE TABLE \"" . $table->getTableName() . '"';
        $types = array('bigint', 'tinyint', 'int', 'varchar', 'char', 'text', 'float', 'double', 'binary', 'geography');
        $primarys = is_array($table->_primary) ? $table->_primary : array($table->_primary);
        $pk_isseted = false;

	foreach ($table->_columns as $name => $column) {
            $s = $this->column_quote($name) . ' ';
            $db_type = in_array($column['type'], $types) ? $column['type'] : 'text';

	    if ($column['unsigned'] and !$column['auto_increment']) {
		$s .= 'UNSIGNED ';
	    }

            if ($column['auto_increment']) {
                $s .= 'SERIAL';
            } elseif ('int' == $db_type) {
                $s .= 'INTEGER';
            } elseif ('binary' == $db_type) {
                $s .= 'BYTEA';
            } elseif ('geography' == $db_type) {
                if (array_key_exists('modifier', $column)) {
                    $type = in_array(strtoupper($column['modifier'][0]), array('POINT', 'LINESTRING', 'POLYGON', 'MULTIPOINT', 'MULTILINESTRING', 'MULTIPOLYGON')) ? strtoupper($column['modifier'][0]) : 'POINT';
                    $srid = array_key_exists(1, $column['modifier']) ? intval($column['modifier'][1]) : 0;
                } else {
                    $type = 'POINT';
                    $srid = 0;
                }
                $s .= 'geography(' . $type . ',' . $srid . ')';
            } else {
                $s .= strtoupper($db_type);
            }

	    if (in_array($db_type, array('varchar', 'char'))) {
		if (!$column['size']) {
		    throw new Exception('you should set the option `size`');
		}
		$s .= '(' . $column['size'] . ')';
	    }

            $s .= ' ';

            if ($column['auto_increment']) {
                if ($primarys[0] != $name or count($primarys) > 1) {
                    throw new Exception('SQLITE 的 AUTOINCREMENT 一定要是唯一的 Primary Key');
                }
                $s .= ' PRIMARY KEY ';
                $pk_isseted = true;
	    }

            if (isset($column['default'])) {
                $s .= 'DEFAULT ' . $this->quoteWithColumn($table, $column['default'], $name) . ' ';
	    }

	    $column_sql[] = $s;
	}

        if (!$pk_isseted) {
            $s = 'PRIMARY KEY ' ;
            $index_columns = array();
            foreach ((is_array($table->_primary) ? $table->_primary : array($table->_primary)) as $pk) {
                $index_columns[] = $this->column_quote($pk);
            }
            $s .= '(' . implode(', ', $index_columns) . ")\n";
            $column_sql[] = $s;
        }

	$sql .= " (\n" . implode(", \n", $column_sql) . ") \n";

        // CREATE TABLE
        $this->query($sql);

        foreach ($table->getIndexes() as $name => $options) {
            if ('unique' == $options['type']) {
                $s = 'CREATE UNIQUE INDEX ';
            } else {
                $s = 'CREATE INDEX ';
            }
            $columns = $options['columns'];
            $s .= $this->column_quote($table->getTableName() . '_' . $name) . ' ON ' . $this->column_quote($table->getTableName());
            $index_columns = array();
            foreach ($columns as $column_name) {
                $index_columns[] = $this->column_quote($column_name);
            }
            $s .= '(' . implode(', ', $index_columns) . ') ';

            $this->query($s);
        }
    }

    public function dropTable($table)
    {
        if (!Pix_Setting::get('Table:DropTableEnable')) {
            throw new Pix_Table_Exception("要 DROP TABLE 前請加上 Pix_Setting::set('Table:DropTableEnable', true);");
        }
        $sql = "DROP TABLE \""  . $table->getTableName() . '"';
	return $this->query($sql, $table);
    }

    /**
     * column_quote 把 $a 字串加上 quote
     * 
     * @param string $a 
     * @param Pix_Table $table
     * @access public
     * @return string
     */
    public function column_quote($a, $table = null)
    {
        if (is_null($table) or !array_key_exists($a, $table->_columns)) {
            return '"' . addslashes($a) . '"';
        }

        $column_options = $table->_columns[$a];
        if ('geography' == $column_options['type']) {
            return 'ST_Y("' . addslashes($a) . '"::geometry) AS "' . addslashes($a) . ':0", ST_X("' . addslashes($a) . '"::geometry) AS "' . addslashes($a) . ':1"';

        }

        return '"' . addslashes($a) . '"';
    }

    public function quoteWithColumn($table, $value, $column_name = null)
    {
	if (is_null($column_name)) {
            return $this->_pdo->quote($value);
	}
	if ($table->isNumbericColumn($column_name)) {
	    return intval($value);
        }
        if ('geography' == $table->_columns[$column_name]['type']) {
            if ('POINT' == $table->_columns[$column_name]['modifier'][0]) {
                return "ST_GeographyFromText('POINT(" . floatval($value[1]) . ' ' . floatval($value[0]) . ")')";
            }
        }

	if (!is_scalar($value)) {
            trigger_error("{$_SERVER['SERVER_NAME']}{$_SERVER['REQUEST_URI']} 的 column `{$column_name}` 格式不正確: " . gettype($value), E_USER_WARNING);
	}
        return $this->_pdo->quote($value);
    }

    public function getLastInsertId($table)
    {
        foreach ($table->getPrimaryColumns() as $col) {
            if ($table->_columns[$col]['auto_increment']) {
                return $this->_pdo->lastInsertId($table->getTableName() . '_id_seq');
            }
        }
        return null;
    }

    /**
     * get all tables in this db
     *
     * @access public
     * @return array
     */
    public function getTables()
    {
        $res = $this->query("SELECT table_name FROM information_schema.tables WHERE table_schema = 'public'");
        $tables = array();
        while ($row = $res->fetch_array()) {
            $tables[] = $row[0];
        }
        $res->free_result();
        return $tables;
    }
}
