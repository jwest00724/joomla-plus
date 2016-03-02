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
 * aliroHTML is progressively taking over from mosHTML.  It is a singleton rather
 * than a set of static methods, for both style and efficiency reasons.  The
 * mosHTML interface still exists, but makes calls to aliroHTML.
 *
 */

class aliroHTML {
	private static $instance = null;
	private $toggleManyDone = false;

	public static function getInstance () {
	    return self::$instance instanceof self ? self::$instance : (self::$instance = new self());
	}

	public function makeOption ($value, $text='', $selected=false, $valuename='value', $textname='text') {
		$obj = new stdClass;
		$obj->$valuename = $value;
		$obj->$textname = trim($text) ? $text : $value;
		$obj->selected = $selected;
		return $obj;
	}

	// Takes an array of objects and uses it to create a select list
	public function selectList ($selections, $tag_name, $tag_attribs='', $key='value', $text='text', $selected=NULL ) {
		if (!is_array($selections)) return '';
		$key = empty($key) ? 'value' : $key;
		$text = empty($text) ? 'text' : $text;
		$selectproperties = array();
		if (is_array($selected)) foreach ($selected as $select) {
			if (is_object($select)) $selectproperties[] = $select->$key;
			else $selectproperties[] = $select;
		}
		else $selectproperties = array($selected);
		$selecthtml = '';
		foreach ($selections as $selection) {
			$select = (!empty($selection->selected) OR in_array($selection->$key, $selectproperties, true)) ? 'selected="selected"' : '';
			$selecthtml .= <<<AN_OPTION
			<option value="{$selection->$key}" $select>
				{$selection->$text}
			</option>
AN_OPTION;
		}
		return <<<THE_SELECT
		<select name="$tag_name" id="$tag_name" $tag_attribs>
			$selecthtml
		</select>
THE_SELECT;
	}
}