<?php

/*******************************************************************************
 * Aliro - the modern, accessible content management system
 *
 * This code is copyright (c) Aliro Software Ltd - please see the notice in the
 * index.php file for full details or visit http://aliro.org/copyright
 *
 * Some parts of Aliro are developed from other open source code, and for more
 * information on this, please see the index.php file or visit
 * http://aliro.org/credits
 *
 * Author: Martin Brampton
 * counterpoint@aliro.org
 *
 * Everything here is to do with database management.
 *
 * aliroDataCache is not yet used - it exists ready for development into a cache
 * for database queries.  This has low priority or may even be abandonded, since
 * it is usually more effective to cache complete output, or more structured
 * data derived from the database, as happens in cached singletons.
 *
 * databaseException uses PHP5 exception handling for database errors, rather
 * than expecting other applications to handle them.  This is combined with the
 * introduction of an error logging table, since detailed diagnostic information
 * is useful to developers, but not much use to users.  Only basic messages are
 * shown to users.
 *
 * aliroBasicDatabase contains all the basic functions to make it easier to
 * code database functions.  It selects an interface class, using either
 * the mysqli or mysql PHP interface mechanisms.
 *
 */

final class aliroDataCache {
	public $records = array();
}

final class databaseException extends Exception {
	public $dbname = '';
	public $sql = '';
	public $number = 0;

	public function __construct ($dbname, $message, $sql, $number, $dbtrace) {
		parent::__construct($message, $number);
		$this->dbname = $dbname;
		$this->sql = $sql;
		$this->dbtrace = $dbtrace;
	}

}

class aliroBasicDatabase {
	protected static $stats = array();
	protected $_sql='';
	protected $_cached=false;
	protected $_errorNum=0;
	protected $_errorMsg='';
	protected $_table_prefix='';
	protected $_resource='';
	protected $_cursor=null;
	protected $_log=array();
	protected $DBname = '';
	protected $interface = null;
	private $host = '';
	private $user = '';
	private $pass = '';
	private $requestTime = 0;
	private $logAll = false;

	public function __construct( $host, $user, $pass, $db, $table_prefix, $return_on_error=false ) {
		$this->requestTime = time();
		// perform a number of fatality checks, then die gracefully if necessary
		$this->DBname = $db;
		$this->host = $host;
		$this->user = $user;
		$this->pass = $pass;
		$this->_table_prefix = $table_prefix;
		if (!$this->interface = aliroDatabaseHandler::getInterface($db)) {
			if ($return_on_error) {
				$this->_errorNum = _ALIRO_DB_NO_INTERFACE;
				return;
			}
			$this->forceOffline(_ALIRO_DB_NO_INTERFACE);
		}
		$this->connectToDB ($return_on_error);
		if (!$this->_resource) return;
		$this->interface->setCharset('utf8');
		clearstatcache();
	}

	protected function __clone () {
		// Enforce singleton
	}

	protected function connectToDB ($return_on_error=false) {
		if (!($this->_resource = $this->interface->connect($this->host, $this->user, $this->pass, $this->DBname))) {
			$this->_errorMsg = $this->interface->connectError();
			if ($return_on_error) {
				$this->_errorNum = _ALIRO_DB_CONNECT_FAILED;
				return;
			}
			$this->forceOffline(_ALIRO_DB_CONNECT_FAILED);
		}
	}

	public function __destruct () {
		try {
			@session_write_close();
			$this->saveStats();
    	} catch (databaseException $exception) {
    		if (_ALIRO_IS_ADMIN) {
    			echo $exception->getMessage();
    		}
    		exit('DB Error during shutdown');
    	}
	}

	protected function T_ ($string) {
		return function_exists('T_') ? T_($string) : $string;
	}

	public function getName () {
		return $this->DBname;
	}

	public function setFieldValue ($data, $type='varchar') {
		return $this->interface->setFieldValue($data, $type);
	}

	protected function forceOffline ($error_number) {
			$offline = new aliroOffline ();
			$offline->show($error_number);
			// Uncomment this for more diagnostics
			// echo aliroBase::trace();
			exit();
	}

	public function defaultDate () {
		return $this->interface->defaultDate();
	}

	public function dateNow () {
		return $this->interface->dateNow();
	}

	// Deprecated in favour of leaving all error handling to the system
	public function getErrorNum() {
		return $this->_errorNum;
	}

	// Deprecated as above
	public function getErrorMsg() {
		return str_replace( array( "\n", "'" ), array( '\n', "'" ), $this->_errorMsg );
	}

