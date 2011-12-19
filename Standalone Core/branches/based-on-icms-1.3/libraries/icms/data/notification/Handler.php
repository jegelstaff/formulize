<?php
/**
 * Manage Notifications
 *
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license		LICENSE.txt
 * @category	ICMS
 * @package		Notification
 * @version		SVN: $Id: Handler.php 20458 2010-12-03 00:01:23Z skenow $
 */

defined('ICMS_ROOT_PATH') or die('ImpressCMS root path not defined');

// RMV-NOTIFY
include_once ICMS_ROOT_PATH . '/include/notification_constants.php';

/**
 * Notification handler class.
 *
 * This class is responsible for providing data access mechanisms to the data source
 * of notification class objects.
 *
 * @category	ICMS
 * @package		Notification
 * @author	    Michael van Dam <mvandam@caltech.edu>
 */
class icms_data_notification_Handler extends icms_core_ObjectHandler {

	/**
	 * Create a {@link icms_data_notification_Object}
	 *
	 * @param	bool    $isNew  Flag the object as "new"?
	 *
	 * @return	object
	 */
	public function &create($isNew = true) {
		$notification = new icms_data_notification_Object();
		if ($isNew) {
			$notification->setNew();
		}
		return $notification;
	}

	/**
	 * Retrieve a {@link icms_data_notification_Object}
	 *
	 * @param   int $id ID
	 *
	 * @return  object  {@link icms_data_notification_Object}, FALSE on fail
	 **/
	public function &get($id) {
		$notification = false;
		$id = (int) $id;
		if ($id > 0) {
			$sql = "SELECT * FROM ".$this->db->prefix('xoopsnotifications')." WHERE not_id='".$id."'";
			if (!$result = $this->db->query($sql)) {
				return $notification;
			}
			$numrows = $this->db->getRowsNum($result);
			if ($numrows == 1) {
				$notification = new icms_data_notification_Object();
				$notification->assignVars($this->db->fetchArray($result));
			}
		}
		return $notification;
	}

	/**
	 * Inserts a notification(subscription) into database
	 *
	 * @param   object  &$notification
	 *
	 * @return  bool
	 **/
	public function insert(&$notification) {
		/**
		 * @TODO: Change to if (!(class_exists($this->className) && $obj instanceof $this->className)) when going fully PHP5
		 */
		if (!is_a($notification, 'icms_data_notification_Object')) {
			return false;
		}
		if (!$notification->isDirty()) {
			return true;
		}
		if (!$notification->cleanVars()) {
			return false;
		}
		foreach ($notification->cleanVars as $k => $v) {
			${$k} = $v;
		}
		if ($notification->isNew()) {
			$not_id = $this->db->genId('xoopsnotifications_not_id_seq');
			$sql = sprintf("INSERT INTO %s (not_id, not_modid, not_itemid, not_category, not_uid, not_event, not_mode) VALUES ('%u', '%u', '%u', %s, '%u', %s, '%u')", $this->db->prefix('xoopsnotifications'), (int) $not_id, (int) $not_modid, (int) $not_itemid, $this->db->quoteString($not_category), (int) $not_uid, $this->db->quoteString($not_event), (int) $not_mode);
		} else {
			$sql = sprintf("UPDATE %s SET not_modid = '%u', not_itemid = '%u', not_category = %s, not_uid = '%u', not_event = %s, not_mode = '%u' WHERE not_id = '%u'", $this->db->prefix('xoopsnotifications'), (int) $not_modid, (int) $not_itemid, $this->db->quoteString($not_category), (int) $not_uid, $this->db->quoteString($not_event), (int) $not_mode, (int) $not_id);
		}
		if (!$result = $this->db->query($sql)) {
			return false;
		}
		if (empty($not_id)) {
			$not_id = $this->db->getInsertId();
		}
		$notification->assignVar('not_id', (int)$not_id);
		return true;
	}

	/**
	 * Delete a {@link icms_data_notification_Object} from the database
	 *
	 * @param   object  &$notification {@link icms_data_notification_Object}
	 *
	 * @return  bool
	 **/
	public function delete(&$notification) {
		/**
		 * @TODO: Change to if (!(class_exists($this->className) && $obj instanceof $this->className)) when going fully PHP5
		 */
		if (!is_a($notification, 'icms_data_notification_Object')) {
			return false;
		}

		$sql = sprintf("DELETE FROM %s WHERE not_id = '%u'", $this->db->prefix('xoopsnotifications'), (int)$notification->getVar('not_id'));
		if (!$result = $this->db->query($sql)) {
			return false;
		}
		return true;
	}

