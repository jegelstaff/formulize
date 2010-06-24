<?php
/**
* Creates a form styled by a table
*
* @copyright	http://www.xoops.org/ The XOOPS Project
* @copyright	XOOPS_copyrights.txt
* @copyright	http://www.impresscms.org/ The ImpressCMS Project
* @license	LICENSE.txt
* @package	XoopsForms
* @since	XOOPS
* @author	http://www.xoops.org The XOOPS Project
* @author	modified by UnderDog <underdog@impresscms.org>
* @version	$Id: tableform.php 8662 2009-05-01 09:04:30Z pesianstranger $
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
 * the base class
 */
include_once ICMS_ROOT_PATH."/class/xoopsform/form.php";

/**
 * Form that will output formatted as a HTML table
 * 
 * No styles and no JavaScript to check for required fields.
 * 
 * @author	Kazumi Ono	<onokazu@xoops.org>
 * @copyright	copyright (c) 2000-2003 XOOPS.org
 * 
 * @package     kernel
 * @subpackage  form
 */
class XoopsTableForm extends XoopsForm
{

	/**
	 * create HTML to output the form as a table
	 * 
   * @return	string  $ret  the constructed HTML
	 */
	function render()
	{
		$ret = $this->getTitle()."\n<form name='".$this->getName()."' id='".$this->getName()."' action='".$this->getAction()."' method='".$this->getMethod()."'".$this->getExtra().">\n<table border='0' width='100%'>\n";
		$hidden = '';
		foreach ( $this->getElements() as $ele ) {
			if ( !$ele->isHidden() ) {
				$ret .= "<tr valign='top' align='"._GLOBAL_LEFT."'><td>".$ele->getCaption();
				if ($ele_desc = $ele->getDescription()) {
					$ret .= '<br /><br /><span style="font-weight: normal;">'.$ele_desc.'</span>';
				}
				$ret .= "</td><td>".$ele->render()."</td></tr>";
			} else {
				$hidden .= $ele->render()."\n";
			}
		}
		$ret .= "</table>\n$hidden</form>\n";
		return $ret;
	}
}

?>