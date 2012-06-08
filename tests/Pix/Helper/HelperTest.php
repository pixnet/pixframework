<?php

class Pix_Helper_HelperTest_Helper1 extends Pix_Helper
{
    public function getFuncs()
    {
        return array('test1');
    }

    public function test1()
    {
        return 'test1 returned';
    }
}

class Pix_Helper_HelperTest_NotPixHelper
{
}

class Pix_Helper_HelperTest extends PHPUnit_Framework_TestCase
{
    public function testManager()
    {
        $manager = new Pix_Helper_Manager();
        $manager->addHelper('Pix_Helper_HelperTest_Helper1');

        $this->assertTrue($manager->hasMethod('test1'));

        $this->assertEquals($manager->getMethods(), array('test1'));

        $this->assertEquals($manager->callHelper('test1', array()), 'test1 returned');
    }

    /**
     * test invalid helper name in Pix_Helper_Manager->addHelper();
     * @expectedException Pix_Helper_Exception
     */
    public function testInvalidHelperName()
    {
        $manager = new Pix_Helper_Manager();
        $manager->addHelper(array());
    }

    /**
     * test not exists helper class name 
     * @expectedException Pix_Helper_Exception
     */
    public function testNotExistedHelperClass()
    {
        $manager = new Pix_Helper_Manager();
        $manager->addHelper('Pix_Helper_HelperTest_HelperNotExists');
    }

    /**
     * test not exists helper class name (addHelper with $methods, it will throw exception in callHelper)
     * @expectedException Pix_Helper_Exception
     */
    public function testNotExistedHelperClass2()
    {
        $manager = new Pix_Helper_Manager();
        $manager->addHelper('Pix_Helper_HelperTest_HelperNotExists', array('test'));
        $manager->callHelper('test', array());
    }

    /**
     * test addHelper() which is not Pix_Helper
     * @expectedException Pix_Helper_Exception
     */
    public function testNotPixHelperHelperClass()
    {
        $manager = new Pix_Helper_Manager();
        $manager->addHelper('Pix_Helper_HelperTest_NotPixHelper');
    }

    /**
     * test addHelper() which is not Pix_Helper (addHelper with $methods, it will throw exception in callHelper)
     * @expectedException Pix_Helper_Exception
     */
    public function testNotPixHelperHelperClass2()
    {
        $manager = new Pix_Helper_Manager();
        $manager->addHelper('Pix_Helper_HelperTest_NotPixHelper', array('test'));
        $manager->callHelper('test', array());
    }


    /**
     * test invalid method in addHelper
     * @expectedException Pix_Helper_Exception
     */
    public function testInvalidMethodsInAddHelper()
    {
        $manager = new Pix_Helper_Manager();
        $manager->addHelper('Pix_Helper_HelperTest_Helper1', 'must array');
    }

    /**
     * test call method is not exists
     * @expectedException Pix_Helper_Exception
     */
    public function testCallMethodIsNotExists()
    {
        $manager = new Pix_Helper_Manager();
        $manager->callHelper('notExists', array());
    }
}
