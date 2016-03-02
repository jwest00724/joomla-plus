<?php

/**
* Class to support function caching
* with backwards compatibility for Mambo and Joomla
*/

class mosCache {
    /**
	* @return object A function cache object
	*/
    static function getCache ($group) {
        return new aliroCache ($group);
    }
    /**
	* Cleans the cache
	*/
    public static function cleanCache ($group=false) {
		global $mosConfig_caching;
        if ($mosConfig_caching) {
            $cache = mosCache::getCache( $group );
            $cache->clean ($group);
        }
    }
}
