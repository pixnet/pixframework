<?php

/**
 * Pix_Table_Plugin 
 * 
 * @package Table
 * @copyright 2003-2012 PIXNET Digital Media Corporation
 * @license http://framework.pixnet.net/license BSD License
 */
class Pix_Table_Plugin
{
    protected $_options = array();

    public function __construct($options = array())
    {
	$this->_options = is_array($options) ? $options : array();
    }

    protected function getOption($key)
    {
	return $this->_options[$key];
    }

    public function init($row)
    {
    }

    public function call($method, $row, $args)
    {
	array_unshift($args, $row);

	$this->init($row);

	return call_user_func_array(array($this, $method), $args);
    }
}
