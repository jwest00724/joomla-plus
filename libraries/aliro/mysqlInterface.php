<?php

/*******************************************************************************
 * Aliro - the modern, accessible content management system
 *
 * This code is copyright (c) Aliro Software Ltd - please see the notice in the
 * index.php file for full details or visit http://aliro.org/copyright
 *
 * Some parts of Aliro are developed from other open source code, and for more
 * information on this, please see the index.php file or visit
 * http://aliro.org/credits
 *
 * Author: Martin Brampton
 * counterpoint@aliro.org
 *
 * mysqlInterface is the database interface class that provides links with the
 * basic PHP mysql interface.
 * 
 */

class mysqlInterface {
	// All non-string data types for MySQL
	private static $nsdatatypes = array(
	'tinyint' => '0', 
	'smallint' => '0',
	'mediumint' => '0',
	'int' => '0',
	'bigint' => '0',
	'float' => '0',
	'double' => '0',
	'decimal' => '0',
	'date' => "'0000-00-00'",
	'datetime' => "'0000-00-00 00:00:00'",
	'binary' => '?',
	'varbinary' => '?'
	);
	
	private $resource = null;
	private $queries = array();
	private $qcursor = 0;
	private $cursor = null;
	private $dbname = '';

	public function __construct ($dbname) {
		$this->dbname = $dbname;
	}

	public function defaultDate () {
		return '0000-00-00 00:00:00';
	}
	
	public function dateNow () {
		return date('Y-m-d H:i:s');
	}

	public function connect ($host, $user, $pass, $db) {
		if ($this->resource = @mysql_connect( $host, $user, $pass, true ) AND mysql_select_db($db)) return $this->resource;
		return null;
	}

	public function connectError () {
		return function_exists('T_') ? T_('Connection error') : 'Connection error';
	}

	public function setCharset ($charset) {
		mysql_query ("SET CHARSET '$charset'", $this->resource);
	}
	
	public function query ($sql) {
		$this->cursor = mysql_query ($sql, $this->resource);
		return $this->cursor;
	}

	public function errno () {
		return mysql_errno($this->resource);
	}
	
	public function error () {
		return mysql_error($this->resource);
	}

	public function getEscaped($text) {
		return mysql_real_escape_string ((string) $text);
	}

	public function getNumRows ($cur=null) {
		return mysql_num_rows( $cur ? $cur : $this->cursor );
	}

	public function getAffectedRows () {
		return mysql_affected_rows($this->resource);
	}

	public function insertid() {
		return mysql_insert_id($this->resource);
	}

	public function getVersion() {
		return mysql_get_server_info();
	}

	public function getFetchFunc() {
		return 'mysql_fetch_';
	}

	public function freeResultSet ($cur=null) {
		mysql_free_result($cur ? $cur : $this->cursor);
	}

	public function multiQuery ($sql) {
		$this->queries = explode (';', $sql);
		$this->qcursor = 0;
		return count($this->queries);
	}

	public function storeResult () {
		if (!empty($this->queries[$this->qcursor])) return $this->query ($this->queries[$this->qcursor]);
		return null;
	}

	public function nextResult () {
		$this->qcursor++;
		return isset($this->queries[$this->qcursor]);
	}

	public function setFieldValue ($value, $type) {
		if (isset(self::$nsdatatypes[$type])) return $this->setNumericFieldValue($value, $type);
		return "'".$this->getEscaped((string) $value)."'";
	}
	
	private function setNumericFieldValue ($value, $type) {
		return ('date' == $type OR 'datetime' == $type) ? "'".$this->getEscaped((string) $value)."'" : $value;
	}

}