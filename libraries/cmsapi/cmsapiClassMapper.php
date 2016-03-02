<?php

/*******************************************************************
* This file is a generic interface to Aliro, Joomla 1.5+, Joomla 1.0.x and Mambo
* Copyright (c) 2008-10 Martin Brampton
* Issued as open source under GNU/GPL
* For support and other information, visit http://remository.com
* To contact Martin Brampton, write to martin@remository.com
*
*/

if (basename(@$_SERVER['REQUEST_URI']) == basename(__FILE__)) die ('This software is for use within a larger system');

class cmsapiClassMapper extends cachedSingleton {
	protected static $instance = __CLASS__;
	private $classmap = array();
	private $directories = array();

	public static function getInstance () {
		return self::$instance instanceof self ? self::$instance : self::$instance = parent::getCachedSingleton(self::$instance);
	}

	public function addDirectories ($directories, $cname, $admin=false, $initialize=false) {
		foreach ((array) $directories as $directory) {
			$dirname = _CMSAPI_ABSOLUTE_PATH.($admin ? $this->adminDir() : '').DS.'components'.DS.$cname.DS.$directory;
			if (!$initialize AND in_array($dirname, $this->directories)) continue;
			$this->directories[] = $dirname;
			$newdir = true;
			$dirobject = new aliroDirectory($dirname);
			$files = $dirobject->listAll();
			if ($files) foreach ($files as $file) {
				$fullpath = $dirname.DS.$file;
				$classname = basename($fullpath, '.php');
				$this->classmap[$classname] = $fullpath;
			}
		}
		if (!empty($newdir)) $this->cacheNow();
	}

	private function adminDir () {
		return DS.'administrator';
	}

	public function classPath ($classname) {
		return isset($this->classmap[$classname]) ? $this->classmap[$classname] : false;
	}
}