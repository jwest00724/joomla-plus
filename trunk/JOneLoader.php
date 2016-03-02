<?php

/*
 * J-One-Plus Loader class
 * 
 */

// Don't allow direct linking
if (!defined( '_VALID_MOS' ) AND !defined('_JEXEC')) die( 'Direct Access to this location is not allowed.' );

if (!defined('DS')) define( 'DS', DIRECTORY_SEPARATOR );
if (!defined('_CMSAPI_CMS_BASE')) define ('_CMSAPI_CMS_BASE', 'JOnePlus');
if (!defined('_CMSAPI_ABSOLUTE_PATH')) define ('_CMSAPI_ABSOLUTE_PATH', dirname(__FILE__));
if (!defined('_ALIRO_ABSOLUTE_PATH')) define ('_ALIRO_ABSOLUTE_PATH', dirname(__FILE__));
if (!defined('_ALIRO_SITE_BASE')) define ('_ALIRO_SITE_BASE', _ALIRO_ABSOLUTE_PATH);

class jaliroDebug {

	public static function trace ($error=true, $useHTML=true) {
	    static $counter = 0;
		$html = '';
		$newline = $useHTML ? '<br />' : "\n";
		foreach(debug_backtrace() as $back) {
		    if (isset($back['file']) AND $back['file']) {
			    $html .= $newline.$back['file'].':'.$back['line'];
			}
		}
		if ($error) $counter++;
		if (1000 < $counter) {
		    echo $html;
		    die ('Program killed - Probably looping');
        }
		return $html;
	}

	public static function dumpValues ($values) {
		foreach ($values as $name=>$value) {
			if (is_array($value)) $vars[] = sprintf('Array: %s size %d', $name, count($value));
			elseif (is_bool($value)) $vars[] = sprintf('Bool: %s -> %s', $name, $value ? 'true' : 'false');
			elseif (is_null($value)) $vars[] = 'Null: '.$name;
			elseif (is_object($value)) $vars[] = sprintf('Object: %s (%s)', $name, get_class($value));
			elseif (is_resource($value)) $vars[] = sprintf('Resource: %s -> %s', $name, intval($value));
			elseif (is_string($value)) sprintf("Value: %s -> '%s'", $name, $value);
			else $vars[] = sprintf('Value: %s -> %s', $name, $value);
 		}
		return isset($vars) ? implode('; ', $vars) : 'none';
	}
}

class aliroBase {
	
	public static function trace () {
		return jaliroDebug::trace();
	}
}

class JOneLoader {
	private static $aliroSpecial = array(
		'cachedSingleton',
		'mysqliInterface',
		'mysqlInterface'
	);
	private static $mosSpecial = array(
		'database',
		'Cpdf',
		'Cezpdf',
		'modules_html',
		'gacl',
		'gacl_api',
		'patHTML',
		'MENU_Default',
		'joomlaVersion'
	);
	private static $extclasses = array(
		'PEAR' => 'PEAR',
		'PEAR_Error' => 'PEAR',
		'PEAR5' => 'PEAR5',
		'Archive_Tar' => 'Tar',
		'InputFilter' => 'InputFilter',
		'httpRequest' => 'eac_httprequest/eac_httprequest.class',
		'httpRequest_auth' => 'eac_httprequest/eac_httprequest.auth',
		'httpRequest_cache' => 'eac_httprequest/eac_httprequest.cache',
		'curlRequest' => 'eac_httprequest/eac_httprequest.curl',
		'socketRequest' => 'eac_httprequest/eac_httprequest.socket',
		'streamRequest' => 'eac_httprequest/eac_httprequest.stream',
		'zipfile' => 'zipfile',
		'PclZip' => 'pcl/pclzip.lib',
		'GeSHi' => 'GeSHi'
	);

	/**
	 * Load the file for a class
	 *
	 * @access  public
	 * @param   string  $class  The class that will be loaded
	 * @return  boolean True on success
	 * @since   1.5
	 */
	public static function load( $class )
	{
		if (!class_exists($class, false)) {
			$lowerclass = strtolower($class); //force to lower case
			if ('mos' == substr($class,0,3) OR in_array($class, self::$mosSpecial)) {
				$file = dirname(__FILE__).'/libraries/mos/'.$class.'.php';
			}
			if ('aliro' == substr($class,0,5) OR in_array($class, self::$aliroSpecial)) {
				$file = dirname(__FILE__).'/libraries/aliro/'.$class.'.php';
			}
			elseif ('cmsapi' == substr($class,0,6)) {
				$file = dirname(__FILE__).'/libraries/cmsapi/'.$class.'.php';
			}
			elseif ('remository' == substr($class,0,10)) {
				$file = dirname(__FILE__).'/libraries/remository/'.$class.'.php';
			}
			elseif (isset(self::$extclasses[$class])) {
				$file = dirname(__FILE__).'/libraries/extclasses/'.self::$extclasses[$class].'.php';
			}
			if (empty($file) OR !is_readable($file)) {
				$file = cmsapiClassMapper::getInstance()->classPath($class);
				if (!$file) return false;
			}
			if (is_readable($file)) {
				require_once($file);
				if (class_exists($class, false)) return true;
			}
			return false;
		}
	}	
}

class aliroOffline {
	
	public function show ($error=0) {
		include (_ALIRO_ABSOLUTE_PATH.'/configuration.php');
		foreach ($GLOBALS as $name=>$value) if ('mosConfig' == substr($name,0,9)) $$name = $value;
		if ($error) $mosSystemError = $error;
		include (_ALIRO_ABSOLUTE_PATH.'/offline.php');
	}
}

class aliroCore {
	private static $instance = null;
	
	public static function getInstance () {
		return self::$instance instanceof self ? self::$instance : self::$instance = new self();
	}
	
	public function getCfg ($name) {
		return cmsapiInterface::getInstance('com_cmsapi')->getCfg($name);
	}
}

spl_autoload_register(array('JOneLoader', 'load'));
