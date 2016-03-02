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

abstract class aliroBasicCache {
	protected $sizelimit = 0;
	protected $timeout = 0;
	protected $handler = null;

	public function __construct () {
		$handlerclass = _ALIRO_CACHE_HANDLER;
		$this->handler = new $handlerclass($this->sizelimit, $this->timeout);
	}

	protected function getBasePath () {
		return $this->handler->getBasePath();
	}

	abstract protected function getCachePath ($name);

	public function store ($object, $cachename='', $reportSizeError=true) {
		$path = $this->getCachePath($this->getCacheName($object, $cachename));
		if (is_object($object)) $object->aliroCacheTimer = time();
		else {
			$givendata = $object;
			$object = new stdClass();
			$object->aliroCacheData = $givendata;
			$object->aliroCacheTimer = -time();
		}
		$s = serialize($object);
		$s .= md5($s);
		$result = $this->handler->storeData ($path, $s, $reportSizeError);
		if (!$result) {
			trigger_error(sprintf($this->T_('Cache failed on write, class %s, path %s'), get_class($object), $path));
			$this->handler->delete($path);
		}
		return $result;
	}

	protected function getCacheName ($object, $cachename) {
		if ($cachename) return $cachename;
		if (is_object($object)) return get_class($object);
		trigger_error($this->T('Attempt to cache non-object without providing a name for the cache'));
	}
	
	protected function T_ ($string) {
		return function_exists('T_') ? T_($string) : $string;
	}
	
	public function retrieve ($class, $time_limit = 0) {
		// $timer = class_exists('aliroProfiler') ? new aliroProfiler() : null;
		$path = $this->getCachePath($class);
		$result = $this->handler->getData($path);
		// if ($result AND $timer) echo "<br />Loaded $class in ".$timer->getElapsed().' secs';
		return $result;
	}
}
