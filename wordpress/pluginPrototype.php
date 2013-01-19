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

function sayHello()
{
	echo "Hello";
	echo '<script>';
        echo 'alert("Hello");';
	echo '</script>';
}

add_action("wp_loaded",sayHello);


?>
</body>
</html>
