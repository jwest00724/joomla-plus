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
 * Classes to do with building objects linked to database rows
 *
 */

// Not currently used, but provides a way to create a data object when the class is a variable
// rather than a constant
class aliroDBRowFactory {

	static public function makeObject ($classname, $key=null) {
		if (is_subclass_of($classname, 'aliroDBGeneralRow')) {
			$object = new $classname;
			if (!empty($key)) $object->load($key);
			return $object;
		}
		else trigger_error(T_('Asked aliroDBRowFactory to create object not subclassed from aliroDBGeneralRow'));
	}
}

// This is the general database row handling class, extended by other classes below
abstract class aliroDBGeneralRow {
	public $_error = '';

	protected function T_($string) {
		return function_exists('T_') ? T_($string) : $string;
	}

	function getError() {
		return $this->_error;
	}

	public function check() {
		return true;
	}

	public function getDatabase () {
		return isset($this->db) ? $this->db : call_user_func(array($this->DBclass, 'getInstance'));
	}

	public function getNumRows( $cur=null ) {
		return $this->getDatabase()->getNumRows($cur);
	}

	public function getAffectedRows () {
		return $this->getDatabase()->getAffectedRows();
	}

	public function insert () {
		return $this->getDatabase()->insertObject($this->tableName, $this, $this->rowKey);
	}

	public function update ($updateNulls=true) {
		return $this->getDatabase()->updateObject($this->tableName, $this, $this->rowKey, $updateNulls);
	}

	public function load( $key=null ) {
		$k = $this->rowKey;
		if (null !== $key) $this->$k = $key;
		if (empty($this->$k)) return false;
		$this->getDatabase()->setQuery("SELECT * FROM $this->tableName WHERE $this->rowKey='{$this->$k}'" );
		return $this->getDatabase()->loadObject($this);
	}

	public function store( $updateNulls=false ) {
		$k = $this->rowKey;
		$ret = $this->$k ? $this->update($updateNulls) : $this->insert();
		if (!$ret) $this->_error = strtolower(get_class( $this ))."::store failed <br />" . $this->getDatabase()->getErrorMsg();
		return $ret;
	}

	public function storeNonAuto ($updateNulls=false, $ignore=false) {
		$this->getDatabase()->insertOrUpdateObject($this->tableName, $this, $this->rowKey, $updateNulls, $ignore);
	}

	public function insertNonAuto () {
		$this->getDatabase()->insertObjectSafely($this->tableName, $this);
	}

	public function bind( $objectorarray, $ignore='', $strip=true ) {
		$fields = $this->getDatabase()->getAllFieldNames ($this->tableName);
		foreach ($fields as $key=>$field) if (false !== strpos($ignore, $field)) unset($fields[$key]);
		return $this->bindDoWork ($objectorarray, $fields, $strip);
	}

	public function bindOnly ($objectorarray, $accept='', $strip=true) {
		$fields = $this->getDatabase()->getAllFieldNames ($this->tableName);
		foreach ($fields as $key=>$field) if (false === strpos($accept, $field)) unset($fields[$key]);
		return $this->bindDoWork ($objectorarray, $fields, $strip);
	}

	private function bindDoWork ($objectorarray, $fields, $strip) {
		if (is_array($objectorarray) OR is_object($objectorarray)) {
			foreach ($fields as $field) {
				$data = is_array($objectorarray) ? @$objectorarray[$field] : @$objectorarray->$field;
				if (is_string($data)) {
					$this->$field = $strip ? $this->stripMagicQuotes($data) : $data;
					if ('params' != $field AND (false !== strpos($this->$field, '&') OR false !== strpos($this->$field, '<'))) {
						$this->$field = $this->doPurify($this->$field);
					}
				}
			}
			return true;
		}
		$this->_error = strtolower(get_class($this)).$this->T_('::bind failed, parameter not an array');
		return false;
	}

	protected function doPurify ($field) {
return $field;
		return aliroRequest::getInstance()->doPurify($field);
	}