	/**
	 * Get some {@link icms_data_notification_Object}s
	 *
	 * @param   object  $criteria
	 * @param   bool    $id_as_key  Use IDs as keys into the array?
	 *
	 * @return  array   Array of {@link icms_data_notification_Object} objects
	 **/
	public function getObjects($criteria = null, $id_as_key = false) {
		$ret = array();
		$limit = $start = 0;
		$sql = 'SELECT * FROM '.$this->db->prefix('xoopsnotifications');
		if (isset($criteria) && is_subclass_of($criteria, 'icms_db_criteria_Element')) {
			$sql .= ' '.$criteria->renderWhere();
			$sort = ($criteria->getSort() != '') ? $criteria->getSort() : 'not_id';
			$sql .= ' ORDER BY '.$sort.' '.$criteria->getOrder();
			$limit = $criteria->getLimit();
			$start = $criteria->getStart();
		}
		$result = $this->db->query($sql, $limit, $start);
		if (!$result) {
			return $ret;
		}
		while ($myrow = $this->db->fetchArray($result)) {
			$notification = new icms_data_notification_Object();
			$notification->assignVars($myrow);
			if (!$id_as_key) {
				$ret[] =& $notification;
			} else {
				$ret[$myrow['not_id']] =& $notification;
			}
			unset($notification);
		}
		return $ret;
	}

	// TODO: Need this??
	/**
	* Count Notifications
	*
	* @param   object  $criteria   {@link icms_db_criteria_Element}
	*
	* @return  int     Count
	**/
	public function getCount($criteria = null) {
		$sql = 'SELECT COUNT(*) FROM '.$this->db->prefix('xoopsnotifications');
		if (isset($criteria) && is_subclass_of($criteria, 'icms_db_criteria_Element')) {
			$sql .= ' '.$criteria->renderWhere();
		}
		if (!$result =& $this->db->query($sql)) {
			return 0;
		}
		list($count) = $this->db->fetchRow($result);
		return $count;
	}

	/**
	 * Delete multiple notifications
	 *
	 * @param   object  $criteria   {@link icms_db_criteria_Element}
	 *
	 * @return  bool
	 **/
	public function deleteAll($criteria = null) {
		$sql = 'DELETE FROM '.$this->db->prefix('xoopsnotifications');
		if (isset($criteria) && is_subclass_of($criteria, 'icms_db_criteria_Element')) {
			$sql .= ' '.$criteria->renderWhere();
		}
		if (!$result = $this->db->query($sql)) {
			return false;
		}
		return true;
	}

	// Need this??
	/**
	* Change a value in multiple notifications
	*
	* @param   string  $fieldname  Name of the field
	* @param   string  $fieldvalue Value to write
	* @param   object  $criteria   {@link icms_db_criteria_Element}
	*
	* @return  bool
	**/
	/*
	 function updateAll($fieldname, $fieldvalue, $criteria = null)
	 {
	 $set_clause = is_numeric($fieldvalue) ? $filedname.' = '.$fieldvalue : $filedname." = '".$fieldvalue."'";
	 $sql = 'UPDATE '.$this->db->prefix('xoopsnotifications').' SET '.$set_clause;
	 if (isset($criteria) && is_subclass_of($criteria, 'icms_db_criteria_Element')) {
	 $sql .= ' '.$criteria->renderWhere();
	 }
	 if (!$result = $this->db->query($sql)) {
	 return false;
	 }
	 return true;
	 }
	 */

	// TODO: rename this...
	// Also, should we have get by module, get by category, etc...??
	/**
	* Change a value in multiple notifications
	*
	* @param   int     $module_id  module ID to get notification for
	* @param   string  $category   category to get notification for
	* @param   int     $item_id    item ID to get notification for
	* @param   string  $event      module ID to get notification for
	* @param   int     $user_id    user ID to get notification for
	*
	* @return  mixed   array of objects or false
	**/
	public function &getNotification($module_id, $category, $item_id, $event, $user_id) {
		$criteria = new icms_db_criteria_Compo();
		$criteria->add(new icms_db_criteria_Item('not_modid', (int)$module_id));
		$criteria->add(new icms_db_criteria_Item('not_category', icms::$xoopsDB->escape($category)));
		$criteria->add(new icms_db_criteria_Item('not_itemid', (int)$item_id));
		$criteria->add(new icms_db_criteria_Item('not_event', icms::$xoopsDB->escape($event)));
		$criteria->add(new icms_db_criteria_Item('not_uid', (int)$user_id));
		$objects = $this->getObjects($criteria);
		if (count($objects) == 1) {
			return $objects[0];
		}
		$inst = false;
		return $inst;
	}

