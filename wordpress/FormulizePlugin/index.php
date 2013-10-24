<?php

    /*
     Plugin Name: Formulize Plugin
     Plugin URI: http://www.freeformsolutions.ca/en/formulize
     Description: This will be a description NEW VERSION
     Version: The Plugin's Version Number, e.g.: 1.0
     Author: Authors
     Author URI: http://URI_Of_The_Plugin_Author
     License: A "Slug" license name e.g. GPL2
     */

$formulize_path = get_option('formulize_path', NULL);
include_once($formulize_path . DIRECTORY_SEPARATOR . 'integration_api.php');

// Wordpress does not use role_ids, assign arbitrary values to wordpress built-in roles to satisfy formulize
$formulize_role_ids = array(
		    "administrator"  => 1,
		    "editor" => 2,
		    "author" => 3,
		    "contributor" => 4,
		    "subscriber" => 5,
		);
    
if(!class_exists('FormulizePluginOptions')) :

// DEFINE PLUGIN ID
define('FORMULIZEPLUGINOPTIONS_ID', 'formulize-plugin-options');
// DEFINE PLUGIN NICK
define('FORMULIZEPLUGINOPTIONS_NICK', 'Formulize Plugin Options');

    class FormulizePluginOptions
    {
	/** function/method
	* Usage: return absolute file path
	* Arg(1): string
	* Return: string
	*/
	public static function file_path($file)
	{
		return ABSPATH.'wp-content/plugins/'.str_replace(basename( __FILE__),"",plugin_basename(__FILE__)).$file;
	}
	/** function/method
	* Usage: hooking the plugin options/settings
	* Arg(0): null
	* Return: void
	*/
	
	public static function register()
	{
		register_setting(FORMULIZEPLUGINOPTIONS_ID.'_options', 'formulize_path');
		register_setting(FORMULIZEPLUGINOPTIONS_ID.'_options', 'synchronize_users_button');
	}
	/** function/method
	* Usage: hooking (registering) the plugin menu
	* Arg(0): null
	* Return: void
	*/
	public static function menu()
	{
		// Create menu tab
		add_options_page(FORMULIZEPLUGINOPTIONS_NICK.' Plugin Options', FORMULIZEPLUGINOPTIONS_NICK, 'manage_options', FORMULIZEPLUGINOPTIONS_ID.'_options', array('FormulizePluginOptions', 'options_page'));
	}
	/** function/method
	* Usage: show options/settings form page
	* Arg(0): null
	* Return: void
	*/
	public static function options_page()
	{ 
		if (!current_user_can('manage_options')) 
		{
			wp_die( __('You do not have sufficient permissions to access this page.') );
		}
		
		$plugin_id = FORMULIZEPLUGINOPTIONS_ID;
		// display options page
		include(self::file_path('options.php'));
	}
	/** function/method
	* Usage: filtering the content
	* Arg(1): string
	* Return: string
	*/
	public static function content_with_quote($content)
	{
		$quote = '<p><blockquote>' . get_option('kkpo_quote') . '</blockquote></p>';
		return $content . $quote;
	}
	
    }
    
    /* Add box to the post and page screens */
    function formulize_meta_box() 
    {
		add_meta_box('formulize_sectionid',
		     __('Formulize', 'formulize_textlabel'),
		     'formulize_inner_custom_box',
		     'page'
		     );
    }

    function formulize_settings_link($links, $file)
    {
        $settings_link = $settings_link = '<a href="'.admin_url().'options-general.php?page=formulize-plugin-options_options">' . __('Settings') . '</a>';
        array_unshift($links, $settings_link);

        return $links;
    }
    
    /* Print box content */
    function formulize_inner_custom_box($post) {
        global $post;
        $meta = get_post_meta($post->ID, 'formulize_select', true);

        $values = get_post_custom($post->ID);
        $selected = isset($values['formulize_select']) ? esc_attr($values['formulize_select']) : '';
        // We'll use this nonce field later on when saving.
        wp_nonce_field( 'my_formulize_nonce', 'formulize_nonce' );
        $screen_names = Formulize::getScreens();
        array_unshift($screen_names, 'No Screens');

		echo '<label for ="formulize_select">Choose screen: </label>';
	 	echo '<select name="formulize_select" id="formulize_select">';
	 			if(count($screen_names) > 0) { 
	                foreach ($screen_names as $id) {
	                    echo '<option ', $meta == $id ? ' selected="selected"' : '', '>', $id, '</option>';
	                }
	            } else {
	            	// No options
		            echo '<option value=0> No screens found </option>';
	            }
	                echo '</select>';
    }
    
    /* When the post is saved, saves our custom data */
    function formulize_save_postdata($post_id) {
            //verify if this is an auto save routine
        //If our form hasn't been submitted we don't want to do anything
        if (defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
            return;
        
        // if our nonce isn't there, or we can't verify it, bail
        if (!isset($_POST['formulize_nonce'])
            || !wp_verify_nonce($_POST['formulize_nonce'], 'my_formulize_nonce' )) return;
        
        // now we can actually save the data
        $allowed = array(
                         'a' => array( // on allow a tags
                                      'href' => array() // and those anchors can only have href attribute
                                      )  
                         );  

        $old = get_post_meta($post_id, 'formulize_select', true);
        $new = $_POST['formulize_select'];
        if ($new && $new != $old) {
            update_post_meta($post_id, 'formulize_select', $new);
        } elseif ('' == $new && $old) {
            delete_post_meta($post_id, 'formulize_select', $old);
        } 
    }
    
	if ( is_admin() )
	{
		add_action('admin_init', array('FormulizePluginOptions', 'register'));
		add_action('admin_menu', array('FormulizePluginOptions', 'menu'));
	}
	add_filter('the_content', array('FormulizePluginOptions', 'content_with_quote'));
	

    endif;

    /**
     * This function is used to insert the contents of a Formulize table on a Wordpress
     * page.
     */
    function insertFormulize($content)
    {
		//Here we echo the original content of the page.
		echo $content;
				
		//Here is where we obtain the screen we should display. Once we find the adequate screen name, we will
		//render our Formulize screen, then break from our loop to ensure only one screen is rendered.
		$custom_fields = get_post_custom($GLOBALS['post']->ID);
		$formulize_screen_id = -1; // Default is to have no screens displayed
		$screen_names = Formulize::getScreens();
		
		foreach($screen_names as $id=>$name)
		{ 
			if ($custom_fields['formulize_select'][0] == $name)
			{
				Formulize::renderScreen($id);
				break;
			}
		}
    }
    
    /**
     * This function handles the addition of the Formulize style-sheet to the plugin. It will ensure
     * that the necessary .css file is included. Additionally, it will also start a session for us with
     * formulize. Depending on whether you are a registered user or anonymous user it will sync accordingly
     * and grant permissions for interactions with the various forms as such.
     *
     */
    function insertFormulizeStylesheet()
    {	
	//Here we register and enqueue the style sheet for the plug in. 
        wp_register_style( 'newstyle', plugins_url('newstyle.css', __FILE__));
        wp_enqueue_style( 'newstyle');
    }
	
    /*
     * This function is called when a new user registers on the wordpress site.
     * It is called as the new user form is submitted, and pushes the information
     * to formulize as the new user is created on WP.
     */
    function addUser($userID)
    {
	$wpUser = get_userdata($userID);
	$userData = array(
			'uid'=>$wpUser->ID,
			'uname'=>$wpUser->display_name,
			'login_name'=>$wpUser->user_login,
			'email'=>$wpUser->user_email,
			'timezone_offset'=> 0,
			);
	$formUser = new FormulizeUser($userData);
	Formulize::createUser($formUser);
	updateUserRole($userID, $wpUser->roles[0]);
    }
    
    
    /**
     * This function will delete a user from the Formulize database once the user is deleted on Wordpress.
     *
     * AS OF 03/02/12 - DeleteUser in Formulize API calls die(). This causes the script to end and breaks WP functionality.
     * Do not uncomment the delete user hook.
     */
    function deleteUser($userID)
    {
	Formulize::deleteUser($userID);
    }
    
    
    //Need to add a check in here so we don't synchronized a user twice...same for adding a user.
    //I.e. We need a function in the API to query the formulize database so that we can confirm whether
    //the user already exists (Or is this in the API?)
    function synchronizeUsers()
    {
	echo "CLICKEDCLICKEDCLICKED";
	$users = get_users();
	foreach ($users as $wpUser)
	{
		$userData = array(
			'uid'=>$wpUser->ID,
			'uname'=>$wpUser->display_name,
			'login_name'=>$wpUser->user_login,
			'email'=>$wpUser->user_email,
			'timezone_offset'=> 0,
			);
		$formUser = new FormulizeUser($userData);
		if (Formulize::createUser($formUser)==FALSE)
		{
			echo "FALSE FALSE!";
		}
		echo '<li>'. $wpUser->user_email . " " . $wpUser->ID . " " . $wpUser->display_name . " " . $wpUser->user_login .  '</li>';
	}
    }
	
/**
 * This function will update the information for a user in the Formulize database. It will be run
 * at the end of every profile update currently, such that as soon as User information is edited, the
 * information stored in Formulize will likewise be updated.
 *
 * AS OF 03/02/12 - Update Users function in API appear to do nothing yet. This function has no effect as of yet.
 */
	function updateUser($wpUser)
	{
		$userData = array(
				'uid'=>$wpUser->ID,
				'uname'=>$wpUser->display_name,
				'login_name'=>$wpUser->user_login,
				'email'=>$wpUser->user_email,
				'timezone_offset'=> 0,
				);
		if($wpUser->ID!=1)
		{
			if(Formulize::updateUser($wpUser->ID,$userData))
			{
	
			}
		}
		
	}
	
	function initializeUserInfo()
	{
		$current_user = $current_user = wp_get_current_user();
		if(!($current_user instanceof WP_User))
		{
			$GLOBALS['formulizeHostSystemUserId'] = 0;
		}
		{
			$GLOBALS['formulizeHostSystemUserId'] = $current_user->ID;
		}
		
		//Initialize Formulize
		Formulize::init();
		
		global $wp_roles;
		$roles = $wp_roles->roles;
		$rolesArray = array();
		do
		{
			$rolesArray[] = key($roles);
		}
		while(next($roles)!==FALSE);
	}
	
	
	if(isset($_GET['syncUsers']))
	{
	   if($_GET['syncUsers']==TRUE)
	   {
		synchronizeUsers();
	    }
        }

    function updateUserRole($user_id, $role_name) 
    {

		global $formulize_role_ids;
		$role = get_role($role_name);
    	$user = get_userdata($user_id);

    	// since there is no hook for removing a wordpress role, I make the assumption that users can belong to AT MOST one wordpress role at a time.
		//remove user from all groups (we have no way to tell which role this user switched from)
		foreach ($formulize_role_ids as $role_id)
		{
			Formulize::removeUserFromGroup($user->ID, $role_id);
		}

    	if ($role != null && $formulize_role_ids[$role->name] != null) // we only support default wp roles at the moment
    	{
    		$formulize_group = new FormulizeGroup(
    		array(
    		'groupid'=>$formulize_role_ids[$role->name],
    		'name'=>$role->name,
    		'description'=>$role->name
    		));
    		
    		// lazily create this group (incase this is the first time)
	    	Formulize::createGroup($formulize_group);
			Formulize::addUserToGroup($user->ID, $formulize_role_ids[$role->name]);	
    	}
    	
    }
	
add_action('init','initializeUserInfo');
add_action( 'wp_enqueue_scripts', 'insertFormulizeStylesheet' );
add_action('delete_user','deleteUser'); //<-- Commented out. The delete function in the API calls the die function. Uncommenting this and attempting to delete a user crashes WP.
add_action('edit_user_profile', 'updateUser'); // <-- Update user is stub. Doesn't do anything yet in API.
add_action('add_meta_boxes', 'formulize_meta_box');
add_action('save_post', 'formulize_save_postdata'); 
add_action('user_register','addUser'); // <--Currently this function works and updates the Formulize site.
add_filter('the_content','insertFormulize'); //Need to fix this hook so that the table is displayed appropriately on each page
add_filter('plugin_action_links', 'formulize_settings_link', 10, 2); 
add_action('set_user_role', 'updateUserRole', 10, 2);

?>