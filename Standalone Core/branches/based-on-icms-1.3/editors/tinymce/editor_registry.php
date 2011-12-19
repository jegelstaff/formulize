<?php
/**
* Handles the editor registry
*
* @copyright	http://www.xoops.org/ The XOOPS Project
* @copyright	XOOPS_copyrights.txt
* @copyright	http://www.impresscms.org/ The ImpressCMS Project
* @license	LICENSE.txt
* @package	xoopseditors
* @since	XOOPS
* @author	http://www.xoops.org The XOOPS Project
* @author	modified by UnderDog <underdog@impresscms.org>
* @version	$Id: editor_registry.php 19775 2010-07-11 18:54:25Z malanciault $
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
if (DIRECTORY_SEPARATOR != "/" ) $current_path = str_replace( strpos( $current_path, "\\\\", 2 ) ? "\\\\" : DIRECTORY_SEPARATOR, "/", $current_path);
$root_path = dirname($current_path);

$icmsConfig['language'] = preg_replace("/[^a-z0-9_\-]/i", "", $icmsConfig['language']);
if (!@include_once($root_path."/language/".$icmsConfig['language'].".php")) {
	include_once $root_path."/language/english.php" ;
}

return $config = array(
		"name"	=>	"tinymce",
		"class"	=>	"XoopsFormTinymce",
		"file"	=>	$root_path."/formtinymce.php",
		"title"	=>	_XOOPS_EDITOR_TINYMCE,
		"order"	=>	3
	);
?>