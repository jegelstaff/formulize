<?php
/**
 * Handles all notification constants within ImpressCMS
 *
 * @copyright	http://www.xoops.org/ The XOOPS Project
 * @copyright	XOOPS_copyrights.txt
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license	LICENSE.txt
 * @package	core
 * @since	XOOPS
 * @author	http://www.xoops.org The XOOPS Project
 * @author	modified by UnderDog <underdog@impresscms.org>
 * @version	$Id: notification_constants.php 19775 2010-07-11 18:54:25Z malanciault $
 */

// RMV-NOTIFY

define('XOOPS_NOTIFICATION_MODE_SENDALWAYS', 0);
define('XOOPS_NOTIFICATION_MODE_SENDONCETHENDELETE', 1);
define('XOOPS_NOTIFICATION_MODE_SENDONCETHENWAIT', 2);
define('XOOPS_NOTIFICATION_MODE_WAITFORLOGIN', 3);

define('XOOPS_NOTIFICATION_METHOD_DISABLE', 0);
define('XOOPS_NOTIFICATION_METHOD_PM', 1);
define('XOOPS_NOTIFICATION_METHOD_EMAIL', 2);

define('XOOPS_NOTIFICATION_DISABLE', 0);
define('XOOPS_NOTIFICATION_ENABLEBLOCK', 1);
define('XOOPS_NOTIFICATION_ENABLEINLINE', 2);
define('XOOPS_NOTIFICATION_ENABLEBOTH', 3);

