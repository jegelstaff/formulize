<?php
/**
 * ADD THESE TWO LINES OF CODE AT THE TOP OF YOUR FILE
 * ---------------------------------------------------
 * $formulize_path = variable_get('formulize_full_path', NULL);
 * require_once(dirname($formulize_path) .  DIRECTORY_SEPARATOR . 'integration_api.php');
 * ---------------------------------------------------
 */

class Formulize {

	static $db = null;
    
    /**
     * Intialize the Formuliz environment
     */
    static function init() {
        if (self::$db == null) {
            require_once('mainfile.php');
	    include_once('header.php');
            self::$db = $GLOBALS['xoopsDB'];
        }
    }
    
    //User Management

    /**
     * Create a new XOOPS user from the provided FormulizeUser data
     * @param	userData	FormulizeUser		The user data
	 * @return				boolean				Whether the creation succeeded
     */
	static function createUser($userData) {
        self::init();
        //Create a XOOPS user from the provided FormulizeUser data
        $member_handler = xoops_gethandler('member');
	    $newUser = $member_handler->createUser();
	    $newUser->setVar('uname', $userData->get('uname'));
	    $newUser->setVar('login_name', $userData->get('login_name'));
	    $newUser->setVar('email', $userData->get('email'));
        //Use the default timezone offset from ImpressCMS
	    $newUser->setVar('timezone_offset', $userData->get('timezone_offset'));
	    $newUser->setVar('notify_method', $userData->get('notify_method')); //email
	    $newUser->setVar('level', $userData->get('level')); //active, can login
	    //If the user wasn't inserted, return false
	    if(!$member_handler->insertUser($newUser, true)) {
	    	return false;
	    }
	    $userId = $newUser->getVar('uid');
	    //If setting the proper uid failed, return false
	    if(!self::$db->queryF('UPDATE ' . self::$db->prefix('users') . ' SET uid = \'' . $userData->get('uid') . '\' WHERE uid = \'' . $userId . '\'')) {
	    	return false;
	    }

	    return true;
	}

	/**
	 * Removes the specified user
	 * @param	userID		int			The ID of the user to remove
	 * @return				boolean		Whether the delete succeeded
	 */
    static function deleteUser($userID) {
        self::init();
        $member_handler = xoops_gethandler('member');
        $user = $member_handler->getUser($userID);
        die(print_r($user));
        if(!$member_handler->deleteUser($user)) {
        	echo 'e';
        	return false;
        }
        return true;
    }
    
    /**
     * Updates user data in XOOPS
     * @param   userID      int          	The ID of the user to update
     * @param   userData    FormulizeUser   The FormulizeUser (with fields that
     *                                      require updates) to gather updated data
     *                                      from
     */
    static function updateUser($userID, $userData) {
        self::init();
        
    }
    
    //Group Management
    
    /**
     * Creates a new user group in XOOPS
     * @param   group   FormulizeGroup
     */
    static function createGroup($group) {
        self::init();
        
    }
    
    /**
     * Rename an existing XOOPS group
     * @param   groupID     int      	The ID of the group being renamed
     * @param   name        String      The new name for the group
     */
    static function renameGroup($groupID, $name) {
        self::init();
        
    }
    
    /**
     * Deletes an existing XOOPS group
     * @param   groupID     int      	The ID of the XOOPS group to delete
     */
    static function deleteGroup($groupID) {
        self::init();
        
    }
    
    /**
     * Adds an existing user to a group
     * @param   userID      int      	The ID of the user being added
     * @param   groupID     int      	The ID of the group being added to
     */
    static function addUserToGroup($userID, $groupID) {
        self::init();
        
    }
    
    /**
     * Removes an existing user from a group
     * @param   userID      int      	The ID of the user being removed
     * @param   groupID     int      	The ID of the group being removed from
     */ 
    static function removeUserFromGroup($userID, $groupID) {
        self::init();
        
    }
    
    /**
     * Obtain a list of the available screen names
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
            
        if($result = self::$db->query($sql)) {
        	while($row = self::$db->fetchArray($result)) {
    			$options[$row['sid']] = $row['desc_form'] . ' - ' . $row['title'];
    		}
    	}
    
    	if(count($options) == 0) {
    		$options[0] = t('No Formulize Screens Found');
    	}
    	
    	return $options;
    }

}

class FormulizeUser {

	private $uid = '';
	private $uname = '';
	private $login_name = '';
	private $email = '';
	private $timezone_offset = null; //Default is set in the constructor
    private $notify_method = 2;
	private $level = 1;
    
    /**
     * Construct a Fomulize User object from CMS data
     * @param   userData    array   The user data acquired from the user object
     *                              in the base CMS
     */
    function __construct($userData) {
    	Formulize::init();

        $this->uid = $userData['uid'];
        $this->uname = $userData['uname'];
        $this->login_name = $userData['login_name'];
        $this->email = $userData['email'];
        $this->timezone_offset = $userData['timezone_offset'];
        $this->notify_method = $userData['notify_method'];
        $this->level = $userData['level'];

        //Set defaults if necessary
        if($this->timezone_offset == null) {
        	$this->timezone_offset = $GLOBALS['xoopsConfig']['default_TZ'];
        }
    }

    /**
     * Get the value of a field in this FormulizeUser
     * @param	key		String		The name of the field to be retrieved
     */
    function get($key) {
    	if(!isset($this->{$key})) throw new Exception('Attempted to get an invalid user field.');
    	return $this->{$key};
    }

    /**
     * Set the value of a field in this FormulizeUser
     * @param	key		String		The name of the field to be retrieved
     * @param	key		Object		The value to be stored in this field
     */
    function set($key, $value) {
    	if(!isset($this->{$key})) throw new Exception('Attempted to set an invalid user field.');
    	$this->{$key} = $value;
    }

}

class FormulizeGroup {

    private $uid = null;
	private $uname = null; //Displayed
	private $login_name = null; //Login
	private $email = null;
	private $timezone_offset = null; //Default: the current system's offset
    private $notify_method = null; //
	private $level = 1;
    
    /**
     * Construct a Forulize User object from CMS data
     * @param   userData    array   The user data acquired from the user object
     *                              in the base CMS
     */
    function __construct($userData) {
        
    }

}