<?php
/**
* Creates a form attribute which is able to select a usergroup
*
* @copyright	http://www.xoops.org/ The XOOPS Project
* @copyright	XOOPS_copyrights.txt
* @copyright	http://www.impresscms.org/ The ImpressCMS Project
* @license	LICENSE.txt
* @package	XoopsForms
* @since	XOOPS
* @author	http://www.xoops.org The XOOPS Project
* @author	modified by UnderDog <underdog@impresscms.org>
* @version	$Id: formselectgroup.php 8662 2009-05-01 09:04:30Z pesianstranger $
*/

if (!defined('ICMS_ROOT_PATH')) {
	die("ImpressCMS root path not defined");
}
/**
 * @package     kernel
 * @subpackage  form
 * 
 * @author	    Kazumi Ono	<onokazu@xoops.org>
 * @copyright	copyright (c) 2000-2003 XOOPS.org
 */

/**
 * Parent
 */
include_once ICMS_ROOT_PATH."/class/xoopsform/formselect.php";

/**
 * A select field with a choice of available groups
 * 
 * @package     kernel
 * @subpackage  form
 * 
 * @author	    Kazumi Ono	<onokazu@xoops.org>
 * @copyright	copyright (c) 2000-2003 XOOPS.org
 */
class XoopsFormSelectGroup extends XoopsFormSelect
{
	/**
	 * Constructor
	 * 
	 * @param	string	$caption	
	 * @param	string	$name
	 * @param	bool	$include_anon	Include group "anonymous"?
	 * @param	mixed	$value	    	Pre-selected value (or array of them).
	 * @param	int		$size	        Number or rows. "1" makes a drop-down-list.
   * @param	bool    $multiple       Allow multiple selections?
	 */
	function XoopsFormSelectGroup($caption, $name, $include_anon = false, $value = null, $size = 1, $multiple = false)
	{
	    $this->XoopsFormSelect($caption, $name, $value, $size, $multiple);
		$member_handler =& xoops_gethandler('member');
		if (!$include_anon) {
			$this->addOptionArray($member_handler->getGroupList(new Criteria('groupid', XOOPS_GROUP_ANONYMOUS, '!=')));
		} else {
			$this->addOptionArray($member_handler->getGroupList());
		}
	}
}
?>