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

abstract class cmsapiAuthorisationAdmin extends aliroAuthorisationAdmin {

	// CMS Specific method - Aliro code overriden by CMS API mechanisms
	protected function getUsefulObjects () {
		$this->request = cmsapiInterface::getInstance('com_cmsapi');
		$this->html = aliroHTML::getInstance();
	}

	// CMS Specific method
	protected function getUserID () {
		return cmsapiInterface::getInstance('com_cmsapi')->getUser()->id;
	}

	// CMS Specific method 
	protected function getParam ($arr, $name, $def='', $mask=0) {
		return $this->request->getParam($arr, $name, $def, $mask);
	}

}