	// Takes a string and escapes any characters needing it
	// Optionally adds extra escaping of % and _
	public function getEscaped($text, $extra=false) {
		return $extra ? addcslashes($this->interface->getEscaped($text), '%_') : $this->interface->getEscaped($text);
	}

	// Deprecated - does not add enough value
	public function Quote( $text ) {
		return '\''.$this->getEscaped($text).'\'';
	}

	// No conversion of prefix marker - use only internally within Aliro DB framework
	public function setBareQuery($sql) {
		$this->_sql = $sql;
	}

	// Replaces #_ by the chosen database prefix and saves the query
	public function setQuery( $sql, $cached=false, $prefix='#__' ) {
		$this->_sql = $this->replacePrefix($sql, $prefix);
		$this->_cached = $cached;
	}

	// Carries out prefix marker replacement
	public function replacePrefix ($sql, $prefix='#__') {
		$text = $sql;
		$result = '';
		while ($text) {
			$firstquote = $this->nonzeromin(strpos($text, "'"), strpos($text, '"'));
			if ($firstquote) {
				$result .= str_replace($prefix, $this->_table_prefix, substr($text,0,$firstquote));
				$text = substr($text, $firstquote);
				$endquote = $this->findMatchingQuote($text, $text[0]);
				$result .= substr($text, 0, $endquote+1);
				$text = substr($text, $endquote+1);
			}
			else {
				$result .= str_replace($prefix, $this->_table_prefix, $text);
				break;
			}
		}
		return $result;
	}

	protected function nonzeromin ($x, $y) {
		if (false === $x) return $y;
		if (false === $y) return $x;
		return min($x, $y);
	}

	protected function findMatchingQuote ($text, $quote) {
		$skip = 1;
		do {
			$endquote = $quote ? strpos($text, $quote, $skip) : strlen($text) - 1;
			if ($endquote) $skip = $endquote+1;
		}
		while ($endquote AND '\\' == $text[$endquote-1]);
		if ($endquote) return $endquote;
		else return strlen($text)-1;
	}

	public function restoreOnePrefix ($tablename) {
		if (substr($tablename, 0, strlen($this->_table_prefix)) === $this->_table_prefix) return '#__'.substr($tablename, strlen($this->_table_prefix));
		else return $tablename;
	}

	// Returns stored SQL with replacements, ready to display
	public function getQuery ($sql='') {
		if ($sql == '') $sql = $this->_sql;
		return "<pre>" . htmlspecialchars( $sql ) . "</pre>";
	}

	public function query ($sql='') {
		if (empty($sql)) $sql = $this->_sql;
		$timer = new aliroProfiler('Database timer');
        $this->_cursor = $this->doQueryWork($timer, $sql);
		if ($this->_cursor) {
			if ($this->logAll) {
				$sql = $this->replacePrefix("INSERT INTO #__allquery_log (query, stamp) VALUES ('$sql', $this->requestTime)");
				$this->interface->query($sql);
			}
			return $this->_cursor;
		}
		else {
			$this->_errorNum = $this->interface->errno();
			// If error is lost connection, try reconnecting and repeating the operation
			if (2006 == $this->_errorNum OR 2013 == $this->_errorNum) {
				$this->connectToDB();
                $this->_cursor = $this->doQueryWork($timer, $sql);
				if ($this->_cursor) return $this->_cursor;
			}
			$this->_errorMsg = $this->interface->error()." SQL=$sql";
			throw new databaseException ($this->DBname, $this->_errorMsg, $this->_sql, $this->_errorNum, mosMainFrame::trace());
		}
	}

	protected function doQueryWork ($timer, $sql) {
		$cursor = $this->interface->query($sql);
        if ($cursor) {
			$this->_errorNum = 0;
			$this->_errorMsg = '';
			$stats = new stdClass;
			$stats->timer = $timer->getElapsed();
			$stats->trace = mosMainFrame::trace(false);
			$query = strlen($sql) < 250 ? $sql : $this->T_('LONG QUERY STARTING: ').substr($sql, 0, 120);
			$stats->sql = $query;
			self::$stats[] = $stats;
			$this->_log[] = htmlspecialchars($query).'<br />'.$timer->mark('secs for query').'<br />'.$stats->trace;
			return $cursor;
		}
		return null;
	}

	public function query_batch() {
		$this->_errorNum = 0;
		$this->_errorMsg = '';
		if ($this->interface->multiQuery($this->_sql)) {
		    do {
				$this->interface->storeResult();
				$errno = $this->interface->errno();
				if ($errno AND in_array($errno, array(2006,2013))) {
					$this->connectToDB();
					$this->interface->storeResult();
					$errno = $this->interface->errno();
				}
			}
		    while (0 == $errno AND $this->interface->nextResult());
		}
		if ($errno) {
			$this->_errorNum = $this->interface->errno();
			$this->_errorMsg = $this->interface->error();
			throw new databaseException ($this->DBname, $this->_errorMsg, $this->T_('Batch query'), $this->_errorNum, aliroBase::trace());
		}
	}

