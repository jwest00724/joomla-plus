<?php

class mosCommonHTML {

	function ContentLegend( ) {
		?>
		<table cellspacing="0" cellpadding="4" border="0" align="center">
		<tr align="center">
			<td>
			<img src="images/publish_y.png" width="12" height="12" border="0" alt="Pending" />
			</td>
			<td>
			Published, but is <u>Pending</u> |
			</td>
			<td>
			<img src="images/publish_g.png" width="12" height="12" border="0" alt="Visible" />
			</td>
			<td>
			Published and is <u>Current</u> |
			</td>
			<td>
			<img src="images/publish_r.png" width="12" height="12" border="0" alt="Finished" />
			</td>
			<td>
			Published, but has <u>Expired</u> |
			</td>
			<td>
			<img src="images/publish_x.png" width="12" height="12" border="0" alt="Finished" />
			</td>
			<td>
			Not Published
			</td>
		</tr>
		<tr>
			<td colspan="8" align="center">
			Click on icon to toggle state.
			</td>
		</tr>
		</table>
		<?php
	}

	function menuLinksContent( $menus ) {
		?>
		<script language="javascript" type="text/javascript">
		function go2( pressbutton, menu, id ) {
			var form = document.adminForm;

			// assemble the images back into one field
			var temp = new Array;
			for (var i=0, n=form.imagelist.options.length; i < n; i++) {
				temp[i] = form.imagelist.options[i].value;
			}
			form.images.value = temp.join( '\n' );

			if (pressbutton == 'go2menu') {
				form.menu.value = menu;
				submitform( pressbutton );
				return;
			}

			if (pressbutton == 'go2menuitem') {
				form.menu.value 	= menu;
				form.menuid.value 	= id;
				submitform( pressbutton );
				return;
			}
		}
		</script>
		<?php
		foreach( $menus as $menu ) {
			?>
			<tr>
				<td colspan="2">
				<hr />
				</td>
			</tr>
			<tr>
				<td width="90px" valign="top">
				Menu
				</td>
				<td>
				<a href="javascript:go2( 'go2menu', '<?php echo $menu->menutype; ?>' );" title="Go to Menu">
				<?php echo $menu->menutype; ?>
				</a>
				</td>
			</tr>
			<tr>
				<td width="90px" valign="top">
				Link Name
				</td>
				<td>
				<strong>
				<a href="javascript:go2( 'go2menuitem', '<?php echo $menu->menutype; ?>', '<?php echo $menu->id; ?>' );" title="Go to Menu Item">
				<?php echo $menu->name; ?>
				</a>
				</strong>
				</td>
			</tr>
			<tr>
				<td width="90px" valign="top">
				State
				</td>
				<td>
				<?php
				switch ( $menu->published ) {
					case -2:
						echo '<font color="red">Trashed</font>';
						break;
					case 0:
						echo 'UnPublished';
						break;
					case 1:
					default:
						echo '<font color="green">Published</font>';
						break;
				}
				?>
				</td>
			</tr>
			<?php
		}
		?>
		<input type="hidden" name="menu" value="" />
		<input type="hidden" name="menuid" value="" />
		<?php
	}

