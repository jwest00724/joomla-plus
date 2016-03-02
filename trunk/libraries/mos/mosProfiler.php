<?php

// no direct access
defined( '_VALID_MOS' ) or die( 'Restricted access' );

/**
 * Page generation time
 * @package Joomla
 */
class mosProfiler {
	/** @var int Start time stamp */
	var $start=0;
	/** @var string A prefix for mark messages */
	var $prefix='';
	/** @var array A buffer for data */
	var $buffer=array();

	/**
	 * Constructor
	 * @param string A prefix for mark messages
	 */
	function mosProfiler( $prefix='' ) {
		$this->start = $this->getmicrotime();
		$this->prefix = $prefix;
	}

	/**
	 * @return string A format message of the elapsed time
	 */
	function mark( $label ) {
		return sprintf ( "\n<div class=\"profiler\">$this->prefix %.3f $label</div>", $this->getmicrotime() - $this->start );
	}

	/**
	 * @return float The current time in milliseconds
	 */
	function getmicrotime(){
		list($usec, $sec) = explode(" ",microtime());
		return ((float)$usec + (float)$sec);
	}
	
	function getMemory () {
		return memory_get_usage();
	}
}
