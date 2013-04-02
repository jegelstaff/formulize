<?php

/**
 * Formulize Object for API calls
 */
class Formulize {

	private static $db = null;

	//Resource types and tables for the mapping methods
	const GROUP_RESOURCE = 0;
	const USER_RESOURCE = 1;
	private static $mapping_table = 'formulize_resource_mapping';
	private static $default_mapping_active = 1;
	
	/**
	 * Intialize the Formulize environment
	 */
	static function init() {
		if (self::$db == null) {
			include_once('mainfile.php');
			require_once('modules/formulize/include/functions.php');

			self::$db = $GLOBALS['xoopsDB'];
			self::$db->allowWebChanges = true;
		}
	}
	
	/**
	 * Create a new XOOPS user from the provided FormulizeUser data
	 * @param   user_data   FormulizeUser       The user data
	 * @return        boolean       Whether the user was successfully created
	 */
	static function createUser($user_data) {
		self::init();
		if($user_data->get('uid') == -1)
			throw new Exception('Formulize::createUser() - The supplied user doesn\'t have an ID.');
		//Create a XOOPS user from the provided FormulizeUser data
		$member_handler = xoops_gethandler('member');
		$newUser = $member_handler->createUser();
		$newUser->setVar('uname', $user_data->get('uname'));
		$newUser->setVar('login_name', $user_data->get('login_name'));
		$newUser->setVar('email', $user_data->get('email'));
		//Use the default timezone offset from ImpressCMS
		$newUser->setVar('timezone_offset', $user_data->get('timezone_offset'));
		$newUser->setVar('notify_method', $user_data->get('notify_method')); //email
		$newUser->setVar('level', $user_data->get('level')); //active, can login
		//If the user wasn't inserted, return false
		if (!$member_handler->insertUser($newUser, true)) {
			return false;
		}
		//Map the created user to the external ID provided
		$user_id = $newUser->getVar('uid');
		return self::createResourceMapping(self::USER_RESOURCE, $user_data->get('uid'), $user_id);
	}

	/**
	 * Retrieves the specified User's data
	 * @param user_id   int       The ID of the user to retrieve
	 * @return        FormulizeUser The User's data
	 */
	static function getUser($user_id) {
		self::init();
		$user_id = self::getXoopsResourceID(self::USER_RESOURCE, $user_id);
		$member_handler = xoops_gethandler('member');
		$user = $member_handler->getUser($user_id);

		if($user) {
			return new FormulizeUser(array(
				'uid' => $user->getVar('uid'),
				'uname' => $user->getVar('uname'),
				'login_name' => $user->getVar('login_name'),
				'email' => $user->getVar('email'),
				'timezone_offset' => $user->getVar('timezone_offset'),
				'notify_method' => $user->getVar('notify_method'),
				'level' => $user->getVar('level')
			));
		} else {
			return null;
		}
	}

	/**
	 * Retrieves highest user ID
	 * @return        Integer The highest user ID
	 */
	static function getHighestUserID() {
		self::init();
		$member_handler = xoops_gethandler('member');
		$list = $member_handler->getUserList();
		$max = 0;

		foreach ($list as $key => $value) {
			if ($max < $key)
				$max = $key;
		}
		return $max;
	}

	/**
	 * Removes the specified user, and obfuscates their identification fields
	 * (uname, login_name, email) to allow those pieces of data to be used for
	 * future registrations.
	 * @param   user_id     int         The ID of the user to remove
	 * @return        boolean   Whether the user was successfully deleted
	 */
	static function deleteUser($user_id) {
		self::init();
		$external_id = $user_id;
		$xoops_user_id = self::getXoopsResourceID(self::USER_RESOURCE, $user_id);
		if(!$xoops_user_id) {
			return false;
		}
		$uuid = uniqid(); //Generate a UUID for obfuscation
		$member_handler = xoops_gethandler('member');
		$user = $member_handler->getUser($xoops_user_id);
		//Obfuscate identification
		if($user) {
			self::updateUser($external_id, array(
				'uname' => $uuid . '-' . $user->getVar('uname'),
				'login_name' => $uuid . '-' . $user->getVar('login_name'),
				'email' => $uuid . '-' . $user->getVar('email')
			));
			if($member_handler->deleteUser($user)) {
				return self::deactivateResourceMapping(self::USER_RESOURCE, $external_id);
			} else {
				return false;
			}
		} else {
			return false;
		}	
	}
	
