<?php
/**
* Pagination Styles Library Configuration File
*
* This file is responsible for configuration of the Pagination Styles library
*
* @copyright	The ImpressCMS Project http://www.impresscms.org/
* @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
* @package		libraries
* @since		1.2
* @author		TheRplima <therplima@impresscms.org>
* @version		$Id: paginationstyles.php 1742 2008-04-20 14:46:20Z real_therplima $
*/

$style_list = icms_core_Filesystem::getFileList(ICMS_LIBRARIES_PATH . "/paginationstyles/css/", "", array("css"), TRUE);

foreach ($style_list as $filename) {
	$filename = str_ireplace(".css", "", $filename);
	$styles[] = array(
		'name' => ucfirst($filename),
		'fcss' => $filename,
	);
}
