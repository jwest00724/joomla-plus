<?php
/**
* @version $Id: INSTALL.php 5975 2006-12-11 01:26:33Z robs $
* @package Joomla
* @copyright Copyright (C) 2005 Open Source Matters, 2011 Aliro Software Ltd. All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
* J-One-Plus is free software. This version may have been modified pursuant
* to the GNU General Public License, and as distributed it includes or
* is derivative of works licensed under the GNU General Public License or
* other free or open source software licenses.
* See COPYRIGHT.php for copyright notices and details.
*/

// no direct access
defined( '_VALID_MOS' ) or die( 'Restricted access' );
?>

REQUIREMENTS
------------

First you must have the base environment for J-One-Plus.
We have thoroughly tested J-One-Plus on: Linux, Free BSD, Mac OS X and Windows NT/2000.
Linux or one of the BSD's are recommended, but anything else that can run the
3 pieces of software listed below should do it.

Apache	-> http://www.apache.org
MySQL	-> http://www.mysql.com
PHP	-> http://www.php.net


SERVER CONFIGURATION
--------------------

You MUST ensure that PHP has been compiled with support for MySQL and Zlib
in order to successfully run J-One-Plus.

While we have reports that J-One-Plus works on IIS server we recommend Apache
for running J-One-Plus on Windows.


OPTIONAL COMPONENTS
-------------------

If you want support for SEF (Search Engine Friendly) URLs, you'll need mod_rewrite and the ability to
use local .htaccess files.


INSTALLATION
------------

1. DOWNLOAD J-One-Plus

	You can obtain the latest J-One-Plus release from:
		http://joomlaguru.net

	Copy the tar.gz file into a working directory e.g.

	$ cp JOnePlusVx.x.x-Stable.tar.gz /tmp/JOnePlus

	Change to the working directory e.g.

	$ cd /tmp/JOnePlus

	Extract the files e.g.

	$ tar -zxvf JOnePlusVx.x.x-Stable.tar.gz

	This will extract all J-One-Plus files and directories.  Move the contents
	of that directory into a directory within your web server's document
	root or your public HTML directory e.g.

	$ mv /tmp/JOnePlus/* /var/www/html

	Alternatively if you downloaded the file to your computer and unpacked
	it locally use a FTP program to upload all files to your server.
	Make sure all PHP, HTML, CSS and JS files are sent in ASCII mode and
	image files (GIF, JPG, PNG) in BINARY mode.


2. CREATE THE J-One-Plus DATABASE

	J-One-Plus will currently only work with MySQL.  In the following examples,
	"db_user" is an example MySQL user which has the CREATE and GRANT
	privileges.  You will need to use the appropriate user name for your
	system.

	First, you must create a new database for your J-One-Plus site e.g.

	$ mysqladmin -u db_user -p create JOnePlus

	MySQL will prompt for the 'db_user' database password and then create
	the initial database files.  Next you must login and set the access
	database rights e.g.

	$ mysql -u db_user -p

	Again, you will be asked for the 'db_user' database password.  At the
	MySQL prompt, enter following command:

	GRANT ALL PRIVILEGES ON JOnePlus.*
		TO nobody@localhost IDENTIFIED BY 'password';

	where:

	'JOnePlus' is the name of your database
	'nobody@localhost' is the userid of your webserver MySQL account
	'password' is the password required to log in as the MySQL user

	If successful, MySQL will reply with

	Query OK, 0 rows affected

	to activate the new permissions you must enter the command

	flush privileges;

	and then enter '\q' to exit MySQL.

	Alternatively you can use your web control panel or phpMyAdmin to
	create a database for J-One-Plus.


3. WEB INSTALLER

Finally point your web browser to http://www.mysite.com where the J-One-Plus web
based installer will guide you through the rest of the installation.


4. CONFIGURE J-One-Plus

You can now launch your browser and point it to your J-One-Plus site e.g.

	http://www.mysite.com -> Main Site
	http://www.mysite.com/administrator -> Admin

You can log into Admin using the username 'admin' along with the
password that was generated or you chose during the web based install.


J-One-Plus ADMINISTRATION
----------------------

Upon a new installation, your J-One-Plus website defaults to a very basic
configuration with only a few active components, modules and templates
(CMTs).

Use Admin to install and configure additional CMTs, add users, select
default language and much more.

Note that additional community-contributed CMTs and languages are
available via http://joomlaguru.net