	/**
	 * Determine if a user is subscribed to a particular event in
	 * a particular module.
	 *
	 * @param  string  $category  Category of notification event
	 * @param  int     $item_id   Item ID of notification event
	 * @param  string  $event     Event
	 * @param  int     $module_id ID of module (default current module)
	 * @param  int     $user_id   ID of user (default current user)
	 * return int  0 if not subscribe; non-zero if subscribed (boolean... sort of)
	 */
	public function isSubscribed($category, $item_id, $event, $module_id, $user_id) {
		$criteria = new icms_db_criteria_Compo();
		$criteria->add(new icms_db_criteria_Item('not_modid', (int)$module_id));
		$criteria->add(new icms_db_criteria_Item('not_category', icms::$xoopsDB->escape($category)));
		$criteria->add(new icms_db_criteria_Item('not_itemid', (int)$item_id));
		$criteria->add(new icms_db_criteria_Item('not_event', icms::$xoopsDB->escape($event)));
		$criteria->add(new icms_db_criteria_Item('not_uid', (int)$user_id));
		return $this->getCount($criteria);
	}

	// TODO: how about a function to subscribe a whole group of users???
	// e.g. if we want to add all moderators to be notified of subscription
	// of new threads...
	/**
	 * Subscribe for notification for an event(s)
	 *
	 * @param  string $category    category of notification
	 * @param  int    $item_id     ID of the item
	 * @param  mixed  $events      event string or array of events
	 * @param  int    $mode        force a particular notification mode
	 *                             (e.g. once_only) (default to current user preference)
	 * @param  int    $module_id   ID of the module (default to current module)
	 * @param  int    $user_id     ID of the user (default to current user)
	 **/
	public function subscribe($category, $item_id, $events, $mode=null, $module_id=null, $user_id=null) {
		if (!isset($user_id)) {
			if (empty(icms::$user)) {
				return false;  // anonymous cannot subscribe
			} else {
				$user_id = icms::$user->getVar('uid');
			}
		}

		if (!isset($module_id)) {
			global $icmsModule;
			$module_id = $icmsModule->getVar('mid');
		}

		if (!isset($mode)) {
			$user = new icms_member_user_Object($user_id);
			$mode = $user->getVar('notify_mode');
		}

		if (!is_array($events)) $events = array($events);
		foreach ($events as $event) {
			if ($notification =& $this->getNotification($module_id, $category, $item_id, $event, $user_id)) {
				if ($notification->getVar('not_mode') != $mode) {
					$this->updateByField($notification, 'not_mode', $mode);
				}
			} else {
				$notification =& $this->create();
				$notification->setVar('not_modid', $module_id);
				$notification->setVar('not_category', $category);
				$notification->setVar('not_itemid', $item_id);
				$notification->setVar('not_uid', $user_id);
				$notification->setVar('not_event', $event);
				$notification->setVar('not_mode', $mode);
				$this->insert($notification);
			}
		}
	}

	// TODO: this will be to provide a list of everything a particular
	// user has subscribed to... e.g. for on the 'Profile' page, similar
	// to how we see the various posts etc. that the user has made.
	// We may also want to have a function where we can specify module id
	/**
	 * Get a list of notifications by user ID
	 *
	 * @param  int  $user_id  ID of the user
	 *
	 * @return array  Array of {@link icms_data_notification_Object} objects
	 **/
	public function getByUser($user_id) {
		$criteria = new icms_db_criteria_Item('not_uid', $user_id);
		return $this->getObjects($criteria, true);
	}

	// TODO: rename this??
	/**
	* Get a list of notification events for the current item/mod/user
	* @param  string   $category  category for the subscribed events
	* @param  int      $item_id  ID of the subscribed items
	* @param  int      $module_id  ID of the module of the subscribed items
	* @param  int      $user_id  ID of the user of the subscribed items
	* @return array    Array of {@link icms_data_notification_Object} objects
	**/
	public function getSubscribedEvents($category, $item_id, $module_id, $user_id) {
		$criteria = new icms_db_criteria_Compo();
		$criteria->add(new icms_db_criteria_Item('not_modid', (int) $module_id));
		$criteria->add(new icms_db_criteria_Item('not_category', icms::$xoopsDB->escape($category)));
		if ($item_id) {
			$criteria->add(new icms_db_criteria_Item('not_itemid', (int) $item_id));
		}
		$criteria->add(new icms_db_criteria_Item('not_uid', (int)$user_id));
		$results = $this->getObjects($criteria, true);
		$ret = array();
		foreach (array_keys($results) as $i) {
			$ret[] = $results[$i]->getVar('not_event');
		}
		return $ret;
	}

	// TODO: is this a useful function?? (Copied from comment_handler)
	/**
	 * Retrieve items by their ID
	 *
	 * @param   int     $module_id  Module ID
	 * @param   int     $item_id    Item ID
	 * @param   string  $order      Sort order
	 * @param   string  $status     status
	 *
	 * @return  array   Array of {@link icms_data_notification_Object} objects
	 **/
	public function getByItemId($module_id, $item_id, $order = null, $status = null) {
		$criteria = new icms_db_criteria_Compo(new icms_db_criteria_Item('com_modid', (int) $module_id));
		$criteria->add(new icms_db_criteria_Item('com_itemid', (int) $item_id));
		if (isset($status)) {
			$criteria->add(new icms_db_criteria_Item('com_status', (int) $status));
		}
		if (isset($order)) {
			$criteria->setOrder($order);
		}
		return $this->getObjects($criteria);
	}

