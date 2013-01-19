<?php
/**
 * ADD THESE TWO LINES OF CODE AT THE TOP OF YOUR FILE
 * ---------------------------------------------------
 * $formulize_path = variable_get('formulize_full_path', NULL);
 * require_once(dirname($formulize_path) . '/integration_api.php');
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
            self::$db = $GLOBALS['xoopsDB'];
        }
    }
    
    //User Management

	static function createUser($userData) {
        self::init();
        
	}

    static function deleteUser($userID) {
        self::init();
    }
    
    /**
     * Updates user data in XOOPS
     * @param   userID      String          The ID of the user to update
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
     * @param   groupID     String      The ID of the group being renamed
     * @param   name        String      The new name for the group
     */
    static function renameGroup($groupID, $name) {
        self::init();
        
    }
    
    /**
     * Deletes an existing XOOPS group
     * @param   groupID     String      The ID of the XOOPS group to delete
     */
    static function deleteGroup($groupID) {
        self::init();
        
    }
    
    /**
     * Adds an existing user to a group
     * @param   userID      String      The ID of the user being added
     * @param   groupID     String      The ID of the group being added to
     */
    static function addUserToGroup($userID, $groupID) {
        self::init();
        
    }
    
    /**
     * Removes an existing user from a group
     * @param   userID      String      The ID of the user being removed
     * @param   groupID     String      The ID of the group being removed from
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
    		drupal_set_message(t('No screens found at this time.'));
    	}
    	
    	return $options;
    }

}

class FormulizeUser {

	private $uid = null;
	private $uname = null; //Displayed
	private $login_name = null; //Login
	private $email = null;
	private $timezone_offset = null; //Default: the current system's offset
    private $notify_method = null; //
	private $level = 1;
    
    /**
     * Construct a Fomulize User object from CMS data
     * @param   userData    array   The user data acquired from the user object
     *                              in the base CMS
     */
    function __construct($userData) {
        
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