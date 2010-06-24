<?php
/**
* Creates a form attribut styled by the theme
*
* @copyright	http://www.xoops.org/ The XOOPS Project
* @copyright	XOOPS_copyrights.txt
* @copyright	http://www.impresscms.org/ The ImpressCMS Project
* @license	LICENSE.txt
* @package	XoopsForms
* @since	XOOPS
* @author	http://www.xoops.org The XOOPS Project
* @author	modified by UnderDog <underdog@impresscms.org>
* @version	$Id: themeform.php 9847 2010-02-21 23:23:03Z skenow $
*/

if (!defined('ICMS_ROOT_PATH')) {
	die("ImpressCMS root path not defined");
}




/**
 *
 *
 * @package     kernel
 * @subpackage  form
 *
 * @author	    Kazumi Ono	<onokazu@xoops.org>
 * @copyright	copyright (c) 2000-2003 XOOPS.org
 */
/**
 * base class
 */
include_once ICMS_ROOT_PATH."/class/xoopsform/form.php";

/**
 * Form that will output as a theme-enabled HTML table
 *
 * Also adds JavaScript to validate required fields
 *
 * @author	Kazumi Ono	<onokazu@xoops.org>
 * @copyright	copyright (c) 2000-2003 XOOPS.org
 *
 * @package     kernel
 * @subpackage  form
 */
class XoopsThemeForm extends XoopsForm
{
	/**
	 * Insert an empty row in the table to serve as a seperator.
	 *
   * @param	string  $extra  HTML to be displayed in the empty row.
	 * @param	string	$class	CSS class name for <td> tag
	 */
	function insertBreak($extra = '', $class= '')
	{
    	$class = ($class != '') ? " class='$class'" : '';
     	//Fix for $extra tag not showing
		if ($extra) {
			$extra = "<tr><td colspan='2' $class>$extra</td></tr>";
			$this->addElement($extra);
		} else {
			$extra = "<tr><td colspan='2' $class>&nbsp;</td></tr>";
			$this->addElement($extra);
		}
	}

	/**
	 * create HTML to output the form as a theme-enabled table with validation.
   *
	 * @return	string
	 */
	function render()
	{
    	$ele_name = $this->getName();
		$ret = "
			<form name='".$ele_name."' action='".$this->getAction()."' method='".$this->getMethod()."' onsubmit='return xoopsFormValidate_".$ele_name."();'".$this->getExtra().">
			<div class='xo-theme-form'>
			<table width='100%' class='outer' cellspacing='1'>
			<tr><th colspan='2'>".$this->getTitle()."</th></tr>
		";
		$hidden = '';
		$class ='even';
		foreach ( $this->getElements() as $ele ) {
			if (!is_object($ele)) {
				$ret .= $ele;
			} elseif ( !$ele->isHidden() ) {
				$ret .= "<tr valign='top' align='"._GLOBAL_LEFT."'><td class='head'>";
				if ( ($caption = $ele->getCaption()) != '' ) {
				    $ret .=
				        "<div class='xoops-form-element-caption" . ($ele->isRequired() ? "-required" : "" ) . "'>".
				        "<span class='caption-text'>{$caption}</span>".
				        "<span class='caption-marker'>*</span>".
				        "</div>";
			    }
				if ( ($desc = $ele->getDescription()) != '' ) {
				    $ret .= "<div class='xoops-form-element-help'>{$desc}</div>";
				}
				$ret .= "</td><td class='$class'>".$ele->render()."</td></tr>\n";
			} else {
				$hidden .= $ele->render();
			}
		}
		$ret .= "</table>\n$hidden\n</div>\n</form>\n";
		$ret .= $this->renderValidationJS( true );
		return $ret;
	}
}


?>