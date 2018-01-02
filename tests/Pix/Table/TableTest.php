<?php

class Pix_Table_TableTest_TableRow extends Pix_Table_Row
{
    public function getTable2()
    {
    }
}

class Pix_Table_TableTest_Table extends Pix_Table
{
    public function init()
    {
        $this->_name = 'table_`test`_escape';
        $this->_primary = 't1_id';
        $this->_rowClass = 'Pix_Table_TableTest_TableRow';

        $this->_columns['t1_id'] = array('type' => 'int', 'auto_increment' => true);
        $this->_columns['value'] = array('type' => 'text', 'default' => 'default');

        $this->_relations['table3s'] = array('rel' => 'has_many', 'type' => 'Pix_Table_TableTest_Table3', 'foreign_key' => 't1_id');
        $this->_relations['table2'] = array('rel' => 'has_one', 'type' => 'Pix_Table_TableTest_Table2', 'foreign_key' => 't1_id');
        $this->_relations['nf_table3s'] = array('rel' => 'has_many', 'type' => 'Pix_Table_TableTest_Table3');
        $this->_relations['nf_table2'] = array('rel' => 'has_one', 'type' => 'Pix_Table_TableTest_Table2');
        $this->_relations['table_error'] = array('rel' => 'xxx');

        $this->_hooks['hook_table2'] = array('get' => 'getTable2', 'set' => 'setTable2');
    }
}

class Pix_Table_TableTest_Table2 extends Pix_Table
{
    public function init()
    {
        $this->_name = 'table2';
        $this->_primary = 't2_id';

        $this->_columns['t2_id'] = array('type' => 'int', 'auto_increment' => true);
        $this->_columns['value'] = array('type' => 'text');

        $this->addFilter('filter_default', array());
    }
}

class Pix_Table_TableTest_Table3 extends Pix_Table
{
    public function init()
    {
        $this->_name = 'table3';
        $this->_primary = array('t3_id', 't3_id2');

        $this->_columns['t3_id'] = array('type' => 'int');
        $this->_columns['t3_id2'] = array('type' => 'int');
        $this->_columns['value'] = array('type' => 'text');
    }
}

class Pix_Table_TableTest_TableContstructFailed extends Pix_Table
{
    public function __construct()
    {
        // 在 __construct 用 $this->xxx function 會 failed, 要在 init 才行...
        $this->createTable();
    }
}

/**
 * Test class for Pix_Table.
 * Generated by PHPUnit on 2011-11-04 at 16:41:48.
 */
