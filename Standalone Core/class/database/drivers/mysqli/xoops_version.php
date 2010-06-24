<?php
/**
 * Configuration file of the MySQLi Database Connector.
 *
 * @package database
 * @subpackage mysqli
 * @since ImpressCMS 1.0
 * @version $Id: xoops_version.php 8558 2009-04-11 11:24:42Z icmsunderdog $
 *
 * @author Gustavo Pilla <nekro@impresscms.org>
 * @copyright Copyright (c) 2008, ImpressCMS <http://www.impresscms.org>
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 */

$classversion['name'] = "MySQLi Database Driver";
$classversion['version'] = 1.00;
$classversion['description'] = "XOOPS MySQLi Database Driver";
$classversion['author'] = "";
$classversion['credits'] = "The XOOPS Project";
$classversion['license'] = "GPL see LICENSE";
$classversion['official'] = 1;
$classversion['dirname'] = "system";

$classversion['require']['php_version'] = "5.1.0";
$classversion['require']['php_extension'] = "mysqli";

?>