<?php

/**
 * Pix_Table_Db_Adapter_MysqlConf 可以吃 PIXNET 專用 config 格式來建立 DB 的功能
 * 
 * @uses Pix_Table_Db_Adapter
 * @package Table
 * @copyright 2003-2012 PIXNET Digital Media Corporation
 * @license http://framework.pixnet.net/license BSD License
 */
class Pix_Table_Db_Adapter_MysqlConf extends Pix_Table_Db_Adapter_SQL
{
    protected $_config;
    public static $_connect_version = 1;

    public function __get($name)
    {
        throw new Exception("不知名的 column: {$name}");
    }
    public function __construct($config)
    {
	$this->_config = $config;
    }

    protected $_link_pools = array();
    protected $_link_pool_version;

    public function getSupportFeatures()
    {
        return array('force_master', 'immediate_consistency', 'check_table');
    }

    protected function _getLink($type = 'master')
    {
        if (($link = $this->_link_pools['master']) and $this->_link_pool_version == self::$_connect_version and @$link->ping()) {
            return $link;
        }
        if (($link = $this->_link_pools[$type])  and $this->_link_pool_version == self::$_connect_version and @$link->ping()) {
            return $link;
        }

        $link = mysqli_init();

        $wrong = array();
        $retry = 3;

        for ($i = 0; $i < $retry; $i ++) {
            $confs = $this->_config;

            // 只有設定數量大於 1 筆才需要 shuffle
            while (count($confs) > 1) {
                shuffle($confs);

                // 如果有失敗的 log 並且是在五分鐘以內，暫時不連這一台
                $conf = $confs[0]->{$type};
                if ($time = intval(@file_get_contents("/tmp/Pix_Table_Db_Adapter_MysqlConf-{$conf->host}-{$conf->dbname}")) and $time > time() - 300) {
                    array_shift($confs);
                    continue;
                }
                break;
            }

            $conf = $confs[0]->{$type};
            $link->options(MYSQLI_OPT_CONNECT_TIMEOUT, 5);
            $starttime = microtime(true);
            @$link->real_connect($conf->host, $conf->username, $conf->password, $conf->dbname);
            $delta = microtime(true) - $starttime;
            if ($delta > 0.5) {
                trigger_error("{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']} connect to {$conf->host} time: $delta", E_USER_NOTICE);
            }

            if (!$link->connect_errno) {
                break;
            }
            $error = mysqli_connect_error();
            $wrong[] = "[{$conf->host} $error]";
            file_put_contents("/tmp/Pix_Table_Db_Adapter_MysqlConf-{$conf->host}-{$conf->dbname}", time() . ' ' . $error);
        }

        // $retry 用完了還是失敗
        if ($link->connect_errno) {
            trigger_error("{$_SERVER['HTTP_HOST']} reconnect to ($conf_file) $i times failed: " . implode(', ', $wrong), E_USER_NOTICE);
            throw new Pix_DbConnectErrorException("Connect to ($conf_file)($i times) failed: " . implode(', ', $wrong));
            // 有失敗過
        } elseif ($i) {
            trigger_error("{$_SERVER['HTTP_HOST']} reconnect to ($conf_file) $i times: " . implode(', ', $wrong), E_USER_NOTICE);
        }

        if ($conf->init) {
            $link->query($conf->init);
        }

        if ($conf->charset) {
            $link->set_charset($conf->charset);
        } else {
            $link->set_charset('UTF8');
        }

        $this->_link_pool_version = self::$_connect_version;
        return $this->_link_pools[$type] = $link;
    }

    static public function resetConnect()
    {
        self::$_connect_version ++;
    }

