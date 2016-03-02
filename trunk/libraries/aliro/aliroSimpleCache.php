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
 * aliroCache emulates the PEAR light cache, but is much less code
 *
 */

class aliroSimpleCache extends aliroBasicCache {
	protected $group = '';
	protected $idencoded = '';
	protected $caching = true;
	protected $timeout = _ALIRO_HTML_CACHE_TIME_LIMIT;
	protected $sizelimit = _ALIRO_HTML_CACHE_SIZE_LIMIT;
	protected $livesite = '';
	
	public function __construct ($group, $maxsize=0) {
		parent::__construct();
		if ($group) $this->group = $group;
        else trigger_error ('Cannot create cache without specifying group name');
		if ($maxsize) $this->sizelimit = $maxsize;
	}

	protected function getGroupPath () {
		return $this->getBasePath()."html/$this->group/";
	}

	protected function makeDirectory ($dirpath) {
		return new aliroDirectory($dirpath);
	}

	protected function getCachePath ($name) {
		return $this->getGroupPath().$name;
	}
	
	public function setTimeout ($timeout) {
		$this->timeout = $timeout;
	}

	public function clean () {
		$path = $this->getGroupPath();
		$dir = $this->makeDirectory($path);
		$dir->deleteFiles();
	}

	public function get ($id) {
		$this->idencoded = $this->encodeID($id);
		return $this->retrieve ($this->idencoded);
	}

	public function save ($data, $id=null, $reportSizeError=true) {
		if ($id) $this->idencoded = $this->encodeID($id);
		return $this->store ($data, $this->idencoded, $reportSizeError);
	}
	
	protected function encodeID ($id) {
		return md5(serialize($id).$this->livesite);
	}
}
