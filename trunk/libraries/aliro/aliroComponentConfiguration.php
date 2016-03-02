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
 * aliroComponentConfiguration is able to hold configuration data for a component.
 * A new instance of the class is created for each distinct component that uses its
 * services.  The class keeps track of the various instances in a class variable.
 *
 */

class aliroComponentConfiguration {
	protected static $instance = array();
	protected static $cache = null;
	protected $aliroConfigComponent = '';
	protected $DBname = 'aliroCoreDatabase';

	protected function __construct ($cname) {
		$this->aliroConfigComponent = $cname;
		$path = _CMSAPI_ABSOLUTE_PATH."/components/{$cname}/install_settings.php";
		if (is_readable($path)) require($path);
	}

	// getInstance is deprecated in favour of getConfiguration so as to avoid
	// giving the impression the class is a singleton
    public static function getInstance ($cname='', $update=false) {
		return self::getConfiguration($cname, $update);
	}

	// Use of the symbol _THIS_COMPONENT_NAME is deprecated, a parameter should be passed
    public static function getConfiguration ($cname='', $update=false) {
		if (!$cname AND defined('_THIS_COMPONENT_NAME')) $cname = _THIS_COMPONENT_NAME;
		if (!$cname OR false !== strpos($cname, '..')) {
			echo jaliroDebug::trace();
			die ('Invalid component name in aliroComponent Configuration');
		}
		if (empty(self::$instance)) {
			self::$cache = new aliroSimpleCache('aliroComponentConfiguration');
			$cached = self::$cache->get('instances');
			if (!empty($cached)) self::$instance = $cached;
		}
        if (empty(self::$instance[$cname])) {
			$config = new self($cname);
			$config->loadFromDB($update);
		}
        return self::$instance[$cname];
    }

	protected function loadFromDB ($update) {
		$database = call_user_func(array($this->DBname, 'getInstance'));
		$database->setQuery("SELECT configuration FROM #__cmsapi_configurations WHERE component = '$this->aliroConfigComponent'");
		$configdata = $database->loadResult();
		if ($configdata) {
			$configdata = unserialize(base64_decode($configdata));
        	if ($update) $this->update($configdata);
			else {
				self::$instance[$this->aliroConfigComponent] = $configdata;
				self::saveCache();
				return;
			}
		}
		$this->save();
	}
	
	public function save () {
		$configdata = base64_encode(serialize($this));
		// Need to construct SQL dynamically
		$database = call_user_func(array($this->DBname, 'getInstance'));
		$database->doSQL("INSERT INTO #__cmsapi_configurations (component, configuration) VALUES ('$this->aliroConfigComponent', '$configdata') ON DUPLICATE KEY UPDATE configuration = '$configdata'");
		self::$instance[$this->aliroConfigComponent] = $this;
		self::saveCache();
	}

	protected function update ($configdata) {
		foreach (get_object_vars($configdata) as $key=>$value) $this->$key = $value;
		$this->save();
	}

	public function delete () {
		$database = call_user_func(array($this->DBname, 'getInstance'));
		$database->doSQL("DELETE FROM #__cmsapi_configurations WHERE component = '$this->aliroConfigComponent'");
		$this->deleteCache();
	}

	public function deleteCache () {
		unset (self::$instance[$this->aliroConfigComponent]);
		self::saveCache();
	}

	protected static function saveCache () {
		if (empty(self::$cache)) self::$cache = new aliroSimpleCache('aliroComponentConfiguration');
		self::$cache->save(self::$instance, 'instances');
	}
	
	public function displayEditConfiguration ($xml) {
		$params = new aliroParameters();
		$params->loadXMLString($xml);
		$params->setValues($this);
		return $params->render();
	}
	
	public function saveConfigurationData ($xml) {
		$params = isset($_POST['params']) ? $_POST['params'] : '';
		$pobject = new aliroParameters($params);
		$pobject->loadXMLString($xml);
		$pobject->loadObject($this);
		$this->save();
	}
}