	/**
	 * Updates user data in XOOPS
	 * @param   user_id   int     The ID of the user to update
	 * @param   user_data array   An associative array of fields to update
	 * @return        boolean   Whether the user was successfully updated
	 */
	static function updateUser($user_id, $data) {
		self::init();
		$xoops_user_id = self::getXoopsResourceID(self::USER_RESOURCE, $user_id);
		if(!$xoops_user_id) {
			return false;
		}
		$member_handler = xoops_gethandler('member');
		$xoops_user = $member_handler->getUser($xoops_user_id);
		//Update fields specified in $user_data
		if($xoops_user) {
			foreach($data as $key => $value) {
				$xoops_user->setVar($key, $value);
			}
			//Make sure the user ID isn't changed
			$xoops_user->setVar('uid', $xoops_user_id);
			//If the user wasn't inserted, return false
			return $member_handler->insertUser($xoops_user, true);
		} else {
			return false;
		}
	}
	
	/**
	 * Creates a new user group in XOOPS
	 * @param group   FormulizeGroup    The group to create
	 * @return        boolean       Whether the group was successfully created
	 */
	static function createGroup($group) {
		self::init();
		//Every group needs a name and ID
		if($group->get('name') == null || $group->get('groupid') == null)
			throw new Exception("Formulize::createGroup() - The supplied group needs a name and groupid.");
		//TODO: Figure out how to use XOOPS CriteriaElement to prevent duplicate group creation
		$group_handler = xoops_gethandler('group');
		$xoops_group = $group_handler->create(true);
		$xoops_group->setVar('name', $group->get('name'));
		$xoops_group->setVar('description', $group->get('description'));
		$xoops_group->setVar('group_type', $group->get('group_type'));

		//We only want to create this group if it doesn't already exist in XOOPS
		if(self::getXoopsResourceID(self::GROUP_RESOURCE, $group->get('groupid')) == NULL) {
			$result = $group_handler->insert($xoops_group);
			if($result) {
				//If the group was created, add it to the mapping
				return self::createResourceMapping(self::GROUP_RESOURCE, $group->get('groupid'), $xoops_group->getVar('groupid'));
			} else {
				return false;
			}
		} else {
			return false;
		}
	}

	/**
	 * Rename an existing XOOPS group
	 * @param   groupid     int         The ID of the group being renamed
	 * @param   name        String      The new name for the group
	 * @return        boolean   Whether the group was successfully renamed
	 */
	static function renameGroup($groupid, $name) {
		self::init();
		$group_handler = xoops_gethandler('group');
		$xoops_groupid = self::getXoopsResourceID(Formulize::GROUP_RESOURCE, $groupid);
		//If a group was found, rename it
		if($xoops_groupid != null) {
			$xoops_group = $group_handler->get($xoops_groupid);
			//If the ID matched, rename the group
			if($xoops_group) {
				$xoops_group->setVar('name', $name);
				return $group_handler->insert($xoops_group);
			//No group with this ID. Can't be renamed.
			} else {
				return false;
			}
		} else {
			return false;
		}
	}
	
	/**
	 * Deletes an existing XOOPS group
	 * TODO: Need to also remove the mapping when the group is deleted
	 * @param groupid   int     The ID of the XOOPS group to delete
	 * @return        boolean   Whether the group was successfully deleted
	 */
	static function deleteGroup($groupid) {
		self::init();
		$group_handler = xoops_gethandler('group');
		$xoops_groupid = self::getXoopsResourceID(Formulize::GROUP_RESOURCE, $groupid);
		//If a group was found, delete it
		if($xoops_groupid != null) {
			$xoops_group = $group_handler->get($xoops_groupid);   
			//If the ID matched, remove the group
			if($xoops_group) {
				if($group_handler->delete($xoops_group)) {
					return self::deactivateResourceMapping(self::GROUP_RESOURCE, $groupid);
				} else {
					return false;
				}
			//Else the group wasn't removed
			} else {
				return false;
			}
		} else {
			return false;
		}
	}
	
