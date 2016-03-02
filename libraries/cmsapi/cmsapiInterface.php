<?php

/*******************************************************************
* This file is a generic interface to Aliro, Joomla 1.5+, Joomla 1.0.x and Mambo
* Copyright (c) 2008-10 Martin Brampton
* Issued as open source under GNU/GPL
* For support and other information, visit http://acmsapi.org
* To contact Martin Brampton, write to martin@remository.com
*
*/

// Don't allow direct linking
if (!defined( '_VALID_MOS' ) AND !defined('_JEXEC')) die( 'Direct Access to this location is not allowed.' );

// Aliro error levels
if (!defined('_ALIRO_ERROR_INFORM')) define ('_ALIRO_ERROR_INFORM', 0);
if (!defined('_ALIRO_ERROR_WARN')) define ('_ALIRO_ERROR_WARN', 1);
if (!defined('_ALIRO_ERROR_SEVERE')) define ('_ALIRO_ERROR_SEVERE', 2);
if (!defined('_ALIRO_ERROR_FATAL')) define ('_ALIRO_ERROR_FATAL', 3);

if (!defined('_ALIRO_HTML_CACHE_TIME_LIMIT')) define('_ALIRO_HTML_CACHE_TIME_LIMIT', 600);
if (!defined('_ALIRO_HTML_CACHE_SIZE_LIMIT')) define('_ALIRO_HTML_CACHE_SIZE_LIMIT', 100000);
if (!defined('_ALIRO_OBJECT_CACHE_TIME_LIMIT')) define('_ALIRO_OBJECT_CACHE_TIME_LIMIT', 600);
if (!defined('_ALIRO_OBJECT_CACHE_SIZE_LIMIT')) define('_ALIRO_OBJECT_CACHE_SIZE_LIMIT', 100000);

if (!defined('_MOS_NOTRIM')) define( '_MOS_NOTRIM', 0x0001 );  		// prevent getParam trimming input
if (!defined('_MOS_ALLOWHTML')) define( '_MOS_ALLOWHTML', 0x0002 );		// cause getParam to allow HTML - purified on user side
if (!defined('_MOS_ALLOWRAW')) define( '_MOS_ALLOWRAW', 0x0004 );		// suppresses forcing of integer if default is numeric

if (!defined('_CMSAPI_CHARSET')) define ('_CMSAPI_CHARSET', 'utf-8');
if ('utf-8' == _CMSAPI_CHARSET) define ('_CMSAPI_LANGFILE', 'language-utf/');
else define ('_CMSAPI_LANGFILE', 'language/');

define ('_CMSAPI_PARAMETER_CLASS', 'JParameter');


class cmsapiInterface {
	protected static $instances = array();
	protected static $Itemids = array();
	protected static $langdone = array();

	protected $cname = '';

	protected $magic_quotes_value = 0;
	protected $mainframe;
	protected $absolute_path;
	protected $ipaddress = '';
	protected $live_site;
	protected $cachepath;
	protected $lang;
	protected $sitename;
	protected $apiClasses = array(
	'aliroBasicCache' => 'cmsapi.cache',
	'aliroSimpleCache' => 'cmsapi.cache',
	'cachedSingleton' => 'cmsapi.cache',
	'aliroSingletonObjectCache' => 'cmsapi.cache',
	'aliroExtendedDatabase' => 'cmsapi.database',
	'cmsapiDatabase' => 'cmsapi.database',
	'aliroDBGeneralRow' => 'cmsapi.database',
	'aliroDatabaseRow' => 'cmsapi.database',
	'aliroFileManager' => 'cmsapi.filemanager',
	'aliroDirectory' => 'cmsapi.filemanager',
	'cmsapiUserAdmin' => 'cmsapi.useradmin',
	'cmsapiAdminManager' => 'cmsapi.adminmanager',
	'cmsapiFileManager' => 'cmsapi.filemanager',
	'cmsapiDirectory' => 'cmsapi.filemanager',
	'cmsapiDatabaseRow' => 'cmsapi.databaserow',
	'cmsapiSimpleCache' => 'cmsapi.cmsapicache'
	);
	protected $applicationClasses = array();

