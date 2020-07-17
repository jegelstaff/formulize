<?php
/**
* About page of the module
*
* @copyright	The ImpressCMS Project
* @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
* @since		1.0
* @author		Rodrigo P Lima aka TheRplima <therplima@impresscms.org>
* @package		content
* @version		$Id: about.php 20051 2010-08-28 16:30:42Z phoenyx $
*/

include_once "admin_header.php";

$aboutObj = new icms_ipf_About();
$aboutObj->render();