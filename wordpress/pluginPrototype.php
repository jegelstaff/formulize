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
	include '/Users/dpage/Sites/formulize/htdocs/mainfile.php';
	$formulize_screen_id = 2;
	include XOOPS_ROOT_PATH . '/modules/formulize/index.php';
}


function synchronizeUsers()
{

}
add_action("wp_loaded",insertFormulize);

?>
</body>
</html>

