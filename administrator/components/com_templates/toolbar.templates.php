<?php
/**
* @version $Id: toolbar.templates.php 10002 2008-02-08 10:56:57Z willebil $
* @package Joomla
* @subpackage Templates
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

$client = strval( mosGetParam( $_REQUEST, 'client', '' ) );

switch ($task) {

	case 'view':
		TOOLBAR_templates::_VIEW();
		break;

	case 'edit_source':
		TOOLBAR_templates::_EDIT_SOURCE();
		break;

	case 'edit_css':
		TOOLBAR_templates::_EDIT_CSS();
		break;

	case 'assign':
		TOOLBAR_templates::_ASSIGN();
		break;

	case 'positions':
		TOOLBAR_templates::_POSITIONS();
		break;

	default:
		TOOLBAR_templates::_DEFAULT($client);
		break;
}
?>