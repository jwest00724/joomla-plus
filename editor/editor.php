<?php
/**
* @version $Id: editor.php 2446 2006-02-17 18:59:08Z stingrey $
* @package Joomla
* @copyright Copyright (C) 2005 Open Source Matters, 2011 Aliro Software Ltd. All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
* J-One-Plus is free software. This version may have been modified pursuant
* to the GNU General Public License, and as distributed it includes or
* is derivative of works licensed under the GNU General Public License or
* other free or open source software licenses.
* See COPYRIGHT.php for copyright notices and details.
*/

// no direct access
defined( '_VALID_MOS' ) or die( 'Restricted access' );

if (!defined( '_JOS_EDITOR_INCLUDED' )) {
	global $mosConfig_editor;
	global $my;

	if ($mosConfig_editor == '') {
		$mosConfig_editor = 'none';
	}

	// Per User Editor selection
	$params = new mosParameters( $my->params );
	$editor = $params->get( 'editor', '' );
	if (!$editor) {
		$editor = $mosConfig_editor;
	}

	$_MAMBOTS->loadBot( 'editors', $editor, 1 );

	function initEditor() {
		global $mainframe, $_MAMBOTS;

		if ($mainframe->get( 'loadEditor' )) {
			$results = $_MAMBOTS->trigger( 'onInitEditor' );
			foreach ($results as $result) {
				if (trim($result)) {
				   echo $result;
				}
			}
		}
	}
	function getEditorContents( $editorArea, $hiddenField ) {
		global $mainframe, $_MAMBOTS;

		$mainframe->set( 'loadEditor', true );

		$results = $_MAMBOTS->trigger( 'onGetEditorContents', array( $editorArea, $hiddenField ) );
		foreach ($results as $result) {
			if (trim($result)) {
				echo $result;
			}
		}
	}
	// just present a textarea
	function editorArea( $name, $content, $hiddenField, $width, $height, $col, $row ) {
		global $mainframe, $_MAMBOTS, $my;

		// ensure editor-xtd bots are loaded only once
		if (!defined( '_JOS_EDITORXTD_INCLUDED' )) {
			define( '_JOS_EDITORXTD_INCLUDED', 1 );
			$_MAMBOTS->loadBotGroup( 'editors-xtd' );
		}
		
		$mainframe->set( 'loadEditor', true );

		$results = $_MAMBOTS->trigger( 'onEditorArea', array( $name, $content, $hiddenField, $width, $height, $col, $row ) );
		foreach ($results as $result) {
			if (trim($result)) {
				echo $result;
			}
		}
	}
	define( '_JOS_EDITOR_INCLUDED', 1 );
}
?>