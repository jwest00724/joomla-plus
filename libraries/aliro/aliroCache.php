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

final class aliroCache extends aliroSimpleCache {

	public function __construct ($group, $maxsize=0) {
		global $mosConfig_caching, $mosConfig_live_site;
		$this->caching = $mosConfig_caching ? true : false;
		$this->livesite = $mosConfig_live_site;
		parent::__construct($group, $maxsize);
	}

	public function call () {
		$arguments = func_get_args();
		$cached = $this->caching ? $this->get($arguments) : null;
		if (!$cached) {
			ob_start();
			ob_implicit_flush(false);
			$function = array_shift($arguments);
			if (is_string($function)) {
				if (strpos($function, '::')) $function = explode('::', $function);
				elseif (strpos($function, '->')) {
					$funcparts = explode('->', $function);
					global $$funcparts[0];
					$function = array($$funcparts[0], $funcparts[1]);
				}
			}
			$results = call_user_func_array($function, $arguments);
			$html = ob_get_clean();
			$cached = new stdClass();
			$cached->html = $html;
			$cached->results = $results;
			if ($this->caching) {
			    aliroRequest::getInstance()->setMetadataInCache($cached);
				$cached->pathway = aliroPathway::getInstance()->getPathway();
				$this->save($cached);
			}
		}
		else {
		    aliroRequest::getInstance()->setMetadataFromCache($cached);
		    aliroPathway::getInstance()->setPathway($cached->pathway);
		}
		echo $cached->html;
		return $cached->results;
	}

	public static function deleteAll () {
		$dir = new aliroDirectory (_ALIRO_SITE_BASE.'/cache/singleton/');
		$dir->deleteContents(true);
		$dir = new aliroDirectory (_ALIRO_SITE_BASE.'/cache/html/');
		$dir->deleteContents(true);
		$dir = new aliroDirectory (_ALIRO_SITE_BASE.'/cache/rssfeeds/');
		$dir->deleteContents(true);
		$fmanager = aliroFileManager::getInstance();
		$fmanager->simpleCopy(_ALIRO_SITE_BASE.'/cache/index.html', _ALIRO_SITE_BASE.'/cache/singleton/index.html', 0644);
		$fmanager->simpleCopy(_ALIRO_SITE_BASE.'/cache/index.html', _ALIRO_SITE_BASE.'/cache/html/index.html', 0644);
		$fmanager->simpleCopy(_ALIRO_SITE_BASE.'/cache/index.html', _ALIRO_SITE_BASE.'/cache/rssfeeds/index.html', 0644);
	}
}