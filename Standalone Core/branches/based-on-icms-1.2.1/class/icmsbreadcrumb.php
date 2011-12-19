<?php
if (!defined('ICMS_ROOT_PATH')) die("ImpressCMS root path not defined");

/**
* IcmsBreadcrumb
*
* Managing page breadcrumb
*
* @copyright	The ImpressCMS Project http://www.impresscms.org/
* @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
* @package		IcmsPersistableObject
* @since		1.1
* @author		marcan <marcan@impresscms.org>
* @version		$Id:icmspersistabletable.php 1948 2008-05-01 19:01:10Z malanciault $
 */
class IcmsBreadcrumb {

	private $_tpl;
	private $items;

	/**
    * Constructor
    */
	public function __construct($items) {
		$this->items = $items;
	}

	function render($fetchOnly=false)
	{
		include_once XOOPS_ROOT_PATH . '/class/template.php';

		$this->_tpl =& new XoopsTpl();
		$this->_tpl->assign('icms_breadcrumb_items', $this->items);

		if ($fetchOnly) {
			return $this->_tpl->fetch( 'db:system_breadcrumb.html');
		} else {
			$this->_tpl->display('db:system_breadcrumb.html');
		}
	}
}

?>