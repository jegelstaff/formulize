<?php
/**
 * XML Parser RPC Api
 *
 * @copyright	http://www.xoops.org/ The XOOPS Project
 * @copyright	XOOPS_copyrights.txt
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license	LICENSE.txt
 * @package	XML
 * @since	XOOPS
 * @author	http://www.xoops.org The XOOPS Project
 * @author	modified by UnderDog <underdog@impresscms.org>
 * @version	$Id: xmlrpcapi.php 19612 2010-06-24 23:28:29Z malanciault $
 */

class XoopsXmlRpcApi
{

	// reference to method parameters
	var $params;

	// reference to xmlrpc document class object
	var $response;

	// reference to module class object
	var $module;

	// map between xoops tags and blogger specific tags
	var $xoopsTagMap = array();

	// user class object
	var $user;

	var $isadmin = false;

	function XoopsXmlRpcApi(&$params, &$response, &$module)
	{
		$this->params =& $params;
		$this->response =& $response;
		$this->module =& $module;
	}

	function _setUser(&$user, $isadmin = false)
	{
		if (is_object($user)) {
			$this->user =& $user;
			$this->isadmin = $isadmin;
		}
	}

	function _checkUser($username, $password)
	{
		if (isset($this->user)) {
			return true;
		}
		$member_handler = icms::handler('icms_member');
		$this->user =& $member_handler->loginUser(addslashes($username), addslashes($password));
		if (!is_object($this->user)) {
			unset($this->user);
			return false;
		}
		$moduleperm_handler = icms::handler('icms_member_groupperm');
		if (!$moduleperm_handler->checkRight('module_read', $this->module->getVar('mid'), $this->user->getGroups())) {
			unset($this->user);
			return false;
		}
		return true;
	}

	function _checkAdmin()
	{
		if ($this->isadmin) {
			return true;
		}
		if (!isset($this->user)) {
			return false;
		}
		if (!$this->user->isAdmin($this->module->getVar('mid'))) {
			return false;
		}
		$this->isadmin = true;
		return true;
	}

	function &_getPostFields($post_id = null, $blog_id = null)
	{
		$ret = array();
		$ret['title'] = array('required' => true, 'form_type' => 'textbox', 'value_type' => 'text');
		$ret['hometext'] = array('required' => false, 'form_type' => 'textarea', 'data_type' => 'textarea');
		$ret['moretext'] = array('required' => false, 'form_type' => 'textarea', 'data_type' => 'textarea');
		$ret['categories'] = array('required' => false, 'form_type' => 'select_multi', 'data_type' => 'array');
		/*
		 if (!isset($blog_id)) {
		 if (!isset($post_id)) {
		 return false;
		 }
		 $itemman =& $this->mf->get(MANAGER_ITEM);
		 $item =& $itemman->get($post_id);
		 $blog_id = $item->getVar('sect_id');
		 }
		 $sectman =& $this->mf->get(MANAGER_SECTION);
		 $this->section =& $sectman->get($blog_id);
		 $ret =& $this->section->getVar('sect_fields');
		 */
		return $ret;
	}

	function _setXoopsTagMap($xoopstag, $blogtag)
	{
		if (trim($blogtag) != '') {
			$this->xoopsTagMap[$xoopstag] = $blogtag;
		}
	}

	function _getXoopsTagMap($xoopstag)
	{
		if (isset($this->xoopsTagMap[$xoopstag])) {
			return $this->xoopsTagMap[$xoopstag];
		}
		return $xoopstag;
	}

	function _getTagCdata(&$text, $tag, $remove = true)
	{
		$ret = '';
		$match = array();
		if (preg_match("/\<".$tag."\>(.*)\<\/".$tag."\>/is", $text, $match)) {
			if ($remove) {
				$text = str_replace($match[0], '', $text);
			}
			$ret = $match[1];
		}
		return $ret;
	}

	// kind of dirty method to load XOOPS API and create a new object thereof
	// returns itself if the calling object is XOOPS API
	function &_getXoopsApi(&$params)
	{
		if (strtolower(get_class($this)) != 'xoopsapi') {
			require_once ICMS_ROOT_PATH.'/class/xml/rpc/xoopsapi.php' ;
			return new XoopsApi($params, $this->response, $this->module);
		} else {
			return $this;
		}
	}
}
?>