	protected function __construct ($cname) {
		global $mosConfig_live_site, $mosConfig_lang, $mosConfig_cachepath;
		$this->cname = $cname;
		$this->absolute_path = _CMSAPI_ABSOLUTE_PATH;
		$this->getMainFrame();
		$this->live_site = $mosConfig_live_site;
		if ('/' == substr($this->live_site, -1)) $this->live_site = substr($this->live_site, 0, -1);
		$this->admin_site = $this->live_site.'/administrator';
		$this->lang = $mosConfig_lang;
		$this->cachepath = $mosConfig_cachepath;
		// Is magic quotes on?
		if (get_magic_quotes_gpc()) {
		 	// Yes? Strip the added slashes
			$this->remove_magic_quotes($_REQUEST);
			$this->remove_magic_quotes($_GET);
			$this->remove_magic_quotes($_POST);
			$this->remove_magic_quotes($_FILES, 'name');
		}
		$this->magic_quotes_value = ini_get('magic_quotes_runtime');
		ini_set('magic_quotes_runtime', 0);
	}

	public function __destruct () {
		ini_set('magic_quotes_runtime',$this->magic_quotes_value);
	}

	public static function getInstance ($cname) {
		return (@self::$instances[$cname] instanceof self) ? self::$instances[$cname] : self::$instances[$cname] = new self($cname);
	}

	public function getVersion () {
		return '2.0.0';
	}

	public function getCMSVersion () {
		$version = new joomlaVersion();
		return $version->getShortVersion();
	}

	public function loadLanguageFile ($configuration=null, $forcelang=false, $alternative='') {
		if (empty(self::$langdone[$this->cname])) {
			$lang = $forcelang ? (empty($configuration->language) ? $this->getCfg('lang') : $configuration->language) : $this->getCfg('lang');
			// May need config values for language files
			if (is_object($configuration)) foreach (get_object_vars($configuration) as $k=>$v) $$k = $configuration->$k;
			if ($alternative AND is_readable(_CMSAPI_ABSOLUTE_PATH."/$alternative/$lang.php")) require_once(_CMSAPI_ABSOLUTE_PATH."/$alternative/$lang.php");
			if (is_readable(_CMSAPI_ABSOLUTE_PATH."/components/$this->cname/"._CMSAPI_LANGFILE.$lang.'.php')) require_once(_CMSAPI_ABSOLUTE_PATH."/components/$this->cname/"._CMSAPI_LANGFILE.$lang.'.php');
			require_once(_CMSAPI_ABSOLUTE_PATH."/components/$this->cname/"._CMSAPI_LANGFILE."english.php");
			self::$langdone[$this->cname] = true;
		}
	}

	protected function remove_magic_quotes (&$array, $keyname=null) {
		foreach ($array as $k => $v) {
			if (is_array($v)) $this->remove_magic_quotes($array[$k], $keyname);
			elseif (is_object($v)) continue;
			elseif (empty($keyname) OR $k == $keyname) $array[$k] = stripslashes($v);
		}
	}

	public function indexFileName ($name='index2') {
		return ('Aliro' == _CMSAPI_CMS_BASE OR 'Joomla' == _CMSAPI_CMS_BASE) ? 'index.php' : $name.'.php';
	}

	public function doPurify ($string) {
		if ('Aliro' == _CMSAPI_CMS_BASE) return aliroRequest::getInstance()->doPurify($string);
		elseif ('Joomla' == _CMSAPI_CMS_BASE) return JFilterInput::clean($string);
		return $string;
	}

	public function class_exists ($string, $autoload=false) {
		return class_exists($string, $autoload);
	}

	protected function getMainFrame () {
		global $mainframe;
		if (!is_object($this->mainframe)) {
			$this->mainframe = $mainframe;
		}
	}

	public function getItemid ($component='com_remository') {
		if (isset(self::$Itemids[$component])) return self::$Itemids[$component];
		$database = $this->getDB();
		$callingID = $this->getParam($_REQUEST, 'Itemid', 0);
		if ($callingID) {
			$database->setQuery("SELECT link FROM #__menu WHERE id = $callingID");
			$link = $database->loadResult();
			if (false !== strpos($link, 'option='.$component)) {
				self::$Itemids[$component] = $Itemid = $callingID;
				return $Itemid;
			}
		}
		$database->setQuery("SELECT id, (CASE menutype WHEN 'mainmenu' THEN 1 WHEN 'topmenu' THEN 2 WHEN 'othermenu' THEN 3 ELSE 99 END) menorder,"
			." (CASE type WHEN 'url' THEN 2 ELSE 1 END) typorder"
			." FROM #__menu WHERE link LIKE 'index.php?option=$component%' AND type LIKE 'component%' AND published=1 ORDER BY typorder, menorder");
		self::$Itemids[$component] = $Itemid = $database->loadResult();
		return $Itemid;
	}

