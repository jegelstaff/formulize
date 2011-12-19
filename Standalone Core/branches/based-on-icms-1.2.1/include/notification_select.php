<?php
/**
* Handles all notification select functions within ImpressCMS
*
* @copyright	http://www.xoops.org/ The XOOPS Project
* @copyright	XOOPS_copyrights.txt
* @copyright	http://www.impresscms.org/ The ImpressCMS Project
* @license	LICENSE.txt
* @package	core
* @since	XOOPS
* @author	http://www.xoops.org The XOOPS Project
* @author	modified by UnderDog <underdog@impresscms.org>
* @version	$Id: notification_select.php 9520 2009-11-11 14:32:52Z pesianstranger $
*/

if (!defined('ICMS_ROOT_PATH')) {
	exit();
}
include_once ICMS_ROOT_PATH.'/include/notification_constants.php';
include_once ICMS_ROOT_PATH.'/include/notification_functions.php';
$xoops_notification = array();
$xoops_notification['show'] = isset($icmsModule) && is_object($icmsUser) && notificationEnabled('inline') ? 1 : 0;
if ($xoops_notification['show']) {
	icms_loadLanguageFile('core', 'notification');
	$categories =& notificationSubscribableCategoryInfo();
	$event_count = 0;
	if (!empty($categories)) {
		$notification_handler =& xoops_gethandler('notification');
		foreach ($categories as $category) {
			$section['name'] = $category['name'];
			$section['title'] = $category['title'];
			$section['description'] = $category['description'];
			$section['itemid'] = $category['item_id'];
			$section['events'] = array();
			$subscribed_events = $notification_handler->getSubscribedEvents($category['name'], $category['item_id'], $icmsModule->getVar('mid'), $icmsUser->getVar('uid'));
			foreach (notificationEvents($category['name'], true) as $event) {
				if (!empty($event['admin_only']) && !$icmsUser->isAdmin($icmsModule->getVar('mid'))) {
					continue;
				}
				if (!empty($event['invisible'])) {
					continue;
				}
				$subscribed = in_array($event['name'], $subscribed_events) ? 1 : 0;
				$section['events'][$event['name']] = array ('name'=>$event['name'], 'title'=>$event['title'], 'caption'=>$event['caption'], 'description'=>$event['description'], 'subscribed'=>$subscribed);
				$event_count ++;
			}
			$xoops_notification['categories'][$category['name']] = $section;
		}
		$xoops_notification['target_page'] = "notification_update.php";
		$xoops_notification['redirect_script'] = xoops_getenv('PHP_SELF');
		$xoopsTpl->assign(array('lang_activenotifications' => _NOT_ACTIVENOTIFICATIONS, 'lang_notificationoptions' => _NOT_NOTIFICATIONOPTIONS, 'lang_updateoptions' => _NOT_UPDATEOPTIONS, 'lang_updatenow' => _NOT_UPDATENOW, 'lang_category' => _NOT_CATEGORY, 'lang_event' => _NOT_EVENT, 'lang_events' => _NOT_EVENTS, 'lang_checkall' => _NOT_CHECKALL, 'lang_notificationmethodis' => _NOT_NOTIFICATIONMETHODIS, 'lang_change' => _NOT_CHANGE, 'editprofile_url' => ICMS_URL . '/edituser.php?uid=' . $icmsUser->getVar('uid')));
		switch ($icmsUser->getVar('notify_method')) {
		case XOOPS_NOTIFICATION_METHOD_DISABLE:
			$xoopsTpl->assign('user_method', _NOT_DISABLE);
			break;
		case XOOPS_NOTIFICATION_METHOD_PM:
			$xoopsTpl->assign('user_method', _NOT_PM);
			break;
		case XOOPS_NOTIFICATION_METHOD_EMAIL:
			$xoopsTpl->assign('user_method', _NOT_EMAIL);
			break;
		}
	} else {
		$xoops_notification['show'] = 0;
	}
	if ($event_count == 0) {
		$xoops_notification['show'] = 0;
	}
}
$xoopsTpl->assign('xoops_notification', $xoops_notification);

?>