<?php

/**
* Plugin handler
* @package Joomla
*/
class mosMambotHandler {
	/** @var array An array of functions in event groups */
	var $_events			= null;
	/** @var array An array of lists */
	var $_lists				= null;
	/** @var array An array of mambots */
	var $_bots				= null;
	/** @var int Index of the mambot being loaded */
	var $_loading			= null;

	/** Added as of 1.0.8 to ensure queries are only called once **/

	/** @var array An array of the content mambots in the system */
	var $_content_mambots	= null;
	/** @var array An array of the content mambot params */
	var $_content_mambot_params	= array();
	/** @var array An array of the content mambot params */
	var $_search_mambot_params	= array();

	/**
	* Constructor
	*/
	function mosMambotHandler() {
		$this->_events = array();
	}
	/**
	* Loads all the bot files for a particular group
	* @param string The group name, relates to the sub-directory in the mambots directory
	*/
	function loadBotGroup( $group ) {
		global $database, $my;

		$group = trim( $group );
		if (is_object( $my )) {
			$gid = $my->gid;
		} else {
			$gid = 0;
		}

		$group = trim( $group );

		switch ( $group ) {
			case 'content':
				if (!defined( '_JOS_CONTENT_MAMBOTS' )) {
					/** ensure that query is only called once */
					define( '_JOS_CONTENT_MAMBOTS', 1 );

					$query = "SELECT folder, element, published, params"
					. "\n FROM #__mambots"
					. "\n WHERE access <= " . (int) $gid
					. "\n AND folder = 'content'"
					. "\n ORDER BY ordering"
					;
					$database->setQuery( $query );

					// load query into class variable _content_mambots
					if (!($this->_content_mambots = $database->loadObjectList())) {
						//echo "Error loading Mambots: " . $database->getErrorMsg();
						return false;
					}
				}

				// pull bots to be processed from class variable
				$bots = $this->_content_mambots;
				break;

			default:
				$query = "SELECT folder, element, published, params"
				. "\n FROM #__mambots"
				. "\n WHERE published >= 1"
				. "\n AND access <= " . (int) $gid
				. "\n AND folder = " . $database->Quote( $group )
				. "\n ORDER BY ordering"
				;
				$database->setQuery( $query );

				if (!($bots = $database->loadObjectList())) {
					//echo "Error loading Mambots: " . $database->getErrorMsg();
					return false;
				}
				break;
		}

		// load bots found by queries
		$n = count( $bots);
		for ($i = 0; $i < $n; $i++) {
			$this->loadBot( $bots[$i]->folder, $bots[$i]->element, $bots[$i]->published, $bots[$i]->params );
		}

		return true;
	}
	/**
	 * Loads the bot file
	 * @param string The folder (group)
	 * @param string The elements (name of file without extension)
	 * @param int Published state
	 * @param string The params for the bot
	 */
	function loadBot( $folder, $element, $published, $params='' ) {
		global $mosConfig_absolute_path;
		global $_MAMBOTS;

		$path = $mosConfig_absolute_path . '/mambots/' . $folder . '/' . $element . '.php';
		if (file_exists( $path )) {
			$this->_loading = count( $this->_bots );
			$bot = new stdClass;
			$bot->folder 	= $folder;
			$bot->element 	= $element;
			$bot->published = $published;
			$bot->lookup 	= $folder . '/' . $element;
			$bot->params 	= $params;
			$this->_bots[] 	= $bot;

			require_once( $path );

			$this->_loading = null;
		}
	}
	/**
	* Registers a function to a particular event group
	* @param string The event name
	* @param string The function name
	*/
	function registerFunction( $event, $function ) {
		$this->_events[$event][] = array( $function, $this->_loading );
	}
	/**
	* Makes a option for a particular list in a group
	* @param string The group name
	* @param string The list name
	* @param string The value for the list option
	* @param string The text for the list option
	*/
	function addListOption( $group, $listName, $value, $text='' ) {
		$this->_lists[$group][$listName][] = mosHTML::makeOption( $value, $text );
	}
	/**
	* @param string The group name
	* @param string The list name
	* @return array
	*/
	function getList( $group, $listName ) {
		return $this->_lists[$group][$listName];
	}
	/**
	* Calls all functions associated with an event group
	* @param string The event name
	* @param array An array of arguments
	* @param boolean True is unpublished bots are to be processed
	* @return array An array of results from each function call
	*/
	function trigger( $event, $args=null, $doUnpublished=false ) {
		$result = array();
		if ($args === null) {
			$args = array();
		}
		if ($doUnpublished) {
			// prepend the published argument
			array_unshift( $args, null );
		}
		if (isset( $this->_events[$event] )) {
			foreach ($this->_events[$event] as $func) {
				if (function_exists( $func[0] )) {
					if ($doUnpublished) {
						$args[0] = $this->_bots[$func[1]]->published;
						$result[] = call_user_func_array( $func[0], $args );
					} else if ($this->_bots[$func[1]]->published) {
						$result[] = call_user_func_array( $func[0], $args );
					}
				}
			}
		}
		return $result;
	}
	/**
	* Same as trigger but only returns the first event and
	* allows for a variable argument list
	* @param string The event name
	* @return array The result of the first function call
	*/
	function call( $event ) {
		$doUnpublished=false;

		$args = func_get_args();
		array_shift( $args );

		if (isset( $this->_events[$event] )) {
			foreach ($this->_events[$event] as $func) {
				if (function_exists( $func[0] )) {
					if ($this->_bots[$func[1]]->published) {
						return call_user_func_array( $func[0], $args );
					}
				}
			}
		}
		return null;
	}
}
