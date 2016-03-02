<?php

/**************************************************************
* This file is part of Jaliro
* Copyright (c) 2008-10 Martin Brampton
* Issued as open source under GNU/GPL
* For support and other information, visit http://remository.com
* To contact Martin Brampton, write to martin@remository.com
*
* Please see jaliro.php for more details
*/

class cmsapiUser {
	protected $user = null;
	protected $isAdmin = false;
	protected $admintypes = array('administrator', 'superadministrator', 'super administrator');

	public function __construct ($user) {
		$this->user = $user;
		foreach ($this->admintypes as $type) if (0 == strcasecmp($user->usertype, $type)) {
			$this->isAdmin = true;
			break;
		}
	}

	public function __toString () {
		return 'cmsapiUser:: '.jaliroDebug::dumpValues(get_object_vars($this->user));
	}

	public function __get ($property) {
		// For some CMS property "email" requires special action
		return isset($this->user->$property) ? $this->user->$property : null;
	}

	public function __set ($property, $value) {
		$this->user->$property = $value;
	}

	public function isLogged () {
		return 0 != $this->user->id;
	}

	public function isAdmin () {
		return $this->isAdmin;
	}

	public function isUser () {
		return $this->isLogged() AND !$this->isAdmin();
	}
}