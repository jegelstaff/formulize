<?php
/**
* Images Manager - DHTML Image Editor Tool - Filter Plugin
*
* Filter plugin for DHTML Image Editor Tool
*
* @copyright	The ImpressCMS Project http://www.impresscms.org/
* @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
* @package		core
* @since		1.2
* @author		Rodrigo Pereira Lima (AKA TheRplima) <therplima@impresscms.org>
* @version		$Id: icms_plugin_version.php 1244 2008-03-18 17:09:11Z real_therplima $
*/

$plugversion['name'] = _FILTER_PLUGNAME;
$plugversion['version'] = 1.00;
$plugversion['description'] = _FILTER_PLUGDESC;
$plugversion['author'] = "Rodrigo Pereira Lima (AKA TheRplima) <therplima@impresscms.org>";
$plugversion['credits'] = "The ImpressCMS Project";
$plugversion['license'] = "GPL see LICENSE";
$plugversion['official'] = 1;
$plugversion['icon'] = 'images/filter.png';
$plugversion['folder'] = 'filter';
$plugversion['file'] = 'filter_image.php';
$plugversion['block_template'] = 'filter_image.html';
$plugversion['init_js_function'] = 'filter_progressBar();';
$plugversion['stop_js_function'] = 'filter_delpreview()';
?>