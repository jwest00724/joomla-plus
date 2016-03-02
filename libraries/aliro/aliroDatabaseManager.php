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
 * aliroDatabaseManager provides methods for updating the structure of
 * database tables, or storing the current structure.  Information about
 * tables is stored as JSON.
 *
 */

if (!defined('JSON_TAB')) define ('JSON_TAB', '  ');

class aliroDatabaseManager {

	public function getTablesAsJSON ($tablenames, $prettify=false) {
		$database = aliroDatabase::getInstance();
		foreach ((array) $tablenames as $tablename) {
			$dbstructures[$tablename] = $database->doSQLget("DESCRIBE `$tablename`", 'stdClass', 'Field');
		}
		$json = json_encode($dbstructures);
		return $prettify ? $this->prettify($json) : $json;
	}

	public function updateTables ($tablejson, $database='aliroDatabase') {
		$specarray = json_decode($tablejson, true);
		$database = call_user_func(array($database, 'getInstance'));
		if ($specarray) foreach ($specarray as $dbname=>$shouldbe) {
			$asis = $database->doSQLget("DESCRIBE `{$dbname}`", 'stdClass', 'Field');
			$asis = json_decode(json_encode($asis), true);
			$asisnames = array_keys($asis);
			$shouldbenames = array_keys($shouldbe);
			foreach (array_diff($asisnames, $shouldbenames) as $fieldname) {
				echo '<br />Remove field '.$dbname.'/'.$fieldname;
				$database->dropFieldIfPresent ($dbname, $fieldname);
			}
			foreach (array_diff($shouldbenames, $asisnames) as $fieldname) {
				$fieldspec = $this->makeFieldSpec($shouldbe[$fieldname], $shouldbenames);
                                echo '<br />Add field '.$dbname.'/'.$fieldname.'/'.$fieldspec;
				// Need to specify where the new field is to go
				$database->addFieldIfMissing ($dbname, $fieldname, $fieldspec);
			}
			foreach (array_intersect($asisnames, $shouldbenames) as $fieldname) {
				$asisdata = $asis[$fieldname];
				$shouldbedata = $shouldbe[$fieldname];
				$fieldspec = $this->makeFieldSpec($shouldbedata);
				if ($this->fieldsDiffer($asisdata, $shouldbedata)) {
					echo '<br />Update field '.$dbname.'/'.$fieldname.'/'.$fieldspec;
					$database->alterField($dbname, $fieldname, $fieldspec);
				}
			}
		}
	}

	private function makeFieldSpec ($fielddata, $fields=null) {
		$fieldspec = $fielddata['Type'];
		if ('NO' == $fielddata['Null']) $fieldspec .= ' NOT NULL';
		if ($fielddata['Extra']) $fieldspec .= ' '.$fielddata['Extra'];
		$fieldspec .= ('YES' == $fielddata['Null'] AND 'null' == $fielddata['Default']) ? ' default NULL' : (is_null($fielddata['Default']) OR 'null' == $fielddata['Default']) ? '' : " default '{$fielddata['Default']}'";
		if ($fields) {
			$sub = array_search($fielddata['Field'], $fields);
			if ($sub) {
				$previous = $fields[$sub-1];
				$fieldspec .= " AFTER `$previous`";
			}
			else $fieldspec .= ' FIRST';
		}
		return $fieldspec;
	}

	private function fieldsDiffer ($a, $b) {
		if ($a['Type'] != $b['Type']) return true;
			if ($a['Null'] != $b['Null']) return true;
			if ($a['Default'] !== $b['Default']) return true;
			if ($a['Extra'] != $b['Extra']) return true;
		return false;
	}


	public function prettify ($json) {
		//$json_obj = json_decode($json);
		//if(false == $json_obj) return false;

		//$json = json_encode($json_obj);

		for ($new_json = '', $in_string = false, $indent_level = 0, $c = 0; $c < strlen($json); $c++) {
			$char = $json[$c];
			switch($char) {
				case '{':
				case '[':
					$new_json .=  $in_string ? $char : $char . "\n" . str_repeat(JSON_TAB, ++$indent_level);
					break;
				case '}':
				case ']':
					$new_json .= $in_string ? $char : "\n" . str_repeat(JSON_TAB, --$indent_level) . $char;
					break;
				case ',':
					$new_json .= $in_string ? $char : ",\n" . str_repeat(JSON_TAB, $indent_level);
					break;
				case ':':
					$new_json .= $in_string ? $char : ": ";
					break;
				case '"':
					if ($c > 0 AND '\\' != $json[$c-1]) $in_string = !$in_string;
					//$new_json .= $char;
				default:
					$new_json .= $char;
				break;
			}
		}
		return $new_json;
	}
}