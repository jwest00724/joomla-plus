<?php

class aliroCoreDatabase {
	
	public static function getInstance () {
		global $database;
		return $database;
	}
}