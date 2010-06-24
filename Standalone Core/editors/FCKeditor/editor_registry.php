<?php
/**
 * FCKeditor adapter for XOOPS
 *
 * @copyright	The XOOPS project http://www.xoops.org/
 * @license		http://www.fsf.org/copyleft/gpl.html GNU public license
 * @author		Taiwen Jiang (phppp or D.J.) <php_pp@hotmail.com>
 * @since		4.00
 * @version		$Id: editor_registry.php 4709 2008-09-06 21:07:58Z skenow $
 * @package		xoopseditor
 */
global $icmsConfig;

$current_path = __FILE__;
if ( DIRECTORY_SEPARATOR != "/" ) $current_path = str_replace( strpos( $current_path, "\\\\", 2 ) ? "\\\\" : DIRECTORY_SEPARATOR, "/", $current_path);
$root_path = dirname($current_path);

$icmsConfig['language'] = preg_replace("/[^a-z0-9_\-]/i", "", $icmsConfig['language']);
if(!@include_once($root_path."/language/".$icmsConfig['language'].".php")){
	include_once($root_path."/language/english.php");
}

return $config = array(
		"name"	=>	"FCKeditor",
		"class"	=>	"XoopsFormFckeditor",
		"file"	=>	$root_path."/formfckeditor.php",
		"title"	=>	_XOOPS_EDITOR_FCKEDITOR,
		"order"	=>	3
	);
?>