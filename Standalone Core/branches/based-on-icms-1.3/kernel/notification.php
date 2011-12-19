<?php
/**
 * Manage of Notifications
 *
 * @copyright	http://www.xoops.org/ The XOOPS Project
 * @copyright	XOOPS_copyrights.txt
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license	LICENSE.txt
 * @package	core
 * @since	XOOPS
 * @author	http://www.xoops.org The XOOPS Project
 * @author	modified by UnderDog <underdog@impresscms.org>
 * @version	$Id: notification.php 19431 2010-06-16 20:46:34Z david-sf $
 */

if (!defined('ICMS_ROOT_PATH')) die("ImpressCMS root path not defined");

// RMV-NOTIFY
include_once XOOPS_ROOT_PATH . '/include/notification_constants.php';
include_once XOOPS_ROOT_PATH . '/include/notification_functions.php';

/**
 *
 *
 * @package     kernel
 * @subpackage  notification
 *
 * @author	    Michael van Dam	<mvandam@caltech.edu>
 * @copyright	copyright (c) 2000-2003 XOOPS.org
 */

/**
 * A Notification
 *
 * @package     kernel
 * @subpackage  notification
 *
 * @author	    Michael van Dam	<mvandam@caltech.edu>
 * @copyright	copyright (c) 2000-2003 XOOPS.org
 * @deprecated	Use icms_data_notification_Object, instead
 * @todo		Remove in version 1.4
 */
class XoopsNotification extends icms_data_notification_Object
{
	private $_deprecated;

	/**
	 * Constructor
	 **/
	function XoopsNotification()
	{
		parent::__construct();
		$this->_deprecated = icms_core_Debug::setDeprecated('icms_data_notification_Object', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
	}


}

/**
 * XOOPS notification handler class.
 *
 * This class is responsible for providing data access mechanisms to the data source
 * of XOOPS notification class objects.
 *
 *
 * @package     kernel
 * @subpackage  notification
 *
 * @author	    Michael van Dam <mvandam@caltech.edu>
 * @copyright	copyright (c) 2000-2003 XOOPS.org
 * @deprecated	Use icms_data_notification_Handler, instead
 * @todo		Remove in version 1.4
 */
class XoopsNotificationHandler extends icms_data_notification_Handler {
	private $_deprecated;
	public function __construct(&$db) {
		parent::__construct($db);
		$this->_deprecated = icms_core_Debug::setDeprecated('icms_data_notification_Handler', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
	}
}
