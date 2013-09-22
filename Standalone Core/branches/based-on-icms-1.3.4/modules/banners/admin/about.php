<?php
/**
* About page of the module
*
* @copyright	The ImpressCMS Project
* @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
* @since		1.0
* @author		Phoenyx <phoenyx@impresscms.de>
* @package		banners
* @version		$Id: about.php 20170 2010-09-19 14:01:59Z phoenyx $
*/

include_once "admin_header.php";

$aboutObj = new icms_ipf_About();
$aboutObj->render();