<?php
/**
* @version $Id: users.class.php 10002 2008-02-08 10:56:57Z willebil $
* @package Joomla
* @subpackage Users
* @copyright Copyright (C) 2005 Open Source Matters, 2011 Aliro Software Ltd. All rights reserved.
* @license http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL, see LICENSE.php
* J-One-Plus is free software. This version may have been modified pursuant
* to the GNU General Public License, and as distributed it includes or
* is derivative of works licensed under the GNU General Public License or
* other free or open source software licenses.
* See COPYRIGHT.php for copyright notices and details.
*/

// no direct access
defined( '_VALID_MOS' ) or die( 'Restricted access' );

/**
 * @package Joomla
 */
class mosUserParameters extends mosParameters {
	/**
	* @param string The name of the form element
	* @param string The value of the element
	* @param object The xml element for the parameter
	* @param string The control name
	* @return string The html for the element
	*/
	function _form_editor_list( $name, $value, $node, $control_name ) {
		global $database;

		// compile list of the editors
		$query = "SELECT element AS value, name AS text"
		. "\n FROM #__mambots"
		. "\n WHERE folder = 'editors'"
		. "\n AND published = 1"
		. "\n ORDER BY ordering, name"
		;
		$database->setQuery( $query );
		$editors = $database->loadObjectList();

		array_unshift( $editors, mosHTML::makeOption( '', '- Select Editor -' ) );

		return mosHTML::selectList( $editors, ''. $control_name .'['. $name .']', 'class="inputbox"', 'value', 'text', $value );
	}
}
?>