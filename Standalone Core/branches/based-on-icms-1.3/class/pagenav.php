<?php
/**
 * Generates pagination
 *
 * @copyright	http://www.xoops.org/ The XOOPS Project
 * @copyright	XOOPS_copyrights.txt
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license	LICENSE.txt
 * @package	core
 * @since	XOOPS
 * @author	http://www.xoops.org The XOOPS Project
 * @author	modified by UnderDog <underdog@impresscms.org>
 * @version	$Id: pagenav.php 20634 2010-12-30 22:48:01Z skenow $
 */

/**
 * Class to facilitate navigation in a multi page document/list
 * @deprecated	Use icms_view_PageNav, instead
 * @todo		Remove in version 1.4
 *
 * @package		kernel
 * @subpackage	util
 *
 * @author		Kazumi Ono 	<onokazu@xoops.org>
 * @copyright	(c) 2000-2003 The Xoops Project - www.xoops.org
 */
class XoopsPageNav extends icms_view_PageNav {
	private $_deprecated;

	public function XoopsPageNav($total_items, $items_perpage, $current_start, $start_name = "start", $extra_arg = "") {
		self::__construct($total_items, $items_perpage, $current_start, $start_name, $extra_arg);
	}
	
	public function __construct($total_items, $items_perpage, $current_start, $start_name = "start", $extra_arg = "") {
		parent::__construct($total_items, $items_perpage, $current_start, $start_name, $extra_arg);
		$this->_deprecated = icms_core_Debug::setDeprecated('icms_view_PageNav', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
	}
	
}

?>