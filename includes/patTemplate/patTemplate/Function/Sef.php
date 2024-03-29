<?php
/**
* @version $Id: Sef.php 87 2005-09-15 23:12:03Z eddieajau $
* @package Joomla
* @copyright Copyright (C) 2005 Open Source Matters, 2011 Aliro Software Ltd. All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
* J-One-Plus is free software. This version may have been modified pursuant
* to the GNU General Public License, and as distributed it includes or
* is derivative of works licensed under the GNU General Public License or
* other free or open source software licenses.
* See COPYRIGHT.php for copyright notices and details.
*/

class patTemplate_Function_Sef extends patTemplate_Function
{
   /**
	* name of the function
	* @access	private
	* @var		string
	*/
	var $_name	=	'Sef';

   /**
	* call the function
	*
	* @access	public
	* @param	array	parameters of the function (= attributes of the tag)
	* @param	string	content of the tag
	* @return	string	content to insert into the template
	*/
	function call( $params, $content )
	{
		/*
		if( !isset( $params['macro'] ) ) {
            return false;
		}
		*/

		return sefRelToAbs( $content );;
	}
}
?>