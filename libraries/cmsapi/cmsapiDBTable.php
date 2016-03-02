<?php

/**************************************************************
* This file is part of A CMS API
* Copyright (c) 2008-10 Martin Brampton
* Issued as open source under GNU/GPL
* For support and other information, visit http://remository.com
* To contact Martin Brampton, write to martin@remository.com
*
* Please see glossary.php for more details
*/

if (basename(@$_SERVER['REQUEST_URI']) == basename(__FILE__)) die ('This software is for use within a larger system');

class cmsapiDBTable extends mosDBTable {
	function cmsapiDBTable ($table, $key, $db) {
		parent::mosDBTable ($table, $key, $db);
	}
}