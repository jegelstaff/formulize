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
function metaBox()
{
add_meta_box(1,'Hello');
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

?>
</body>
</html>

