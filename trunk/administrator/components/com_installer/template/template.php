<?php
/**
* @version $Id: template.php 10002 2008-02-08 10:56:57Z willebil $
* @package Joomla
* @subpackage Installer
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

// ensure user has access to this function
if ( !$acl->acl_check( 'administration', 'install', 'users', $my->usertype, $element . 's', 'all' ) ) {
	mosRedirect( 'index2.php', _NOT_AUTH );
}

$client 	= mosGetParam( $_REQUEST, 'client', '' );
$userfile 	= mosGetParam( $_REQUEST, 'userfile', dirname( __FILE__ ) );
$userfile 	= mosPathName( $userfile );

HTML_installer::showInstallForm( 'Install new Template <small><small>[ ' . ($client == 'admin' ? 'Administrator' : 'Site') .' ]</small></small>',
	$option, 'template', $client, $userfile,
	'<a href="index2.php?option=com_templates&client='.$client.'">Back to Templates</a>'
);
?>
<table class="content">
<?php
writableCell( 'media' );
writableCell( 'administrator/templates' );
writableCell( 'templates' );
writableCell( 'images/stories' );
?>
</table>