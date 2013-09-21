<?php
/**
 * ImpressCMS core constants definition
 *
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @package		core
 * @since		1.3
 * @version		SVN: $Id: constants.php 11514 2011-12-29 02:16:36Z sato-san $
 */

if (!defined('ICMS_ROOT_PATH')) {
	define('ICMS_ROOT_PATH', XOOPS_ROOT_PATH);
}
if (!defined('ICMS_TRUST_PATH')) {
	define('ICMS_TRUST_PATH', XOOPS_TRUST_PATH);
}
if (!defined('ICMS_URL')) {
	define('ICMS_URL', XOOPS_URL);
}
if (!defined('ICMS_GROUP_ADMIN')) {
	define('ICMS_GROUP_ADMIN', XOOPS_GROUP_ADMIN);
}
if (!defined('ICMS_GROUP_USERS')) {
	define('ICMS_GROUP_USERS', XOOPS_GROUP_USERS);
}
if (!defined('ICMS_GROUP_ANONYMOUS')) {
	define('ICMS_GROUP_ANONYMOUS', XOOPS_GROUP_ANONYMOUS);
}

/**#@+
 * Creating ICMS specific constants
 */
define('ICMS_PLUGINS_PATH', ICMS_ROOT_PATH . '/plugins');
define('ICMS_PLUGINS_URL', ICMS_URL . '/plugins');
define('ICMS_PRELOAD_PATH', ICMS_PLUGINS_PATH . '/preloads');
define('ICMS_PURIFIER_CACHE', ICMS_TRUST_PATH . '/cache/htmlpurifier');
// ImpressCMS Modules path & url
define('ICMS_MODULES_PATH', ICMS_ROOT_PATH . '/modules');
define('ICMS_MODULES_URL', ICMS_URL . '/modules');
/**#@-*/

// ################# Creation of the ImpressCMS Libraries ##############
/**
 * @todo The definition of the library path needs to be in mainfile
 */
// ImpressCMS Third Party Libraries folder
define('ICMS_LIBRARIES_PATH', ICMS_ROOT_PATH . '/libraries');
define('ICMS_LIBRARIES_URL', ICMS_URL . '/libraries');
// ImpressCMS Third Party Library for PDF generator
define('ICMS_PDF_LIB_PATH', ICMS_LIBRARIES_PATH . '/tcpdf');
define('ICMS_PDF_LIB_URL', ICMS_URL . '/libraries/tcpdf');
/**#@+
 * Constants
 */
define('XOOPS_SIDEBLOCK_LEFT', 1);
define('XOOPS_SIDEBLOCK_RIGHT', 2);
define('XOOPS_SIDEBLOCK_BOTH', -2);
define('XOOPS_CENTERBLOCK_LEFT', 3);
define('XOOPS_CENTERBLOCK_RIGHT', 5);
define('XOOPS_CENTERBLOCK_CENTER', 4);
define('XOOPS_CENTERBLOCK_ALL', -6);
define('XOOPS_CENTERBLOCK_BOTTOMLEFT', 6);
define('XOOPS_CENTERBLOCK_BOTTOMRIGHT', 8);
define('XOOPS_CENTERBLOCK_BOTTOM', 7);

define('XOOPS_BLOCK_INVISIBLE', 0);
define('XOOPS_BLOCK_VISIBLE', 1);
define('XOOPS_MATCH_START', 0);
define('XOOPS_MATCH_END', 1);
define('XOOPS_MATCH_EQUAL', 2);
define('XOOPS_MATCH_CONTAIN', 3);

define('ICMS_KERNEL_PATH', ICMS_ROOT_PATH . '/kernel/');
define('ICMS_INCLUDE_PATH', ICMS_ROOT_PATH . '/include');
define('ICMS_INCLUDE_URL', ICMS_ROOT_PATH . '/include');
define('ICMS_UPLOAD_PATH', ICMS_ROOT_PATH . '/uploads');
define('ICMS_UPLOAD_URL', ICMS_URL . '/uploads');
define('ICMS_THEME_PATH', ICMS_ROOT_PATH . '/themes');
define('ICMS_THEME_URL', ICMS_URL . '/themes');
define('ICMS_COMPILE_PATH', ICMS_ROOT_PATH . '/templates_c');
define('ICMS_CACHE_PATH', ICMS_ROOT_PATH . '/cache');
define('ICMS_IMAGES_URL', ICMS_URL . '/images');
define('ICMS_EDITOR_PATH', ICMS_ROOT_PATH . '/editors');
define('ICMS_EDITOR_URL', ICMS_URL . '/editors');
define('ICMS_IMANAGER_FOLDER_PATH', ICMS_UPLOAD_PATH . '/imagemanager');
define('ICMS_IMANAGER_FOLDER_URL', ICMS_UPLOAD_URL . '/imagemanager');
/**#@-*/

/**
 * @todo make this $icms_images_setname as an option in preferences...
 */
$icms_images_setname = 'kfaenza';
define('ICMS_IMAGES_SET_URL', ICMS_IMAGES_URL . '/' . $icms_images_setname);

/**#@+
 * @deprecated - for backward compatibility
 */
define('XOOPS_INCLUDE_PATH', ICMS_INCLUDE_PATH);
define('XOOPS_INCLUDE_URL', ICMS_INCLUDE_URL);
define('XOOPS_UPLOAD_PATH', ICMS_UPLOAD_PATH);
define('XOOPS_UPLOAD_URL', ICMS_UPLOAD_URL);
define('XOOPS_THEME_PATH', ICMS_THEME_PATH);
define('XOOPS_THEME_URL', ICMS_THEME_URL);
define('XOOPS_COMPILE_PATH', ICMS_COMPILE_PATH);
define('XOOPS_CACHE_PATH', ICMS_CACHE_PATH);
define('XOOPS_EDITOR_PATH', ICMS_EDITOR_PATH);
define('XOOPS_EDITOR_URL', ICMS_EDITOR_URL);
/**#@-*/
define('SMARTY_DIR', ICMS_LIBRARIES_PATH . '/smarty/');

if (!defined('XOOPS_XMLRPC')) {
	define('XOOPS_DB_CHKREF', 1);
} else {
	define('XOOPS_DB_CHKREF', 0);
}