class Pix_Table_TableTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Pix_Table
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown()
    {
    }

    /**
     * 測試 QueryComment 能不能正常被寫入
     */
    public function testSetQueryComment()
    {
        Pix_Table::setQueryComment('test');
        $this->assertEquals(Pix_Table::getQueryComment(), 'test');

        Pix_Table::setQueryComment();
        $this->assertEquals(Pix_Table::getQueryComment(), null);
    }

    /**
     * 未設定過 LongQueryTime 時，應該要傳回 0
     */
    public function testGetLongQueryTimeInitialValue()
    {
        $this->assertEquals(0, Pix_Table::getLongQueryTime());
    }

    /**
     * 測試 LongQueryTime 是否能正常讀取寫入
     */
    public function testSetLongQueryTime()
    {
        Pix_Table::setLongQueryTime();
        $this->assertEquals(Pix_Table::getLongQueryTime(), 1);

        Pix_Table::setLongQueryTime(100);
        $this->assertEquals(Pix_Table::getLongQueryTime(), 100);

        Pix_Table::setLongQueryTime(0);
        $this->assertEquals(Pix_Table::getLongQueryTime(), 0);

        Pix_Table::setLongQueryTime();
        $this->assertEquals(Pix_Table::getLongQueryTime(), 1);
    }

    /**
     * 測試增加 filter 和取得 filter
     */
    public function testAddFilter()
    {
        $this->assertEquals(count(Pix_Table::getTable('Pix_Table_TableTest_Table')->getFilters()), 0);
        $this->assertEquals(count(Pix_Table::getTable('Pix_Table_TableTest_Table2')->getFilters()), 1);
    }

    /**
     * 測試 ForceMaster 參數的設定和取得
     */
    public function testSetForceMaster()
    {
        Pix_Table::setForceMaster(1);
        $this->assertEquals(Pix_Table::getForceMaster(), 1);

        Pix_Table::setForceMaster(0);
        $this->assertEquals(Pix_Table::getForceMaster(), 0);
    }

    /**
     * 測試 Cache 相關
     */
    public function testSetCache()
    {
        Pix_Table::setCache(null);
        $this->assertEquals(Pix_Table::getCache(), null);

        $cache = new Pix_Cache;
        Pix_Table::setCache($cache);
        $this->assertEquals(Pix_Table::getCache(), $cache);

        Pix_Table::setCache(null);
        $this->assertEquals(Pix_Table::getCache(), null);
    }

    /**
     * 測試 setCache 傳入不正確的東西
     * @expectedException           Pix_Table_Exception
     */
    public function testSetCacheException()
    {
        Pix_Table::setCache(123);
    }

    /**
     * Log Group 狀態
     */
    public function testEnableLog()
    {
        Pix_Table::enableLog(Pix_Table::LOG_CACHE);
        $this->assertEquals(Pix_Table::getLogStatus(Pix_Table::LOG_CACHE), true);

        Pix_Table::disableLog(Pix_Table::LOG_CACHE);
        $this->assertEquals(Pix_Table::getLogStatus(Pix_Table::LOG_CACHE), null);
    }

    /**
     * 設定 CACHE 狀態
     */
    public function testEnableCache()
    {
        Pix_Table::enableCache(Pix_Table::CACHE_ALL);
        $this->assertEquals(Pix_Table::getCacheStatus(Pix_Table::CACHE_ALL), true);

        Pix_Table::disableCache(Pix_Table::CACHE_ALL);
        $this->assertEquals(Pix_Table::getCacheStatus(Pix_Table::CACHE_ALL), null);
    }

    /**
     * 測試 getDb 找不到 Db 應該要 Pix_Table_Exception
     * @expectedException Pix_Table_Exception
     */
    public function testGetDbException()
    {
        Pix_Table::setDefaultDb(null);
        Pix_Table::getTable('Pix_Table_TableTest_Table2')->getDb();
    }

    /**
     * 測試 getDb , 如果 Table 有指定應該要傳回 Table 指定的，沒有就是 default
     */
    public function testGetDb()
    {
        $db = $this->getMockBuilder('Pix_Table_Db_Adapter_Abstract')
            ->getMock();
        Pix_Table::setDefaultDb($db);

        $this->assertEquals(Pix_Table::getTable('Pix_Table_TableTest_Table2')->getDb(), $db);
    }

    /**
     * 測試 is_a
     */
    public function testIs_a()
    {
        $this->assertEquals(Pix_Table::is_a('123', 'Pix_Table_TableTest_Table'), false);
        $this->assertEquals(Pix_Table::is_a(123, 'Pix_Table_TableTest_Table'), false);
        $this->assertEquals(Pix_Table::is_a(array(123), 'Pix_Table_TableTest_Table'), false);
        $this->assertEquals(Pix_Table::is_a(new StdClass, 'Pix_Table_TableTest_Table'), false);

        $row = Pix_Table_TableTest_Table::createRow();
        $this->assertEquals(Pix_Table::is_a($row, 'Pix_Table_TableTest_Table'), true);

        $row = Pix_Table_TableTest_Table2::createRow();
        $this->assertEquals(Pix_Table::is_a($row, 'Pix_Table_TableTest_Table2'), true);
    }

    /**
     * Pix_Table::getTable 各種用法
     */
    public function testGetTable()
    {
        $table = Pix_Table_TableTest_Table::getTable();
        $this->assertEquals(get_class($table), 'Pix_Table_TableTest_Table');

        $table = Pix_Table::getTable('Pix_Table_TableTest_Table');
        $this->assertEquals(get_class($table), 'Pix_Table_TableTest_Table');

        $table = Pix_Table::getTable($table);
        $this->assertEquals(get_class($table), 'Pix_Table_TableTest_Table');
    }

    /**
     * 測試 get 不存在的 Table
     * @expectedException Pix_Table_Exception
     */
    public function testGetTableExceptionNotFound()
    {
        Pix_Table::getTable('Pix_Table_TableTest_NotFound');
    }

    /**
     * 測試 get 不存在的 Table
     * @expectedException Pix_Table_Exception
     */
    public function testGetTableExceptionCallStaticFunctionInConstruct()
    {
        Pix_Table::getTable('Pix_Table_TableTest_CallStaticFunctionInConstruct');
    }

    /**
     * 測試連接到 adapter 的部分
     */
    public function testCreateTable()
    {
        $db = $this->getMockBuilder('Pix_Table_Db_Adapter_Abstract')
            ->setMethods(['createTable'])
            ->getMock();
        Pix_Table_TableTest_Table::setDb($db);
        $db->expects($this->once())
            ->method('createTable')
            ->will($this->returnValue(true));
        $this->assertEquals(Pix_Table_TableTest_Table::createTable(), true);
    }

    public function testCheckTable()
    {
        $db = $this->getMockBuilder('Pix_Table_Db_Adapter_Abstract')
            ->setMethods(['checkTable', 'support'])
            ->getMock();
        Pix_Table_TableTest_Table::setDb($db);
        $db->expects($this->once())
            ->method('checkTable')
            ->will($this->returnValue(true));

        $db->expects($this->any())
            ->method('support')
            ->with($this->logicalOr('check_table'))
            ->will($this->returnValue(true));
        $this->assertEquals(Pix_Table_TableTest_Table::checkTable(), true);
    }

    public function testDropTable()
    {
        $db = $this->getMockBuilder('Pix_Table_Db_Adapter_Abstract')
            ->setMethods(['dropTable'])
            ->getMock();
        Pix_Table_TableTest_Table::setDb($db);

        $old_value = Pix_Setting::get('Table:DropTableEnable');
        $db->expects($this->once())
            ->method('dropTable')
            ->will($this->returnValue(true));
        Pix_Setting::set('Table:DropTableEnable', true);
        $this->assertEquals(Pix_Table_TableTest_Table::dropTable(), true);
        Pix_Setting::set('Table:DropTableEnable', $old_value);
    }

    /**
     * 測試未指定 DropTableEnable 必需要 dropTable 失敗
     *
     * @access public
     */
    public function testDropTableProtect()
    {
        $old_value = Pix_Setting::get('Table:DropTableEnable');
        Pix_Setting::set('Table:DropTableEnable', false);
        try {
            Pix_Table_TableTest_Table::dropTable();
            $this->assertTrue(false);
        } catch (Pix_Table_Exception $e) {
            $this->assertTrue(true);
        }
        Pix_Setting::set('Table:DropTableEnable', $old_value);
    }

    public function testSearch()
    {
        $table = Pix_Table::getTable('Pix_Table_TableTest_Table');

        // search(all)
        $db = $this->getMockBuilder('Pix_Table_Db_Adapter_Abstract')
            ->setMethods(['fetch'])
            ->getMock();
        Pix_Table_TableTest_Table::setDb($db);
        $search = Pix_Table_Search::factory()->limit(1);
        $db->expects($this->at(0))
            ->method('fetch')
            ->with($this->isInstanceOf('Pix_Table_TableTest_Table'), $search, '*')
            ->will($this->returnValue(array(array('t1_id' => 3, 'value' => 'abc'))));

        $row = Pix_Table_TableTest_Table::search(1)->first();

        $this->assertEquals($row->t1_id, 3);
        $this->assertEquals($row->value, 'abc');

        // search(array())
        $db = $this->getMockBuilder('Pix_Table_Db_Adapter_Abstract')
            ->setMethods(['fetch'])
            ->getMock();
        Pix_Table_TableTest_Table::setDb($db);
        $search = Pix_Table_Search::factory()->limit(1);
        $search = $search->search(array('value' => 5));
        $db->expects($this->once())
            ->method('fetch')
            ->with($this->isInstanceOf('Pix_Table_TableTest_Table', $search, '*'))
            ->will($this->returnValue(array(array('t1_id' => 5, 'value' => '5'))));

        $row = Pix_Table_TableTest_Table::search(array('value' => 5))->first();

        $this->assertEquals($row->t1_id, 5);
        $this->assertEquals($row->value, '5');

        // search(string)
        $db = $this->getMockBuilder('Pix_Table_Db_Adapter_Abstract')
            ->setMethods(['fetch'])
            ->getMock();
        Pix_Table_TableTest_Table::setDb($db);
        $search = Pix_Table_Search::factory();
        $search = $search->search("t1_id > 3");
        $db->expects($this->once())
            ->method('fetch')
            ->with($this->isInstanceOf('Pix_Table_TableTest_Table'), $search, '*')
            ->will($this->returnValue(array(
                    array('t1_id' => 4, 'value' => 4),
                    array('t1_id' => 5, 'value' => 5),
                )));

        $array = Pix_Table_TableTest_Table::search("t1_id > 3")->toArray();

        $this->assertEquals($array, array(
            4 => array('t1_id' => 4, 'value' => 4),
            5 => array('t1_id' => 5, 'value' => 5),
        ));

        // search PK
        $db = $this->getMockBuilder('Pix_Table_Db_Adapter_Abstract')
            ->setMethods(['fetchOne'])
            ->getMock();
        Pix_Table_TableTest_Table::setDb($db);
        $db->expects($this->once())
            ->method('fetchOne')
            ->with($this->isInstanceOf('Pix_Table_TableTest_Table'), array(6))
            ->will($this->returnValue(array('t1_id' => 6, 'value' => 'abc')));

        $row = Pix_Table_TableTest_Table::search(array('t1_id' => 6, 'value' => 'abc'))->first();
        $this->assertEquals($row->t1_id, 6);
        $this->assertEquals($row->value, 'abc');

        $db = $this->getMockBuilder('Pix_Table_Db_Adapter_Abstract')
            ->setMethods(['fetchOne'])
            ->getMock();
        Pix_Table_TableTest_Table::setDb($db);
        $search = Pix_Table_Search::factory();
        $search = $search->search(array("t1_id" => 7, "value" => "def"));
        $db->expects($this->once())
            ->method('fetchOne')
            ->with($this->isInstanceOf('Pix_Table_TableTest_Table'), array(7))
            ->will($this->returnValue(array('t1_id' => 7, 'value' => 'abc')));

        $row = Pix_Table_TableTest_Table::search(array('t1_id' => 7, 'value' => 'def'))->first();
        $this->assertEquals($row, null);
    }

    public function testFind()
    {
        $db = $this->getMockBuilder('Pix_Table_Db_Adapter_Abstract')
            ->setMethods(['fetchOne'])
            ->getMock();
        Pix_Table_TableTest_Table::setDb($db);
        $table = Pix_Table::getTable('Pix_Table_TableTest_Table');

        $db->expects($this->at(0))
            ->method('fetchOne')
            ->with($this->isInstanceOf('Pix_Table_TableTest_Table'), array(1))
            ->will($this->returnValue(null));

        $db->expects($this->at(1))
            ->method('fetchOne')
            ->with($this->isInstanceOf('Pix_Table_TableTest_Table'), array(2))
            ->will($this->returnValue(array('t1_id' => 2, 'value' => 'abc')));

        $this->assertEquals(Pix_Table_TableTest_Table::find(1), null);

        $row = Pix_Table_TableTest_Table::find(2);
        $this->assertEquals($row->t1_id, 2);
        $this->assertEquals($row->value, 'abc');

        $row = Pix_Table_TableTest_Table::find(array(2));
        $this->assertEquals($row->t1_id, 2);
        $this->assertEquals($row->value, 'abc');
    }

    public function testFind_by()
    {
        $table = Pix_Table::getTable('Pix_Table_TableTest_Table');

        $db = $this->getMockBuilder('Pix_Table_Db_Adapter_Abstract')
            ->setMethods(['fetch'])
            ->getMock();
        Pix_Table_TableTest_Table::setDb($db);
        $search = Pix_Table_Search::factory()->limit(1);
        $search = $search->search(array('value' => '999'));
        $db->expects($this->once())
            ->method('fetch')
            ->with($this->isInstanceOf('Pix_Table_TableTest_Table'), $search, '*')
            ->will($this->returnValue(array(
                    array('t1_id' => 8, 'value' => 999),
            )));

        $row = Pix_Table_TableTest_Table::find_by_value(999);
        $this->assertEquals($row->t1_id, 8);
        $this->assertEquals($row->value, 999);
    }

    public function testCreateRow()
    {
        $table = Pix_Table::getTable('Pix_Table_TableTest_Table');

        $db = $this->getMockBuilder('Pix_Table_Db_Adapter')
            ->setMethods(['insertOne', 'support'])
            ->getMock();
        Pix_Table_TableTest_Table::setDb($db);

        $db->expects($this->once())
            ->method('insertOne')
            ->with($this->isInstanceOf('Pix_Table_TableTest_Table'), array('value' => 3))
            ->will($this->returnValue(10));

        $row = Pix_Table_TableTest_Table::createRow();
        $row->value = 3;
        $row->save();

        $this->assertEquals($row->t1_id, 10);
        $this->assertEquals($row->value, 3);

        $db = $this->getMockBuilder('Pix_Table_Db_Adapter')
            ->setMethods(['insertOne', 'support'])
            ->getMock();
        Pix_Table_TableTest_Table::setDb($db);

        $db->expects($this->once())
            ->method('insertOne')
            ->with($this->isInstanceOf('Pix_Table_TableTest_Table'), array('value' => 'default'))
            ->will($this->returnValue(11));

        $row = Pix_Table_TableTest_Table::createRow();
        $row->save();

        $this->assertEquals($row->t1_id, 11);
        $this->assertEquals($row->value, 'default');
    }

    public function testInsert()
    {
        $table = Pix_Table::getTable('Pix_Table_TableTest_Table');

        $db = $this->getMockBuilder('Pix_Table_Db_Adapter_Abstract')
            ->setMethods(['insertOne'])
            ->getMock();
        Pix_Table_TableTest_Table::setDb($db);

        $db->expects($this->once())
            ->method('insertOne')
            ->with($this->isInstanceOf('Pix_Table_TableTest_Table'), array('value' => '999'))
            ->will($this->returnValue(12));

        $row = Pix_Table_TableTest_Table::insert(array('value' => 999));

        $this->assertEquals($row->t1_id, 12);
        $this->assertEquals($row->value, 999);
    }

    public function testGetPrimaryColumns()
    {
        $table = Pix_Table::getTable('Pix_Table_TableTest_Table');
        $this->assertEquals($table->getPrimaryColumns(), array('t1_id'));

        $table = Pix_Table::getTable('Pix_Table_TableTest_Table3');
        $this->assertEquals($table->getPrimaryColumns(), array('t3_id', 't3_id2'));
    }

    public function testIsNumbericColumn()
    {
        $table = Pix_Table::getTable('Pix_Table_TableTest_Table');
        $this->assertEquals($table->isNumbericColumn('t1_id'), true);
        $this->assertEquals($table->isNumbericColumn('value'), false);
    }

    /**
     * 呼叫到不存在的 function
     * @expectedException Pix_Table_Exception
     */
    public function test__callFailed()
    {
        Pix_Table_TableTest_Table::not_exists();
    }

    public function testGetClass()
    {
        $this->assertEquals(Pix_Table::getTable('Pix_Table_TableTest_Table')->getClass(), 'Pix_Table_TableTest_Table');
        $this->assertEquals(Pix_Table::getTable('Pix_Table_TableTest_Table2')->getClass(), 'Pix_Table_TableTest_Table2');
        $this->assertEquals(Pix_Table::getTable('Pix_Table_TableTest_Table3')->getClass(), 'Pix_Table_TableTest_Table3');
    }

    public function testGetRelationForeignTable()
    {
        $this->assertEquals(get_class(Pix_Table_TableTest_Table::getRelationForeignTable('table2')), 'Pix_Table_TableTest_Table2');
        $this->assertEquals(get_class(Pix_Table_TableTest_Table::getRelationForeignTable('table3s')), 'Pix_Table_TableTest_Table3');
    }

    /**
     * 測試 get 不存在的 relation
     * @expectedException Pix_Table_Exception
     */
    public function testGetRelationForeignTableNotFound()
    {
        Pix_Table_TableTest_Table::getRelationForeignTable('relation_not_found');
    }

    /**
     * 測試忘了指定 type 的 relation
     * @expectedException Pix_Table_Exception
     */
    public function testGetRelationForeignTableNoType()
    {
        Pix_Table_TableTest_Table::getRelationForeignTable('table_error');
    }

    public function testGetRelationForeignKeys()
    {
        $this->assertEquals(Pix_Table_TableTest_Table::getRelationForeignKeys('table2'), array('t1_id'));
        $this->assertEquals(Pix_Table_TableTest_Table::getRelationForeignKeys('table3s'), array('t1_id'));
        // A has_one B 未指定的話，預設是 B 的 pk
        $this->assertEquals(Pix_Table_TableTest_Table::getRelationForeignKeys('nf_table2'), array('t2_id'));
        // A has_many B 未指定的話，預設是 A 的 pk
        $this->assertEquals(Pix_Table_TableTest_Table::getRelationForeignKeys('nf_table3s'), array('t1_id'));
    }

    /**
     * 測試 get 不存在的 relation
     * @expectedException Pix_Table_Exception
     */
    public function testGetRelationForeignKeysNotFound()
    {
        Pix_Table_TableTest_Table::getRelationForeignKeys('relation_not_found');
    }

    /**
     * 測試忘了指定 type 的 relation
     * @expectedException Pix_Table_Exception
     */
    public function testGetRelationForeignKeysNoType()
    {
        Pix_Table_TableTest_Table::getRelationForeignKeys('table_error');
    }

    /**
     * 測試忘了指定 has_one or has_many 的 relation
     * @expectedException Pix_Table_Exception
     */
    public function testGetRelationForeignKeysNoRel()
    {
        Pix_Table_TableTest_Table::getRelationForeignKeys('table_error');
    }

    public function testGetTableName()
    {
        $table = Pix_Table::getTable('Pix_Table_TableTest_Table');
        $this->assertEquals($table->getTableName(), 'table_`test`_escape');

        $table = Pix_Table::getTable('Pix_Table_TableTest_Table2');
        $this->assertEquals($table->getTableName(), 'table2');
    }

    /**
     * 如果在 __construct 呼叫 $this->xxx() 會 failed 的測試
     * @expectedException Pix_Table_Exception
     */
    public function testGetTableConstructFailed()
    {
        Pix_Table::getTable('Pix_Table_TableTest_TableContstructFailed');
    }

    public function testIsEditableKey()
    {
        // column
        $this->assertEquals(Pix_Table_TableTest_Table::isEditableKey('t1_id'), true);
        // relation
        $this->assertEquals(Pix_Table_TableTest_Table::isEditableKey('table2'), true);
        // hook
        $this->assertEquals(Pix_Table_TableTest_Table::isEditableKey('hook_table2'), true);
        // _
        $this->assertEquals(Pix_Table_TableTest_Table::isEditableKey('_foo'), true);
        // other false
        $this->assertEquals(Pix_Table_TableTest_Table::isEditableKey('not_exists'), false);
    }
}
