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
 * aliroHTTP is a class that is intended to wrap the eac httprequest classes
 * It needs more design and development to be useful.
 */


class aliroHTTP extends httpRequest {
	// This is merely a wrapper to allow the use of an aliro name, so as to ease
	// a possible future migration to a different HTTP handler.  No such move is
	// currently envisaged.
}