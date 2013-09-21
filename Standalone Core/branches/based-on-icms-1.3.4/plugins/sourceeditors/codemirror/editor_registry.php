<?php
/**
 * CodeMirror adapter for ImpressCMS
 *
 * @copyright	The ImpressCMS project http://www.xoops.org/
 * @license		http://www.fsf.org/copyleft/gpl.html GNU public license
 * @author		MekDrop	<mekdrop@gmail.com>
 * @since		1.2
 * @package		sourceeditors
 */

global $icmsConfig;

$current_path = __FILE__;
if (DIRECTORY_SEPARATOR != "/" ) $current_path = str_replace(strpos($current_path, "\\\\", 2) ? "\\\\" : DIRECTORY_SEPARATOR, "/", $current_path);
$root_path = dirname($current_path);

$icmsConfig['language'] = preg_replace("/[^a-z0-9_\-]/i", "", $icmsConfig['language']);
if (file_exists($root_path . "/language/" . $icmsConfig['language'] . ".php")) {
	require_once $root_path . "/language/" . $icmsConfig['language'] . ".php";
} else {
	require_once $root_path . "/language/english.php";
}

return $config = array(
		"class"	=>	'IcmsSourceEditorCodeMirror',
		"file"	=>	$root_path . '/codemirror.php',
		"title"	=>	_ICMS_SOURCEEDITOR_CODEMIRROR,
		"order"	=>	1,
		"nohtml"=>	1
);