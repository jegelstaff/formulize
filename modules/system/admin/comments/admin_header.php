<?php
/**
 * Administration of comments, Admin Header file
 *
 * Checks the rights of the user for being able to admin the comments
 *
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license		LICENSE.txt
 * @package		Administration
 * @subpackage	Comments
 * @version		SVN: $Id: admin_header.php 20768 2011-02-06 00:02:25Z skenow $
 */

include '../../../../mainfile.php';
include ICMS_ROOT_PATH.'/include/cp_functions.php';
if (is_object(icms::$user)) {
	$module_handler = icms::handler('icms_module');
	$icmsModule =& $module_handler->getByDirname('system');
	if (!in_array(XOOPS_GROUP_ADMIN, icms::$user->getGroups())) {
		$sysperm_handler = icms::handler('icms_member_groupperm');
		if (!$sysperm_handler->checkRight('system_admin', XOOPS_SYSTEM_COMMENT, icms::$user->getGroups())) {
			redirect_header(ICMS_URL . '/', 3, _NOPERM);;
			exit();
		}
	}
} else {
	redirect_header(ICMS_URL . '/', 3, _NOPERM);
	exit();
}

