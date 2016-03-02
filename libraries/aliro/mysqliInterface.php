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
 * mysqliInterface is the interface class that provides links to the more
 * advanced PHP mysqli interface to the MySQL database server.
 * 
 */

class mysqliInterface {
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
		$hostandport = explode(':', $host);
		$this->resource = isset($hostandport[1]) ? @mysqli_connect($hostandport[0], $user, $pass, $db, $hostandport[1]) : @mysqli_connect($host, $user, $pass, $db);
		return $this->resource;
	}

	public function connectError () {
		return mysqli_connect_error();
	}

	public function setCharset ($charset) {
		$this->resource->set_charset($charset);
	}

	public function query ($sql) {
		$this->cursor = mysqli_query($this->resource, $sql);
		return $this->cursor;
	}

	public function errno () {
		return mysqli_errno($this->resource);
	}
	
	public function error () {
		return mysqli_error($this->resource);
	}

	public function getEscaped($text) {
		return mysqli_real_escape_string($this->resource, (string) $text);
	}

	public function getNumRows ($cur=null) {
		return mysqli_num_rows( $cur ? $cur : $this->cursor );
	}

	public function getAffectedRows () {
		return mysqli_affected_rows($this->resource);
	}

	public function insertid() {
		return mysqli_insert_id($this->resource);
	}

	public function getVersion() {
		return mysqli_get_server_info($this->resource);
	}

	public function getFetchFunc() {
		return 'mysqli_fetch_';
	}

	public function freeResultSet ($cur=null) {
		mysqli_free_result($cur ? $cur : $this->cursor);
	}

	public function multiQuery ($sql) {
		return mysqli_multi_query($this->resource, $sql);
	}

	public function storeResult () {
		return mysqli_store_result($this->resource);
	}

	public function nextResult () {
		return mysqli_more_results($this->resource) ? mysqli_next_result($this->resource) : false;
	}

	public function setFieldValue ($value, $type) {
		if (isset(self::$nsdatatypes[$type])) return $this->setNumericFieldValue($value, $type);
		return "'".$this->getEscaped((string) $value)."'";
	}
	
	private function setNumericFieldValue ($value, $type) {
		return ('date' == $type OR 'datetime' == $type) ? "'".$this->getEscaped((string) $value)."'" : $value;
	}

}
