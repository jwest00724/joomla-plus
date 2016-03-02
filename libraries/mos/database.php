<?php

// Override some methods for compatibility

class database extends aliroExtendedDatabase {
	protected $_debug = 0;
	protected $_nullDate		= '0000-00-00 00:00:00';

	public function setQuery( $sql, $offset = 0, $limit = 0, $prefix='#__' ) {
		if ($limit) $sql .= "\nLIMIT ".($offset ? "$offset, $limit" : $limit);
		parent::setQuery($sql, false, $prefix);
	}

	public function getNullDate () {
		return $this->_nullDate;
	}

	public function debug( $level ) {
		$this->_debug = intval( $level );
	}

}
