<?php
/**
 * Management of members
 *
 * @copyright	http://www.xoops.org/ The XOOPS Project
 * @copyright	XOOPS_copyrights.txt
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license	LICENSE.txt
 * @package	core
 * @since	XOOPS
 * @author	http://www.xoops.org The XOOPS Project
 * @author	modified by UnderDog <underdog@impresscms.org>
 * @version	$Id: member.php 19432 2010-06-16 21:40:53Z david-sf $
 */

if (!defined('ICMS_ROOT_PATH')) die("ImpressCMS root path not defined");
require_once ICMS_ROOT_PATH . '/kernel/user.php';
require_once ICMS_ROOT_PATH . '/kernel/group.php';

/**
 * XOOPS member handler class.
 * This class provides simple interface (a facade class) for handling groups/users/
 * membership data.
 *
 *
 * @author  Kazumi Ono <onokazu@xoops.org>
 * @copyright copyright (c) 2000-2003 XOOPS.org
 * @package kernel
 * @deprecated	Use icms_member_Handler, instead
 * @todo		Remove in version 1.4
 *
 */
class XoopsMemberHandler extends icms_member_Handler {
	private $_deprecated;

	public function __construct(&$db) {
		parent::__construct($db);
		$this->_deprecated = icms_core_Debug::setDeprecated('icms_member_Handler', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
	}
}
