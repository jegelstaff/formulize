<html>
<body>

<?php
/*
Plugin Name: Formulize Plugin 
Plugin URI: http://www.freeformsolutions.ca/en/formulize 
Description: This will be a description
Version: The Plugin's Version Number, e.g.: 1.0
Author: Authors
Author URI: http://URI_Of_The_Plugin_Author
License: A "Slug" license name e.g. GPL2
*/

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
	if($user === FALSE)

}


function synchronizeUsers()
{

}

function updateUser($userID, $role)
{
	//This code will go to formulize for updating a user.
}

add_action("wp_loaded",insertFormulize);
add_action('set_user_role',updateUser,"",2);
add_action('user_register',addUser);

// Shannon's code to add custom meta box
/* Define custom box */
add_action('add_meta_boxes', 'formulize_meta_box');
add_action('save_post', 'formulize_save_postdata');

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
  ?>
  <label for ="formulize_select">Choose screen: </label>
  <select name="formulize_select" id="formulize_select">
      <option value="test one" <?php selected($selected, 'test one'); ?>>Test One </option>
      <option value="test two" <?php selected($selected, 'test two'); ?>>Test Two </option>
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


?>
</body>
</html>

