<?php

trigger_error("Pix_Table_Plugin is deprecated, use Pix_Helper instead", E_USER_DEPRECATED);

/**
 * Pix_Table_Plugin_EAV 
 * 
 * @uses Pix
 * @uses _Table_Plugin
 * @options cache => Pix_Cache object(default null), relation => relation name (default eavs), cache_expire => cache expire time(default 3600)
 * @package Table
 * @deprecated Pix_Table_Plugin is deprecated, use Pix_Helper instead
 * @copyright 2003-2012 PIXNET Digital Media Corporation
 * @license http://framework.pixnet.net/license BSD License
 */
class Pix_Table_Plugin_EAV extends Pix_Table_Plugin
{
    protected function _getRelation()
    {
	return ($relation = $this->getOption('relation')) ? $relation : 'eavs';
    }

    protected function _getCacheExpire()
    {
	return ($expire = $this->getOption('cache_expire')) ? $expire : 3600;
    }

    protected function _getKeyColumn()
    {
	return ($column = $this->getOption('key_column')) ? $column : 'key';
    }

    protected function _getValueColumn()
    {
	return ($column = $this->getOption('value_column')) ? $column : 'value';
    }

    protected function _getCacheKey($row, $key)
    {
	return 'Pix_Table_Plugin_EAV::' . $row->getTableClass() . '::' . implode('::', $row->getPrimaryValues()) . '::' . crc32($key);
    }

    public function incEAV($row, $key, $delta = 1, $max = null)
    {
	// TODO : 如果 backend support a = a + 1 就應該改用 a = a + 1 用法
	if (is_null($max)) {
	    $this->setEAV($row, $key, intval($this->getEAV($row, $key)) + $delta);
	} else {
	    $this->setEAV($row, $key, min(intval($this->getEAV($row, $key)) + $delta, $max));
	}
    }

    public function decEAV($row, $key, $delta = 1, $min = null)
    {
	// TODO : 如果 backend support a = a + 1 就應該改用 a = a + 1 用法
	if (is_null($min)) {
	    $this->setEAV($row, $key, intval($this->getEAV($row, $key)) - $delta);
	} else {
	    $this->setEAV($row, $key, max(intval($this->getEAV($row, $key)) - $delta, $min));
	}
    }

    public function getEAV($row, $key)
    {
	if ($cache = $this->getOption('cache')) {
	    $cache_key = $this->_getCacheKey($row, $key);
	}

	if ($cache and false !== ($data = $cache->get($cache_key))) {
	    return $data;
	}

	$key_column = $this->_getKeyColumn();
	$value_column = $this->_getValueColumn();
	$data = ($eav = $row->{$this->_getRelation()}->search(array($key_column => $key))->first()) ? $eav->{$value_column} : null;

	if ($cache) {
	    $cache->set($cache_key, $data, $this->_getCacheExpire());
	}
	return $data;
    }

    public function setEAV($row, $key, $value)
    {
	if ($cache = $this->getOption('cache')) {
	    $cache_key = $this->_getCacheKey($row, $key);
	    if (is_null($value)) {
		$cache->delete($cache_key);
	    } else {
		$cache->set($cache_key, $value, $this->_getCacheExpire());
	    }
	}

	$key_column = $this->_getKeyColumn();
	$value_column = $this->_getValueColumn();
	if (is_null($value)) {
	    $row->{$this->_getRelation()}->search(array($key_column => $key))->delete();
	    return true;
	}

	try {
	    $row->{$this->_getRelation()}->insert(array($key_column => $key, $value_column => $value));
	} catch (Pix_Table_DuplicateException $e) {
	    $row->{$this->_getRelation()}->search(array($key_column => $key))->update(array($value_column => $value));
	}
	return true;
    }
}
