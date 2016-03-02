<?php

abstract class mosDBTable extends aliroDBGeneralRow {
	protected $DBclass = 'aliroDatabase';
	public $_tbl = '';
	public $_tbl_key = '';
	protected $tableName = '';
	protected $rowKey = '';
	protected $db = null;

	public function mosDBTable ($table='', $keyname='id', $db=null) {
		$this->_tbl = $this->tableName = $table;
		$this->_tbl_key = $this->rowKey = $keyname;
		$this->db = $db;
	}

	public function __call ($method, $args) {
		if ('mosDBTable' == $method) {
			call_user_func_array(array($this, '__construct'), $args);
		}
		else {
			echo aliroDebug::trace();
			trigger_error($this->T_('Invalid method call to mosDBTable: '.$method));
		}
	}

	// protected function __get
	public function __get ($name) {
		if ($name == '_db') return $this->getDatabase();
		else return null;
	}

	function filter( $ignoreList=null ) {
		if (class_exists('aliroRequest')) {
			$request = aliroRequest::getInstance();
			foreach ($this->getDatabase()->getAllFieldNames($this->tableName) as $k) {
				if (!is_array($ignoreList) OR !in_array($k, $ignoreList)) {
					$this->$k = $request->doPurify($this->$k);
				}
			}
		}
	}

	function get( $_property ) {
		return isset($this->$_property) ? $this->$_property :null;
	}

	function set( $_property, $_value ) {
		$this->$_property = $_value;
	}

	function reset ($value=null) {
		foreach ($this->getDatabase()->getAllFieldNames($this->tableName) as $k) $this->$k = $value;
	}

	function hit( $keyvalue=null ) {
		global $mosConfig_enable_log_items;
		$k = $this->rowKey;
		if (null !== $keyvalue) $this->$k = intval($keyvalue);
		$this->getDatabase()->doSQL( "UPDATE $this->tableName SET hits=(hits+1) WHERE $this->rowKey='{$this->$k}'" );

		if ($mosConfig_enable_log_items) {
			$now = date( "Y-m-d" );
			$this->getDatabase()->doSQL("INSERT INTO #__core_log_items VALUES"
				."\n ('$now','$this->tableName','".$this->$k."','1')"
				."\n ON DUPLICATE KEY UPDATE hits=(hits+1)");
		}
	}

	function save( $source, $order_filter ) {
		if (!$this->bind($source) OR !$this->check() OR !$this->store()OR !$this->checkin()) return false;
		$this->updateOrder( ($order_filter AND !empty($this->$order_filter)) ? "`$order_filter`='{$this->getDatabase()->getEscaped($this->$order_filter)}'" : "" );
		$this->_error = '';
		return true;
	}

	function publish_array( $cid=null, $publish=1, $myid=0 ) {
		if (!is_array( $cid ) OR count( $cid ) < 1) {
			$this->_error = "No items selected.";
			return false;
		}
		foreach ($cid as $i=>$id) $cid[$i] = intval($id);
		$cids = implode( ',', $cid );
		$publish = $publish ? 1 : 0;
		$myid = intval($myid);
		$this->getDatabase()->doSQL( "UPDATE $this->tableName SET published=$publish"
		. "\nWHERE $this->rowKey IN ($cids) AND (checked_out=0 OR checked_out=$myid)"
		);
		return true;
	}

	function publish( $cid=null, $publish=1, $user_id=0 ) {
		$this->publish_array($cid, $publish, $myid);
	}

	function toXML( $mapKeysToText=false ) {
		if ($mapKeysToText) $attrib = ' mapkeystotext="true"';
		$middle = '';
		foreach ($this->getDatabase()->getAllFieldNames($this->tableName) as $k) {
			$v = $this->$k;
			if (is_null($v) OR is_array($v) OR is_object($v) OR (is_string($v) AND '_' == $v[0])) continue;
			$middle .= "<$k><![CDATA[$v]]></$k>";
		}
		return <<<TO_XML
<record table="$this->tableName"$attrib>
$middle
</record>
TO_XML;

	}
}
