<?php

/*******************************************************************
* This file is a generic interface to Aliro, Joomla 1.5+, Joomla 1.0.x and Mambo
* Copyright (c) 2008-10 Martin Brampton
* Issued as open source under GNU/GPL
* For support and other information, visit http://acmsapi.org
* To contact Martin Brampton, write to martin@remository.com
*
*/

if (basename(@$_SERVER['REQUEST_URI']) == basename(__FILE__)) die ('This software is for use within a larger system');

abstract class cmsapiAuthoriserCache extends aliroAuthoriserCache {

	protected function loadLinkData ($database) {
		$database->setQuery($this->loadLinkSQL());
		$links = $database->loadObjectList();
		if ($links) foreach ($links as $link) $this->all_roles[$link->role] = $link->role;
		$result = new stdClass();
		$result->roles = isset($all_roles) ? $all_roles : array();
		$result->cleartime = time();
		return $result;
	}

	protected function loadLinkSQL () {
		return "SELECT DISTINCT role FROM #__assignments UNION SELECT DISTINCT role FROM #__permissions";
	}

}