	/**
	 * Send notifications to users
	 *
	 * @param  string   $category     notification category
	 * @param  int      $item_id      ID of the item
	 * @param  string   $event        notification event
	 * @param  array    $extra_tags   array of substitutions for template to be
	 *                                merged with the one from function..
	 * @param  array    $user_list    only notify the selected users
	 * @param  int      $module_id    ID of the module
	 * @param  int      $omit_user_id ID of the user to omit from notifications. (default to current user).  set to 0 for all users to receive notification.
	 **/
	// TODO:(?) - pass in an event LIST.  This will help to avoid
	// problem of sending people multiple emails for similar events.
	// BUT, then we need an array of mail templates, etc...  Unless
	// mail templates can include logic in the future, then we can
	// tailor the mail so it makes sense for any of the possible
	// (or combination of) events.
	public function triggerEvents($category, $item_id, $events, $extra_tags=array(), $user_list=array(), $module_id=null, $omit_user_id=null) {
		if (!is_array($events)) {
			$events = array($events);
		}
		foreach ($events as $event) {
			$this->triggerEvent($category, $item_id, $event, $extra_tags, $user_list, $module_id, $omit_user_id);
		}
	}

	/**
	 * Send notifications to users
	 *
	 * @param  string   $category     notification category
	 * @param  int      $item_id      ID of the item
	 * @param  string   $event        notification event
	 * @param  array    $extra_tags   array of substitutions for template to be
	 *                                merged with the one from function..
	 * @param  array    $user_list    only notify the selected users
	 * @param  int      $module_id    ID of the module
	 * @param  int      $omit_user_id ID of the user to omit from notifications. (default to current user).  set to 0 for all users to receive notification.
	 **/
	public function triggerEvent($category, $item_id, $event, $extra_tags=array(), $user_list=array(), $module_id=null, $omit_user_id=null) {
		if (!isset($module_id)) {
			global $icmsModule;
			$module =& $icmsModule;
			$module_id = !empty($icmsModule) ? $icmsModule->getVar('mid') : 0;
		} else {
			$module_handler = icms::handler('icms_module');
			$module =& $module_handler->get($module_id);
		}

		// Check if event is enabled
		$mod_config =& icms::$config->getConfigsByCat(0,$module->getVar('mid'));
		if (empty($mod_config['notification_enabled'])) {
			return false;
		}
		$category_info =& $this->categoryInfo($category, $module_id);
		$event_info =& $this->eventInfo($category, $event, $module_id);
		if (!in_array($this->generateConfig($category_info,$event_info, 'option_name'), $mod_config['notification_events']) && empty($event_info['invisible'])) {
			return false;
		}

		if (!isset($omit_user_id)) {
			if (!empty(icms::$user)) {
				$omit_user_id = icms::$user->getVar('uid');
			} else {
				$omit_user_id = 0;
			}
		}
		$criteria = new icms_db_criteria_Compo();
		$criteria->add(new icms_db_criteria_Item('not_modid', (int) $module_id));
		$criteria->add(new icms_db_criteria_Item('not_category', icms::$xoopsDB->escape($category)));
		$criteria->add(new icms_db_criteria_Item('not_itemid', (int) $item_id));
		$criteria->add(new icms_db_criteria_Item('not_event', icms::$xoopsDB->escape($event)));
		$mode_criteria = new icms_db_criteria_Compo();
		$mode_criteria->add(new icms_db_criteria_Item('not_mode', XOOPS_NOTIFICATION_MODE_SENDALWAYS), 'OR');
		$mode_criteria->add(new icms_db_criteria_Item('not_mode', XOOPS_NOTIFICATION_MODE_SENDONCETHENDELETE), 'OR');
		$mode_criteria->add(new icms_db_criteria_Item('not_mode', XOOPS_NOTIFICATION_MODE_SENDONCETHENWAIT), 'OR');
		$criteria->add($mode_criteria);
		if (!empty($user_list)) {
			$user_criteria = new icms_db_criteria_Compo();
			foreach ($user_list as $user) {
				$user_criteria->add(new icms_db_criteria_Item('not_uid', $user), 'OR');
			}
			$criteria->add($user_criteria);
		}
		$notifications =& $this->getObjects($criteria);
		if (empty($notifications)) {
			return;
		}

		// Add some tag substitutions here
		$not_config = $module->getInfo('notification');
		$tags = array();
		if (!empty($not_config)) {
			if (!empty($not_config['tags_file'])) {
				$tags_file = ICMS_ROOT_PATH . '/modules/' . $module->getVar('dirname') . '/' . $not_config['tags_file'];
				if (file_exists($tags_file)) {
					include_once $tags_file;
					if (!empty($not_config['tags_func'])) {
						$tags_func = $not_config['tags_func'];
						if (function_exists($tags_func)) {
							$tags = $tags_func(icms::$xoopsDB->escape($category), (int) $item_id, icms::$xoopsDB->escape($event));
						}
					}
				}
			}
			// RMV-NEW
			if (!empty($not_config['lookup_file'])) {
				$lookup_file = ICMS_ROOT_PATH . '/modules/' . $module->getVar('dirname') . '/' . $not_config['lookup_file'];
				if (file_exists($lookup_file)) {
					include_once $lookup_file;
					if (!empty($not_config['lookup_func'])) {
						$lookup_func = $not_config['lookup_func'];
						if (function_exists($lookup_func)) {
							$item_info = $lookup_func(icms::$xoopsDB->escape($category), (int) $item_id);
						}
					}
				}
			}
		}

		$tags['X_ITEM_NAME'] = !empty($item_info['name']) ? $item_info['name'] : '[' . _NOT_ITEMNAMENOTAVAILABLE . ']';
		$tags['X_ITEM_URL']  = !empty($item_info['url']) ? $item_info['url'] : '[' . _NOT_ITEMURLNOTAVAILABLE . ']';
		$tags['X_ITEM_TYPE'] = !empty($category_info['item_name']) ? $category_info['title'] : '[' . _NOT_ITEMTYPENOTAVAILABLE . ']';
		$tags['X_MODULE'] = $module->getVar('name');
		$tags['X_MODULE_URL'] = ICMS_URL . '/modules/' . $module->getVar('dirname') . '/';
		$tags['X_NOTIFY_CATEGORY'] = $category;
		$tags['X_NOTIFY_EVENT'] = $event;

		$template_dir = $event_info['mail_template_dir'];
		$template = $event_info['mail_template'] . '.tpl';
		$subject = $event_info['mail_subject'];

		foreach ($notifications as $notification) {
			if (empty($omit_user_id) || $notification->getVar('not_uid') != $omit_user_id) {
				// user-specific tags
				//$tags['X_UNSUBSCRIBE_URL'] = 'TODO';
				// TODO: don't show unsubscribe link if it is 'one-time' ??
				$tags['X_UNSUBSCRIBE_URL'] = ICMS_URL . '/notifications.php';
				$tags = array_merge($tags, $extra_tags);

				$notification->notifyUser($template_dir, $template, $subject, $tags);
			}
		}
	}

