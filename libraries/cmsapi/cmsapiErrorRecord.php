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

class cmsapiErrorRecord extends aliroDatabaseRow {
	protected $DBclass = 'aliroDatabase';
	protected $tableName = '#__cmsapi_error_log';
	protected $rowKey = 'id';

}