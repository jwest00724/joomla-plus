<?php

/**
* @version $Id: frontend.html.php 5134 2006-09-22 15:59:38Z friesengeist $
* @package Joomla
* @copyright Copyright (C) 2005 Open Source Matters, 2011 Aliro Software Ltd. All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
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
*/
class modules_html {

	/*
	* Output Handling for Custom modules
	*/
	function module( $module, $params, $Itemid, $style=0 ) {
		global $_MAMBOTS;
		
		// custom module params
		$moduleclass_sfx 	= $params->get( 'moduleclass_sfx' );
		$rssurl 			= $params->get( 'rssurl' );
		$firebots 			= $params->get( 'firebots', 0 );

		if ( $rssurl ) {
			// feed output
			modules_html::modoutput_feed( $module, $params, $moduleclass_sfx );
		}

		if ($module->content != '' && $firebots) {
		// mambot handling for custom modules
			// load content bots
			$_MAMBOTS->loadBotGroup( 'content' );
			
			$row		= $module;
			$row->text 	= $module->content;
			
			$results	= $_MAMBOTS->trigger( 'onPrepareContent', array( &$row, &$params, 0 ), true );
			
			$module->content = $row->text;
		}
		
		switch ( $style ) {
			case -3:
			// allows for rounded corners
				modules_html::modoutput_rounded( $module, $params, $Itemid, $moduleclass_sfx, 1 );
				break;

			case -2:
			// xhtml (divs and font headder tags)
				modules_html::modoutput_xhtml( $module, $params, $Itemid, $moduleclass_sfx, 1 );
				break;

			case -1:
			// show a naked module - no wrapper and no title
				modules_html::modoutput_naked( $module, $params, $Itemid, $moduleclass_sfx, 1 );
				break;

			default:
			// standard tabled output
				modules_html::modoutput_table( $module, $params, $Itemid, $moduleclass_sfx, 1 );
				break;
		}

		
	}

	/**
	* Output Handling for 3PD modules
	* @param object
	* @param object
	* @param int The menu item ID
	* @param int -1=show without wrapper and title, -2=xhtml style
	*/
	function module2( $module, $params, $Itemid, $style=0, $count=0 ) {
		global $mosConfig_lang, $mosConfig_absolute_path;

		$moduleclass_sfx = $params->get( 'moduleclass_sfx' );

		// check for custom language file
		$path = $mosConfig_absolute_path . '/modules/' . $module->module . $mosConfig_lang .'.php';
		if (file_exists( $path )) {
			include( $path );
		} else {
			$path = $mosConfig_absolute_path .'/modules/'. $module->module .'.eng.php';
			if (file_exists( $path )) {
				include( $path );
			}
		}

		$number = '';
		if ($count > 0) {
			$number = '<span>' . $count . '</span> ';
		}

		switch ( $style ) {
			case -3:
			// allows for rounded corners
				modules_html::modoutput_rounded( $module, $params, $Itemid, $moduleclass_sfx );
				break;

			case -2:
			// xhtml (divs and font headder tags)
				modules_html::modoutput_xhtml( $module, $params, $Itemid, $moduleclass_sfx );
				break;

			case -1:
			// show a naked module - no wrapper and no title
				modules_html::modoutput_naked( $module, $params, $Itemid, $moduleclass_sfx );
				break;

			default:
			// standard tabled output
				modules_html::modoutput_table( $module, $params, $Itemid, $moduleclass_sfx );
				break;
		}
	}

