<?php
// $Id: version.php 19775 2010-07-11 18:54:25Z malanciault $
//%%%%%%	Admin Module Name  Version 	%%%%%
if (!defined('_AM_DBUPDATED')) {define("_AM_DBUPDATED","Database Updated Successfully!");}
define("_AM_VERSION_TITLE", 'ImpressCMS Version Checker');
define("_AM_VERSION_NO_UPDATE", 'You are running the latest version of ImpressCMS !');
define("_AM_VERSION_UPDATE_NEEDED", 'There is a new version of ImpressCMS ! The ImpressCMS Project strongly recommends always using the latest release.');
define("_AM_VERSION_MOREINFO", 'Click on the following link to get more information on the latest release: ');

define("_AM_VERSION_CHECK_RSSDATA_EMPTY", 'No information was available to check for an updated release.');
define("_AM_VERSION_CHANGELOG", 'Changelog');
define("_AM_VERSION_WARNING", 'Warning');
define("_AM_VERSION_WARNING_NOT_A_FINAL", 'Please note that you are currently running a "Final" version of ImpressCMS and the proposed updated release is not a "Final" release. All releases other then "Final" should not be used on a production environment. If your install of ImpressCMS is running on a production environment, we recommend you wait for a final release. More info can be found: <a href="http://wiki.impresscms.org/index.php?title=Final_release">here</a>.');

define("_AM_VERSION_YOUR_VERSION", 'Your ImpressCMS Version:');
define("_AM_VERSION_LATEST_VERSION", 'Latest ImpressCMS Version:');
// Added in ImpressCMS 1.2
define("_AM_VERSION_OP_SYSTEM", 'Server\'s Operating System:');
define("_AM_VERSION_MYSQL_SYSTEM", 'Your MYSQL Version:');
define("_AM_VERSION_PHP_SYSTEM", 'Your PHP Version:');
define("_AM_VERSION_API_SYSTEM", 'Your API name:');
define("_AM_VERSION_SYSTEM_INFO", 'Click here to view your System information');
?>