	/**
	 * Delete all notifications for one user
	 *
	 * @param   int $user_id  ID of the user
	 * @return  bool
	 **/
	public function unsubscribeByUser($user_id) {
		$criteria = new icms_db_criteria_Item('not_uid', (int)$user_id);
		return $this->deleteAll($criteria);
	}

	// TODO: allow these to use current module, etc...
	/**
	 * Unsubscribe notifications for an event(s).
	 *
	 * @param  string  $category    category of the events
	 * @param  int     $item_id     ID of the item
	 * @param  mixed   $events      event string or array of events
	 * @param  int     $module_id   ID of the module (default current module)
	 * @param  int     $user_id     UID of the user (default current user)
	 *
	 * @return bool
	 **/
	public function unsubscribe($category, $item_id, $events, $module_id=null, $user_id=null) {
		if (!isset($user_id)) {
			if (empty(icms::$user)) {
				return false;  // anonymous cannot subscribe
			} else {
				$user_id = icms::$user->getVar('uid');
			}
		}

		if (!isset($module_id)) {
			global $icmsModule;
			$module_id = $icmsModule->getVar('mid');
		}

		$criteria = new icms_db_criteria_Compo();
		$criteria->add(new icms_db_criteria_Item('not_modid', (int) $module_id));
		$criteria->add(new icms_db_criteria_Item('not_category', icms::$xoopsDB->escape($category)));
		$criteria->add(new icms_db_criteria_Item('not_itemid', (int) $item_id));
		$criteria->add(new icms_db_criteria_Item('not_uid', (int) $user_id));
		if (!is_array($events)) {
			$events = array($events);
		}
		$event_criteria = new icms_db_criteria_Compo();
		foreach ($events as $event) {
			$event_criteria->add(new icms_db_criteria_Item('not_event', icms::$xoopsDB->escape($event)), 'OR');
		}
		$criteria->add($event_criteria);
		return $this->deleteAll($criteria);
	}

	// TODO: When 'update' a module, may need to switch around some
	//  notification classes/IDs...  or delete the ones that no longer
	//  exist.
	/**
	 * Delete all notifications for a particular module
	 *
	 * @param   int $module_id  ID of the module
	 * @return  bool
	 **/
	public function unsubscribeByModule($module_id) {
		$criteria = new icms_db_criteria_Item('not_modid', (int)$module_id);
		return $this->deleteAll($criteria);
	}