	// feed output
	function modoutput_feed( $module, $params, $moduleclass_sfx ) {
		global $mosConfig_absolute_path, $mosConfig_cachepath;

		// check if cache directory is writeable
		$cacheDir 		= $mosConfig_cachepath .'/';	
		if ( !is_writable( $cacheDir ) ) {	
			$module->content = 'Cache Directory Unwriteable';
			return;
		}
		
		$rssurl 			= $params->get( 'rssurl' );
		$rssitems 			= $params->get( 'rssitems', 5 );
		$rssdesc 			= $params->get( 'rssdesc', 1 );
		$rssimage 			= $params->get( 'rssimage', 1 );
		$rssitemdesc		= $params->get( 'rssitemdesc', 1 );
		$words 				= $params->def( 'word_count', 0 );
		$rsstitle			= $params->get( 'rsstitle', 1 );
		$rsscache			= $params->get( 'rsscache', 3600 );

		$contentBuffer	= '';
		
		$LitePath 		= $mosConfig_absolute_path .'/includes/Cache/Lite.php';
		require_once( $mosConfig_absolute_path .'/includes/domit/xml_domit_rss.php' );
		
		$rssDoc = new xml_domit_rss_document();
		$rssDoc->setRSSTimeout(2);
		$rssDoc->useCacheLite(true, $LitePath, $cacheDir, $rsscache);
		$success = $rssDoc->loadRSS( $rssurl );

		if ( $success )	{		
			$content_buffer = '';
			$totalChannels 	= $rssDoc->getChannelCount();
	
			for ( $i = 0; $i < $totalChannels; $i++ ) {
				$currChannel = $rssDoc->getChannel($i);
				$elements 	= $currChannel->getElementList();
				$iUrl		= 0;
				foreach ( $elements as $element ) {
					//image handling
					if ( $element == 'image' ) {
						$image = $currChannel->getElement( DOMIT_RSS_ELEMENT_IMAGE );
						$iUrl	= $image->getUrl();
						$iTitle	= $image->getTitle();
					}
				}
	
				// feed title
				$content_buffer = '<table cellpadding="0" cellspacing="0" class="moduletable'.$moduleclass_sfx.'">' . "\n";
							
				if ( $currChannel->getTitle() && $rsstitle ) {
					$feed_title 	= $currChannel->getTitle();
					$feed_title 	= mosCommonHTML::newsfeedEncoding( $rssDoc, $feed_title );

					$content_buffer .= "<tr>\n";
					$content_buffer .= "	<td>\n";
					$content_buffer .= "		<strong>\n";
					$content_buffer .= "		<a href=\"" . ampReplace( $currChannel->getLink() )  . "\" target=\"_blank\">\n";
					$content_buffer .= $feed_title . "</a>\n";
					$content_buffer .= "		</strong>\n";
					$content_buffer .= "	</td>\n";
					$content_buffer .= "</tr>\n";
	
				}
	
				// feed description
				if ( $rssdesc ) {
					$feed_descrip 	= $currChannel->getDescription();
					$feed_descrip 	= mosCommonHTML::newsfeedEncoding( $rssDoc, $feed_descrip );
					
					$content_buffer .= "<tr>\n";
					$content_buffer .= "	<td>\n";
					$content_buffer .= $feed_descrip;
					$content_buffer .= "	</td>\n";
					$content_buffer .= "</tr>\n";
				}
	
				// feed image
				if ( $rssimage && $iUrl ) {
					$content_buffer .= "<tr>\n";
					$content_buffer .= "	<td align=\"center\">\n";
					$content_buffer .= "		<image src=\"" . $iUrl . "\" alt=\"" . @$iTitle . "\"/>\n";
					$content_buffer .= "	</td>\n";
					$content_buffer .= "</tr>\n";
				}
	
				$actualItems 	= $currChannel->getItemCount();
				$setItems 		= $rssitems;
	
				if ($setItems > $actualItems) {
					$totalItems = $actualItems;
				} else {
					$totalItems = $setItems;
				}
	
	
				$content_buffer .= "<tr>\n";
				$content_buffer .= "	<td>\n";
				$content_buffer .= "		<ul class=\"newsfeed" . $moduleclass_sfx . "\">\n";
	
						for ($j = 0; $j < $totalItems; $j++) {
							$currItem = $currChannel->getItem($j);
							// item title
							
							$item_title = $currItem->getTitle();
							$item_title = mosCommonHTML::newsfeedEncoding( $rssDoc, $item_title );
	
							// START fix for RSS enclosure tag url not showing
							$content_buffer .= "<li class=\"newsfeed" . $moduleclass_sfx . "\">\n";
							$content_buffer .= "	<strong>\n";
							if ($currItem->getLink()) {
								$content_buffer .= "        <a href=\"" . ampReplace( $currItem->getLink() ) . "\" target=\"_blank\">\n";
								$content_buffer .= "      " . $item_title . "</a>\n";
							} else if ($currItem->getEnclosure()) {
								$enclosure = $currItem->getEnclosure();
								$eUrl	= $enclosure->getUrl();
								$content_buffer .= "        <a href=\"" . ampReplace( $eUrl ) . "\" target=\"_blank\">\n";
								$content_buffer .= "      " . $item_title . "</a>\n";
							}  else if (($currItem->getEnclosure()) && ($currItem->getLink())) {
								$enclosure = $currItem->getEnclosure();
								$eUrl	= $enclosure->getUrl();
								$content_buffer .= "        <a href=\"" . ampReplace( $currItem->getLink() ) . "\" target=\"_blank\">\n";
								$content_buffer .= "      " . $item_title . "</a><br/>\n";
								$content_buffer .= "        <a href=\"" . ampReplace( $eUrl ) . "\" target=\"_blank\"><u>Download</u></a>\n";
							}
							$content_buffer .= "	</strong>\n";
							// END fix for RSS enclosure tag url not showing
							
								// item description
								if ( $rssitemdesc ) {
									// item description
									$text = $currItem->getDescription();
									$text = mosCommonHTML::newsfeedEncoding( $rssDoc, $text );

									// word limit check
									if ( $words ) {
										$texts = explode( ' ', $text );
										$count = count( $texts );
										if ( $count > $words ) {
											$text = '';
											for( $i=0; $i < $words; $i++ ) {
												$text .= ' '. $texts[$i];
											}
											$text .= '...';
										}
									}
	
									$content_buffer .= "     <div>\n";
									$content_buffer .= "        " . $text;
									$content_buffer .= "		</div>\n";
	
								}
							$content_buffer .= "</li>\n";
						}
				$content_buffer .= "    </ul>\n";
				$content_buffer .= "	</td>\n";
				$content_buffer .= "</tr>\n";
				$content_buffer .= "</table>\n";
			}
			$module->content = $content_buffer;
		}
	}

