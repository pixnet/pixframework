<?php

/**
 * Pix_Table_Db_Adapter_Mysqli 
 * 
 * @uses Pix_Table_Db_Adapter
 * @package Table
 * @copyright 2003-2012 PIXNET Digital Media Corporation
 * @license http://framework.pixnet.net/license BSD License
 */
class Pix_Table_Db_Adapter_Mysqli extends Pix_Table_Db_Adapter_SQL
{
    protected $_link;

    public function __construct($link)
    {
	$this->_link = $link;
    }

    public function getSupportFeatures()
    {
        return array('immediate_consistency');
    }

    /**
     * __get 為了與 MySQLi object 相容所加的 __get($name); 
     * 
     * @param mixed $name 
     * @access public
     * @return void
     */
    public function __get($name)
    {
	return $this->_link->{$name};
    }

    /**
     * __call 為了與 MySQLi 相容所加的 __call()
     * 
     * @param mixed $name 
     * @param mixed $args 
     * @access public
     * @return void
     */
    public function __call($name, $args)
    {
	return call_user_func_array(array($this->_link, $name), $args);
    }

    /**
     * query 對 db 下 SQL query
     * 
     * @param mixed $sql 
     * @access protected
     * @return Mysqli result
     */
    public function query($sql, $table = null)
    {
	if (Pix_Table::$_log_groups[Pix_Table::LOG_QUERY]) {
	    Pix_Table::debug(sprintf("[%s]\t%40s", $this->_link->host_info, $sql));
	}
	// TODO 需要 log SQL Query 功能
	if ($comment = Pix_Table::getQueryComment()) {
	    $sql = trim($sql, '; ') . ' #' . $comment;
	}

	$starttime = microtime(true);
	$res = $this->_link->query($sql);
	if (($t = Pix_Table::getLongQueryTime()) and ($delta = (microtime(true) - $starttime)) > $t) {
	    Pix_Table::debug(sprintf("[%s]\t%s\t%40s", $this->_link->host_info, $delta, $sql));
	}

	if ($res === false) {
	    if ($errno = $this->_link->errno) {
                switch ($errno) {
                case 1062:
                    throw new Pix_Table_DuplicateException($this->_link->error, $errno);
                case 1406:
                    throw new Pix_Table_DataTooLongException($this->_link->error, $errno);
		default:
                    throw new Exception("SQL Error: {$this->_link->error} SQL: $sql");
		}
            }
	}
	return $res;
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
        $sql = "CREATE TABLE " . $this->column_quote($table->getTableName());
	$types = array('bigint', 'tinyint', 'int', 'varchar', 'char', 'text', 'float', 'double', 'binary');

	foreach ($table->_columns as $name => $column) {
            $s = $this->column_quote($name) . ' ';
	    $db_type = in_array($column['type'], $types) ? $column['type'] : 'text';
	    $s .= strtoupper($db_type);

	    if (in_array($db_type, array('varchar', 'char', 'binary'))) {
		if (!$column['size']) {
		    throw new Exception('you should set the option `size`');
		}
		$s .= '(' . $column['size'] . ')';
	    }
	    $s .= ' ';

	    if ($column['unsigned']) {
		$s .= 'UNSIGNED ';
	    }

	    if (isset($column['not-null']) and !$column['not-null']) {
		$s .= 'NULL ';
	    } else {
		$s .= 'NOT NULL ';
	    }

	    if (isset($column['default'])) {
                $s .= 'DEFAULT ' . $this->quoteWithColumn($table, $column['default'], $name) . ' ';
	    }

	    if ($column['auto_increment']) {
		$s .= 'AUTO_INCREMENT ';
	    }

	    $column_sql[] = $s;
	}

	$s = 'PRIMARY KEY ' ;
	$index_columns = array();
	foreach ((is_array($table->_primary) ? $table->_primary : array($table->_primary)) as $pk) {
            $index_columns[] = $this->column_quote($pk);
	}
	$s .= '(' . implode(', ', $index_columns) . ")\n";
	$column_sql[] = $s;

        foreach ($table->getIndexes() as $name => $options) {
            if ('unique' == $options['type']) {
                $s = 'UNIQUE KEY ' . $this->column_quote($name) . ' ';
            } else {
                $s = 'KEY ' . $this->column_quote($name);
            }
            $columns = $options['columns'];

            $index_columns = array();
            foreach ($columns as $column_name) {
                $index_columns[] = $this->column_quote($column_name);
            }
            $s .= '(' . implode(', ', $index_columns) . ') ';

            $column_sql[] = $s;
        }

	$sql .= " (\n" . implode(", \n", $column_sql) . ") ENGINE = InnoDB\n";

	return $this->query($sql, $table);
    }

    /**
     * dropTable 從資料庫內移除 $table 這個 Table
     * 
     * @param Pix_Table $table 
     * @access public
     * @return void
     */
    public function dropTable($table)
    {
        if (!Pix_Setting::get('Table:DropTableEnable')) {
            throw new Pix_Table_Exception("要 DROP TABLE 前請加上 Pix_Setting::set('Table:DropTableEnable', true);");
        }
        $sql = "DROP TABLE " . $this->column_quote($table->getTableName());
	return $this->query($sql, $table);
    }

    /**
     * column_quote 把 $a 字串加上 quote
     * 
     * @param string $a 
     * @access public
     * @return string
     */
    public function column_quote($a)
    {
	return "`" . addslashes($a) . "`";
    }

    public function quoteWithColumn($table, $value, $column_name = null)
    {
	if (is_null($column_name)) {
            return "'" . $this->_link->real_escape_string($value) . "'";
	}
	if ($table->isNumbericColumn($column_name)) {
	    return intval($value);
	}
	if (!is_scalar($value)) {
            trigger_error("{$_SERVER['SERVER_NAME']}{$_SERVER['REQUEST_URI']} 的 column `{$column_name}` 格式不正確: " . gettype($value), E_USER_WARNING);
	}
        return "'" . $this->_link->real_escape_string($value) . "'";
    }

    public function getLastInsertId($table)
    {
        return $this->_link->insert_id;
    }

}
