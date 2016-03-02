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
 * aliroDatabaseHandler figures out getting an interface
 *
 */

class aliroDatabaseHandler extends aliroBasicDatabase {

	public static function validateCredentials ($host, $user, $pass, $db) {
		$interface = aliroDatabaseHandler::getInterface($db);
		if (!$interface) return _ALIRO_DB_NO_INTERFACE;
		return ($interface->connect($host, $user, $pass, $db)) ? 0 : _ALIRO_DB_CONNECT_FAILED;
	}

	public static function getInterface ($dbname) {
		if (function_exists( 'mysqli_connect' )) return new mysqliInterface($dbname);
		if (function_exists( 'mysql_connect' )) return new mysqlInterface($dbname);
		return  null;
	}
}