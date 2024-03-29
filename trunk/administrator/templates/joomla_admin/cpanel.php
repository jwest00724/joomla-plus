<?php
/**
* @version $Id: cpanel.php 9799 2008-01-02 16:30:01Z hackwar $
* @package Joomla
* @copyright Copyright (C) 2005 Open Source Matters, 2011 Aliro Software Ltd. All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
* J-One-Plus is free software. This version may have been modified pursuant
* to the GNU General Public License, and as distributed it includes or
* is derivative of works licensed under the GNU General Public License or
* other free or open source software licenses.
* See COPYRIGHT.php for copyright notices and details.
*/

/** ensure this file is being included by a parent file */
defined( '_VALID_MOS' ) or die( 'Restricted access' );

?>
<table class="adminform">
<tr>
	<td width="55%" valign="top">
	   <?php mosLoadAdminModules( 'icon', 0 ); ?>
	</td>
	<td width="45%" valign="top">
		<div style="width: 100%;">
			<form action="index2.php" method="post" name="adminForm">
			<?php mosLoadAdminModules( 'cpanel', 1 ); ?>
			<input type="hidden" name="<?php echo josSpoofValue(); ?>" value="1" />
		</form>
		</div>
	</td>
</tr>
</table>