	/**
	 * Adds an existing user to a group
	 * @param   user_id   int         The ID of the user being added
	 * @param   groupid     int         The ID of the group being added to
	 * @return        boolean   BooleanWhether the user was successfully added to the group
	 */
	static function addUserToGroup($user_id, $groupid) {
		self::init();
		$user_id = self::getXoopsResourceID(self::USER_RESOURCE, $user_id);
		$members = xoops_gethandler('member');
		$internal_group = self::getXoopsResourceID(Formulize::GROUP_RESOURCE, $groupid);
		if($internal_group) {
			return $members->addUserToGroup($internal_group, $user_id);
		} else {
			return false;
		}
	}
	
	/**
	 * Removes an existing user from a group
	 * @param   user_id   int         The ID of the user being removed
	 * @param   groupid     int         The ID of the group being removed from
	 * @return        boolean   Whether the user was successfully removed from the group
	 */ 
	static function removeUserFromGroup($user_id, $groupid) {
		self::init();
		$user_id = self::getXoopsResourceID(self::USER_RESOURCE, $user_id);
		$members = xoops_gethandler('member');
		$internal_group = self::getXoopsResourceID(Formulize::GROUP_RESOURCE, $groupid);
		if($internal_group) {
			return $members->removeUsersFromGroup($internal_group, array($user_id));
		} else {
			return false;
		}
	}
	
	/**
	 * Obtain a list of the available screen names
	 * @param limitUser boolean   Whether to limit the list of screens to those
	 *                  viewable by the current user
	 * @return        Array   An array of screens (or an empty array if none are retrieved)
	 */
	static function getScreens($limitUser=false) {
		global $xoopsUser;
		self::init();
		$options = array();

		$form_table = self::$db->prefix('formulize_id');
		$screen_table = self::$db->prefix('formulize_screen');
		
		//Getting all screens is straightforward
		if(!$limitUser) {
			$sql = 
			'
				SELECT fi.desc_form, fs.title, fs.sid 
				FROM ' . $form_table . ' AS fi, ' . $screen_table . ' AS fs 
				WHERE fi.id_form = fs.fid 
				ORDER BY fi.desc_form, fs.title
			';
		//If only screens available to the current user are desired
		} else {
			if(!$xoopsUser) {
				$options[0] = ('No Formulize Screens Found');
				return $options;
			}
			$members = xoops_gethandler('member');
			$group_perms = xoops_gethandler('icms_member_groupperm');
			$accessible_forms = array();

			//Get the groups this member belongs to
			$groups = $members->getGroupsByUser($xoopsUser->getVar('uid'));
			//Get the forms visible to each of those groups, and unite them
			foreach($groups as $group) {
				$group_forms = $group_perms->getItemIds('view_form', $group, getFormulizeModId());
				$accessible_forms = array_merge($accessible_forms, $group_forms);
			}
			
			//Get the unique IDs of the accessible forms as integers
			$form_IDs = array_map(intval, array_unique($accessible_forms));
			$in_clause = implode(',', $form_IDs);

			$sql = 
			'
				SELECT fi.desc_form, fs.title, fs.sid 
				FROM ' . $form_table . ' AS fi, ' . $screen_table . ' AS fs 
				WHERE fi.id_form = fs.fid
					AND fi.id_form IN (' . $in_clause . ') 
				ORDER BY fi.desc_form, fs.title
			';
		}

		//Run the query and assemble/return the results
		if ($result = self::$db->query($sql)) {
			while($row = self::$db->fetchArray($result)) {
				$options[$row['sid']] = $row['desc_form'] . ' - ' . $row['title'];
			}
		}
		
		if (count($options) == 0 || !$xoopsUser) {
			$options[0] = 'No Formulize Screens Found';
		}

		return $options;
	}

