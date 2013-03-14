<?php

/**
 * Formulize Object for API calls
 */
class Formulize {

        private static $db = null;
        private static $mapping_table = 'formulize_external_group_mapping';
        
        /**
         * Intialize the Formuliz environment
         */
        static function init() {
                if (self::$db == null) {
                        include_once('mainfile.php');
                        self::$db = $GLOBALS['xoopsDB'];
                }
        }
        
        //User Management

        /**
         * Create a new XOOPS user from the provided FormulizeUser data
         * @param   user_data   FormulizeUser       The user data
         * @return              boolean             Whether the creation succeeded
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
                $user_id = $newUser->getVar('uid');
                //If setting the proper uid failed, return false
                if(!self::$db->queryF('UPDATE ' . self::$db->prefix('users') . ' SET uid = \'' . $user_data->get('uid') . '\' WHERE uid = \'' . $user_id . '\'')) {
                        return false;
                }

                return true;
        }

        /**
         * Retrieves the specified User's data
         * @param       user_id         int                             The ID of the user to retrieve
         * @return                              FormulizeUser   The User's data
         */
        static function getUser($user_id) {
                self::init();
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
         * Removes the specified user, and obfuscates their identification fields
         * (uname, login_name, email) to allow those pieces of data to be used for
         * future registrations.
         * @param   user_id     int         The ID of the user to remove
         * @return              boolean     Whether the delete succeeded
         */
        static function deleteUser($user_id) {
                self::init();
                $uuid = uniqid(); //Generate a UUID for obfuscation
                $member_handler = xoops_gethandler('member');
                $user = $member_handler->getUser($user_id);
                //Obfuscate identification
                self::updateUser($user_id, array(
                        'uname' => $uuid . '-' . $user->getVar('uname'),
                        'login_name' => $uuid . '-' . $user->getVar('login_name'),
                        'email' => $uuid . '-' . $user->getVar('email')
                ));
                return $member_handler->deleteUser($user);
        }
        
        /**
         * Updates user data in XOOPS
         * @param   user_id      int            The ID of the user to update
         * @param   user_data    array          An associative array of fields to update
         */
        static function updateUser($user_id, $data) {
                self::init();
                $member_handler = xoops_gethandler('member');
                $xoops_user = $member_handler->getUser($user_id);
                //Update fields specified in $user_data
                if($xoops_user) {
                        foreach($data as $key => $value) {
                                $xoops_user->setVar($key, $value);
                        }
                        //If the user wasn't inserted, return false
                        return $member_handler->insertUser($xoops_user, true);
                } else {
                        return false;
                }
        }
        
        //Group Management
        
        /**
         * Creates a new user group in XOOPS
         * @param       group           FormulizeGroup
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
                $result = $group_handler->insert($xoops_group);
                if($result) {
                        //If the group was created, add it to the mapping
                        return self::createGroupMapping($group->get('groupid'), $xoops_group->getVar('groupid'));
                } else {
                        return false;
                }
        }

        /**
         * Rename an existing XOOPS group
         * @param   groupid     int         The ID of the group being renamed
         * @param   name        String      The new name for the group
         */
        static function renameGroup($groupid, $name) {
                self::init();
                $group_handler = xoops_gethandler('group');
                $xoops_groupid = self::getXoopsGroupID($groupid);
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
         * @param       groupid         int                     The ID of the XOOPS group to delete
         */
        static function deleteGroup($groupid) {
                self::init();
                $group_handler = xoops_gethandler('group');
                $xoops_groupid = self::getXoopsGroupID($groupid);
                //If a group was found, delete it
                if($xoops_groupid != null) {
                        $xoops_group = $group_handler->get($xoops_groupid);             
                        //If the ID matched, remove the group
                        if($xoops_group) {
                                return $group_handler->delete($xoops_group);
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
         * @param   user_id      int         The ID of the user being added
         * @param   groupid     int         The ID of the group being added to
         */
        static function addUserToGroup($user_id, $groupid) {
                self::init();
                $members = xoops_gethandler('member');
                return $members->addUserToGroup($groupid, $user_id);
        }
        
        /**
         * Removes an existing user from a group
         * @param   user_id      int         The ID of the user being removed
         * @param   groupid     int         The ID of the group being removed from
         */ 
        static function removeUserFromGroup($user_id, $groupid) {
                self::init();
                $members = xoops_gethandler('member');
                return $members->removeUsersFromGroup($groupid, array($user_id));
        }
        
        /**
         * Obtain a list of the available screen names
         * TODO: Enable permissions-based retrieval
         */
        static function getScreens() {
                self::init();

                $options = array();
                
                $sql = 
                'SELECT fi.desc_form, fs.title, fs.sid 
                FROM ' . self::$db->prefix('formulize_id') . ' AS fi, 
                ' . self::$db->prefix('formulize_screen') . ' AS fs 
                WHERE fi.id_form = fs.fid 
                ORDER BY fi.desc_form, fs.title';
                
                if ($result = self::$db->query($sql)) {
                        while($row = self::$db->fetchArray($result)) {
                                $options[$row['sid']] = $row['desc_form'] . ' - ' . $row['title'];
                        }
                }
                
                if (count($options) == 0) {
                        $options[0] = ('No Formulize Screens Found');
                }
                
                return $options;
        }

	/**
	 * This function is used to render a Formulize screen inside a 3rd party CMS.
	 * @param screenID - This is the screen ID that Formulize should render.
	 */
	static function renderScreens($screenID)
	{
		//Set the screen ID
		$formulize_screen_id = $screenID;

		//Declare a formulize div to contain our injected content, with ID formulize_form
		echo '<div id=formulize_form>';
		
		//Include our header file in order to set up xoTheme
		include XOOPS_ROOT_PATH . "/header.php";
		
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
         * Insert a group mapping from the external CMS to Formulize
         * @param       external_groupid        int                     The external group ID
         * @param       groupid                         int                     The Formulize group ID
         * @return                                              boolean         Whether mapping was successful
         * @throws      An exception is thrown if a supplied ID is not of integer form.
         */
        private static function createGroupMapping($external_groupid, $groupid) {
                $mapping_table = self::$db->prefix(self::$mapping_table);
                //Determine whether any mappings exist with the specified IDs
                $num_mappings = mysql_num_rows(self::$db->queryF('
                        SELECT * FROM ' . $mapping_table . ' 
                        WHERE groupid = ' . intval($groupid) . ' 
                        OR external_groupid = ' . intval($external_groupid)
                ));
                if(!is_int($external_groupid) || !is_int($groupid))
                        throw new Exception('Formulize::createGroupMapping() - Expecting two integer IDs.');
                if($num_mappings == 0) {
                        return self::$db->queryF('
                                INSERT INTO ' . $mapping_table . '
                                (external_groupid, groupid)
                                VALUES ( ' . intval($external_groupid) . ',' . intval($groupid) . ')
                        ');
                } else {
                        //A group mapping containing at least one of the IDs already exists. Can't create it.
                        return false;
                }
        }

        /**
         * Converts an external groupid into a XOOPS groupid using the mapping table
         * @param       external_groupid        int             The external groupid to convert
         * @return                                              int             The associated XOOPS groupid
         */
        private static function getXoopsGroupID($external_groupid) {
                $mapping_result = mysql_fetch_row(self::$db->queryF('
                        SELECT groupid FROM ' . self::$db->prefix(self::$mapping_table) . '
                        WHERE external_groupid = ' . intval($external_groupid)
                ));
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
