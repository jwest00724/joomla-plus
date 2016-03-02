<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

if (basename(@$_SERVER['REQUEST_URI']) == basename(__FILE__)) die ('This software is for use within a larger system');

class cmsapiMenuBar extends mosMenuBar {}
