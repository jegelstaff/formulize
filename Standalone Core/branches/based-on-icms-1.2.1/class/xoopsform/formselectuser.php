<?php
/**
* user select with page navigation
*
* limit: Only works with javascript enabled
*
* @copyright	http://www.xoops.org/ The XOOPS Project
* @copyright	XOOPS_copyrights.txt
* @license		http://www.fsf.org/copyleft/gpl.html GNU public license
* @copyright	http://www.impresscms.org/ The ImpressCMS Project
* @license	LICENSE.txt
* @package	XoopsForms
* @since	XOOPS
* @author	http://www.xoops.org The XOOPS Project
* @author		Taiwen Jiang (phppp or D.J.) <php_pp@hotmail.com>
* @author	modified by UnderDog <underdog@impresscms.org>
* @version	$Id: formselectuser.php 9146 2009-08-12 15:08:56Z pesianstranger $
*/



if (!defined('ICMS_ROOT_PATH')) {
	die('ImpressCMS root path not defined');
}


/**
 * @package	 kernel
 * @subpackage  form
 * 
 * @author		Kazumi Ono	<onokazu@xoops.org>
 * @copyright	copyright (c) 2000-2003 XOOPS.org
 */
/**
 * user select with page navigation
 * 
 * @package	 kernel
 * @subpackage  form
 * 
 * @author		Kazumi Ono	<onokazu@xoops.org>
 * @copyright	copyright (c) 2000-2003 XOOPS.org
 */

include_once ICMS_ROOT_PATH.'/class/xoopsform/formelementtray.php';
include_once ICMS_ROOT_PATH.'/class/xoopsform/formselect.php';

class XoopsFormSelectUser extends XoopsFormElementTray
{
	/**
	 * Constructor
	 *
	 * @param	string	$caption
	 * @param	string	$name
	 * @param	mixed	$value			Pre-selected value (or array of them).
	 *									For an item with massive members, such as "Registered Users", "$value" should be used to store selected temporary users only instead of all members of that item
	 * @param	bool	$include_anon	Include user "anonymous"?
	 * @param	int		$size			Number or rows. "1" makes a drop-down-list.
   * @param	bool	$multiple	   Allow multiple selections?
	 */
	function XoopsFormSelectUser($caption, $name, $include_anon = false, $value = null, $size = 1, $multiple = false, $showremovedusers = false, $justremovedusers = false)
	{
		$limit = 200;
		$select_element = new XoopsFormSelect('', $name, $value, $size, $multiple);
		if ($include_anon) {
			$select_element->addOption(0, $GLOBALS['xoopsConfig']['anonymous']);
		}
			$member_handler =& xoops_gethandler('member');
			$user_count = $member_handler->getUserCount();
		$value = is_array($value) ? $value : ( empty($value) ? array() : array($value) );
		if ($user_count > $limit && count($value) > 0) {
			$criteria = new CriteriaCompo(new Criteria("uid", "(".implode(",", $value).")", "IN"));
		} else {
			$criteria = new CriteriaCompo();
			$criteria->setLimit($limit);
		}
		$criteria->setSort('uname');
		if(!$showremovedusers){
			$criteria->add(new Criteria('level', '-1', '!='));
		}elseif($showremovedusers && $justremovedusers){
			$criteria->add(new Criteria('level', '-1'));
		}
		$criteria->setOrder('ASC');
			$users = $member_handler->getUserList($criteria);
		$select_element->addOptionArray($users);
		if ($user_count <= $limit) {
			$this->XoopsFormElementTray($caption, "", $name);
			$this->addElement($select_element);
			return;
		}
		
		icms_loadLanguageFile('core', 'findusers');
		
		$js_addusers =
			"<script type=\"text/javascript\">
			function addusers(opts){
				var num = opts.substring(0, opts.indexOf(\":\"));
				opts = opts.substring(opts.indexOf(\":\")+1, opts.length);
				var sel = xoopsGetElementById(\"". $name . ($multiple ? "[]" : "") . "\");
				var arr = new Array(num);
				for (var n=0; n < num; n++) {
					var nm = opts.substring(0, opts.indexOf(\":\"));
					opts = opts.substring(opts.indexOf(\":\")+1, opts.length);
					var val = opts.substring(0, opts.indexOf(\":\"));
					opts = opts.substring(opts.indexOf(\":\")+1, opts.length);
					var txt = opts.substring(0, nm - val.length);
					opts = opts.substring(nm - val.length, opts.length);
					var added = false;
					for (var k = 0; k < sel.options.length; k++) {
						if(sel.options[k].value == val){
							added = true;
							break;
						}
					}
					if (added == false) {
						sel.options[k] = new Option(txt, val);
						sel.options[k].selected = true;
					}
				}
				return true;
			}
			</script>";
		
		$token = $GLOBALS['xoopsSecurity']->createToken();
		$action_tray = new XoopsFormElementTray("", " | ");
		$action_tray->addElement(new XoopsFormLabel('', "<a href='#' onclick='var sel = xoopsGetElementById(\"" . $name . ( $multiple ? "[]" : "" ) . "\");for (var i = sel.options.length-1; i >= 0; i--) {if (!sel.options[i].selected) {sel.options[i] = null;}}; return false;'>"._MA_USER_REMOVE."</a>"));
		$action_tray->addElement(new XoopsFormLabel('', "<a href='#' onclick='openWithSelfMain(\"".ICMS_URL."/include/findusers.php?target={$name}&amp;multiple={$multiple}&amp;token={$token}\", \"userselect\", 800, 600, null); return false;' >"._MA_USER_MORE."</a>".$js_addusers));

		$this->XoopsFormElementTray($caption, '<br /><br />', $name);
		$this->addElement($select_element);
		$this->addElement($action_tray);
	}
}

?>