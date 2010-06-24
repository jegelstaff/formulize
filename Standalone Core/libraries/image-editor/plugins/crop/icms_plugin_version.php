<?php
/**
* Images Manager - DHTML Image Editor Tool - Crop Plugin
*
* Crop plugin for DHTML Image Editor Tool
*
* @copyright	The ImpressCMS Project http://www.impresscms.org/
* @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
* @package		core
* @since		1.2
* @author		Rodrigo Pereira Lima (AKA TheRplima) <therplima@impresscms.org>
* @version		$Id: icms_plugin_version.php 1244 2008-03-18 17:09:11Z real_therplima $
*/

$plugversion['name'] = _CROP_PLUGNAME;
$plugversion['version'] = 1.00;
$plugversion['description'] = _CROP_PLUGDESC;
$plugversion['author'] = "Rodrigo Pereira Lima (AKA TheRplima) <therplima@impresscms.org>";
$plugversion['credits'] = "The ImpressCMS Project";
$plugversion['license'] = "GPL see LICENSE";
$plugversion['official'] = 1;
$plugversion['icon'] = 'images/crop.png';
$plugversion['folder'] = 'crop';
$plugversion['file'] = 'crop_image.php';
$plugversion['block_template'] = 'crop_image.html';
$plugversion['init_js_function'] = 'init_imageCrop()';
$plugversion['stop_js_function'] = 'crop_removeDivElements()';
?>