	// Combined operation - takes SQL and executes it
	public function doSQL ($sql) {
		$this->setQuery($sql);
		return $this->query();
	}

	public function getNumRows ($cur=null) {
		return $this->interface->getNumRows($cur);
	}

	public function getAffectedRows () {
		return $this->interface->getAffectedRows();
	}

	// Not intended for use outside the database class framework
	public function retrieveResults ($key='', $max=0, $result_type='row') {
		$results = array();
		if (!in_array($result_type, array ('row', 'object', 'assoc'))) {
			$this->_errorMsg = sprintf($this->T_('Unexpected result type of %s in call to database'), $result_type)." SQL=$sql";
			throw new databaseException ($this->DBname, $this->_errorMsg, $this->_sql, $this->_errorNum, aliroBase::trace());
		}
		$sql_function = $this->interface->getFetchFunc().$result_type;
		$cur = $this->query();
		if ($cur) {
			while ($row = $sql_function($cur)) {
				if ($key != '') $results[(is_array($row) ? $row[$key] : $row->$key)] = $row;
				else $results[] = $row;
				if ($max AND count($results) >= $max) break;
			}
			$this->interface->freeResultSet($cur);
		}
		return $results;
	}

	public function loadResult() {
		$results = $this->retrieveResults('', 1, 'row');
		if (count($results)) return $results[0][0];
		else return null;
	}

	public function loadResultArray($numinarray = 0) {
		$results = $this->retrieveResults('', 0, 'row');
		foreach ($results as $result) $values[] = $result[$numinarray];
		return isset($values) ? $values : null;
	}

	public function loadAssoc () {
		$results = $this->retrieveResults('', 0, 'assoc');
		return empty($results[0]) ? null : $results[0];
	}

	public function loadAssocList( $key='' ) {
		$results = $this->retrieveResults($key, 0, 'assoc');
		if (count($results)) return $results;
		else return null;
	}

	// Of questionable value - not used in Aliro except for compatibility in mambofunc
	public function mosBindArrayToObject( $array, $obj, $ignore='', $prefix=NULL, $checkSlashes=true ) {
		if (!is_array($array) OR !is_object($obj)) return false;
		if ($prefix == null) $prefix = '';
		foreach (get_object_vars($obj) as $k => $v) {
			if( substr( $k, 0, 1 ) != '_' AND strpos($ignore, $k) === false) {
				if (isset($array[$prefix.$k])) {
					$obj->$k = ($checkSlashes AND get_magic_quotes_gpc()) ? $this->mosStripslashes( $array[$prefix.$k] ) : $array[$prefix.$k];
				}
			}
		}
		return true;
	}

	// Of questionable value - not used in Aliro except for compatibility in mambofunc
	public function mosStripslashes($value) {
	    if (is_string($value)) $ret = stripslashes($value);
		elseif (is_array($value)) {
	        $ret = array();
	        foreach ($value as $key=>$val) $ret[$key] = $this->mosStripslashes($val);
	    }
		else $ret = $value;
	    return $ret;
	} // mosStripSlashes

	// May be obscure to users how this will behave depending on prior setting of parameter
	public function loadObject(&$object=null) {
		if (!is_object($object)) $results = $this->retrieveResults('', 1, 'object');
		else $results = $this->retrieveResults('', 1, 'assoc');
		if (0 == count($results)) return false;
		if (!is_object($object)) $object = $results[0];
		else {
			if ($object instanceof aliroDBGeneralRow) $object->bind($results[0], '', false);
			else foreach (get_object_vars($object) as $k => $v) {
				if ($k[0] != '_' AND isset($results[0][$k])) $object->$k = $results[0][$k];
			}
		}
		return true;
	}

	public function loadObjectList( $key='' ) {
		$results = $this->retrieveResults($key, 0, 'object');
		return count($results) ? $results : null;
	}

	public function loadRow() {
		$results = $this->retrieveResults('', 1, 'row');
		return count($results) ? $results[0] : null;
	}

	public function loadRowList( $key='' ) {
		$results = $this->retrieveResults($key, 0, 'row');
		return count($results) ? $results : null;
	}