	/**
	 * Delete all subscriptions for a particular item.
	 *
	 * @param  int    $module_id  ID of the module to which item belongs
	 * @param  string $category   Notification category of the item
	 * @param  int    $item_id    ID of the item
	 *
	 * @return bool
	 **/
	public function unsubscribeByItem($module_id, $category, $item_id) {
		$criteria = new icms_db_criteria_Compo();
		$criteria->add(new icms_db_criteria_Item('not_modid', (int) $module_id));
		$criteria->add(new icms_db_criteria_Item('not_category', icms::$xoopsDB->escape($category)));
		$criteria->add(new icms_db_criteria_Item('not_itemid', (int) $item_id));
		return $this->deleteAll($criteria);
	}

	/**
	 * Perform notification maintenance activites at login time.
	 * In particular, any notifications for the newly logged-in
	 * user with mode XOOPS_NOTIFICATION_MODE_WAITFORLOGIN are
	 * switched to mode XOOPS_NOTIFICATION_MODE_SENDONCETHENWAIT.
	 *
	 * @param  int  $user_id  ID of the user being logged in
	 **/
	public function doLoginMaintenance($user_id) {
		$criteria = new icms_db_criteria_Compo();
		$criteria->add(new icms_db_criteria_Item('not_uid', (int) $user_id));
		$criteria->add(new icms_db_criteria_Item('not_mode', XOOPS_NOTIFICATION_MODE_WAITFORLOGIN));

		$notifications = $this->getObjects($criteria, true);
		foreach ($notifications as $n) {
			$n->setVar('not_mode', XOOPS_NOTIFICATION_MODE_SENDONCETHENWAIT);
			$this->insert($n);
		}
	}

	/**
	 * Update
	 *
	 * @param   object  &$notification  {@link icms_data_notification_Object} object
	 * @param   string  $field_name     Name of the field
	 * @param   mixed   $field_value    Value to write
	 *
	 * @return  bool
	 **/
	public function updateByField(&$notification, $field_name, $field_value) {
		$notification->unsetNew();
		$notification->setVar($field_name, $field_value);
		return $this->insert($notification);
	}

	/**
	 * Determine if notification is enabled for the selected module.
	 *
	 * Replaces function notificationEnabled()
	 *
	 * @param  string  $style	  Subscription style: 'block' or 'inline'
	 * @param  int	 $module_id  ID of the module  (default current module)
	 * @return bool
	 */
	static public function isEnabled($style, $module_id=null) {
		if (isset($GLOBALS['xoopsModuleConfig']['notification_enabled'])) {
			$status = $GLOBALS['xoopsModuleConfig']['notification_enabled'];
		} else {
			if (!isset($module_id)) {
				return false;
			}
			$module_handler = icms::handler('icms_module');
			$module =& $module_handler->get($module_id);
			if (!empty($module) && $module->getVar('hasnotification') == 1) {
				$config = icms::$config->getConfigsByCat(0, $module_id);
				$status = $config['notification_enabled'];
			} else {
				return false;
			}
		}
		include_once ICMS_ROOT_PATH . '/include/notification_constants.php';
		if (($style == 'block') && ($status == XOOPS_NOTIFICATION_ENABLEBLOCK || $status == XOOPS_NOTIFICATION_ENABLEBOTH)) {
			return true;
		}
		if (($style == 'inline') && ($status == XOOPS_NOTIFICATION_ENABLEINLINE || $status == XOOPS_NOTIFICATION_ENABLEBOTH)) {
			return true;
		}
		return false;
	}

	/**
	 * Replaces function &notificationCategoryInfo()
	 *
	 * Get an associative array of info for a particular notification
	 * category in the selected module.  If no category is selected,
	 * return an array of info for all categories.
	 *
	 * @param  string  $name	   Category name (default all categories)
	 * @param  int	 $module_id  ID of the module (default current module)
	 * @return mixed
	 */
	static public function &categoryInfo($category_name='', $module_id=null) {
		if (!isset($module_id)) {
			global $icmsModule;
			$module_id = !empty($icmsModule) ? $icmsModule->getVar('mid') : 0;
			$module =& $icmsModule;
		} else {
			$module_handler = icms::handler('icms_module');
			$module =& $module_handler->get($module_id);
		}
		$not_config =& $module->getInfo('notification');
		if (empty($category_name)) {
			return $not_config['category'];
		}
		foreach ($not_config['category'] as $category) {
			if ($category['name'] == $category_name) {
				return $category;
			}
		}
		$ret = false;
		return $ret;
	}

