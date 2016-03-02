<?php

/**************************************************************
* This file is part of Jaliro
* Copyright (c) 2008-10 Martin Brampton
* Issued as open source under GNU/GPL
* For support and other information, visit http://remository.com
* To contact Martin Brampton, write to martin@remository.com
*
* Please see jaliro.php for more details
*/

abstract class cmsapiUserAdmin {

	public function __construct ($control_name, $alternatives, $default, $title, $cname) {
		$interface = cmsapiInterface::getInstance($cname);
		$func = $interface->getParam ($_REQUEST, $control_name, $default);
		if (isset($alternatives[$func])) $method = $alternatives[$func];
		else $method = $func;
		$shortcname = false === strpos($cname, 'com_') ? $cname : substr($cname, 4);
		$qual_method = $shortcname.'_'.$method;
		$classname = $qual_method.'_Controller';
		$no_html = $interface->getParam($_REQUEST, 'no_html', 0);
		if (class_exists($classname)) {
			$controller = new $classname($this);
			$title = $this->getTitle();
			if (method_exists($controller,$qual_method)) {
				$interface->SetPageTitle($title);
				if (!$no_html) {
					echo "\n<!-- Start of $title HTML -->";
					echo "\n<div id='$shortcname'>";
				}
				$controller->$qual_method($func);
				if (!$no_html) {
					echo "\n</div>";
					echo "\n<!-- End of $title HTML -->";
				}
			}
			else {
				header ('HTTP/1.1 404 Not Found');
				trigger_error("Component $title error: attempt to use non-existent method $qual_method in $controller");
			}
		}
		else {
			header ('HTTP/1.1 404 Not Found');
			trigger_error("Component $title error: attempt to use non-existent class $classname");
		}
	}


}