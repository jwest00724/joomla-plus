<?php

/**
* Module database table class
* @package Joomla
*/
class mosMenu extends mosDBTable {
	/** @var int Primary key */
	var $id					= null;
	/** @var string */
	var $menutype			= null;
	/** @var string */
	var $name				= null;
	/** @var string */
	var $link				= null;
	/** @var int */
	var $type				= null;
	/** @var int */
	var $published			= null;
	/** @var int */
	var $componentid		= null;
	/** @var int */
	var $parent				= null;
	/** @var int */
	var $sublevel			= null;
	/** @var int */
	var $ordering			= null;
	/** @var boolean */
	var $checked_out		= null;
	/** @var datetime */
	var $checked_out_time	= null;
	/** @var boolean */
	var $pollid				= null;
	/** @var string */
	var $browserNav			= null;
	/** @var int */
	var $access				= null;
	/** @var int */
	var $utaccess			= null;
	/** @var string */
	var $params				= null;

	/**
	* @param database A database connector object
	*/
	function mosMenu( $db ) {
		$this->mosDBTable( '#__menu', 'id', $db );
	}

	function check() {
		$this->id = (int) $this->id;
		$this->params = (string) trim( $this->params . ' ' );

		$ignoreList = array( 'link' );
		$this->filter( $ignoreList );

		return true;
	}
}