	/**
	 * Replaces function &notificationCommentCategoryInfo()
	 *
	 * Get associative array of info for the category to which comment events
	 * belong.
	 *
	 * @todo This could be more efficient... maybe specify in
	 *		$modversion['comments'] the notification category.
	 *	   This would also serve as a way to enable notification
	 *		of comments, and also remove the restriction that
	 *		all notification categories must have unique item_name. (TODO)
	 *
	 * @param  int  $module_id  ID of the module (default current module)
	 * @return mixed			Associative array of category info
	 */
	static public function &commentCategoryInfo($module_id=null) {
		$ret = false;
		$all_categories =& self::categoryInfo('', $module_id);
		if (empty($all_categories)) {
			return $ret;
		}
		foreach ($all_categories as $category) {
			$all_events =& self::categoryEvents($category['name'], false, $module_id);
			if (empty($all_events)) {
				continue;
			}
			foreach ($all_events as $event) {
				if ($event['name'] == 'comment') {
					return $category;
				}
			}
		}
		return $ret;
	}

	// TODO: some way to include or exclude admin-only events...

	/**
	 * Replaces function &notificationEvents()
	 *
	 * Get an array of info for all events (each event has associative array)
	 * in the selected category of the selected module.
	 *
	 * @param  string  $category_name  Category name
	 * @param  bool	$enabled_only   If true, return only enabled events
	 * @param  int	 $module_id	  ID of the module (default current module)
	 * @return mixed
	 */
	static public function &categoryEvents($category_name, $enabled_only, $module_id=null) {
		if (!isset($module_id)) {
			global $icmsModule;
			$module_id = !empty($icmsModule) ? $icmsModule->getVar('mid') : 0;
			$module =& $icmsModule;
		} else {
			$module_handler = icms::handler('icms_module');
			$module =& $module_handler->get($module_id);
		}
		$not_config =& $module->getInfo('notification');
		$mod_config = icms::$config->getConfigsByCat(0,$module_id);

		$category =& self::categoryInfo($category_name, $module_id);

		global $icmsConfig;
		$event_array = array();

		$override_comment = false;
		$override_commentsubmit = false;
		$override_bookmark = false;

		foreach ($not_config['event'] as $event) {
			if ($event['category'] == $category_name) {
				$event['mail_template_dir'] = ICMS_ROOT_PATH . '/modules/' . $module->getVar('dirname') . '/language/' . $icmsConfig['language'] . '/mail_template/';
				if (!$enabled_only || self::eventEnabled($category, $event, $module)) {
					$event_array[] = $event;
				}
				if ($event['name'] == 'comment') {
					$override_comment = true;
				}
				if ($event['name'] == 'comment_submit') {
					$override_commentsubmit = true;
				}
				if ($event['name'] == 'bookmark') {
					$override_bookmark = true;
				}
			}
		}

		icms_loadLanguageFile('core', 'notification');

		// Insert comment info if applicable

		if ($module->getVar('hascomments')) {
			$com_config = $module->getInfo('comments');
			if (!empty($category['item_name']) && $category['item_name'] == $com_config['itemName']) {
				$mail_template_dir = ICMS_ROOT_PATH . '/language/' . $icmsConfig['language'] . '/mail_template/';
				include_once ICMS_ROOT_PATH . '/include/comment_constants.php';
				$com_config = icms::$config->getConfigsByCat(0, $module_id);
				if (!$enabled_only) {
					$insert_comment = true;
					$insert_submit = true;
				} else {
					$insert_comment = false;
					$insert_submit = false;
					switch($com_config['com_rule']) {
						case XOOPS_COMMENT_APPROVENONE:
							// comments disabled, no comment events
							break;
						case XOOPS_COMMENT_APPROVEALL:
							// all comments are automatically approved, no 'submit'
							if (!$override_comment) {
								$insert_comment = true;
							}
							break;
						case XOOPS_COMMENT_APPROVEUSER:
						case XOOPS_COMMENT_APPROVEADMIN:
							// comments first submitted, require later approval
							if (!$override_comment) {
								$insert_comment = true;
							}
							if (!$override_commentsubmit) {
								$insert_submit = true;
							}
							break;
					}
				}
				if ($insert_comment) {
					$event = array('name'=>'comment', 'category'=>$category['name'], 'title'=>_NOT_COMMENT_NOTIFY, 'caption'=>_NOT_COMMENT_NOTIFYCAP, 'description'=>_NOT_COMMENT_NOTIFYDSC, 'mail_template_dir'=>$mail_template_dir, 'mail_template'=>'comment_notify', 'mail_subject'=>_NOT_COMMENT_NOTIFYSBJ);
					if (!$enabled_only || self::eventEnabled($category, $event, $module)) {
						$event_array[] = $event;
					}
				}
				if ($insert_submit) {
					$event = array('name'=>'comment_submit', 'category'=>$category['name'], 'title'=>_NOT_COMMENTSUBMIT_NOTIFY, 'caption'=>_NOT_COMMENTSUBMIT_NOTIFYCAP, 'description'=>_NOT_COMMENTSUBMIT_NOTIFYDSC, 'mail_template_dir'=>$mail_template_dir, 'mail_template'=>'commentsubmit_notify', 'mail_subject'=>_NOT_COMMENTSUBMIT_NOTIFYSBJ, 'admin_only'=>1);
					if (!$enabled_only || self::eventEnabled($category, $event, $module)) {
						$event_array[] = $event;
					}
				}


			}
		}

		// Insert bookmark info if appropriate

		if (!empty($category['allow_bookmark'])) {
			if (!$override_bookmark) {
				$event = array('name'=>'bookmark', 'category'=>$category['name'], 'title'=>_NOT_BOOKMARK_NOTIFY, 'caption'=>_NOT_BOOKMARK_NOTIFYCAP, 'description'=>_NOT_BOOKMARK_NOTIFYDSC);
				if (!$enabled_only || self::eventEnabled($category, $event, $module)) {
					$event_array[] = $event;
				}
			}
		}

		return $event_array;

	}

