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

abstract class aliroDatabaseRow extends aliroDBGeneralRow {

	// ? protected function __get ?
	public function __get ($property) {
		$database = $this->getDatabase();
		if ('_db' == $property) return $database;
		$field = $database->getFieldInfo ($this->tableName, $property);
		if (!is_object($field)) trigger_error($this->T_('Database row attempt to obtain invalid property:').' '.$property);
		else if ('auto_increment' == $field->Extra) return 0;
		return $field ? $field->Default : null;
	}

	/* Provided in case child class does not implement it.  Can force any values */
	/* within some limited range.  In particular, can force bools to be 0 or 1 */
	function forceBools () {
		return;
	}

	/* Provided in case the child class does not provide a method for timeStampField */
	function timeStampField () {
		return '';
	}

	/* Default method for identifying fields not to be written to the DB */
	/* The child classes may override this and return more items in the array */
	function notSQL () {
		return array ($this->rowKey);
	}

	function assignRoles ($roles, $action, $access) {
		$authorisation = aliroAuthorisationAdmin::getInstance();
		$key = $this->rowKey;
		$authorisation->dropPermissions($action, $this->subjectName, $this->$key);
		if (in_array('Visitor', $roles)) return;
		$none = array_search('none', $roles);
		if (false !== $none) unset($roles[$none]);
		$database = $this->getDatabase();
		foreach ($roles as $role) {
			$role = $database->getEscaped($role);
			$authorisation->permit ($role, $access, $action, $this->subjectName, $this->$key);
		}
	}

}
