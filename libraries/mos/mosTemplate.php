<?php

/**
* Template Table Class
*
* Provides access to the jos_templates table
* @package Joomla
*/
class mosTemplate extends mosDBTable {
	/** @var int */
	var $id				= null;
	/** @var string */
	var $cur_template	= null;
	/** @var int */
	var $col_main		= null;

	/**
	* @param database A database connector object
	*/
	function mosTemplate( $database ) {
		$this->mosDBTable( '#__templates', 'id', $database );
	}
}
