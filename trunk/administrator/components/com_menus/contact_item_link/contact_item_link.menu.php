<?php
/**
* @version $Id: contact_item_link.menu.php 10002 2008-02-08 10:56:57Z willebil $
* @package Joomla
* @subpackage Menus
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

mosAdminMenus::menuItem( $type );

switch ( $task ) {
	case 'contact_item_link':
		// this is the new item, ie, the same name as the menu `type`
		contact_item_link_menu::edit( 0, $menutype, $option );
		break;

	case 'edit':
		contact_item_link_menu::edit( $cid[0], $menutype, $option );
		break;

	case 'save':
	case 'apply':
		saveMenu( $option, $task );
		break;
}
?>