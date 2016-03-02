<?php
/**
* @version $Id: install1.php 5975 2006-12-11 01:26:33Z robs $
* @package Joomla
* @copyright Copyright (C) 2005 Open Source Matters, 2011 Aliro Software Ltd. All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
* J-One-Plus is free software. This version may have been modified pursuant
* to the GNU General Public License, and as distributed it includes or
* is derivative of works licensed under the GNU General Public License or
* other free or open source software licenses.
* See COPYRIGHT.php for copyright notices and details.
*/

// Set flag that this is a parent file
define( "_VALID_MOS", 1 );

/** Include common.php */
require_once( 'common.php' );

$DBhostname = mosGetParam( $_POST, 'DBhostname', '' );
$DBuserName = mosGetParam( $_POST, 'DBuserName', '' );
$DBpassword = mosGetParam( $_POST, 'DBpassword', '' );
$DBname  	= mosGetParam( $_POST, 'DBname', '' );
$DBPrefix  	= mosGetParam( $_POST, 'DBPrefix', 'jos_' );
$DBDel  	= intval( mosGetParam( $_POST, 'DBDel', 0 ) );
$DBBackup  	= intval( mosGetParam( $_POST, 'DBBackup', 0 ) );
$DBSample  	= intval( mosGetParam( $_POST, 'DBSample', 1 ) );

echo "<?xml version=\"1.0\" encoding=\"iso-8859-1\"?".">";
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>J-One-Plus | Web Installer</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<link rel="shortcut icon" href="../images/favicon.ico" />
<link rel="stylesheet" href="install.css" type="text/css" />
<script  type="text/javascript">
<!--
function check() {
	// form validation check
	var formValid=false;
	var f = document.form;
	if ( f.DBhostname.value == '' ) {
		alert('Please enter a Host name');
		f.DBhostname.focus();
		formValid=false;
	} else if ( f.DBuserName.value == '' ) {
		alert('Please enter a Database User Name');
		f.DBuserName.focus();
		formValid=false;
	} else if ( f.DBname.value == '' ) {
		alert('Please enter a Name for your new Database');
		f.DBname.focus();
		formValid=false;
	} else if ( f.DBPrefix.value == '' ) {
		alert('You must enter a MySQL Table Prefix for J-One-Plus to operate correctly.');
		f.DBPrefix.focus();
		formValid=false;
	} else if ( f.DBPrefix.value == 'old_' ) {
		alert('You cannot use "old_" as the MySQL Table Prefix because J-One-Plus uses this prefix for backup tables.');
		f.DBPrefix.focus();
		formValid=false;
	} else if ( confirm('Are you sure these settings are correct? \nJ-One-Plus will now attempt to populate a Database with the settings you have supplied')) {
		formValid=true;
	}

	return formValid;
}
//-->
</script>
</head>
<body onload="document.form.DBhostname.focus();">
<div id="wrapper">
	<div id="header">
		<div id="joomla"><img src="header_install.png" alt="J-One-Plus Installation" /></div>
	</div>
