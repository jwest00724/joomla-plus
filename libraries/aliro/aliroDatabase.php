<?php

class aliroDatabase {
	
	public static function getInstance () {
		global $database;
		return $database;
	}
}