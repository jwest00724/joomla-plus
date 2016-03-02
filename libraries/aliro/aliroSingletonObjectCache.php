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

class aliroSingletonObjectCache extends aliroBasicCache {
	protected static $instance = null;
	protected $timeout = _ALIRO_OBJECT_CACHE_TIME_LIMIT;
	protected $sizelimit = _ALIRO_OBJECT_CACHE_SIZE_LIMIT;

	public static function getInstance () {
	    return (self::$instance instanceof self) ? self::$instance : (self::$instance = new self());
	}

	protected function getCachePath ($name) {
		return $this->getBasePath().'singleton/'.$name;
	}

	public function delete () {
		$classes = func_get_args();
		clearstatcache();
		foreach ($classes as $class) {
			$cachepath = $this->getCachePath($class);
			if (file_exists($cachepath)) @unlink($cachepath);
		}
	}

	public function deleteByExtension ($type) {
		$caches = array (
		'component' => 'aliroComponentHandler',
		'module' => 'aliroModuleHandler',
		'mambot' => 'aliroMambotHandler'
		);
		if (isset($caches[$type])) $this->delete($caches[$type]);
	}

}