	static function renderScreen ($screenID) {
		self::init();
		//Set the screen ID
		$formulize_screen_id = $screenID;

		//Declare a formulize div to contain our injected content, with ID formulize_form
		echo '<div id=formulize_form>';
		
		//Include our header file in order to set up xoTheme
		include XOOPS_ROOT_PATH . '/header.php';
		
		//If we have a xoTheme, then we will be able to dupe the Formulize system into thinking we are in icms, in order
		//to set up an icmsTheme object. The icmsTheme object is required by a number of elements that should work in 3rd
		//party sites (i.e. datebox). We thus mimic what occurs in icms and set up our theme object accordingly.
		if($xoTheme)
		{
			global $icmsTheme;
			$icmsTheme = $xoTheme;
		}
		
		//We buffer our output of HTML injection. This prevents the buffer from being printed before we have printed and loaded our
		//JS scripts to the page.
		ob_start;
		include XOOPS_ROOT_PATH . '/modules/formulize/index.php';
		//Content now contains our buffered contents.
		$content = ob_get_clean();
		
		//Checks icmsTheme is initialized. If this is so, it will drop into further conditionals to check those
		//dependencies relying on library JS files from Formulize stand-alone directory.
		if($icmsTheme)
		{
			//If this global is set, then we are requiring a date-box element. In that case we shall add the following
			//scripts to our page load, in order for the calendar to achieve functionality.
			if(isset($GLOBALS['formulize_calendarFileRequired']))
			{
				echo "<script type='text/javascript' src='" . ICMS_URL . "/libraries/jalalijscalendar/calendar.js'></script>";
				echo "<script type='text/javascript' src='" . ICMS_URL . "/libraries/jalalijscalendar/calendar-setup.js'></script>";
				echo "<script type='text/javascript' src='" . ICMS_URL . "/libraries/jalalijscalendar/jalali.js'></script>";
				echo "<script type='text/javascript' src='" . ICMS_URL . "/language/" . $icmsConfig['language'] . "/local.date.js'></script>";
				echo "<script type='text/javascript'>".$GLOBALS['formulize_calendarFileRequired']."</script>";
				
				//In order to append our stylesheet, and ensure that no matter the load and buffer order of our page, we shall be including
				//the style sheet via a JS call that appends the link tag to the head section on load.
				echo
				"
					<script type='text/javascript'>
					function fetchCalendarCSS(fileURL)
					{
						var newNode=document.createElement('link');
						newNode.setAttribute('rel', 'stylesheet');
						newNode.setAttribute('type', 'text/css');
						newNode.setAttribute('href', fileURL);
						document.getElementsByTagName('head')[0].appendChild(newNode);
					}
					fetchCalendarCSS('" . ICMS_URL . "/libraries/jalalijscalendar/aqua/style.css');
					</script>
				";
			}
		}
		
		//Inject formulize content
		echo $content;
		//Close our div tag
		echo '</div>';
	}

	/**
	 * Insert a mapping from the external resource to a Formulize resource
	 * @param external_id     int     The external resource ID
	 * @param id          int     The Formulize resource ID
	 * @return            boolean   Whether mapping was successful
	 * @throws  An exception is thrown if a supplied ID is not of integer form.
	 */
	public static function createResourceMapping($resource_type, $external_id, $id) {
		self::init();
		$mapping_table = self::$db->prefix(self::$mapping_table);
		//Determine whether any mappings exist with the specified IDs
		$num_mappings = mysql_num_rows(self::$db->queryF('
			SELECT * FROM ' . $mapping_table . ' 
			WHERE (internal_id = ' . intval($id) . ' 
				AND resource_type = ' . intval($resource_type) . ') 
			OR (external_id = ' . intval($external_id) . '
				AND resource_type = ' . intval($resource_type) . ')'
		));
		//+0 will allow string input to be implicitly cast to a numeric
		//type and then checked for integer form
		if(!is_int($external_id + 0) || !is_int($id + 0))
			throw new Exception('Formulize::createResourceMapping() - Expecting two integer IDs.');
		if($num_mappings == 0) {
			return self::$db->queryF('
				INSERT INTO ' . $mapping_table . '
				(external_id, internal_id, resource_type, mapping_active)
				VALUES ( ' . intval($external_id) . ', ' . intval($id) . ', ' . intval($resource_type) . ', ' . self::$default_mapping_active . ')'
			);
		} else {
			//A group mapping containing at least one of the IDs already exists. Can't create it.
			return false;
		}
	}

	public static function deactivateResourceMapping($resource_type, $external_id) {
		self::init();
		$mapping_table = self::$db->prefix(self::$mapping_table);
		return self::$db->queryF('
			UPDATE ' . $mapping_table . '
			SET mapping_active = 0' . '
			WHERE resource_type = ' . intval($resource_type) . '
			AND external_id = ' . intval($external_id)
		);
	}