	/**
	 * Replaces function notificationEventEnabled()
	 *
	 * Determine whether a particular notification event is enabled.
	 * Depends on module config options.
	 *
	 * @todo  Check that this works correctly for comment and other
	 *   events which depend on additional config options...
	 *
	 * @param  array  $category  Category info array
	 * @param  array  $event	 Event info array
	 * @param  object $module	Module
	 * @return bool
	 **/
	static public function eventEnabled(&$category, &$event, &$module) {
		$mod_config = icms::$config->getConfigsByCat(0,$module->getVar('mid'));

		if (is_array($mod_config['notification_events']) && $mod_config['notification_events'] != array()) {
			$option_name = self::generateConfig($category, $event, 'option_name');
			if (in_array($option_name, $mod_config['notification_events'])) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Replaces function &notificationEventInfo()
	 *
	 * Get associative array of info for the selected event in the selected
	 * category (for the selected module).
	 *
	 * @param  string  $category_name  Notification category
	 * @param  string  $event_name	 Notification event
	 * @param  int	 $module_id	  ID of the module (default current module)
	 * @return mixed
	 */
	static public function &eventInfo($category_name, $event_name, $module_id=null) {
		$all_events =& self::categoryEvents($category_name, false, $module_id);
		foreach ($all_events as $event) {
			if ($event['name'] == $event_name) {
				return $event;
			}
		}
		$ret = false;
		return $ret;
	}

	/**
	 * Replaces function &notificationSubscribableCategoryInfo()
	 *
	 * Get an array of associative info arrays for subscribable categories
	 * for the selected module.
	 *
	 * @param  int  $module_id  ID of the module
	 * @return mixed
	 */
	static public function &subscribableCategoryInfo($module_id=null) {
		$all_categories =& self::categoryInfo('', $module_id);

		// FIXME: better or more standardized way to do this?
		$script_url = explode('/', $_SERVER['PHP_SELF']);
		$script_name = $script_url[count($script_url)-1];

		$sub_categories = array();

		foreach ($all_categories as $category) {

			// Check the script name

			$subscribe_from = $category['subscribe_from'];
			if (!is_array($subscribe_from)) {
				if ($subscribe_from == '*') {
					$subscribe_from = array($script_name);
					// FIXME: this is just a hack: force a match
				} else {
					$subscribe_from = array($subscribe_from);
				}
			}
			if (!in_array($script_name, $subscribe_from)) {
				continue;
			}

			// If 'item_name' is missing, automatic match.  Otherwise
			// check if that argument exists...

			if (empty($category['item_name'])) {
				$category['item_name'] = '';
				$category['item_id'] = 0;
				$sub_categories[] = $category;
			} else {
				$item_name = $category['item_name'];
				$id = ($item_name != '' && isset($_GET[$item_name])) ? (int) ($_GET[$item_name]) : 0;
				if ($id > 0)  {
					$category['item_id'] = $id;
					$sub_categories[] = $category;
				}
			}
		}
		return $sub_categories;

	}

	/**
	 * Replaces function notificationGenerateConfig()
	 *
	 * Generate module config info for a particular category, event pair.
	 * The selectable config options are given names depending on the
	 * category and event names, and the text depends on the category
	 * and event titles.  These are pieced together in this function in
	 * case we wish to alter the syntax.
	 *
	 * @param  array  $category  Array of category info
	 * @param  array  $event	 Array of event info
	 * @param  string $type	  The particular name to generate
	 * return string
	 **/
	static public function generateConfig(&$category, &$event, $type) {
		switch ($type) {
			case 'option_value':
			case 'name':
				return 'notify:' . $category['name'] . '-' . $event['name'];
				break;

			case 'option_name':
				return $category['name'] . '-' . $event['name'];
				break;

			default:
				return false;
				break;
		}
	}
}