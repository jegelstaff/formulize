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
$sync[2]['name'] = "Export Database for Synchronization";
$sync[2]['content']['type'] = "export";
$sync[2]['content']['error'] = 0;

// populate the checkboxes for export
$checks = retrieveTableNamesForCheckboxes();
// TODO: need to create the directory "uploads"
$uploadaddr = XOOPS_ROOT_PATH . "\\uploads\\";

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
    $uploadOK = true;                       // todo: should possibly be an associative array with true/false and message ??
    $filepath = basename($_FILES['fileToUpload']['name']);

    if ($filepath != NULL) {
        $uploadPath = $uploadaddr . $filepath;                                  // create temporary path to store zip file
        $fileType = pathinfo($filepath, PATHINFO_EXTENSION);                  // get file type

        if (file_exists($uploadPath)){                                          // check if file has already been uploaded
            $uploadOK = false;
        }
        if ($fileType != "zip"){                                               // check for correct input file type
            $uploadOK = false;
        }
        // place the file in a temporary folder
        if ($uploadOK) {
            if (move_uploaded_file($_FILES['uploadFile']['tmp_name'], $uploadPath)) {
                $tempFolder = doImport($uploadPath);

                if ($tempFolder["success"] == true) {
                    // TODO: add functions to continue with import process
                    $sync[1]['content']['error'] = "success";
                }
                // return an error as there were issues importing the file
                else {
                    $sync[1]['content']['error'] = "import_err";
                }
            }
            // return an error as there were issues moving the file to a temporary location
            else {
                $sync[1]['content']['error'] = "move_err";
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
        $str .= '<input type="checkbox" name="'.$key.'" id="forms" value="form[]" />'.$value.' ';
    }
    return $str;
}