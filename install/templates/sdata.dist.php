<?php
/**
* All information in order to connect to database are going through here.
*
* Be careful if you are changing data's in this file.
*
* @copyright	http://www.xoops.org/ The XOOPS Project
* @copyright	XOOPS_copyrights.txt
* @copyright	http://www.impresscms.org/ The ImpressCMS Project
* @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
* @package		installer
* @since		1.0
* @author		marcan <marcan@impresscms.org>
* @author	   Sina Asghari (aka stranger) <pesian_stranger@users.sourceforge.net>
* @version		$Id: sdata.dist.php 8568 2009-04-11 13:15:53Z icmsunderdog $
*/

// Database Hostname
// Hostname of the database server. If you are unsure, 'localhost' works in most cases.
define('SDATA_DB_HOST', '');

// Database Username
// Your database user account on the host
define('SDATA_DB_USER', '');

// Database Password
// Password for your database user account
define('SDATA_DB_PASS', '');

// Database Name
// The name of database on the host. The installer will attempt to create the database if not exist
define('SDATA_DB_NAME', '');

// Table Prefix
// This prefix will be added to all new tables created to avoid name conflict in the database. If you are unsure, just use the default 'icms'
define('SDATA_DB_PREFIX', '');

// Password Salt Key $mainSalt
// This salt will be appended to passwords in the icms_encryptPass() function.
// Do NOT change this once your site is Live, doing so will invalidate everyones Password.
define('SDATA_DB_SALT', '');
