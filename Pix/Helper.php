<?php

/**
 * Pix_Helper Helper abstract class
 *
 * @abstract
 * @package Helper
 * @copyright 2003-2012 PIXNET Digital Media Corporation
 * @license http://framework.pixnet.net/license BSD License
 */
abstract class Pix_Helper
{
    protected $_options = array();

    public function __construct($options = array())
    {
        $this->_options = is_array($options) ? $options : array();
    }

    protected function getOption($key)
    {
        if (!array_key_exists($key, $this->_options)) {
            return null;
        }

        return $this->_options[$key];
    }
}
