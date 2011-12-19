<?php
/**
 * Admin header file
 *
 * This file is included in all pages of the admin side and being so, it proceeds to a few
 * common things.
 *
 * @copyright	The ImpressCMS Project
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @since		1.0
 * @author		Rodrigo P Lima aka TheRplima <therplima@impresscms.org>
 * @package		content
 * @version		$Id: admin_header.php 20051 2010-08-28 16:30:42Z phoenyx $
 */

include_once '../../../include/cp_header.php';

include_once ICMS_ROOT_PATH.'/modules/' . basename(dirname(dirname(__FILE__))) .'/include/common.php';
if (!defined("CONTENT_ADMIN_URL")) define('CONTENT_ADMIN_URL', CONTENT_URL . "admin/");
include_once CONTENT_ROOT_PATH . 'include/requirements.php';