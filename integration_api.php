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
	 * Initialize the Formulize environment
	 */
	static function init() {
		if (self::$db == null) {
			include_once('mainfile.php');
			self::$db = $GLOBALS['xoopsDB'];
			self::$db->allowWebChanges = true;
            require_once('modules/formulize/include/functions.php');
		}
	}

	/**
	 * Create a new XOOPS user from the provided FormulizeUser data
	 * @param   user_data   FormulizeUser       The user data
	 * @return        boolean       Whether the user was successfully created
	 */
	static function createUser($user_data) {
		self::init();
		if($user_data->get('uid') == -1 && $user_data->get('email') == '')
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

        $newUserCreated = $member_handler->insertUser($newUser, true);
        
		if ($user_data->get('uid') == false AND $newUserCreated) {
			// if there is no user id and the new user was inserted successfully; create a mapping record for internal id and email
			return self::createResourceMapping(self::USER_RESOURCE, $user_data->get('email'), $newUser->getVar('uid'));
		} else if ($user_data->get('uid') == true AND $newUserCreated) {
            // new user account was created; create a mapping record for the new account id and the external id
            return self::createResourceMapping(self::USER_RESOURCE, $user_data->get('uid'), $newUser->getVar('uid'));
        } else {
            // user record could not be created, perhaps because it already exists, so try to load it from the database by email address
            $getuser =& $member_handler->getUsers(new icms_db_criteria_Item('email', icms_core_DataFilter::addSlashes($user_data->get('email'))));
            if (!empty($getuser) && $user_data->get('uid') == false) {
                    // we found an existing user with the same email address and the user id does not exist
                    // so create a resource mapping using email
                    return self::createResourceMapping(self::USER_RESOURCE, $user_data->get('email'), $getuser[0]->getVar('uid'));
            } else if (!empty($getuser)) {
                // we found an existing user with the same email address, so create a resource mapping
                return self::createResourceMapping(self::USER_RESOURCE, $user_data->get('uid'), $getuser[0]->getVar('uid'));
            }
        }
        return false;   // could not create a new account and an account with the email addres does not exist
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
     * @return        boolean           Whether the group was successfully created
     */
    static function createGroup($group_id, $group_name, $group_description, $group_type) {
        self::init();
        // confirm the group has a name and ID
        if ($group_name == null || $group_id == null)
            throw new Exception("Formulize::createGroup() - The supplied group needs a name and groupid.");

        // only create this group if it doesn't already exist in XOOPS
        if (null == self::getXoopsResourceID(self::GROUP_RESOURCE, $group_id)) {
            // TODO: Figure out how to use XOOPS CriteriaElement to prevent duplicate group creation .. or maybe do that in the group handler?
            $group_handler = xoops_gethandler('group');
            $xoops_group = $group_handler->create(true);
            $xoops_group->setVar('name', $group_name);
            $xoops_group->setVar('description', $group_description);
            $xoops_group->setVar('group_type', $group_type);
            if ($result = $group_handler->insert($xoops_group)) {
                // if the group was created, create a mapping record for it
                return self::createResourceMapping(self::GROUP_RESOURCE, $group_id, $xoops_group->getVar('groupid'));
            }
        }
        return false;
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
		// if a group was found, rename it
		if ($xoops_groupid != null) {
			$xoops_group = $group_handler->get($xoops_groupid);
			// if the ID matched, rename the group
			if ($xoops_group) {
				$xoops_group->setVar('name', $name);
				return $group_handler->insert($xoops_group);
			}
		}
		return false;
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
		// if a group was found, delete it
		if ($xoops_groupid != null) {
			$xoops_group = $group_handler->get($xoops_groupid);
			// if the ID matched, remove the group
			if ($xoops_group) {
				if ($group_handler->delete($xoops_group)) {
					return self::deactivateResourceMapping(self::GROUP_RESOURCE, $groupid);
				}
			}
		}
		return false;
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
		if ($internal_group) {
			return $members->addUserToGroup($internal_group, $user_id);
		}
		return false;
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
		}
		return false;
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
		ob_start();
		include XOOPS_ROOT_PATH . '/modules/formulize/index.php';
		//Content now contains our buffered contents.
		$formulizeContent = ob_get_clean();

		//Checks icmsTheme is initialized. If this is so, it will drop into further conditionals to check those
		//dependencies relying on library JS files from Formulize stand-alone directory.
		if($icmsTheme)
		{
			//If this global is set, then we are requiring a date-box element. In that case we shall add the following
			//scripts to our page load, in order for the calendar to achieve functionality.
			if(isset($GLOBALS['formulize_calendarFileRequired']))
			{
				// Include scripts for linking
				foreach($GLOBALS['formulize_calendarFileRequired']['scripts-for-linking'] as $thisScript) {
                                       $restOfContent .= "\n<script type='text/javascript' src='" . $thisScript . "'></script>\n";
                }
				// Include scripts for embedding
				foreach($GLOBALS['formulize_calendarFileRequired']['scripts-for-embedding'] as $thisScript) {
                                       $restOfContent .= "\n<script type='text/javascript'>". $thisScript ."</script>\n";
                }

				//In order to append our stylesheet, and ensure that no matter the load and buffer order of our page, we shall be including
				//the style sheet via a JS call that appends the link tag to the head section on load.
                // Do the same for jQuery and jQuery UI if they are not already loaded, since the calendar element requires them
				$restOfContent .=
				"
					<script type='text/javascript'>
function fetchCSS(href)
					{
						var newNode=document.createElement('link');
    newNode.rel = 'stylesheet';
    newNode.type = 'text/css';
    newNode.href = href;
    document.head.appendChild(newNode);
}

function fetchJS(src) {
    var newNode = document.createElement('script');
    newNode.type = 'text/javascript';
    newNode.src = src;
    document.head.appendChild(newNode);
}

document.addEventListener('DOMContentLoaded', function(event) {
    if(jQuery === undefined) {
        fetchJS('".XOOPS_URL."/libraries/jquery/jquery.js');
        fetchJS('".XOOPS_URL."/libraries/jquery/jquery-migrate-1.2.1.min.js');
        fetchJS('".XOOPS_URL."/libraries/jquery/ui/ui.min.js');
        fetchCSS('".XOOPS_URL."/libraries/jquery/ui/css/ui-smoothness/ui.css');
    } else if(jQuery.datepicker === undefined) {
        fetchJS('".XOOPS_URL."/libraries/jquery/jquery-migrate-1.2.1.min.js');
        fetchJS('".XOOPS_URL."/libraries/jquery/ui/ui.min.js');
        fetchCSS('".XOOPS_URL."/libraries/jquery/ui/css/ui-smoothness/ui.css');
    }
});
                    ";
					foreach($GLOBALS['formulize_calendarFileRequired']['stylesheets'] as $thisSheet) {
						$restOfContent .= " fetchCSS('" . $thisSheet ."'); ";
					}
					$restOfContent .= "</script>";
			}
		}

        // include the formulize.js file
        $restOfContent .= "\n<script type='text/javascript' src='" . XOOPS_URL. "/modules/formulize/libraries/formulize.js'></script>\n";


        //Declare a formulize div to contain our injected content, with ID formulize_form
		echo "<div id=formulize_form>\n".$restOfContent.$formulizeContent."\n</div>\n";
	}

	/**
	 * Insert a mapping from the external resource to a Formulize resource
	 * @param external_id     int/string    The external resource ID
	 * @param id          int     The Formulize resource ID
	 * @return            boolean   Whether mapping was successful
	 * @throws  An exception is thrown if a supplied ID is not of integer form.
	 */
	public static function createResourceMapping($resource_type, $external_id, $id) {
		self::init();
		$mapping_table = self::$db->prefix(self::$mapping_table);
        if($resource_type == self::USER_RESOURCE AND !is_numeric($external_id)) {
            $external_id_FIELD = "external_id_string";
            $external_id_VALUE = "'".formulize_db_escape($external_id)."'";
        } else if ($resource_type == self::GROUP_RESOURCE AND !is_numeric($external_id)) {
            $external_id_FIELD = "external_id_string";
            $external_id_VALUE = "'".formulize_db_escape($external_id)."'";
        } else {
            $external_id_FIELD = "external_id";
            $external_id_VALUE = intval($external_id);
        }
        $external_id_SQL = "$external_id_FIELD = $external_id_VALUE";

		//Determine whether any mappings exist with the specified IDs
		$num_mappings = self::$db->getRowsNum(self::$db->queryF('
			SELECT * FROM ' . $mapping_table . '
			WHERE (internal_id = ' . intval($id) . '
				AND resource_type = ' . intval($resource_type) . ')
			OR ('.$external_id_SQL.'
				AND resource_type = ' . intval($resource_type) . ')'
		));

		if($num_mappings == 0) {
			return self::$db->queryF('
				INSERT INTO ' . $mapping_table . '
				('.$external_id_FIELD.', internal_id, resource_type, mapping_active)
				VALUES ( ' . $external_id_VALUE . ', ' . intval($id) . ', ' . intval($resource_type) . ', ' . self::$default_mapping_active . ')'
			);
		} else {
			//A group mapping containing at least one of the IDs already exists. Can't create it.
			return false;
		}
	}

	public static function deactivateResourceMapping($resource_type, $external_id) {
		self::init();
        if(!$external_id) { return null; }
        if(!is_numeric($external_id)) {
            $external_id_SQL = "external_id_string = '" . formulize_db_escape($external_id) . "'";
        } else {
            $external_id_SQL = "external_id = " . intval($external_id);
        }

		$mapping_table = self::$db->prefix(self::$mapping_table);
		return self::$db->queryF('
			UPDATE ' . $mapping_table . '
			SET mapping_active = 0' . '
			WHERE resource_type = ' . intval($resource_type) . '
			AND '.$external_id_SQL
		);
	}

	/**
	 * updates an external resource ID in the associated mapping table
	 * @param external_id  string   The external resource ID to update (expects string format)
	 * @return            boolean the query success value
	 *  @author Kristen Newbury Feb 21 2018
	 */
    public static function updateResourceMapping($external_id_old, $external_id_new) {
		self::init();
        if(!$external_id_old||!$external_id_new) { return null; }
		$mapping_table = self::$db->prefix(self::$mapping_table);
		$external_id_oldSQL = "external_id_string = '" . formulize_db_escape($external_id_old) . "'";
		$external_id_newSQL = "external_id_string = '" . formulize_db_escape($external_id_new) . "'";
		return self::$db->queryF('
			UPDATE ' . $mapping_table . '
			SET  ' . $external_id_newSQL .'
			WHERE '.  $external_id_oldSQL 
		);
	}

	/**
	 * Converts an external resource ID into a XOOPS resource ID using the associated mapping table
	 * @param external_id  int/string   The external resource ID to convert
	 * @return            int   The associated XOOPS resource ID
	 */
	static function getXoopsResourceID($resource_type, $external_id) {
        if(!$external_id) { return null; }
		self::init();
        if(!is_numeric($external_id)) {
            $external_id_SQL = "external_id_string = '" . formulize_db_escape($external_id) . "'";
        } else {
            $external_id_SQL = "external_id = " . intval($external_id);
        }
		$mapping_table = self::$db->prefix(self::$mapping_table);
		$mapping_result = self::$db->fetchRow(self::$db->queryF('
			SELECT internal_id FROM ' . $mapping_table . '
			WHERE '.$external_id_SQL.'
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
		$mapping_result = self::$db->fetchRow(self::$db->queryF('
			SELECT external_id, external_id_string FROM ' . $mapping_table . '
			WHERE internal_id = ' . intval($xoops_id) . '
			AND resource_type = ' . intval($resource_type)
		));
		if ($mapping_result == NULL) {
			return NULL;
		}
        return $mapping_result[0] ? $mapping_result[0] : $mapping_result[1];
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
    
    function insertAndMapUser($groups) {
        
        global $icmsConfigUser;
        
        $login_name = $this->login_name;
        //parse the space out of the name
        $login_name = str_replace(' ', '', $login_name);
        $uname = $this->uname;
        $email = $this->email;
        //make a random but fake password here since we anticipate the user to only need google login, unless they change it later
        $pass = bin2hex(random_bytes(32));
        $vpass =  $pass;
        $timezone_offset =  $this->timezone_offset;
        $member_handler = icms::handler('icms_member');
        $user_handler = icms::handler('icms_member_user');
        //perform a check for if the password and verified one seem ok
        $stop = $user_handler->userCheck($login_name, $uname, $email, $pass, $vpass);
        if (empty($stop)) {
            //setup password info
            $icmspass = new icms_core_Password();
            $salt = $icmspass->createSalt();
            $enc_type = $icmsConfigUser['enc_type'];
            $pass1 = $icmspass->encryptPass($pass, $salt, $enc_type);
                        
            $newuser = $member_handler->createUser();
            //attempt to create the user
            $newuser->setVar('login_name', $login_name, TRUE);
            $newuser->setVar('uname', $uname, TRUE);
            $newuser->setVar('email', $email, TRUE);
            $newuser->setVar('name', '', TRUE);
            $newuser->setVar('timezone_offset', $timezone_offset, TRUE);
            $newuser->setVar('user_avatar', 'blank.gif', TRUE);
            $newuser->setVar( 'theme', 'impresstheme', TRUE);
            $newuser->setVar('level', 1, TRUE);
            $newuser->setVar('pass', $pass1, TRUE);
            $newuser->setVar('salt', $salt, TRUE);
            $newuser->setVar('enc_type', $enc_type, TRUE);
            
            if ($member_handler->insertUser($newuser)) {
                //assign the user basic registered users group at the very least, and maybe other groups if those were selected
                $newid = (int) $newuser->getVar('uid');
                if (!$member_handler->addUserToGroup(XOOPS_GROUP_USERS, $newid)) {
                    echo _US_REGISTERNG;
                    include XOOPS_ROOT_PATH.'/footer.php';
                    exit();
                }
                //see if there are other groups to add the user to
                foreach($groups as $groupid) {
                    //check in case there were no groups at all stored
                    if($groupid != ""){
                        $member_handler->addUserToGroup(intval($groupid), $newid);
                    }
                }
                Formulize::init();
                if(Formulize::createResourceMapping(Formulize::USER_RESOURCE, $_SESSION['resouceMapKey'], $newid)){
                    $location = isset($_GET['newuser']) ? XOOPS_URL."/?code=".$_GET['newuser']."&newcode=".$_GET['newuser'] : "";
                    if($location) {
                        header("Location: ".$location);
                        exit();
                    } else {
                        return $newid;
                    }
                } else {
                    $icmsConfigUser["stop_error"] = "Error: could not create resource mapping for new user. Please notify a webmaster about this error. You will not be able to login with this account until this error is resolved.";
                }
            } else {
                $icmsConfigUser["stop_error"] = "Error: could not add new user to the database. Please notify a webmaster about this error. You will not be able to login with this account until this error is resolved.";
            }
        } else {
            $icmsConfigUser["stop_error"] = explode("<br />", $stop);
        }
        return false;
    }
    
}
