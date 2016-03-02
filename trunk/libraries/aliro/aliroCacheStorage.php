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
 * These classes provide cache logic, and for no special reason, the profiling
 * logic that supports the timing of operations, aliroProfiler.
 *
 * cachedSingleton is the base for building a singleton class whose internal
 * data is cached.  It is used extensively within the core, especially for the
 * handlers that look after information about the main building blocks of the
 * CMS, such as menus, components, modules, etc.  It provides common code for
 * them so that it becomes simple to create a cached singleton object.
 *
 * aliroBasicCache is the class containing rudimentary cache operations.  It
 * was initially independently developed, and subsequently modified to contain
 * the features of CacheLite.  Except that a number of decisions have been
 * taken as well as the code being exclusively PHP5 - these factors make the
 * code a lot simpler.
 *
 * aliroSingletonObjectCache does any special operations needed in the handling
 * of cached singletons, extending aliroBasicCache.
 *
 * Further cache related code that emulates the services of CacheLite (more or
 * less) is found elsewhere (in aliroCentral.php at the time of writing).
 *
 */

abstract class aliroCacheStorage {
	protected $sizelimit = 0;
	protected $timeout = 0;

	public function __construct ($sizelimit, $timeout) {
		$this->sizelimit = $sizelimit;
		$this->timeout = $timeout;
	}

	public function storeData ($id, $data) {}

	public function getData ($id) {}

	public function delete ($id) {}

	public function deleteAll () {}

	public function getBasePath () {}

	protected function T_ ($string) {
		return function_exists('T_') ? T_($string) : $string;
	}

	protected function checkSize ($data, $id, $reportSizeError=true) {
		if (strlen($data) > $this->sizelimit) {
			if ($reportSizeError) trigger_error(sprintf($this->T_('Cache failed on size limit, ID %s, actual size %s, limit %s'), $id, strlen($data), $this->sizelimit));
			$this->delete($id);
			return false;
		}
		else return true;
	}

	protected function extractObject ($string, $time_limit=0) {
		$s = substr($string, 0, -32);
		$object = ($s AND (md5($s) == substr($string, -32))) ? unserialize($s) : null;
		if (is_object($object)) {
			$time_limit = $time_limit ? $time_limit : $this->timeout;
			$stamp = @$object->aliroCacheTimer;
			if ((time() - abs($stamp)) <= $time_limit) return $stamp > 0 ? $object : @$object->aliroCacheData;
		}
		return null;
	}
}