	private function stripMagicQuotes ($field) {
		return (get_magic_quotes_gpc() AND is_string($field)) ? stripslashes($field) : $field;
	}

	public function lacks( $property ) {
		if (in_array($property, $this->getDatabase()->getAllFieldNames($this->tableName))) return false;
		$this->_error = sprintf ($this->T_('WARNING: %s does not support %s.'), get_class($this), $property);
		return true;
	}

	public function move( $direction, $where='' ) {
		$compops = array (-1 => '<', 0 => '=', 1 => '>');
		$relation = $compops[($direction>0)-($direction<0)];
		$ordering = ($relation == '<' ? 'DESC' : 'ASC');
		$k = $this->rowKey;
		$o1 = $this->ordering;
		$k1 = $this->$k;
		$database = $this->getDatabase();
		$sql = "SELECT $k, ordering FROM $this->tableName WHERE ordering $relation $o1";
		$sql .= ($where ? "\n AND $where" : '').' ORDER BY ordering '.$ordering.' LIMIT 1';
		$database->setQuery( $sql );
		if ($database->loadObject($row)) {
			$o2 = $row->ordering;
			$k2 = $row->$k;
			$sql = "UPDATE $this->tableName SET ordering = (ordering=$o1)*$o2 + (ordering=$o2)*$o1 WHERE $k = $k1 OR $k = $k2";
			$database->doSQL($sql);
		}
	}

	// public function updateOrder( $where='', $cfid=null, $order=null ) {
	public function updateOrder ($where='', $sequence='', $orders=array()) {
		if ($this->lacks('ordering')) return false;
		$sql = "SELECT $this->rowKey, ordering FROM $this->tableName"
			.($where ? "\n WHERE $where" : '')
			."\n ORDER BY ordering"
			.($sequence ? ','.$sequence : '');
		$rows = $this->getDatabase()->doSQLget($sql, 'stdClass', $this->rowKey);
		$allrows = array();
		foreach ($rows as $key=>$row) $allrows[(isset($orders[$key]) ? $orders[$key] : $row->ordering)] = $key;
		ksort($allrows);
		$cases = '';
		$order = 10;
		foreach ($allrows as $ordering=>$id) {
			if ($order != $rows[$id]->ordering) $cases .= " WHEN $this->rowKey = $id THEN $order ";
			$order += 10;
		}
		if ($cases) $this->getDatabase()->doSQL("UPDATE $this->tableName SET ordering = CASE ".$cases.' ELSE ordering END');
		return true;
	}

	// Caller needs to find out the number of affected rows, not rely on true or false return
	public function delete( $key=null ) {
		$k = $this->rowKey;
		if ($key) $this->$k = intval( $key );
		$this->getDatabase()->doSQL( "DELETE FROM $this->tableName WHERE $this->rowKey = '{$this->$k}'" );
		return true;
	}

	public function checkout( $who=0, $key=null ) {
		if (!$who) $who = $this->getUser()->id;
		if ($this->lacks('checked_out')) return false;
		$k = $this->rowKey;
		if (null !== $key) $this->$k = $key;
		$time = date( "Y-m-d H:i:s" );
		$this->getDatabase()->doSQL( "UPDATE $this->tableName"
		. "\nSET checked_out='$who', checked_out_time='$time'"
		. "\nWHERE $k='".$this->$k."'"
		);
		return true;
	}

	protected function getUser () {
		return aliroUser::getInstance();
	}

	public function checkin( $key=null ) {
		if ($this->lacks('checked_out')) return false;
		$k = $this->rowKey;
		if (null !== $key) $this->$k = $key;
		$this->getDatabase()->doSQL( "UPDATE $this->tableName"
		. "\nSET checked_out='0', checked_out_time='0000-00-00 00:00:00'"
		. "\nWHERE $k='".$this->$k."'"
		);
		return true;
	}

	function isCheckedOut ($userid=0) {
		return ($this->checked_out AND $userid != $this->checked_out) ? true : false;
	}

}
