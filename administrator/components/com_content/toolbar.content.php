<?php
/**
* @version $Id: toolbar.content.php 10002 2008-02-08 10:56:57Z willebil $
* @package Joomla
* @subpackage Content
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
	case 'new_content_typed':
	case 'new_content_section':
	case 'edit':
	case 'editA':
	case 'edit_content_typed':
		TOOLBAR_content::_EDIT( );
		break;

	case 'showarchive':
		TOOLBAR_content::_ARCHIVE();
		break;

	case 'movesect':
		TOOLBAR_content::_MOVE();
		break;

	case 'copy':
		TOOLBAR_content::_COPY();
		break;

	default:
		TOOLBAR_content::_DEFAULT();
		break;
}
?>