<?php

$thisdir = dirname(__FILE__);
if (_ALIRO_IS_ADMIN) require_once($thisdir.'/mosAdminPageNav.php');
else require_once($thisdir.'/mosUserPageNav.php');