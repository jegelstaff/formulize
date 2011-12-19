<?php
/**
 * module files can include this file for admin authorization
 * the file that will include this file must be located under xoops_url/modules/module_directory_name/admin_directory_name/
 *
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @package		core
 * @version		SVN: $Id: cp_header.php 20763 2011-02-05 17:26:34Z skenow $
 */
// Make sure the kernel launches the module in admin mode and checks the correct permissions
define('ICMS_IN_ADMIN', 1);

/* if mainfile.php is loaded before this file, the icms::$module will not be loaded properly,
 * causing a fatal error when trying to access any property or method of $icmsModule,
 * $xoopsModule or icms::$module
 */
if (defined('XOOPS_MAINFILE_INCLUDED')) {
	icms_core_Debug::setDeprecated('', 'mainfile.php should not be loaded before including cp_header.php');
	icms::loadService('module', array('icms_module_Handler', 'service'), array(TRUE));
}
/** Load the mainfile */
include_once '../../../mainfile.php';
/** Load the admin functions */
include_once ICMS_ROOT_PATH . '/include/cp_functions.php';

// include the default language file for the admin interface
icms_loadLanguageFile(icms::$module->getVar('dirname'), 'admin');
