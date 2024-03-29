<?php
/**
* @version $Id: banners.php 10002 2008-02-08 10:56:57Z willebil $
* @package Joomla
* @subpackage Banners
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

$bid = intval( mosGetParam( $_REQUEST, 'bid', 0 ) );

switch($task) {
	case 'click':
		clickbanner( $bid );
		break;

	default:
		viewbanner();
		break;
}

function viewbanner() {
	global $database, $mosConfig_live_site;

	$query = "SELECT COUNT(*) AS numrows"
	. "\n FROM #__banner"
	. "\n WHERE showBanner = 1"
	;
	$database->setQuery( $query );
	$numrows = $database->loadResult();
	if ($numrows === null) {
		echo $database->stderr( true );
		return;
	}

	if ($numrows > 1) {
		mt_srand( (double) microtime()*1000000 );
		$bannum = mt_rand( 0, --$numrows );
	} else {
		$bannum = 0;
	}

	$banner = null;
	$query = "SELECT *"
	. "\n FROM #__banner"
	. "\n WHERE showBanner = 1"
	;
	$database->setQuery( $query, $bannum, 1 );
	if ($database->loadObject( $banner )) {
		$query = "UPDATE #__banner"
		. "\n SET impmade = impmade + 1"
		. "\n WHERE bid = " . (int) $banner->bid
		;
		$database->setQuery( $query );
		if(!$database->query()) {
			echo $database->stderr( true );
			return;
		}
		$banner->impmade++;

		if ($numrows > 0) {
			// Check if this impression is the last one and print the banner
			if ($banner->imptotal == $banner->impmade) {
				$query = "INSERT INTO #__bannerfinish"
				. "\n ( cid, type, name, impressions, clicks, imageurl, datestart, dateend )"
				. "\n VALUES ( " . (int) $banner->cid . ", " . $database->Quote( $banner->type ) . ", "
				. $database->Quote( $banner->name ) . ", " . (int) $banner->impmade . ", " . (int) $banner->clicks
				. ", " . $database->Quote( $banner->imageurl ) . ", " . $database->Quote( $banner->date ) . ", 'now()' )"
				;
				$database->setQuery( $query );
				if(!$database->query()) {
					die($database->stderr(true));
				}

				$query = "DELETE FROM #__banner"
				. "\n WHERE bid = " . (int) $banner->bid
				;
				$database->setQuery($query);
				if(!$database->query()) {
					die($database->stderr(true));
				}
			}

			if (trim( $banner->custombannercode )) {
				echo $banner->custombannercode;
			} else if (preg_match( "/(\.bmp|\.gif|\.jpg|\.jpeg|\.png)$/i", $banner->imageurl )) {
				$imageurl = "$mosConfig_live_site/images/banners/$banner->imageurl";
				echo "<a href=\"".sefRelToAbs("index.php?option=com_banners&amp;task=click&amp;bid=$banner->bid")."\" target=\"_blank\"><img src=\"$imageurl\" border=\"0\" alt=\"Advertisement\" /></a>";

			} else if (preg_match("/\.swf$/i", $banner->imageurl)) {
				$imageurl = "$mosConfig_live_site/images/banners/".$banner->imageurl;
				echo "<object classid=\"clsid:D27CDB6E-AE6D-11cf-96B8-444553540000\" codebase=\"http://fpdownload.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=6,0,0,0\" border=\"5\">
						<param name=\"movie\" value=\"$imageurl\"><embed src=\"$imageurl\" loop=\"false\" pluginspage=\"http://www.macromedia.com/go/get/flashplayer\" type=\"application/x-shockwave-flash\"></embed></object>";
			}
		}
	} else {
		echo "&nbsp;";
	}
}

/**
/* Function to redirect the clicks to the correct url and add 1 click
*/
function clickbanner( $bid ) {
	global $database, $mainframe;

	require_once( $mainframe->getPath( 'class' ) );

	$row = new mosBanner($database);
	$row->load((int)$bid);
	$row->clicks();
	
	mosRedirect(preg_match('#^https?\:\/\/#i', $row->clickurl) ? $row->clickurl : 'http://'.$row->clickurl);
}
?>