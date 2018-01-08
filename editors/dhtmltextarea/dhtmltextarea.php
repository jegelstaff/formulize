<?php
/**
 * FCKeditor adapter for XOOPS
 *
 * @copyright	The XOOPS project http://www.xoops.org/
 * @license		http://www.fsf.org/copyleft/gpl.html GNU public license
 * @author		Taiwen Jiang (phppp or D.J.) <php_pp@hotmail.com>
 * @since		4.00
 * @version		$Id: dhtmltextarea.php 1686 2008-04-19 14:33:00Z malanciault $
 * @package		xoopseditor
 */
if (!defined('ICMS_ROOT_PATH')) die("ImpressCMS root path not defined");

/**
 * Pseudo class
 *
 * @author	    phppp (D.J.)
 * @copyright	copyright (c) 2005 XOOPS.org
 */
class FormDhtmlTextArea extends icms_form_elements_Dhtmltextarea
{
	/**
	 * Constructor
	 *
   * @param	array   $configs  Editor Options
   * @param	binary 	$checkCompatible  true - return false on failure
	 */
	function FormDhtmlTextArea($configs, $checkCompatible = false)
	{
		if (!empty($configs)) {
			foreach ($configs as $key => $val) {
				${$key} = $val;
				$this->$key = $val;
			}
		}
		$value = isset($value)? $value : "";
		$rows = isset($rows)? $rows : 5;
		$cols = isset($cols)? $cols : 50;
		$hiddentext = empty($hiddentext)? "xoopsHiddenText" : $hiddentext;
		parent::__construct(@$caption, $name, $value, $rows, $cols, $hiddentext,$configs);
	}
}
?>