	function menuLinksSecCat( $menus ) {
		?>
		<script language="javascript" type="text/javascript">
		function go2( pressbutton, menu, id ) {
			var form = document.adminForm;

			if (pressbutton == 'go2menu') {
				form.menu.value = menu;
				submitform( pressbutton );
				return;
			}

			if (pressbutton == 'go2menuitem') {
				form.menu.value 	= menu;
				form.menuid.value 	= id;
				submitform( pressbutton );
				return;
			}
		}
		</script>
		<?php
		foreach( $menus as $menu ) {
			?>
			<tr>
				<td colspan="2">
				<hr/>
				</td>
			</tr>
			<tr>
				<td width="90px" valign="top">
				Menu
				</td>
				<td>
				<a href="javascript:go2( 'go2menu', '<?php echo $menu->menutype; ?>' );" title="Go to Menu">
				<?php echo $menu->menutype; ?>
				</a>
				</td>
			</tr>
			<tr>
				<td width="90px" valign="top">
				Type
				</td>
				<td>
				<?php echo $menu->type; ?>
				</td>
			</tr>
			<tr>
				<td width="90px" valign="top">
				Item Name
				</td>
				<td>
				<strong>
				<a href="javascript:go2( 'go2menuitem', '<?php echo $menu->menutype; ?>', '<?php echo $menu->id; ?>' );" title="Go to Menu Item">
				<?php echo $menu->name; ?>
				</a>
				</strong>
				</td>
			</tr>
			<tr>
				<td width="90px" valign="top">
				State
				</td>
				<td>
				<?php
				switch ( $menu->published ) {
					case -2:
						echo '<font color="red">Trashed</font>';
						break;
					case 0:
						echo 'UnPublished';
						break;
					case 1:
					default:
						echo '<font color="green">Published</font>';
						break;
				}
				?>
				</td>
			</tr>
			<?php
		}
		?>
		<input type="hidden" name="menu" value="" />
		<input type="hidden" name="menuid" value="" />
		<?php
	}

	function checkedOut( $row, $overlib=1 ) {
		$hover = '';
		if ( $overlib ) {
			$date 				= mosFormatDate( $row->checked_out_time, '%A, %d %B %Y' );
			$time				= mosFormatDate( $row->checked_out_time, '%H:%M' );
			$editor				= addslashes( htmlspecialchars( html_entity_decode( $row->editor, ENT_QUOTES ) ) );
			$checked_out_text 	= '<table>';
			$checked_out_text 	.= '<tr><td>'. $editor .'</td></tr>';
			$checked_out_text 	.= '<tr><td>'. $date .'</td></tr>';
			$checked_out_text 	.= '<tr><td>'. $time .'</td></tr>';
			$checked_out_text 	.= '</table>';
			$hover = 'onMouseOver="return overlib(\''. $checked_out_text .'\', CAPTION, \'Checked Out\', BELOW, RIGHT);" onMouseOut="return nd();"';
		}
		$checked	 		= '<img src="images/checked_out.png" '. $hover .'/>';

		return $checked;
	}

	/*
	* Loads all necessary files for JS Overlib tooltips
	*/
	function loadOverlib() {
		global  $mosConfig_live_site, $mainframe;

		if ( !$mainframe->get( 'loadOverlib' ) ) {
		// check if this function is already loaded
			?>
			<script language="javascript" type="text/javascript" src="<?php echo $mosConfig_live_site;?>/includes/js/overlib_mini.js"></script>
			<script language="javascript" type="text/javascript" src="<?php echo $mosConfig_live_site;?>/includes/js/overlib_hideform_mini.js"></script>
			<?php
			// change state so it isnt loaded a second time
			$mainframe->set( 'loadOverlib', true );
		}
	}


	/*
	* Loads all necessary files for JS Calendar
	*/
	function loadCalendar() {
		global  $mosConfig_live_site;
		?>
		<link rel="stylesheet" type="text/css" media="all" href="<?php echo $mosConfig_live_site;?>/includes/js/calendar/calendar-mos.css" title="green" />
		<!-- import the calendar script -->
		<script type="text/javascript" src="<?php echo $mosConfig_live_site;?>/includes/js/calendar/calendar_mini.js"></script>
		<!-- import the language module -->
		<script type="text/javascript" src="<?php echo $mosConfig_live_site;?>/includes/js/calendar/lang/calendar-en.js"></script>
		<?php
	}

