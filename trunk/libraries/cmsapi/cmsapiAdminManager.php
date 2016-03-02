<?php

/**************************************************************
* This file is part of Glossary
* Copyright (c) 2008-9 Martin Brampton
* Issued as open source under GNU/GPL
* For support and other information, visit http://remository.com
* To contact Martin Brampton, write to martin@remository.com
*
* Please see glossary.php for more details
*/

class cmsapiAdminManager {
	protected $interface = null;
	public $bare_name = '';
	public $full_name = '';
	public $act = '';
	public $actname = '';
	public $task = '';
	public $limitstart = 0;
	public $limit = 0;
	public $cfid = 0;
	public $currid = 0;
	public $c_classes_path = '';
	public $v_classes_path = '';

	public function __construct ($bare_name, $full_name) {
		if ('Aliro' != _CMSAPI_CMS_BASE) require_once(_CMSAPI_ABSOLUTE_PATH.'/administrator/includes/pageNavigation.php');
		$this->full_name = $full_name;
		$this->interface = cmsapiInterface::getInstance($full_name);
		$mosConfig_live_site = $this->interface->getCfg('live_site');
		$style = <<<ADMIN_STYLE
<link rel="stylesheet" href="$mosConfig_live_site/administrator/components/$full_name/admin.css" type="text/css" />
ADMIN_STYLE;
		if (defined('_MAMBO_46PLUS') OR defined ('_MAMBO_45MINUS')) echo $style;
		else $this->interface->addCustomHeadTag($style);
		// Include files that contain definitions
		$configuration = aliroComponentConfiguration::getConfiguration($full_name);
		// Need to set all the config variables in case any are used in the language file
		foreach (get_object_vars($configuration) as $k=>$v) $$k = $configuration->$k;
		$mosConfig_sitename = $this->interface->getCfg('sitename');
		$mosConfig_live_site = $this->interface->getCfg('live_site');
		$lang = $configuration->language ? $configuration->language : $this->interface->getCfg('lang');
		if (file_exists(_CMSAPI_ABSOLUTE_PATH."/components/$full_name/"._CMSAPI_LANGFILE.$lang.'.php')) require_once(_CMSAPI_ABSOLUTE_PATH."/components/$full_name/"._CMSAPI_LANGFILE.$lang.'.php');
		require_once(_CMSAPI_ABSOLUTE_PATH."/components/$full_name/"._CMSAPI_LANGFILE."english.php");
		$this->bare_name = $bare_name;
		$this->c_classes_path = $this->v_classes_path = _CMSAPI_ABSOLUTE_PATH."/components/$full_name/";
		$this->c_classes_path .= 'controller-admin-classes/';
		$this->v_classes_path .= 'view-admin-classes/';
		if ($this->act = $this->interface->getParam ($_REQUEST, 'act', 'cpanel'));
		else $this->act = 'cpanel';
		if ($this->task = $this->interface->getParam($_REQUEST, 'task', 'list'));
		else $this->task = 'list';
		if ('cpanel' == $this->task) $this->act = 'cpanel';
		$_REQUEST['act'] = $this->act;
		$this->actname = strtoupper(substr($this->act,0,1)).strtolower(substr($this->act,1));
		$default_limit  = $this->interface->getUserStateFromRequest( "viewlistlimit", 'limit', $this->interface->getCfg('list_limit') );
		$this->limit = intval( $this->interface->getParam( $_REQUEST, 'limit', $default_limit ) );
		if (1 > $this->limit) $this->limit = 99999;
		$this->limitstart = intval( $this->interface->getParam( $_REQUEST, 'limitstart', 0 ) );
		$this->cfid = $this->interface->getParam($_REQUEST, 'cfid', array(0));
		if (is_array( $this->cfid )) {
			foreach ($this->cfid as $key=>$value) $this->cfid[$key] = intval($value);
			$this->currid=$this->cfid[0];
		}
		else $this->currid = intval($this->cfid);
		$control_class = $this->bare_name.'Admin'.$this->actname;
		if (class_exists($control_class)) {
			$controller = new $control_class($this, $bare_name, $full_name);
			$task = $this->task.'Task';
			if (method_exists($controller,$task)) $controller->$task();
			else trigger_error(sprintf(_CMSAPI_METHOD_NOT_PRESENT, $this->bare_name, $task, $control_class));
		}
		else {
			$view_class = 'list'.$this->actname.'HTML';
			$controller = new cmsapiAdminControllers($this, $bare_name, $full_name);
			$view = $this->newHTMLClassCheck ($view_class, $controller, 0, '');
			if ($view AND $this->checkCallable($view, 'view')) $view->view();
		}
	}

	// Not used in Glossary
	public function check_selection ($text) {
		if (!is_array($this->cfid) OR count( $this->cfid ) < 1) {
			echo "<script> alert('".$text."'); window.history.go(-1);</script>\n";
			exit;
		}
	}

	public function newHTMLClassCheck ($name, $controller, $total_items, $clist) {
		$controller->makePageNav($this, $total_items);
		if (class_exists($name)) return new $name ($controller, $this->limit, $clist, $this->full_name);
		trigger_error(sprintf(_CMSAPI_CLASS_NOT_PRESENT, $this->bare_name, $name));
		return false;
	}

	public function checkCallable ($object, $method) {
		if (method_exists($object, $method)) return true;
		$name = get_class($object);
		trigger_error(sprintf("Component $this->bare_name error: attempt to use non-existent method $method in $name", $this->bare_name, $method, $name));
		return false;
	}

}

class cmsapiAdminControllers {
	public $remUser = '';
	public $configuration = '';
	public $interface = '';
	public $admin = '';
	public $pageNav = '';
	public $idparm = 0;

	public function __construct ($admin, $bare_name, $full_name) {
		$this->admin = $admin;
		$this->configuration = aliroComponentConfiguration::getConfiguration($full_name);
		$this->interface = cmsapiInterface::getInstance($full_name);
		$this->remUser = $this->interface->getUser();
		$this->idparm = $this->interface->getParam($_REQUEST, 'id', 0);
	}

	public function makePageNav ($admin, $total) {
		$this->pageNav = $this->interface->makePageNav( $total, $admin->limitstart, $admin->limit );
	}

	// Not used in Glossary
	function backTask() {
		$this->interface->redirect( "index2.php?option=$this->full_name");
	}

	protected function publishToggle ($table, $idarray, $pvalue) {
		foreach ($idarray as $key=>$value) $idarray[$key] = intval($value);
		$idlist = implode(',', $idarray);
		if ($idlist) {
			$pvalue = intval($pvalue);
			$database = $this->interface->getDB();
			$database->setQuery("UPDATE $table SET published = $pvalue WHERE id IN ($idlist)");
			$database->query();
		}
	}

	// Not used in Glossary
	function error_popup ($message) {
		echo "<script> alert('".$message."'); window.history.go(-1); </script>\n";
	}

}