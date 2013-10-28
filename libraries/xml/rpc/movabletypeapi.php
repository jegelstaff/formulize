<?php
/**
 * XML Parser Moveable Type Api
 *
 * @copyright	http://www.xoops.org/ The XOOPS Project
 * @copyright	XOOPS_copyrights.txt
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license	LICENSE.txt
 * @package	XML
 * @since	XOOPS
 * @author	http://www.xoops.org The XOOPS Project
 * @author	modified by UnderDog <underdog@impresscms.org>
 * @version	$Id: movabletypeapi.php 19118 2010-03-27 17:46:23Z skenow $
 */

if (!defined('ICMS_ROOT_PATH')) {
	die("ImpressCMS root path not defined");
}
require_once ICMS_LIBRARIES_PATH . '/xml/rpc/xmlrpcapi.php';

class MovableTypeApi extends XoopsXmlRpcApi
{
	function MovableTypeApi(&$params, &$response, &$module)
	{
		$this->XoopsXmlRpcApi($params, $response, $module);
	}

	function getCategoryList()
	{
		if (!$this->_checkUser($this->params[1], $this->params[2])) {
			$this->response->add(new XoopsXmlRpcFault(104));
		} else {
			$xoopsapi =& $this->_getXoopsApi($this->params);
			$xoopsapi->_setUser($this->user, $this->isadmin);
			$ret =& $xoopsapi->getCategories(false);
			if (is_array($ret)) {
				$arr = new XoopsXmlRpcArray();
				foreach ($ret as $id => $name) {
					$struct = new XoopsXmlRpcStruct();
					$struct->add('categoryId', new XoopsXmlRpcString($id));
					$struct->add('categoryName', new XoopsXmlRpcString($name['title']));
					$arr->add($struct);
					unset($struct);
				}
				$this->response->add($arr);
			} else {
				$this->response->add(new XoopsXmlRpcFault(106));
			}
		}
	}

	function getPostCategories()
	{
		$this->response->add(new XoopsXmlRpcFault(107));
	}

	function setPostCategories()
	{
		$this->response->add(new XoopsXmlRpcFault(107));
	}

	function supportedMethods()
	{
		$this->response->add(new XoopsXmlRpcFault(107));
	}
}
?>