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
* @author		Phoenyx <phoenyx@impresscms.de>
* @package		banners
* @version		$Id: admin_header.php 20170 2010-09-19 14:01:59Z phoenyx $
*/

include_once "../../../include/cp_header.php";
include_once ICMS_ROOT_PATH."/modules/".basename(dirname(dirname(__FILE__)))."/include/common.php";
include_once BANNERS_ROOT_PATH."include/requirements.php";