	public function getIP() {
		if ($this->ipaddress) return $this->ipaddress;
	    $ip = false;
	    if (!empty($_SERVER['HTTP_CLIENT_IP'])) $ip = $_SERVER['HTTP_CLIENT_IP'];
	    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
	        $ips = explode (', ', $_SERVER['HTTP_X_FORWARDED_FOR']);
	        if ($ip != false) {
	            array_unshift($ips,$ip);
	            $ip = false;
	        }
	        $count = count($ips);
	        // Exclude IP addresses that are reserved for LANs
	        for ($i = 0; $i < $count; $i++) {
	            if (!preg_match("/^(10|172\.16|192\.168)\./i", $ips[$i])) {
	                $ip = $ips[$i];
	                break;
	            }
	        }
	    }
	    $this->ipaddress = (false == $ip AND isset($_SERVER['REMOTE_ADDR'])) ? $_SERVER['REMOTE_ADDR'] : $ip;
	    return $this->ipaddress;
	}

	public function getParameters () {
		$menuhandler = mosMenuHandler::getInstance();
		$menu = $menuhandler->getMenuByID($this->getCurrentItemid());
		$params = new mosParameters ($menu->params);
		return $params;
	}

	public function setBase ($ref='') {
		if ('Joomla' == _CMSAPI_CMS_BASE) {
			if (!$ref) $ref = JUri::root();
			JFactory::getDocument()->setBase($ref);
		}
	}
	
	public function getLocale () {
		if ('Joomla' == _CMSAPI_CMS_BASE) {
			$language = JFactory::getLanguage();
		    return $language->getLocale();
		}
	}

	protected function rawGetCfg ($string) {
		if (isset($this->$string)) return $this->$string;
		if (('Aliro' == _CMSAPI_CMS_BASE OR method_exists($this->mainframe, 'getCfg')) AND !is_null($result = $this->mainframe->getCfg($string))) return $result;
		else {
			if (!empty($this->$string)) return $this->$string;
			if ('Joomla' == _CMSAPI_CMS_BASE) {
				$this->live_site = substr(JURI::root(), 0, -1);
				$lang = JFactory::getLanguage();
				$this->lang = $lang->get('backwardlang');
				$this->cachepath = JPATH_CACHE;
				return (empty($this->$string)) ? '' : $this->$string;
			}
			if ('Aliro' == _CMSAPI_CMS_BASE) die ('Could not find configuration item '.$string);
			include ($this->absolute_path.'/configuration.php');
			$this->live_site = $mosConfig_live_site;
			$this->lang = $mosConfig_lang;
			$this->sitename = $mosConfig_sitename;
			$configitem = 'mosConfig_'.$string;
			$this->$string = $$configitem;
			return $$configitem;
		}
	}

	public function getCfg ($string) {
		$result = $this->rawGetCfg($string);
		if ('live_site' == $string OR 'absolute_path' == $string) {
			if ('/' == substr($result,-1)) $result = substr($result,0,-1);
		}
		return $result;
	}

	public function isFrontPage () {
		return 'Joomla' == _CMSAPI_CMS_BASE ? (JRequest::getVar('view') == 'frontpage') :
		('com_frontpage' == $this->getParam($_REQUEST, 'option'));
	}

	public function getTemplate () {
		return $this->mainframe->getTemplate();
	}

	public function appendPathWay ($name, $link) {
		if ('Joomla' == _CMSAPI_CMS_BASE) {
			JFactory::getApplication()->getPathway()->addItem($name, $link);
		}
		elseif (defined('_MAMBO_46PLUS')) {
			mosPathway::getInstance()>addItem($name, $link);
		}
		else {
			$url = $this->sefRelToAbs($link);
			$url = preg_replace ('/\&([^amp;])/', '&amp;$1', $url);
			$this->mainframe->appendPathWay('<a href="'.$url.'">'.$name.'</a>');
		}
	}

	public function getDB () {
		if ('Aliro' == _CMSAPI_CMS_BASE) return aliroDatabase::getInstance();
		return aliroDatabase::getInstance();
	}

	public function getEscaped ($string) {
		$database = $this->getDB();
		return $database->getEscaped($string);
	}

	public function getParam ($arr, $name, $def='', $mask=0) {
		if ('Aliro' == _CMSAPI_CMS_BASE) return aliroRequest::getInstance()->getParam($arr, $name, $def, $mask);
	    if (isset( $arr[$name] )) {
	        if (is_array($arr[$name])) foreach ($arr[$name] as $key=>$element) {
	        	$result[$key] = $this->getParam ($arr[$name], $key, $def, $mask);
	        }
	        else {
	            $result = $arr[$name];
	            if (!($mask&_MOS_NOTRIM)) $result = trim($result);
	            if (!is_numeric($result)) {
	                if (!($mask&_MOS_ALLOWRAW) AND is_numeric($def)) $result = $def;
	                elseif ($result) {
	                	if ($mask & _MOS_ALLOWHTML) $result = $this->doPurify($result);
		                else {
							$result = strip_tags($result);
						}
	                }
	            }
	        }
	        return $result;
	    }
	    return $def;
	}

	public function getUser () {
		if ('Joomla' == _CMSAPI_CMS_BASE) $my = JFactory::getUser();
		elseif (method_exists('mamboCore','get')) {
			if (mamboCore::is_set('currentUser')) $my = mamboCore::get('currentUser');
			else $my = aliroUser::getInstance();
		}
		else global $my;
		return $my;
	}

	public function getIdentifiedUser ($id) {
		if ('Joomla' == _CMSAPI_CMS_BASE) {
			$my = new JUser($id);
			return $my;
		}
		$database = $this->getDB();
		$my = new mosUser($database);
		$my->load($id);
		return $my;
	}

	public function getCurrentItemid () {
		if (method_exists('mamboCore','get')) $Itemid = mamboCore::get('Itemid');
		else global $Itemid;
		return intval($Itemid);
	}

	public function getFromConfig ($component, $name, $default='') {
		$config = JFactory::getApplication()->getPageParameters($component);
		return $config->get($name, $default);
	}

	public function getUserStateFromRequest ($var_name, $req_name, $var_default=null) {
		$this->getMainFrame();
		$mainframe = $this->mainframe;
		if (isset($var_default) AND is_numeric($var_default)) $forcenumeric = true;
		else $forcenumeric = false;
		if (isset($_REQUEST[$req_name])) {
			if ($forcenumeric) $mainframe->setUserState($var_name, intval($_REQUEST[$req_name]));
			else $mainframe->setUserState($var_name, $_REQUEST[$req_name]);
		}
        elseif (isset($var_default) AND !isset($mainframe->userstate[$var_name])) $mainframe->setUserState($var_name, $var_default);
        return $mainframe->getUserState($var_name);
	}

	public function getPath ($name, $option='') {
		if ('Joomla' == _CMSAPI_CMS_BASE) return JApplicationHelper::getPath($name, $option);
		$this->getMainFrame();
		return $this->mainframe->getPath($name, $option);
	}

	public function setPageTitle ($title) {
		$this->getMainFrame();
		if (method_exists($this->mainframe, 'SetPageTitle')) $this->mainframe->SetPageTitle($title);
	}

	public function prependMetaTag ($tag, $content) {
		$this->getMainFrame();
		if (method_exists($this->mainframe, 'prependMetaTag')) $this->mainframe->prependMetaTag($tag, $content);
	}

	public function addCustomHeadTag ($tag) {
		$this->getMainFrame();
		$this->mainframe->addCustomHeadTag($tag);
	}

	public function addMetaTag ($name, $content, $prepend='', $append='') {
		$this->getMainFrame();
		$this->mainframe->addMetaTag($name, $content, $prepend='', $append='');
	}

	public function redirect ($url, $msg='') {
    	if ('Joomla' == _CMSAPI_CMS_BASE) $this->mainframe->redirect($url, $msg);
    	else mosRedirect($url, $msg);
    }

    function makePageNav ($total, $limitstart, $limit) {
		$pagenav = new cmsapiPageNav($total, $limitstart, $limit, $this->cname);
    	return $pagenav;
    }

    public function triggerMambots ($group, $event, $args=null, $doUnpublished=false) {
    	global $_MAMBOTS;
    	if ('Joomla' == _CMSAPI_CMS_BASE) {
    		$handler = JDispatcher::getInstance();
    		call_user_func(array('JPluginHelper', 'importPlugin'), $group);
    	}
    	elseif ('Aliro' == _CMSAPI_CMS_BASE) $handler = aliroMambotHandler::getInstance();
    	else {
    		$handler = $_MAMBOTS;
    		$handler->loadBotGroup($group);
    	}
    	return $handler->trigger($event, $args, $doUnpublished);
    }

    public function invokeContentPlugins ($text) {
		$class = _CMSAPI_PARAMETER_CLASS;
		$param = new $class();
		$row = new stdClass();
		$row->text = $text;
		$results = $this->triggerMambots('onPrepareContent', array($row, $param), true);
		return $row->text;
    }

    public function getEditorContents ($hiddenField) {
    	if ('Joomla' == _CMSAPI_CMS_BASE) {
    		$editor = JFactory::getEditor();
    		$editor->getContent ($hiddenField);
    	}
    	else getEditorContents ($hiddenField, $hiddenField);
    }

	public function editorArea($name, $content, $hiddenField, $width, $height, $col, $row) {
		echo $this->editorAreaText($name, $content, $hiddenField, $width, $height, $col, $row);
	}

	public function editorAreaText ($name, $content, $hiddenField, $width, $height, $col, $row) {
		if ('Joomla' == _CMSAPI_CMS_BASE) {
			$editor = JFactory::getEditor();
			// Last parameter controls display of buttons
			return $editor->display($hiddenField, $content, $width, $height, $col, $row, false);
		}
		else {
			$results = $this->triggerMambots('onEditorArea', array( $name, $content, $hiddenField, $width, $height, $col, $row ) );
			$html = '';
			foreach ($results as $result) $html .= trim($result);
			return $html;
		}
	}

	public function makeImageURI ($imageName, $width=32, $height=32, $title='') {
		$element = '<img src="';
		$element .= $this->getCfg('live_site')."/components/{$this->cname}/images/".$imageName;
		$element .= '" width="';
		$element .= $width;
		$element .= '" height="';
		$element .= $height;
		if ($title) {
			$element .= '" title="';
			$element .= $title;
		}
		$element .= '" alt="" />';
		return $element;
	}

	public function objectSort ($objarray, $property, $direction='asc') {
		$GLOBALS['cmsapiSortProperty'] = $property;
		$GLOBALS['cmsapiDirection'] = strtolower($direction);
		usort( $objarray, create_function('$a,$b','
	        global $cmsapiSortProperty, $cmsapiDirection;
	        $result = strcmp($a->$cmsapiSortProperty, $b->$cmsapiSortProperty);
	        return \'asc\' == $cmsapiDirection ? $result : -$result;' ));
		return $objarray;
	}

	public function sefRelToAbs ($link) {
		if ('Joomla' == _CMSAPI_CMS_BASE) $seflink = JRoute::_($link);
		else $seflink = sefRelToAbs($link);
		return 'http' == substr($seflink, 0, 4) ? $seflink : $this->getCfg('live_site').$seflink;

	}

	public function sendMail ($from, $fromname, $recipient, $subject, $body, $mode=0, $cc=NULL, $bcc=NULL, $attachment=NULL, $replyto=NULL, $replytoname=NULL ) {
		if ('Joomla' == _CMSAPI_CMS_BASE) return JUTility::sendMail($from, $fromname, $recipient, $subject, $body, $mode, $cc, $bcc, $attachment, $replyto, $replytoname );
		else return mosMail ($from, $fromname, $recipient, $subject, $body, $mode, $cc, $bcc, $attachment, $replyto, $replytoname);
	}

	public static function addDirectories ($directories, $cname, $admin=false, $initialize=false) {
		cmsapiClassMapper::getInstance()->addDirectories ($directories, $cname, $admin, $initialize);
	}
}
