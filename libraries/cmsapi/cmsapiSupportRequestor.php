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

class cmsapiSupportRequestor extends aliroSupportRequestor {
	protected $CMSname = 'J-One-Plus';
	protected $interface = null;
	protected $database = null;

	public function __construct ($cname) {
		parent::__construct($cname);
		$this->interface = cmsapiInterface::getInstance($cname);
		$this->database = $this->interface->getDB();
	}

	protected function getUser () {
		return $this->interface->getUser();
	}

	protected function getUserEmail ($user) {
		$userid = (int) $user->id;
		$this->database->setQuery("SELECT email FROM #__users WHERE id = $userid");
		return $this->database->loadResult();
	}

	protected function errorsFromDatabase () {
		return $this->database->doSQLget("SELECT * FROM #__cmsapi_error_log WHERE SUBDATE(NOW(), INTERVAL 24 HOUR) < timestamp ORDER BY timestamp DESC");
	}

	protected function getCMSVersion () {
		$version = new joomlaVersion();
		return $version->getShortVersion();
	}

	protected function sendMail ($mailfrom, $name, $mailto, $mailsubject, $mailbody) {
		return $this->interface->sendMail ($mailfrom, $name, $mailto, $mailsubject, $mailbody);
	}
}