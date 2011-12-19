<?php
/**
 * FCKeditor adapter for XOOPS
 *
 * @copyright	The XOOPS project http://www.xoops.org/
 * @license		http://www.fsf.org/copyleft/gpl.html GNU public license
 * @author		Taiwen Jiang (phppp or D.J.) <php_pp@hotmail.com>
 * @since		4.00
 * @version		$Id: editor_registry.php 1686 2008-04-19 14:33:00Z malanciault $
 * @package		xoopseditor
 */
/**
 * XOOPS editor registry
 *
 * @author	    phppp (D.J.)
 * @copyright	copyright (c) 2005 XOOPS.org
 *
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
		//"name"	=>	"dhtmltextarea",
		"class"	=>	"FormDhtmlTextArea",
		"file"	=>	$root_path."/dhtmltextarea.php",
		"title"	=>	_XOOPS_EDITOR_DHTMLTEXTAREA,
		"order"	=>	1,
		"nohtml"=>	1
	);
?>