	// Deprecated in favour of allowing the system to handle errors
	public function stderr( $showSQL = false ) {
		return "DB function failed with error number $this->_errorNum"
		."<br /><font color=\"red\">$this->_errorMsg</font>"
		.($showSQL ? "<br />SQL = <pre>$this->_sql</pre>" : '');
	}

	public function insertid() {
		return $this->interface->insertid();
	}

	public function getVersion()
	{
		return $this->interface->getVersion();
	}

	public function getCollation () {
		$this->setQuery( "SHOW VARIABLES LIKE 'collation_connection'" );
		$info = $this->loadAssoc();
		return isset($info['Value']) ? $info['Value'] : '';
	}

	/**
	* Fudge method for ADOdb compatibility???? Not used in Aliro
	*/
	public function GenID () {
		return '0';
	}

	// Usual use is to check for existence of a table - easier to use tableExists which expects #_ type table name
	// Also more efficient as tableExists uses cache
	public function getTableList() {
		$this->setQuery('SHOW tables');
		return $this->loadResultArray();
	}

	// This is probably useful - what exactly does it do?
	public function getTableCreate( $tables ) {
		$result = array();

		foreach ($tables as $tblval) {
			$this->setQuery( 'SHOW CREATE table ' . $tblval );
			$this->query();
			$result[$tblval] = $this->loadResultArray( 1 );
		}

		return $result;
	}

	// This is also potentially useful, but requires translated prefix.
	// Easier to use getAllFieldNames repeatedly - also more efficient, as uses cache
	public function getTableFields( $tables ) {
		$result = array();

		foreach ($tables as $tblval) {
			$this->setQuery('SHOW FIELDS FROM ' . $tblval);
			$fields = $this->retrieveResults ('', 0, 'object');
			foreach ($fields as $field) {
				$result[$tblval][$field->Field] = preg_replace("/[(0-9)]/",'', $field->Type );
			}
		}

		return $result;
	}

	public function getCount () {
		return count($this->_log);
	}

	public function getLogged () {
		$text = '<h4>'.$this->getCount().' queries executed</h4>';
	 	foreach ($this->_log as $k=>$sql) $text .= "\n".($k+1)."<br />".$sql.'<hr />';
		if (count($this->_log)) return $text;
		else return '';
	}

	protected function saveStats () {
return;
		new aliroObjectSorter(self::$stats, 'timer');
		$n = count(self::$stats);
		if ($n > 0) {
			$median = self::$stats[intval($n/2)]->timer;
			$total = 0.0;
			foreach (self::$stats as $stat) $total += $stat->timer;
			$mean = $total/$n;
			$var = 0.0;
			foreach (self::$stats as $stat) $var += ($stat->timer - $mean) * ($stat->timer - $mean);
			$stdev = sqrt($var);
			$best = self::$stats[0]->timer;
			$worst = self::$stats[$n-1]->timer;
			$elapsed = aliro::getInstance()->getElapsed();
			$memory = memory_get_usage();
			$uri = isset($_SERVER['REQUEST_URI']) ? htmlspecialchars($_SERVER['REQUEST_URI'], ENT_QUOTES, 'UTF-8') : '';
			$post = base64_encode(serialize($_POST));
			$ip = aliroSession::getSession()->getIP();
			$database = aliroCoreDatabase::getInstance();
			$database->doSQL("INSERT INTO #__query_stats (count, mean, median, stdev, best, worst, total, elapsed, memory, uri, post, ip) VALUES ($n, '$mean', '$median', '$stdev', '$best', '$worst', '$total', '$elapsed', '$memory', '$uri', '$post', '$ip')");
			$queryid = $this->insertid();
			for ($i = $n-1; $i >= 0; $i--) {
				if (0.5 < self::$stats[$i]->timer) {
					$stat = self::$stats[$i];
					$querytext = $database->getEscaped($stat->sql);
					$tracetext = $this->getEscaped($stat->trace);
					$database->doSQL("INSERT INTO #__query_slow (queryid, time, trace, querytext) VALUES ($queryid, '$stat->timer', '$tracetext', '$querytext')");
				}
				else break;
			}
			if (42 == mt_rand(1,100)) {
				$database->doSQL("DELETE LOW_PRIORITY FROM #__query_stats WHERE stamp < DATE_SUB(NOW(), INTERVAL 48 HOUR)");
				$database->doSQL("OPTIMIZE TABLE #__query_stats");
				$database->doSQL("DELETE LOW_PRIORITY FROM #__query_slow WHERE queryid NOT IN (SELECT id FROM #__query_stats)");
				$database->doSQL("OPTIMIZE TABLE #__query_slow");
			}
		}
		self::$stats = array();
	}
}