	function AccessProcessing( $row, $i ) {
		if ( !$row->access ) {
			$color_access = 'style="color: green;"';
			$task_access = 'accessregistered';
		} else if ( $row->access == 1 ) {
			$color_access = 'style="color: red;"';
			$task_access = 'accessspecial';
		} else {
			$color_access = 'style="color: black;"';
			$task_access = 'accesspublic';
		}

		$href = '
		<a href="javascript: void(0);" onclick="return listItemTask(\'cb'. $i .'\',\''. $task_access .'\')" '. $color_access .'>
		'. $row->groupname .'
		</a>'
		;

		return $href;
	}

	function CheckedOutProcessing( $row, $i ) {
		global $my;

		if ( $row->checked_out) {
			$checked = mosCommonHTML::checkedOut( $row );
		} else {
			$checked = mosHTML::idBox( $i, $row->id, ($row->checked_out && $row->checked_out != $my->id ) );
		}

		return $checked;
	}

	function PublishedProcessing( $row, $i ) {
		$img 	= $row->published ? 'publish_g.png' : 'publish_x.png';
		$task 	= $row->published ? 'unpublish' : 'publish';
		$alt 	= $row->published ? 'Published' : 'Unpublished';
		$action	= $row->published ? 'Unpublish Item' : 'Publish item';

		$href = '
		<a href="javascript: void(0);" onclick="return listItemTask(\'cb'. $i .'\',\''. $task .'\')" title="'. $action .'">
		<img src="images/'. $img .'" border="0" alt="'. $alt .'" />
		</a>'
		;

		return $href;
	}

	/*
	* Special handling for newfeed encoding and possible conflicts with page encoding and PHP version
	* Added 1.0.8
	* Static Function
	*/
	function newsfeedEncoding( $rssDoc, $text ) {
		if (!defined( '_JOS_FEED_ENCODING' )) {
		// determine encoding of feed
			$feed 			= $rssDoc->toNormalizedString(true);
			$feed 			= strtolower( substr( $feed, 0, 150 ) );
			$feedEncoding 	= strpos( $feed, 'encoding=&quot;utf-8&quot;' );

			if ( $feedEncoding !== false ) {
			// utf-8 feed
				$utf8 = 1;
			} else {
			// non utf-8 page
				$utf8 = 0;
			}

			define( '_JOS_FEED_ENCODING', $utf8 );
		}

		if (!defined( '_JOS_SITE_ENCODING' )) {
		// determine encoding of page
			if ( strpos( strtolower( _ISO ), 'utf' ) !== false ) {
			// utf-8 page
				$utf8 = 1;
			} else {
			// non utf-8 page
				$utf8 = 0;
			}

			define( '_JOS_SITE_ENCODING', $utf8 );

		}

		if ( phpversion() >= 5 ) {
		// handling for PHP 5
			if ( _JOS_FEED_ENCODING ) {
			// handling for utf-8 feed
				if ( _JOS_SITE_ENCODING ) {
				// utf-8 page
					$encoding = 'html_entity_decode';
				} else {
				// non utf-8 page
					$encoding = 'utf8_decode';
				}
			} else {
			// handling for non utf-8 feed
				if ( _JOS_SITE_ENCODING ) {
					// utf-8 page
					$encoding = '';
				} else {
					// non utf-8 page
					$encoding = 'utf8_decode';
				}
			}
		} else {
		// handling for PHP 4
			if ( _JOS_FEED_ENCODING ) {
			// handling for utf-8 feed
				if ( _JOS_SITE_ENCODING ) {
				// utf-8 page
					$encoding = '';
				} else {
				// non utf-8 page
					$encoding = 'utf8_decode';
				}
			} else {
			// handling for non utf-8 feed
				if ( _JOS_SITE_ENCODING ) {
				// utf-8 page
					$encoding = 'utf8_encode';
				} else {
				// non utf-8 page
					$encoding = 'html_entity_decode';
				}
			}
		}

		if ( $encoding ) {
			$text = $encoding( $text );
		}
		$text = str_replace('&apos;', "'", $text);

		return $text;
	}
}
