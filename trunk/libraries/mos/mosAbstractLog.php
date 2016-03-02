<?php

/**
 * @package Joomla
 * @abstract
 */
class mosAbstractLog {
	/** @var array */
	var $_log	= null;

	/**
	 * Constructor
	 */
	function mosAbstractLog() {
		$this->__constructor();
	}

	/**
	 * Generic constructor
	 */
	function __constructor() {
		$this->_log = array();
	}

	/**
	 * @param string Log message
	 * @param boolean True to append to last message
	 */
	function log( $text, $append=false ) {
		$n = count( $this->_log );
		if ($append && $n > 0) {
			$this->_log[count( $this->_log )-1] .= $text;
		} else {
			$this->_log[] = $text;
		}
	}

	/**
	 * @param string The glue for each log item
	 * @return string Returns the log
	 */
	function getLog( $glue='<br/>', $truncate=9000, $htmlSafe=false ) {
		$logs = array();
		foreach ($this->_log as $log) {
			if ($htmlSafe) {
				$log = htmlspecialchars( $log );
			}
			$logs[] = substr( $log, 0, $truncate );
		}
		return  implode( $glue, $logs );
	}
}
