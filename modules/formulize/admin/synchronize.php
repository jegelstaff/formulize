<?php
/**
 * Helps create the page for synchronizing these two systems (DBs)
 * User: Vanessa Synesael
 * Date: 2016-01-16
 */

// exporting the entire DB can take a lot of memory and time!!
ini_set('memory_limit', '1024M'); 
ini_set('max_execution_time', '600');
 
include_once '../include/synchronization.php';

if(!class_exists('ZipArchive')) {
    print 'ERROR: Synchronization is not possible, because the server cannot create or read .zip files. Add the zip extension to PHP and restart the web server.';
}

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
        $export = doExport($filename, $formsChecked);

        if ($export["success"] == true) {
            header('Content-Description: File Transfer');
            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename=' . basename($export["filepath"]) );
            header('Content-Length: ' . filesize($export["filepath"]));
            readfile($export["filepath"]);
            exit();
        } else {
            // return error message flag
            $sync[2]['content']['error'] = 1;
        }
    }
}
// retrieve the post information from the import submit
else if(isset($_POST['import']) OR isset($_GET['partial'])) {
    
    if(!isset($_GET['partial'])) {
    
    $uploadOK = true;                       // todo: should possibly be an associative array with true/false and message ??
    $filepath = $_FILES['fileToUpload']['tmp_name'];

    if ($filepath != NULL) {
        $fileType = pathinfo($_FILES['fileToUpload']['name'], PATHINFO_EXTENSION);  // get file type

        if ($fileType != "zip"){                                                    // check for correct input file type
            $uploadOK = false;
        }
        if ($uploadOK) {
            $cachedArchiveFilepath = cacheExportFile($filepath); // move the tmp uploaded export zip to formulize cache
            $extractResult = extractCSVs($cachedArchiveFilepath);

            if ($extractResult["success"] == true) {
                $csvPath = $extractResult["csvPath"];
                $dbResult = csvToDB($csvPath);
                } // return an error as there were issues importing the file
            else {
                $sync[1]['content']['error'] = "import_err";
            }
            } // return error as there were issues with the file selected
        else {
            $sync[1]['content']['error'] = "upload_err";
        }
        }// return an error as no filename was chosen (or set in $filepath)
    else {
        $sync[1]['content']['error'] = "file_err";
    }

    } else {
        $csvPath = $_GET['partial'];
        $dbResult = csvToDB($_GET['partial']);
    }
        
    if ($dbResult["success"] == true AND $dbResult["partial"] == false) {
        deleteDir($csvPath); // clean up temp folder and CSV files
        header("Location: ui.php?page=sync-import"); // redirect to sync import review changes
    } elseif($dbResult["success"] == true) {
        $groupsMatch = ((isset($_POST['groupsMatch']) AND $_POST['groupsMatch'] == 2) OR (isset($_GET['groupsMatch']) AND $_GET['groupsMatch'] == 2)) ? 2 : 1;
        header("Location: ui.php?page=synchronize&partial=".urlencode($csvPath)."&groupsMatch=$groupsMatch"); // redirect to continue import
        // alternatively, process all in one page load, but could be prohibitive in a large database due to timeouts!
        /*while($dbResult["partial"] != false) {
            $dbResult = csvToDB($csvPath);
        }
        exit();*/
    } else {
        $sync[1]['content']['error'] = "import_err";
    }
    
}
else {
    $filepath = "";
    // flush any data from previous pageloads if any. we need to start fresh every time we go through the import process.
    include_once XOOPS_ROOT_PATH."/modules/formulize/include/synccompare.php";
    SyncCompareCatalog::clearCachedChanges();
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
