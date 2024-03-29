<?php
/**
* @version $Id: toolbar.messages.html.php 10002 2008-02-08 10:56:57Z willebil $
* @package Joomla
* @subpackage Messages
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
* @subpackage Messages
*/
class TOOLBAR_messages {
	function _VIEW() {
		mosMenuBar::startTable();
		mosMenuBar::customX('reply', 'restore.png', 'restore_f2.png', 'Reply', false );
		mosMenuBar::spacer();
		mosMenuBar::deleteList();
		mosMenuBar::spacer();
		mosMenuBar::cancel();
		mosMenuBar::endTable();
	}

	function _EDIT() {
		mosMenuBar::startTable();
		mosMenuBar::save( 'save', 'Send' );
		mosMenuBar::spacer();
		mosMenuBar::cancel();
		mosMenuBar::spacer();
		mosMenuBar::help( 'screen.messages.edit' );
		mosMenuBar::endTable();
	}

	function _CONFIG() {
		mosMenuBar::startTable();
		mosMenuBar::save( 'saveconfig' );
		mosMenuBar::spacer();
		mosMenuBar::cancel( 'cancelconfig' );
		mosMenuBar::spacer();
		mosMenuBar::help( 'screen.messages.conf' );
		mosMenuBar::endTable();
	}

	function _DEFAULT() {
		mosMenuBar::startTable();
		mosMenuBar::deleteList();
		mosMenuBar::spacer();
		mosMenuBar::addNewX();
		mosMenuBar::spacer();
		mosMenuBar::help( 'screen.messages.inbox' );
		mosMenuBar::endTable();
	}
}
?>