</div>
<div id="ctr" align="center">
	<form action="install2.php" method="post" name="form" id="form" onsubmit="return check();">
	<div class="install">
		<div id="stepbar">
			<div class="step-off">
				pre-installation check
			</div>
			<div class="step-off">
				license
			</div>
			<div class="step-on">
				step 1
			</div>
			<div class="step-off">
				step 2
			</div>
			<div class="step-off">
				step 3
			</div>
			<div class="step-off">
				step 4
			</div>
		</div>
		<div id="right">
			<div class="far-right">
				<input class="button" type="submit" name="next" value="Next >>"/>
  			</div>
	  		<div id="step">
	  			step 1
	  		</div>
  			<div class="clr"></div>
  			<h1>MySQL database configuration:</h1>
	  		<div class="install-text">
  				<p>Setting up J-One-Plus to run on your server involves 4 simple steps...</p>
  				<p>Please enter the hostname of the server J-One-Plus is to be installed on.</p>
				<p>Enter the MySQL username, password and database name you wish to use with J-One-Plus</p>
				<p>Enter a table name prefix to be used by this J-One-Plus install and select what
					to do with existing tables from former installations.</p>
				<p>Install the sample data unless you are an experienced J-One-Plus User wanting to start with a completely empty site.</p>
  			</div>
			<div class="install-form">
  				<div class="form-block">
  		 			<table class="content2">
  		  			<tr>
  						<td></td>
  						<td></td>
  						<td></td>
  					</tr>
  		  			<tr>
  						<td colspan="2">
  							Host Name
  							<br/>
  							<input class="inputbox" type="text" name="DBhostname" value="<?php echo "$DBhostname"; ?>" />
  						</td>
			  			<td>
			  				<em>This is usually 'localhost'</em>
			  			</td>
  					</tr>
					<tr>
			  			<td colspan="2">
			  				MySQL User Name
			  				<br/>
			  				<input class="inputbox" type="text" name="DBuserName" value="<?php echo "$DBuserName"; ?>" />
			  			</td>
			  			<td>
			  				<em>Either something as 'root' or a username given by the hoster</em>
			  			</td>
  					</tr>
			  		<tr>
			  			<td colspan="2">
			  				MySQL Password
			  				<br/>
			  				<input class="inputbox" type="text" name="DBpassword" value="<?php echo "$DBpassword"; ?>" />
			  			</td>
			  			<td>
			  				<em>For site security using a password for the mysql account is mandatory</em>
			  			</td>
					</tr>
  		  			<tr>
  						<td colspan="2">
  							MySQL Database Name
  							<br/>
  							<input class="inputbox" type="text" name="DBname" value="<?php echo "$DBname"; ?>" />
  						</td>
			  			<td>
			  				<em>Some hosts allow only a certain DB name per site. Use table prefix in this case for distinct J-One-Plus sites.</em>
			  			</td>
  					</tr>
  		  			<tr>
  						<td colspan="2">
  							MySQL Table Prefix
  							<br/>
  							<input class="inputbox" type="text" name="DBPrefix" value="<?php echo "$DBPrefix"; ?>" />
  						</td>
			  			<td>
			  			<!--
			  			<em>Don't use 'old_' since this is used for backup tables</em>
			  			-->
			  			</td>
  					</tr>
  		  			<tr>
			  			<td>
			  				<input type="checkbox" name="DBDel" id="DBDel" value="1" <?php if ($DBDel) echo 'checked="checked"'; ?> />
			  			</td>
						<td>
							<label for="DBDel">Drop Existing Tables</label>
						</td>
  						<td>
  						</td>
			  		</tr>
  		  			<tr>
			  			<td>
			  				<input type="checkbox" name="DBBackup" id="DBBackup" value="1" <?php if ($DBBackup) echo 'checked="checked"'; ?> />
			  			</td>
						<td>
							<label for="DBBackup">Backup Old Tables</label>
						</td>
  						<td>
  							<em>Any existing backup tables from former J-One-Plus installations will be replaced</em>
  						</td>
			  		</tr>
  		  			<tr>
			  			<td>
			  				<input type="checkbox" name="DBSample" id="DBSample" value="1" <?php if ($DBSample) echo 'checked="checked"'; ?> />
			  			</td>
						<td>
							<label for="DBSample">Install Sample Data</label>
						</td>
			  			<td>
			  				<em>Don't uncheck this option unless you are experienced in using J-One-Plus</em>
			  			</td>
			  		</tr>
		  		 	</table>
  				</div>
			</div>
		</div>
		<div class="clr"></div>
	</div>
	</form>
</div>
<div class="clr"></div>
<div class="ctr">
	<a href="http://www.joomlaguru.net" target="_blank">J-One-Plus</a> is Free Software released under the GNU/GPL License.
</div>
</body>
</html>