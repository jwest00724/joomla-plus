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
 * aliroSupportRequestor provides a way to submit an email to a support ticket
 * service, such as the one offered by Aliro application com_ticket.
 *
 */

class aliroSupportRequestor {
	protected $cname = '';
	protected $CMSname = 'Aliro';

	public function __construct ($cname) {
		$this->cname = $cname;
	}

	public function sendRequest ($mailto, $subject='', $message='', $appversion='', $objects=array()) {
		if (!$mailto) return false;
		$mailsubject = $subject ? $subject : 'Suppport Request for '.$this->cname;
		$mailbody = $message ? $message : 'No user message provided';
		$mailbody .= "\n\nCMS is $this->CMSname version ".$this->getCMSVersion().'.  Application is '.$this->cname;
		if ($appversion) $mailbody .= ' version '.$appversion.'.';
		if (class_exists('jaliro', false)) $mailbody .= '  Jaliro version '.jaliro::$version.'.';
		$mailbody .= $this->getErrors();
		if (!is_array($objects)) $objects = array($objects);
		if (count($objects)) $mailbody .= "\n".str_repeat('-', 65)."\n\n";
		foreach ($objects as $object) $mailbody .= "COMPONENT OBJECT: ".print_r($object, true)."\n\n";
		$user = $this->getUser();
		$mailfrom = $this->getUserEmail($user);
		if (!$mailfrom) return false;
		return $this->sendMail ($mailfrom, $user->name, $mailto, $mailsubject, $mailbody);
	}

	protected function getErrors () {
		$errorlist = "\n\nFROM THE ERROR LOG:\n";
		$errors = $this->errorsFromDatabase();
		foreach ($errors as $error) if ($this->cname) $errorlist .= $this->showError($error);
		return $errorlist;
	}

	protected function getUser () {
		return aliroUser::getInstance();
	}

	protected function showError ($error) {
		$get = base64_decode($error->get);
		$post = unserialize(base64_decode($error->post));
		if (false === strpos($get, $this->cname) AND (!isset($post['option']) OR $this->cname != $post['option'])) return '';
		$message = "\n".str_repeat('-', 65)."\n\n";
		$message .= $error->timestamp.'   Referer: '.$error->referer.'   IP: '.$error->ip."\n\n";
		$message .= $error->lmessage."\n\n";
		$message .= "TRACE: \n".str_replace('<br />', "\n", $error->trace)."\n\n";
		$message .= 'GET: '.$get."\n\n";
		$message .= 'POST: '.print_r($post, true)."\n\n";
		return $message;
	}

	protected function getUserEmail ($user) {
		return $user->email;
	}

	protected function errorsFromDatabase () {
		return aliroCoreDatabase::getInstance()->doSQLget("SELECT * FROM #__error_log WHERE SUBDATE(NOW(), INTERVAL 24 HOUR) < timestamp ORDER BY timestamp DESC");
	}

	protected function getCMSVersion () {
		$version = version::getInstance();
		return $version->RELEASE.' '.$version->DEV_STATUS.' '.$version->DEV_LEVEL;
	}

	protected function sendMail ($mailfrom, $name, $mailto, $mailsubject, $mailbody) {
		$mailer = new aliroMailMessage($mailfrom, $name);
		return $mailer->sendMail($mailto, $mailsubject, $mailbody);
	}
}