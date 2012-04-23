<?php

class Pix_Table_Row_Plugin_Plugin1 extends Pix_Table_Plugin
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

class Pix_Table_Row_Plugin_Plugin2 extends Pix_Table_Plugin
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

class Pix_Table_Row_Plugin_Plugin3NotPlugin
{
    public function getFuncs()
    {
        return array('no');
    }
}

class Pix_Table_TableRowPluginTest_User extends Pix_Table
{
    public function init()
    {
	$this->_name = 'user';
	$this->_primary = 'id';

	$this->_columns['id'] = array('type' => 'int', 'auto_increment' => true);
	$this->_columns['name'] = array('type' => 'varchar', 'size' => 32);
        $this->_columns['password'] = array('type' => 'varchar', 'size' => 32, 'default' => '');

        $this->addPlugins(array('plugin1_add_3'), 'Pix_Table_Row_Plugin_Plugin1');
        $this->addPlugins(array('plugin1_add_3_alias_name' => 'plugin1_add_3'), 'Pix_Table_Row_Plugin_Plugin1');
        $this->addPlugins('plugin2_add_4', 'Pix_Table_Row_Plugin_Plugin2');
    }
}

class Pix_Table_TableRowPluginTest extends PHPUnit_Framework_TestCase
{
    public function test()
    {
        $this->assertEquals(Pix_Table_TableRowPluginTest_User::createRow()->plugin1_add_3(1, 2), 6);
        $this->assertEquals(Pix_Table_TableRowPluginTest_User::createRow()->plugin1_add_3_alias_name(1, 2), 6);
        $this->assertEquals(Pix_Table_TableRowPluginTest_User::createRow()->plugin2_add_4(1, 2), 7);
    }

    /**
     * 測試 add 不存在的 plugin 
     * @expectedException Pix_Table_Exception
     */
    public function testNoThisRowPlugin()
    {
        Pix_Table_TableRowPluginTest_User::addPlugin('NoThisClass');
    }

    /**
     * 測試找不到 plugin function
     * @expectedException Pix_Table_Exception
     */
    public function testNoThisRowPluginFunction()
    {
        Pix_Table_TableRowPluginTest_User::createRow()->no_this_function();
    }

    /**
     * 測試找不到 plugin class
     * @expectedException Pix_Table_Exception
     */
    public function testNoThisRowPluginNoThisClass()
    {
        $table = Pix_Table::getTable('Pix_Table_TableRowPluginTest_User');
        $table->addPlugins(array('no'), 'Pix_Table_TableRowPluginTest_NotExistsClass');
        $table->createRow()->no();
    }

    /**
     * 測試 plugin class 不是 Pix_Table_Plugin
     * @expectedException Pix_Table_Exception
     */
    public function testNoThisRowPluginNotPluginClass()
    {
        $table = Pix_Table::getTable('Pix_Table_TableRowPluginTest_User');
        $table->addPlugins(array('no'), 'Pix_Table_Row_Plugin_Plugin3NotPlugin');
        $table->createRow()->no();
    }
}
