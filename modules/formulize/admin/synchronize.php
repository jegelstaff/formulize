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
$sync[1]['content']['error'] = "none";
$sync[1]['content']['complete'] = 0;
$sync[2]['name'] = "Export Database for Synchronization";
$sync[2]['content']['type'] = "export";
$sync[2]['content']['error'] = 0;

// populate the checkboxes for export
$sync[2]['content']['checkboxes'] = createCheckboxInfo();
$xoopsTpl->assign('checkboxData', $sync[2]['content']['checkboxes']);

// retrieve the post information from the export submit
if (isset($_POST['export'])) {

    // get the filename submitted by the user for saving the DB
    $filename = $_POST['filename'];
    $formsChecked = $_POST['forms'];

    if ($filename != "") {

        // perform the export
        // validate user input - make sure filename has .zip at the end
        $zip = ".zip";
        if (!endsWithZip($filename, $zip)) {
            $filename .= $zip;
        }

        // perform export
        $export = doExport($filename, $forms);

        if ($export["success"] == true) {
            header('Content-Description: File Transfer');
            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename=' . basename($export["filepath"]) );
            header('Content-Length: ' . filesize($export["filepath"]));
            readfile($export["filepath"]);
        } else {
            // return error message flag
            $sync[2]['content']['error'] = 1;
        }
    }
}
// retrieve the post information from the import submit
else if(isset($_POST['import'])) {
    $uploadOK = true;                       // todo: should possibly be an associative array with true/false and message ??
    $filepath = $_FILES['fileToUpload']['tmp_name'];

    if ($filepath != NULL) {
        $fileType = pathinfo($_FILES['fileToUpload']['name'], PATHINFO_EXTENSION);  // get file type

        if ($fileType != "zip"){                                                    // check for correct input file type
            $uploadOK = false;
        }
        if ($uploadOK) {
            $tempFolder = doImport($filepath);

            if ($tempFolder["success"] == true) {
                $sync[1]['content']['error'] = "success";
            }
            // return an error as there were issues importing the file
            else {
                $sync[1]['content']['error'] = "import_err";
            }
        }
        // return error as there were issues with the file selected
        else {
            $sync[1]['content']['error'] = "upload_err";
        }
    }
    // return an error as no filename was chosen (or set in $filepath)
    else {
        $sync[1]['content']['error'] = "file_err";
    }
}
else if (isset($_POST['complete'])) {
    // if this post was sent then load the cached comparison data and commit it to the database
    $catalog = new SyncCompareCatalog();
    $catalog->loadCachedChanges();

    // commit database changes
    $sync[1]['content']['complete'] = $catalog->commitChanges();
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

// Function to create the list of forms to display in checkboxes
// has key value pairings of form id => form name
function createCheckboxInfo() {
    global $xoopsDB;
    $sql = "SELECT id_form, desc_form FROM " . XOOPS_DB_PREFIX . "_formulize_id;";
    $result = icms::$xoopsDB->query($sql);

    $ids = array();
    $names = array();
    while ($row = $xoopsDB->fetchRow($result)) {
        array_push($ids, $row[0]);
        array_push($names, $row[1]);
    }
    return array_combine($ids, $names);
}