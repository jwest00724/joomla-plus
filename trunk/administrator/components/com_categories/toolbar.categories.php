<?php
/**
* @version $Id: toolbar.categories.php 10002 2008-02-08 10:56:57Z willebil $
* @package Joomla
* @subpackage Categories
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

switch ($task){
	case 'new':
	case 'edit':
	case 'editA':
		TOOLBAR_categories::_EDIT();
		break;

	case 'moveselect':
		TOOLBAR_categories::_MOVE();
		break;

	case 'copyselect':
		TOOLBAR_categories::_COPY();
		break;

	default:
		TOOLBAR_categories::_DEFAULT();
		break;
}
?>