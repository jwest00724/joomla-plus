<?php

/*******************************************************************************
 * Aliro - the modern, accessible content management system
 *
 * Aliro is open source software, free to use, and licensed under GPL.
 * You can find the full licence at http://www.gnu.org/copyleft/gpl.html GNU/GPL
 *
 * The author freely draws attention to the fact that Aliro derives from Mambo,
 * software that is controlled by the Mambo Foundation.  However, this section
 * of code is totally new.  If it should contain any fragments that are similar
 * to Mambo, please bear in mind (1) there are only so many ways to do things
 * and (2) the author of Aliro is also the author and copyright owner for large
 * parts of Mambo 4.6.
 *
 * Tribute should be paid to all the developers who took Mambo to the stage
 * it had reached at the time Aliro was created.  It is a feature rich system
 * that contains a good deal of innovation.
 *
 * Your attention is also drawn to the fact that Aliro relies on other items of
 * open source software, which is very much in the spirit of open source.  Aliro
 * wishes to give credit to those items of code.  Please refer to
 * http://aliro.org/credits for details.  The credits are not included within
 * the Aliro package simply to avoid providing a marker that allows hackers to
 * identify the system.
 *
 * Copyright in this code is strictly reserved by its author, Martin Brampton.
 * If it seems appropriate, the copyright will be vested in the Aliro Organisation
 * at a suitable time.
 *
 * Copyright (c) 2007 Martin Brampton
 *
 * http://aliro.org
 *
 * counterpoint@aliro.org
 *
 * This files consists only of definitions of symbols and interfaces
 *
 */

// Set the version
$_VERSION = new joomlaVersion();
$version = $_VERSION->PRODUCT .' '. $_VERSION->RELEASE .'.'. $_VERSION->DEV_LEVEL .' '
. $_VERSION->DEV_STATUS
.' [ '.$_VERSION->CODENAME .' ] '. $_VERSION->RELDATE .' '
. $_VERSION->RELTIME .' '. $_VERSION->RELTZ;

// Standard definitions for Aliro
// define ('_ALIRO_CLASS_BASE', '/var/www/OutsideDocRoot');

DEFINE('_ALIRO_REGEXP_URL','/(https?|ftps?)\:\/\/[a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,3}(:[a-zA-Z0-9]*)?\/?([a-zA-Z0-9\-\._\?\,\'\/\\\+&%\$#\=~])*[^\.\,\)\(\s]/');
DEFINE('_ALIRO_REGEXP_IP','/(https?|ftps?)\:\/\/\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}\/?([a-zA-Z0-9\-\._\?\,\'\/\\\+&%\$#\=~])*[^\.\,\)\(\s]/');

// define ('_ALIRO_IS_PRESENT', 1);

// Define the class to handle storage of cached data, must inherit from aliroCacheStorage
define ('_ALIRO_CACHE_HANDLER', 'aliroCacheDiskStorage');

// Define values for login validation results
define ('_ALIRO_LOGIN_GOOD', 0);
define ('_ALIRO_LOGIN_FAILED', 1);
define ('_ALIRO_LOGIN_PROHIBITED', 2);

// Allows extensions written for Joomla 1.5+ to run
// define ('_JEXEC', 1);
// define ('JPATH_BASE', _ALIRO_ABSOLUTE_PATH);

// Used with the getParam method of aliroAbstractRequest
define( '_MOS_NOTRIM', 0x0001 );  		// prevent getParam trimming input
define( '_MOS_ALLOWHTML', 0x0002 );		// cause getParam to allow HTML - purified on user side
define( '_MOS_ALLOWRAW', 0x0004 );		// suppresses forcing of integer if default is numeric
define( '_MOS_NOSTRIP', 0x0008 );		// suppress stripping of magic quotes

// Aliro error levels
define ('_ALIRO_ERROR_INFORM', 0);
define ('_ALIRO_ERROR_WARN', 1);
define ('_ALIRO_ERROR_SEVERE', 2);
define ('_ALIRO_ERROR_FATAL', 3);

// Aliro form checking mechanism
define ('_ALIRO_FORM_CHECK_OK', 0);
define ('_ALIRO_FORM_CHECK_REPEAT', 1);
define ('_ALIRO_FORM_CHECK_FAIL', 2);
define ('_ALIRO_FORM_CHECK_NULL', 3);
define ('_ALIRO_FORM_CHECK_EXPIRED', 4);

// Database error conditions
define ('_ALIRO_DB_NO_INTERFACE', 1);
define ('_ALIRO_DB_CONNECT_FAILED', 2);

define ('_ALIRO_USER_SIDE', 1);
define ('_ALIRO_ADMIN_SIDE', 2);

// Menu options
define ('_ALIRO_SHOW_NO_SUBMENUS', 0);
define ('_ALIRO_SHOW_ACTIVE_SUBMENUS', 1);
define ('_ALIRO_SHOW_ALL_SUBMENUS', 2);

// Default user side page size
DEFINE ('_ALIRO_PAGE_NAV_DISPLAY_PAGES', 10);

// Cache size and time limits
define ('_ALIRO_OBJECT_CACHE_SIZE_LIMIT', 150000);
define ('_ALIRO_OBJECT_CACHE_TIME_LIMIT', 3600);

define ('_ALIRO_HTML_CACHE_SIZE_LIMIT', 100000);
define ('_ALIRO_HTML_CACHE_TIME_LIMIT', 600);

define ('_ALIRO_SEF_MAX_CACHE_SIZE', 500000);
define ('_ALIRO_SEF_MAX_CACHE_TIME', 3600);

define ('_ALIRO_AUTHORISER_SESSION_CACHE_TIME', 600);

define ('_ALIRO_DATABASE_CACHE_TIME', 3600);

// How long to keep records of material checked for spam
define ('_ALIRO_KEEP_DAYS_OF_SPAM', 7);

interface ifAliroModule {

	public function activate ($module, &$content, $area, $params);

}

interface ifAliroTemplate {
	public static function defaultModulePosition ();
	public function positions ();
	public function render ();
}

interface ifAliroMainTemplate {
	public static function defaultModulePosition ();
	public function positions ();
	public function render ();
	public function component_render ();
}

interface ifAliroLocalTemplate {
	public static function defaultModulePosition ();
	public function positions ();
	public function render ();
}

interface ifTemplateRenderer {
	public function display ($template='');
	public function fetch ($template='');
	public function getengine ();
	public function addvar ($key, $value);
	public function addbyref ($key, &$value);
	public function getvars ($name);
	public function setdir ($dir);
	public function settemplate ($template);
}