	/*
	* standard tabled output
	*/
	function modoutput_table( $module, $params, $Itemid, $moduleclass_sfx, $type=0 ) {
		global $mosConfig_live_site, $mosConfig_sitename, $mosConfig_lang, $mosConfig_absolute_path;
		global $mainframe, $database, $my;
		?>
		<table cellpadding="0" cellspacing="0" class="moduletable<?php echo $moduleclass_sfx; ?>">
		<?php
		if ( $module->showtitle != 0 ) {
			?>
			<tr>
				<th valign="top">
					<?php echo htmlspecialchars( $module->title ); ?>
				</th>
			</tr>
			<?php
		}
		?>
		<tr>
			<td>
				<?php
				if ( $type ) {
					modules_html::CustomContent( $module, $params);
				} else {
					include( $mosConfig_absolute_path . '/modules/' . $module->module . '.php' );
					
					if (isset( $content)) {
						echo $content;
					}
				}
				?>
			</td>
		</tr>
		</table>
		<?php
	}

	/*
	* show a naked module - no wrapper and no title
	*/
	function modoutput_naked( $module, $params, $Itemid, $moduleclass_sfx, $type=0 ) {
		global $mosConfig_live_site, $mosConfig_sitename, $mosConfig_lang, $mosConfig_absolute_path;
		global $mainframe, $database, $my;

		if ( $type ) {
			modules_html::CustomContent( $module, $params);
		} else {
			include( $mosConfig_absolute_path . '/modules/' . $module->module . '.php' );
			
			if (isset( $content)) {
				echo $content;
			}
		}
	}

	/*
	* xhtml (divs and font headder tags)
	*/
	function modoutput_xhtml( $module, $params, $Itemid, $moduleclass_sfx, $type=0 ) {
		global $mosConfig_live_site, $mosConfig_sitename, $mosConfig_lang, $mosConfig_absolute_path;
		global $mainframe, $database, $my;
		?>
		<div class="moduletable<?php echo $moduleclass_sfx; ?>">
			<?php
			if ($module->showtitle != 0) {
				//echo $number;
				?>
				<h3>
					<?php echo htmlspecialchars( $module->title ); ?>
				</h3>
				<?php
			}

			if ( $type ) {
				modules_html::CustomContent( $module, $params);
			} else {
				include( $mosConfig_absolute_path . '/modules/' . $module->module . '.php' );
				
				if (isset( $content)) {
					echo $content;
				}
			}
			?>
		</div>
		<?php
	}

	/*
	* allows for rounded corners
	*/
	function modoutput_rounded( $module, $params, $Itemid, $moduleclass_sfx, $type=0 ) {
		global $mosConfig_live_site, $mosConfig_sitename, $mosConfig_lang, $mosConfig_absolute_path;
		global $mainframe, $database, $my;
		?>
		<div class="module<?php echo $moduleclass_sfx; ?>">
			<div>
				<div>
					<div>
						<?php
						if ($module->showtitle != 0) {
							echo "<h3>" . htmlspecialchars( $module->title ) . "</h3>";
						}

						if ( $type ) {
							modules_html::CustomContent( $module, $params);
						} else {
							include( $mosConfig_absolute_path . '/modules/' . $module->module . '.php' );
							
							if (isset( $content)) {
								echo $content;
							}
						}
						?>
					</div>
				</div>
			</div>
		</div>
		<?php
	}
	
	function CustomContent( $module, $params) {
		global $_MAMBOTS;
		
		$firebots 			= $params->get( 'firebots', 0 );
		
		if ( $firebots ) {
			$row		= $module;
			$row->text	= $module->content;		
		
			$results = $_MAMBOTS->trigger( 'onBeforeDisplayContent', array( $row, $params, 0 ) );
			echo trim( implode( "\n", $results ) );
			
			$module->content = $row->text;
		}
		
		// output custom module contents
		echo $module->content;
		
		if ( $firebots ) {
			$results = $_MAMBOTS->trigger( 'onAfterDisplayContent', array( $row, $params, 0 ) );
			echo trim( implode( "\n", $results ) );
			
			$module->content = $row->text;
		}
	}
}
