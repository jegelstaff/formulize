<?php
/**
 * Helps create the page for synchronizing these two systems (DBs)
 * User: Vanessa Synesael
 * Date: 2016-01-16
 */

$sync = array();

$sync[1]['name'] = "Import Database for Synchronization";
$sync[1]['content']['type'] = "import";
$sync[2]['name'] = "Export Database for Synchronization";
$sync[2]['content']['type'] = "export";

if (isset($_POST['export'])) {

    // get the filename submitted by the user for saving the DB
    $filename = $_POST['filename'];

    if ($filename != "") {

        // perform the export
        include '../include/synchronization.php';

        // validate user input - make sure filename has .zip at the end
        $zip = ".zip";
        if (!endsWithZip($filename, $zip)) {
            $filename .= $zip;
        }
        //doExport($filename);  ** uncomment this when function is complete
    }
}
else {

    $filepath ="";
}

function endsWithZip($filename, $zip) {
    // search forward starting from end minus needle length characters
    return $zip === "" || (($temp = strlen($filename) - strlen($zip)) >= 0 && strpos($filename, $zip, $temp) !== FALSE);
}

$adminPage['sync'] = $sync;
$adminPage['template'] = "db:admin/synchronize.html";

$breadcrumbtrail[1]['url'] = "page=home";
$breadcrumbtrail[1]['text'] = "Home";
$breadcrumbtrail[2]['text'] = "Synchronize";