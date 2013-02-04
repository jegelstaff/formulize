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
    
if(!class_exists('kkPluginOptions')) :

// DEFINE PLUGIN ID
define('KKPLUGINOPTIONS_ID', 'kk-plugin-options');
// DEFINE PLUGIN NICK
define('KKPLUGINOPTIONS_NICK', 'Formulize Plugin Options');

    class kkPluginOptions
    {
	
	//This function is used to insert the formulize table into a wordpress page.
	//Currently screen id is hard-coded, however this will be fetched from the dropdown/some source
	//once we know how we're calling formulize within wordpress
	
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
			register_setting(KKPLUGINOPTIONS_ID.'_options', 'formulize_path');
		}
		/** function/method
		* Usage: hooking (registering) the plugin menu
		* Arg(0): null
		* Return: void
		*/
		public static function menu()
		{
			// Create menu tab
			add_options_page(KKPLUGINOPTIONS_NICK.' Plugin Options', KKPLUGINOPTIONS_NICK, 'manage_options', KKPLUGINOPTIONS_ID.'_options', array('kkPluginOptions', 'options_page'));
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
			
			$plugin_id = KKPLUGINOPTIONS_ID;
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
	
	
	/*
	 * This is hopefully the code to add a link right below the plugin to take the user to the settings page (similar to Exec-PHP)
	 
	public static function filter_plugin_actions_links($links, $file)
	{
	    if ($file == ExecPhp_HOMEDIR. '/exec-php.php')
	    {
		$settings_link = $settings_link = '<a href="options-general.php?page='. ExecPhp_HOMEDIR. '/includes/config_ui.php">' . __('Settings') . '</a>';
		array_unshift($links, $settings_link);
	    }
	    return $links;
	}
	 
	 */
	
	
    }
    
    /* Add box to the post and page screens */
    function formulize_meta_box() {
		add_meta_box('formulize_sectionid',
		     __('Formulize', 'formulize_textlabel'),
		     'formulize_inner_custom_box',
		     'page'
		     );
    }
    
    /* Print box content */
    function formulize_inner_custom_box($post) {
	global $post;
	$values = get_post_custom($post->ID);
	$selected = isset($values['formulize_select']) ? esc_attr($values['formulize_select']) : '';
	// We'll use this nonce field later on when saving.
	wp_nonce_field( 'my_formulize_nonce', 'formulize_nonce' );
	$screen_names = Formulize::getScreens();
	/*
	$screen_names = array(
		"1" => "option 1",
		"2" => "option 2",
	);
	 
	 */
	       

	/**
	** TODO(02/03/13): This should be saving the user's last choice; currently it defaults to the order of 
	** "None, Screen 1, Screen 2," etc.
	**/
    echo '<label for ="formulize_select">Choose screen: </label>
	  <select name="formulize_select" id="formulize_select">';
	    
	    if(count($screen_names) > 0) {
	    print "<option value=-1>None</option>";
		foreach($screen_names as $screen_id=>$name) {
		    print "<option value=$screen_id>$name</option>";
		}
	    } else {
		// no options
		print "<option value=0>No screens found</option>";
	    }
	    
	    

    echo '</select>';

    }
    
    /* When the post is saved, saves our custom data */
    function formulize_save_postdata($post_id)
    {
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
	
	if (isset( $_POST['formulize_select']))  
	    update_post_meta($post_id, 'formulize_select', esc_attr( $_POST['formulize_select']));  
    }
    
	if ( is_admin() )
	{
		add_action('admin_init', array('kkPluginOptions', 'register'));
		add_action('admin_menu', array('kkPluginOptions', 'menu'));
	}
	add_filter('the_content', array('kkPluginOptions', 'content_with_quote'));

    endif;
    

    /*
     * This function is used to insert the contents of a Formulize table on a Wordpress
     * page.
     *
     * If the option for 'formulize_select' is set to -1, this means that no screens are to be shown.
     */
    function insertFormulize($content)
    {
	Formulize::init();
	$custom_fields = get_post_custom($GLOBALS['post']->ID);
	if ($custom_fields['formulize_select'][0] != -1) {
		$formulize_screen_id = $custom_fields['formulize_select'][0];
		include XOOPS_ROOT_PATH . '/modules/formulize/index.php';
	}
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
			'notify_method'=> "email",
			'level'=> "active");
	$formUser = new FormulizeUser($userData);
	Formulize::createUser($formUser);
	/*
	 $to = "paged90@gmail.com";
	$subject = "MESSAGE";
	$message = "User has been added successfully.";
	wp_mail($to,$subject,$message);
	*/
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
	$users = get_users();
	//print_r($users);
	foreach ($users as $x)
	{
		$temp = array(
			      'uid'=>$x->ID,
			      'uname'=>$x->display_name,
			      'login_name'=>$x->user_login,
			      'email'=>$x->user_email,
			      'timezone_offset'=> 0,
			      'notify_method'=> "email",
			      'level'=> "active");
		$tempUser = new FormulizeUser($temp);
		Formulize::createUser($tempUser);
		echo '<li>'. $x->user_email . " " . $x->ID . '</li>';
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
				'notify_method'=> "email",
				'level'=> "active");
		
		Formulize::updateUser($wpUser->ID,$userdata);
		/*
		 $to = "paged90@gmail.com";
		$subject = "MESSAGE";
		$message = "Testing update user further.";
		wp_mail($to,$subject,$message);
		*/
	}
//add_action('init','synchronizeUsers'); <-- Commented out. Will talk about where to place this function. Maybe as Formulize full path variable is changed?
//add_action('delete_user','deleteUser'); <-- Commented out. The delete function in the API calls the die function. Uncommenting this and attempting to delete a user crashes WP.
add_action('edit_user_profile', 'updateUser'); // <-- Update user is stub. Doesn't do anything yet in API.
add_action('add_meta_boxes', 'formulize_meta_box');
add_action('save_post', 'formulize_save_postdata'); 
add_action('user_register','addUser'); // <--Currently this function works and updates the Formulize site.
add_filter('the_content','insertFormulize'); //Need to fix this hook so that the table is displayed appropriately on each page
?>