    public function query($sql, $table = null)
    {
        // 判斷要用 Master 還是 Slave
        $type = 'master';
        if (!Pix_Table::$_force_master and preg_match('#^SELECT #', strtoupper($sql))) {
            $type = 'slave';
        }

        if (Pix_Setting::get('Table:ExplainFileSortEnable')) {
            if (preg_match('#^SELECT #', strtoupper($sql))) {
                $res = $this->_getLink($type)->query("EXPLAIN $sql");
                $row = $res->fetch_assoc();
                if (preg_match('#Using filesort#', $row['Extra'])) {
                    trigger_error("Using Filesort Query {$sql}", E_USER_WARNING);
                }
                $res->free_result();
            }
        }

        if (Pix_Setting::get('Table:SQLNoCache')) {
            if (preg_match('#^SELECT #', strtoupper($sql))) {
                $sql = 'SELECT SQL_NO_CACHE ' . substr($sql, 7);
            }
        }

        // 加上 Query Comment
        if ($comment = Pix_Table::getQueryComment()) {
            $sql = trim($sql, '; ') . ' #' . $comment;
        }

        for ($i = 0; $i < 3; $i ++) {
            if (!$link = $this->_getLink($type)) {
                throw new Exception('找不到 Link');
            }

            $starttime = microtime(true);
            $res = $link->query($sql);
            $this->insert_id = $link->insert_id;
            $delta = microtime(true) - $starttime;
            if (Pix_Table::$_log_groups[Pix_Table::LOG_QUERY]) {
                Pix_Table::debug(sprintf("[%s-%s](%f)%s", strval($link->host_info), $type, $delta, $sql));
            } elseif (($t = Pix_Table::getLongQueryTime()) and $delta > $t) {
                Pix_Table::debug(sprintf("[%s-%s](%f)%s", strval($link->host_info), $type, $delta, $sql));
            }

            if ($res === false) {
                if ($errno = $link->errno) {
                    $message = (is_null($table) ? '': "Table: {$table->getClass()}") . "SQL Error: ({$errno}){$link->error} " . substr($sql, 0, 128);
                    switch ($errno) {
                    case 1146:
                        throw new Pix_Table_TableNotFoundException($message);
                    case 1062:
                        throw new Pix_Table_DuplicateException((is_null($table) ? '': "(Table: {$table->getClass()})") . $link->error, $errno);
                    case 1406:
                        throw new Pix_Table_DataTooLongException($message);

                    case 2006: // MySQL server gone away
                    case 2013: // Lost connection to MySQL server during query
                        trigger_error("Pix_Table " . $message, E_USER_WARNING);
                        $this->resetConnect();
                        continue 2;
                    }
                }
                throw new Pix_Table_Exception($message);
            }

            if ($link->warning_count) {
                $e = $link->get_warnings();

                do {
                    if (1592 == $e->errno) {
                        continue;
                    }
                    trigger_error("Pix_Table " . (is_null($table) ? '': "Table: {$table->getClass()}") . "SQL Warning: ({$e->errno}){$e->message} " . substr($sql, 0, 128), E_USER_WARNING);
                } while ($e->next());
            }
            return $res;
        }

        throw new Pix_Table_Exception("query 三次失敗");
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

    public function checkTable($table)
    {
        $columns = $table->_columns;
        $res = $this->query("DESCRIBE " . $this->column_quote($table->_name));
        $ret = array();

        while ($row = $res->fetch_assoc()) {
            $field = $row['Field'];

            // 檢查 autoincrement
            if ($row['Extra'] == 'auto_increment' xor $columns[$field]['auto_increment']) {
                $db = ($row['Extra'] == 'auto_increment') ? 1 : 0;
                $model = $columns[$field]['auto_increment'] ? 1 : 0;
                $ret[] = array(
                    'auto_increment',
                    $table,
                    $this,
                    "Model 沒設定到 $field 這個 column 為 Auto_Increment(db: {$db}, Model: {$model})",
                );
            }

            // 檢查 default 值
            if (($row['Default'] != $columns[$field]['default'])) {
                $db = strval($row['Default']);
                $model = $columns[$field]['default'];
                $ret[] = array(
                    'default',
                    $table,
                    $this,
                    "Model 沒設定到 $field 這個 column default(db: {$db}, Model: {$model})",
                );
            }

            // 檢查 Type
            $type = $row['Type'];
            if (preg_match('#^([a-z]+)\((\d+)\)\s?(unsigned)?$#', $type, $matches)) {
                if ($columns[$field]['type'] != $matches[1]) {
                    $db = strval($matches[1]);
                    $model = $columns[$field]['type'];
                    $ret[] = array(
                        'type',
                        $table,
                        $this,
                        "Model $field type 不符合(db: {$db}, Model: {$model})",
                    );
                }
                if (in_array($matches[1], array('binary', 'varchar', 'char')) and $columns[$field]['size'] != $matches[2]) {
                    $db = $matches[2];
                    $model = $columns[$field]['size'];
                    $ret[] = array(
                        'type|size',
                        $table,
                        $this,
                        "Model $field size 不符合(db: {$db}, Model: {$model})",
                    );
                }
                if ($ret[3] == 'unsigned' xor $columns[$field]['unsigned']) {
                    $db = ($ret[3] == 'unsigned') ? 'unsigned' : 'signed';
                    $model = $columns[$field]['unsigned'] ? 'unsigned' : 'signed';
                    $ret[] = array(
                        'type|unsigned',
                        $table,
                        $this,
                        "Model $field unsigned 不符合(db: {$db}, Model: {$model})",
                    );
                }
            } elseif (preg_match('#^([a-z]+)\s?(unsigned)?$#', $type, $matches)) {
                if ($columns[$field]['type'] != $matches[1]) {
                    $db = $matches[1];
                    $model = $columns[$field]['type'];
                    $ret[] = array(
                        'type',
                        $table,
                        $this,
                        "Model $field type 不符合(db: {$db}, Model: {$model})",
                    );
                }
                if ($matches[2] == 'unsigned' xor $columns[$field]['unsigned']) {
                    $db = ($ret[2] == 'unsigned') ? 'unsigned' : 'signed';
                    $model = $columns[$field]['unsigned'] ? 'unsigned' : 'signed';
                    $ret[] = array(
                        'type|unsigned',
                        $table,
                        $this,
                        "Model $field unsigned 不符合(db: {$db}, Model: {$model})",
                    );
                }
            } elseif (preg_match('#^enum\((.*)\)$#', $type, $matches)) {
                if ($columns[$field]['type'] != 'enum') {
                    $db = 'enum';
                    $model = $columns[$field]['type'];
                    $ret[] = array(
                        'type',
                        $table,
                        $this,
                        "Model $field type 不符合(db: {$db}, Model: {$model})",
                    );
                }
                $fields[$name]['list'] = $matches[1];
            } elseif (preg_match('#^set\((.*)\)$#', $type, $matches)) {
                if ($columns[$field]['type'] != 'set') {
                    $db = 'set';
                    $model = $columns[$field]['type'];
                    $ret[] = array(
                        'type',
                        $table,
                        $this,
                        "Model $field type 不符合(db: {$db}, Model: {$model})"
                    );
                }
                $list = $matches[1];
            } else {
                $ret[] = array(
                    'type',
                    $table,
                    $this,
                    "Unknown: $type",
                );
                // TODO: Enum
            }

            // 檢查未設定到的 column
            if (isset($columns[$field])) {
                unset($columns[$field]);
            } else {
                $ret[] = array(
                    'field',
                    $table,
                    $this,
                    "Model 沒設定到 $field 這個 column",
                );
            }
        }
        $res->free_result();
        foreach ($columns as $name => $c) {
            $ret[] = array(
                '',
                $table,
                $this,
                "Model 在資料庫上沒有 $name 這個 column"
            );
        }

        return $ret;
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

    /**
     * quote 將 $str 字串內容 quote 起來。
     * 
     * @param string $str 
     * @access public
     * @return string
     */
    public function quoteWithColumn($table, $value, $column_name)
    {
        $link = $this->_getLink('slave');

	if (is_null($column_name)) {
            return "'" . $link->real_escape_string(strval($value)) . "'";
	}
	if ($table->isNumbericColumn($column_name)) {
	    return intval($value);
	}
	if (!is_scalar($value)) {
            trigger_error("{$_SERVER['SERVER_NAME']}{$_SERVER['REQUEST_URI']} 的 column `{$column_name}` 格式不正確: " . gettype($value), E_USER_WARNING);
	}
        return "'" . $link->real_escape_string(strval($value)) . "'";
    }

    public function getLastInsertId($table = null)
    {
        return $this->insert_id;
    }

}
