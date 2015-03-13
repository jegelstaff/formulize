// This scipt is called by an administrator of the Formulize install within the Admin section of the site, in order to remove all files in the 'templates_c' folder.
// This script should only be called if the user is developing new templates and needs a a quick way to delete existing cached template data.
<?php
    $files = glob($_SERVER["DOCUMENT_ROOT"].'/formulize/templates_c/*'); // Get all file names in directory.
    foreach ($files as $file) { // iterate files
        if (is_file($file)) {
            unlink($file); // delete file
        }
    }
    return 'deleted'; // Notify user files are deleted.
?>