<?php
/**
* @version $Id: toolbar.poll.php 10002 2008-02-08 10:56:57Z willebil $
* @package Joomla
* @subpackage Polls
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

require_once( $mainframe->getPath( 'toolbar_html' ) );

switch ($task) {
	case 'new':
		TOOLBAR_poll::_NEW();
		break;

	case 'edit':
		$cid = josGetArrayInts( 'cid' );

		$query = "SELECT published"
		. "\n FROM #__polls"
		. "\n WHERE id = " . (int) $cid[0]
		;
		$database->setQuery( $query );
		$published = $database->loadResult();

		$cur_template = $mainframe->getTemplate();

		TOOLBAR_poll::_EDIT( $cid[0], $cur_template );
		break;

	case 'editA':
		$id = intval( mosGetParam( $_REQUEST, 'id', 0 ) );

		$query = "SELECT published"
		. "\n FROM #__polls"
		. "\n WHERE id = " . (int) $id
		;
		$database->setQuery( $query );
		$published = $database->loadResult();

		$cur_template = $mainframe->getTemplate();

		TOOLBAR_poll::_EDIT( $id, $cur_template );
		break;

	default:
		TOOLBAR_poll::_DEFAULT();
		break;
}
?>