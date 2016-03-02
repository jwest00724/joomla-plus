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
 * aliroObjectSorter sorts an array of objects
 */

class aliroObjectSorter {
    var $_keyname = '';
    var $_direction = 0;
    var $_object_array = array();

    public function __construct (&$a, $k, $sort_direction=1) {
        $this->_keyname = $k;
        $this->_direction = $sort_direction;
        $this->_object_array =& $a;
        $this->sort();
    }

    // This is not genuinely public, but has to be declared so for the callback
    public function aliroObjectCompare (&$a, &$b) {
        $key = $this->_keyname;
        if ($a->$key > $b->$key) return $this->_direction;
        if ($a->$key < $b->$key) return -$this->_direction;
        return 0;
    }

    private function sort () {
        usort($this->_object_array, array($this,'aliroObjectCompare'));
    }

}