	/**
	 * Converts an external resource ID into a XOOPS resource ID using the associated mapping table
	 * @param external_id  int   The external resource ID to convert
	 * @return            int   The associated XOOPS resource ID
	 */
	static function getXoopsResourceID($resource_type, $external_id) {
		self::init();
		$mapping_table = self::$db->prefix(self::$mapping_table);
		$mapping_result = mysql_fetch_row(self::$db->queryF('
			SELECT internal_id FROM ' . $mapping_table . '
			WHERE external_id = ' . intval($external_id) . '
			AND resource_type = ' . intval($resource_type) . '
			AND mapping_active = 1'
		));
		if ($mapping_result == NULL) {
			return NULL;
		}
		return intval($mapping_result[0]);
	}

	/**
	 * Converts an XOOPS resource ID into a external resource ID using the associated mapping table
	 * @param xoops_id  int   The external resource ID to convert
	 * @return            bool  true if external mapping exists.
	 */
	static function getExternalResourceID($resource_type, $xoops_id) {
		self::init();
		$mapping_table = self::$db->prefix(self::$mapping_table);
		$mapping_result = mysql_fetch_row(self::$db->queryF('
			SELECT external_id FROM ' . $mapping_table . '
			WHERE internal_id = ' . intval($xoops_id) . '
			AND resource_type = ' . intval($resource_type)
		));
		if ($mapping_result == NULL) {
			return NULL;
		}
		return $mapping_result[0];
	}
}

/**
 * Formulize objects extend this class so that their
 * instance fields can be manipulated.
 */
class FormulizeObject {

	/**
	 * Get the value of a field in this FormulizeUser
	 * @param   key     String      The name of the field to be retrieved
	 * @return      [AnyType] The requested property
	 */
	function get($key) {
		if(!isset($this->{$key}) && $this->{$key} != null) throw new Exception('FormulizeObject - Attempted to get an invalid object field: ' . $key);
		return $this->{$key};
	}

	/**
	 * Set the value of a field in this FormulizeUser
	 * @param   key     String      The name of the field to be retrieved
	 * @param   key     Object      The value to be stored in this field
	 */
	function set($key, $value) {
		if(!isset($this->{$key})) throw new Exception('FormulizeObject - Attempted to set an invalid object field: ' . $key);
		$this->{$key} = $value;
	}
}

/**
 * Formulize User Object
 */
class FormulizeUser extends FormulizeObject {

	protected $uid = -1;
	protected $uname = '';
	protected $login_name = '';
	protected $email = '';
	protected $timezone_offset = null;
	protected $notify_method = 2;
	protected $level = 1;
	
	/**
	 * Construct a Fomulize User object from CMS data
	 * @param   user_data    array   The user data acquired from the user object
	 *                              in the base CMS
	 */
	function __construct($user_data) {
		Formulize::init();

		if(isset($user_data['uid']))
			$this->uid = $user_data['uid'];
		if(isset($user_data['uname']))
			$this->uname = $user_data['uname'];
		if(isset($user_data['login_name']))
			$this->login_name = $user_data['login_name'];
		if(isset($user_data['email']))
			$this->email = $user_data['email'];
		if(isset($user_data['timezone_offset']))
			$this->timezone_offset = $user_data['timezone_offset'];

		//Set defaults if necessary
		if ($this->timezone_offset == null) {
			$this->timezone_offset = $GLOBALS['xoopsConfig']['default_TZ'];
		}
	}

}

/**
 * Formulize Group Object
 */
class FormulizeGroup extends FormulizeObject {

	protected $groupid = null;
	protected $name = null;
	protected $description = '';
	protected $group_type = 'User';

	/**
	 * Construct a Forulize User object from CMS data
	 * @param   user_data    array   The user data acquired from the user object
	 *                              in the base CMS
	 */
	function __construct($group_data) {
		Formulize::init();

		if(isset($group_data['groupid']))
			$this->groupid = $group_data['groupid'];
		if(isset($group_data['name']))
			$this->name = $group_data['name'];
		if(isset($group_data['description']))
			$this->description = $group_data['description'];
		if(isset($group_data['group_type']))
			$this->group_type = $group_data['group_type'];
	}

}