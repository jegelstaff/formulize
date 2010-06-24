<?php
/**
* Creates a simple form
*
* @copyright	http://www.xoops.org/ The XOOPS Project
* @copyright	XOOPS_copyrights.txt
* @copyright	http://www.impresscms.org/ The ImpressCMS Project
* @license	LICENSE.txt
* @package	XoopsForms
* @since	XOOPS
* @author	http://www.xoops.org The XOOPS Project
* @author	modified by UnderDog <underdog@impresscms.org>
* @version	$Id: simpleform.php 8662 2009-05-01 09:04:30Z pesianstranger $
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
 * Form that will output as a simple HTML form with minimum formatting
 * 
 * @author	Kazumi Ono	<onokazu@xoops.org>
 * @copyright	copyright (c) 2000-2003 XOOPS.org
 * 
 * @package     kernel
 * @subpackage  form
 */
class XoopsSimpleForm extends XoopsForm
{
	/**
	 * create HTML to output the form with minimal formatting
	 * 
   * @return	string
	 */
	function render()
	{
		$ret = $this->getTitle()."\n<form name='".$this->getName()."' id='".$this->getName()."' action='".$this->getAction()."' method='".$this->getMethod()."'".$this->getExtra().">\n";
		foreach ( $this->getElements() as $ele ) {
			if ( !$ele->isHidden() ) {
				$ret .= "<b>".$ele->getCaption()."</b><br />".$ele->render()."<br />\n";
			} else {
				$ret .= $ele->render()."\n";
			}
		}
		$ret .= "</form>\n";
		return $ret;
	}
}


?>