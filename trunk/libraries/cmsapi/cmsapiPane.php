<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

// Don't allow direct linking
if (!defined( '_VALID_MOS' ) AND !defined('_JEXEC')) die( 'Direct Access to this location is not allowed.' );

		class cmsapiPane extends mosTabs {
			function __construct () {
				parent::mosTabs(0);
	}
}