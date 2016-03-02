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
 * aliroIPStore provides for caching information until same IP appears
 *
 */

class aliroIPStore {
	protected $iplist = array();
	protected $stored = null;
	protected $id = '';
	protected $cache = null;

	public function __construct ($id) {
		$this->id = $id;
		$this->cache = new aliroSimpleCache('aliroIPStore');
		$this->iplist = $this->cache->get($this->id.'iplist');
	}

	public function get ($ipaddress) {
		if (in_array($ipaddress, $this->iplist)) return null;
		else {
			$this->iplist[] = $ipaddress;
			$this->cache->save($this->iplist, $this->id.'iplist');
			return empty($this->stored) ? $this->stored = $this->cache->get($this->id.'stored') : $this->stored;
		}
	}

	public function save ($ipaddress, $object) {
		$this->iplist = array($ipaddress);
		$this->cache->save($this->iplist, $this->id.'iplist');
		$this->stored = $object;
		$this->cache->save($this->stored, $this->id.'stored');
	}
}