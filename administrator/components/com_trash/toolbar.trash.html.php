<?php
/**
* @version $Id: toolbar.trash.html.php 10002 2008-02-08 10:56:57Z willebil $
* @package Joomla
* @subpackage Trash
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
* @subpackage Trash
*/
class TOOLBAR_Trash {
	function _DEFAULT() {
		mosMenuBar::startTable();
		mosMenuBar::custom('restoreconfirm','restore.png','restore_f2.png','Restore', true);
		mosMenuBar::spacer();
		mosMenuBar::custom('deleteconfirm','delete.png','delete_f2.png','Delete', true);
		mosMenuBar::spacer();
		mosMenuBar::help( 'screen.trashmanager' );
		mosMenuBar::endTable();
	}

	function _DELETE() {
		mosMenuBar::startTable();
		mosMenuBar::cancel();
		mosMenuBar::endTable();
	}

	function _SETTINGS() {
		mosMenuBar::startTable();
		mosMenuBar::back();
		mosMenuBar::spacer();
		mosMenuBar::save();
		mosMenuBar::endTable();
	}

}
?>