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
    
if(!class_exists('FormulizePluginOptions')) :

// DEFINE PLUGIN ID
define('FORMULIZEPLUGINOPTIONS_ID', 'formulize-plugin-options');
// DEFINE PLUGIN NICK
define('FORMULIZEPLUGINOPTIONS_NICK', 'Formulize Plugin Options');

    class FormulizePluginOptions
    {
        
        //This function is used to insert the formulize table into a wordpress page.
        //Currently screen id is hard-coded, however this will be fetched from the dropdown/some source
        //once we know how we're calling formulize within wordpress
        
        function insertFormulize()
        {
            echo "Hello";
            //	include '/Users/dpage/Sites/formulize/htdocs/mainfile.php';
            //	$formulize_screen_id = 2;
            //	include XOOPS_ROOT_PATH . '/modules/formulize/index.php';
        }

		function addUser($userID)
        {
            $user = get_userdata($userID);
                
        }
        
        function synchronizeUsers()
        {
            
        }
        
        function updateUser($userID, $role)
        {
            //This code will go to formulize for updating a user.
        }
	
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
               
        ?>
<label for ="formulize_select">Choose screen: </label>
    <select name="formulize_select" id="formulize_select">
        <?php
            
            if(count($screen_names) > 0) {
                foreach($screen_names as $name) {
                    print "<option value=$name>$name</option>";
                }
            } else {
                // no options
                print "<option value=0>No screens found</option>";
            }
            
            
            ?>

    </select>

<?php
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
        
        if (isset( $_POST['formulize_select']))  
            update_post_meta($post_id, 'formulize_select', esc_attr( $_POST['formulize_select']));  
    }
    
	if ( is_admin() )
	{
		add_action('admin_init', array('FormulizePluginOptions', 'register'));
		add_action('admin_menu', array('FormulizePluginOptions', 'menu'));
	}
	add_filter('the_content', array('FormulizePluginOptions', 'content_with_quote'));

    endif;
    
    // Shannon's code to add custom meta box
    /* Define custom box */
    add_action('add_meta_boxes', 'formulize_meta_box');
    add_action('save_post', 'formulize_save_postdata');
    add_action("wp_loaded",'insertFormulize');  // <- This line is causing a warning, look at the log in /var/log/apache2/error_log. This error is caused because the "insertFormulize" method is inside of the Formulize Plugin Options Class.
    add_action('set_user_role','updateUser',"",2);
    add_action('user_register','addUser');
    add_filter('plugin_action_links', 'formulize_settings_link', 10, 2); 
?>