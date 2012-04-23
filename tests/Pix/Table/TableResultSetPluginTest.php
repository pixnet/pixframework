<?php

class Pix_Table_ResultSet_Plugin_Plugin1
{
    public function getFuncs()
    {
        return array('plugin1_add_3');
    }

    public function plugin1_add_3($self, $a, $b)
    {
        return $a + $b + 3;
    }
}

class Pix_Table_ResultSet_Plugin_Plugin2
{
    public function getFuncs()
    {
        return array('plugin2_add_4');
    }

    public function plugin2_add_4($self, $a, $b)
    {
        return $a + $b + 4;
    }
}

class Pix_Table_TableResultSetPluginTest_User extends Pix_Table
{
    public function init()
    {
	$this->_name = 'user';
	$this->_primary = 'id';

	$this->_columns['id'] = array('type' => 'int', 'auto_increment' => true);
	$this->_columns['name'] = array('type' => 'varchar', 'size' => 32);
        $this->_columns['password'] = array('type' => 'varchar', 'size' => 32, 'default' => '');

        $this->addResultSetStaticPlugins('Plugin1', array('plugin1_add_3'));
        $this->addResultSetStaticPlugins('Plugin2');
    }
}

class Pix_Table_TableResultSetPluginTest extends PHPUnit_Framework_TestCase
{
    public function test()
    {
        $this->assertEquals(Pix_Table_TableResultSetPluginTest_User::search(1)->plugin1_add_3(1, 2), 6);
        $this->assertEquals(Pix_Table_TableResultSetPluginTest_User::search(1)->plugin2_add_4(1, 2), 7);
    }

    /**
     * 測試 add 不存在的 plugin 
     * @expectedException Pix_Table_Exception
     */
    public function testNoThisResultSetPlugin()
    {
        Pix_Table_TableResultSetPluginTest_User::addResultSetStaticPlugins('NoThisClass');
    }

    /**
     * 測試找不到 plugin function
     * @expectedException Pix_Table_Exception
     */
    public function testNoThisResultSetPluginFunction()
    {
        Pix_Table_TableResultSetPluginTest_User::search(1)->no_this_function();
    }
}
