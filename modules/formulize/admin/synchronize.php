<?php
/**
 * Helps create the page for synchronizing these two systems (DBs)
 * User: Vanessa Synesael
 * Date: 2016-01-16
 */

include_once '../include/synchronization.php';

$sync = array();

$sync[1]['name'] = "Import Database for Synchronization";
$sync[1]['content']['type'] = "import";
$sync[2]['name'] = "Export Database for Synchronization";
$sync[2]['content']['type'] = "export";

// populate the checkboxes for export
$checks = retrieveTableNamesForCheckboxes();

// retrieve the post information from the export submit
if (isset($_POST['export'])) {

    // get the filename submitted by the user for saving the DB
    $filename = $_POST['filename'];
    if ($filename != "") {

        // perform the export
        // validate user input - make sure filename has .zip at the end
        $zip = ".zip";
        if (!endsWithZip($filename, $zip)) {
            $filename .= $zip;
        }
        doExport($filename, $checks);
    }
}
// retrieve the post information from the import submit
else if(isset($_POST['import'])) {

    $filename = $_POST['file'];

}
else {

    $filename ="";
}

$adminPage['sync'] = $sync;
$adminPage['template'] = "db:admin/synchronize.html";

$breadcrumbtrail[1]['url'] = "page=home";
$breadcrumbtrail[1]['text'] = "Home";
$breadcrumbtrail[2]['text'] = "Synchronize";

/*
 * Determines if the input from the user ends in .zip
 * @filename - text input by the user
 * @zip - string representation for .zip
 */
function endsWithZip($filename, $zip) {
    // search forward starting from end minus needle length characters
    return $zip === "" || (($temp = strlen($filename) - strlen($zip)) >= 0 && strpos($filename, $zip, $temp) !== FALSE);
}

/*
 * Retrieves the data for the export checkboxes and populates it to the ui
 * @return string of checkboxes
 */
function retrieveTableNamesForCheckboxes() {

    $str = '';
    // list of the data we want to populate to the checkboxes
    $datalist = syncDataTablesList();
    $forms = array();
    $i = 0;
    foreach ($datalist as $data) {
        $forms[$i] = $data; // for array, key is index and value is form name
        $i++;
    }

    while(list($key,$value)=each($forms)) {
        $str .= '<input type="checkbox" name="'.$key.'" value="form[]" />'.$value.' ';
    }
    return $str;
}