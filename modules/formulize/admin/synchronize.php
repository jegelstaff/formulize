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
$sync[1]['content']['error'] = 0;
$sync[2]['name'] = "Export Database for Synchronization";
$sync[2]['content']['type'] = "export";
$sync[2]['content']['error'] = 0;

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
        // needs to return the filepath and success/fail
        // TODO: doExport is causing a 500 error, so commented out for now
        $export = doExport($filename, $checks);

        if ($export["success"] == true) {
            header('Content-Description: File Transfer');
            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename=' . $export["filepath"]);
            header('Content-Length: ' . filesize($export["filepath"]));
            readfile($export["filepath"]);
        }
        else {
            // return error message flag
            $sync[2]['content']['error'] = 1;
        }
    }
}
// retrieve the post information from the import submit
else if(isset($_POST['import'])) {

    $filepath = $_POST['file'];

    if ($filepath != NULL) {
        // import the zip into a temporary folder
        $tempFolder = extractArchiveFolders($filename);

        if ($tempFolder["success"] == true) {
            //continue with next phase of import
        }
        else {
            $sync[1]['content']['error'] = 1;
        }
    }
    // didn't select a file, do nothing
}
else {

    $filepath = "";
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

    // add indexing to the data list
    foreach ($datalist as $data) {
        $forms[$i] = $data; // for array, key is index and value is form name
        $i++;
    }

    // dynamically generate the checkboxes based on number of tables
    while(list($key,$value)=each($forms)) {
        $str .= '<input type="checkbox" name="'.$key.'" value="form[]" />'.$value.' ';
    }
    return $str;
}