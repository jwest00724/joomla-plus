<?php
/**
* @version $Id: poll.class.php 10002 2008-02-08 10:56:57Z willebil $
* @package Joomla
* @subpackage Polls
* @copyright Copyright (C) 2005 Open Source Matters, 2011 Aliro Software Ltd. All rights reserved.
* @license http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL, see LICENSE.php
* J-One-Plus is free software. This version may have been modified pursuant
* to the GNU General Public License, and as distributed it includes or
* is derivative of works licensed under the GNU General Public License or
* other free or open source software licenses.
* See COPYRIGHT.php for copyright notices and details.
*/

/// no direct access
defined( '_VALID_MOS' ) or die( 'Restricted access' );

/**
* @package Joomla
* @subpackage Polls
*/
class mosPoll extends mosDBTable {
	/** @var int Primary key */
	var $id					= null;
	/** @var string */
	var $title				= null;
	/** @var string */
	var $checked_out		= null;
	/** @var time */
	var $checked_out_time	= null;
	/** @var boolean */
	var $published			= null;
	/** @var int */
	var $access				= null;
	/** @var int */
	var $lag				= null;

	/**
	* @param database A database connector object
	*/
	function mosPoll( &$db ) {
		$this->mosDBTable( '#__polls', 'id', $db );
	}

	// overloaded check function
	function check() {
		// check for valid name
		if (trim( $this->title ) == '') {
			$this->_error = 'Your Poll must contain a title.';
			return false;
		}
		// check for valid lag
		$this->lag = intval( $this->lag );
		if ($this->lag == 0) {
			$this->_error = 'Your Poll must have a non-zero lag time.';
			return false;
		}
		// check for existing title
		$query = "SELECT id"
		. "\n FROM #__polls"
		. "\n WHERE title = " . $this->_db->Quote( $this->title )
		;
		$this->_db->setQuery( $query );

		$xid = intval( $this->_db->loadResult() );
		if ( $xid && $xid != intval( $this->id ) ) {
			$this->_error = 'There is a module already with that name, please try again.';
			return false;
		}

		return true;
	}

	// overloaded delete function
	function delete( $oid=null ) {
		$k = $this->_tbl_key;
		if ( $oid ) {
			$this->$k = intval( $oid );
		}

		if (mosDBTable::delete( $oid )) {
			$query = "DELETE FROM #__poll_data"
			. "\n WHERE pollid = " . (int) $this->$k
			;
			$this->_db->setQuery( $query );
			if ( !$this->_db->query() ) {
				$this->_error .= $this->_db->getErrorMsg() . "\n";
			}

			$query = "DELETE FROM #__poll_date"
			. "\n WHERE poll_id = " . (int) $this->$k
			;
			$this->_db->setQuery( $query );
			if ( !$this->_db->query() ) {
				$this->_error .= $this->_db->getErrorMsg() . "\n";
			}

			$query = "DELETE from #__poll_menu"
			. "\n WHERE pollid = " . (int) $this->$k
			;
			$this->_db->setQuery( $query );
			if ( !$this->_db->query() ) {
				$this->_error .= $this->_db->getErrorMsg() . "\n";
			}

			return true;
		} else {
			return false;
		}
	}
}
?>