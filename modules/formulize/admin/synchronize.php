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
$error = "";

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
        $export = doExport($filename, $checks);
        $save = array( "success" => "true", "filepath" => $export );

        if ($save["success"] == "true") {
            header('Content-Description: File Transfer');
            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename=' . $save["filepath"]);
            header('Content-Length: ' . filesize($save["filepath"]));
            readfile($filepath);
            $error = "An error occurred while exporting";
        }
        else {
            // return error message to the user
            $errorMsg = "errorMsg";
            $error .= '<span class='.$errorMsg.'>echo '.$error.'</span>';
        }
    }
}
// retrieve the post information from the import submit
else if(isset($_POST['import'])) {

    $filename = $_POST['file'];

    if ($filename != NULL) {
        // kick-off the import
    }
    // didn't select a file, do nothing
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

function saveZipToLocation() {
    $headers = array();
    foreach($cols as $thiscol) {
        if ($thiscol == "creator_email") {
            $headers[] = _formulize_DE_CALC_CREATOR_EMAIL;
        } else {
            $colMeta = formulize_getElementMetaData($thiscol, true);
            $headers[] = $colMeta['ele_colhead'] ? trans($colMeta['ele_colhead']) : trans($colMeta['ele_caption']);
        }
    }

    $filename = prepExport($headers, $cols, $data, $fdchoice, "", "", false, $fid, $groups);

    $pathToFile = str_replace(XOOPS_URL,XOOPS_ROOT_PATH, $filename);

    if ($_GET['type'] == "update") {
        $fileForUser = str_replace(XOOPS_URL. SPREADSHEET_EXPORT_FOLDER, "", $filename);
    } else {
        $form_handler = xoops_getmodulehandler('forms','formulize');
        $formObject = $form_handler->get($fid);
        if (is_object($formObject)) {
            $formTitle = "'".str_replace(array(" ", "-", "/", "'", "`", "\\", ".", "?", ",", ")", "(", "[", "]"), "_", trans($formObject->getVar('title')))."'";
        } else {
            $formTitle = "a_form";
        }
        $fileForUser = _formulize_EXPORT_FILENAME_TEXT."_".$formTitle."_".date("M_j_Y_Hi").".csv";
    }

    header('Content-Description: File Transfer');
    header('Content-Type: text/csv; charset='._CHARSET);
    header('Content-Disposition: attachment; filename='.$fileForUser);
    header('Content-Transfer-Encoding: binary');
    header('Expires: 0');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Pragma: public');

    if (strstr(strtolower(_CHARSET),'utf') AND $_POST['excel'] == 1) {
        echo "\xef\xbb\xbf"; // necessary to trigger certain versions of Excel to recognize the file as unicode
    }
    if (strstr(strtolower(_CHARSET),'utf-8') AND $_POST['excel'] != 1) {
        ob_start();
        readfile($pathToFile);
        $fileContents = ob_get_clean();
        header('Content-Length: '. filesize($pathToFile) * 2);
        // open office really wants it in UTF-16LE before it will actually trigger an automatic unicode opening?! -- this seems to cause problems on very large exports?
        print iconv("UTF-8","UTF-16LE//TRANSLIT", $fileContents);
    } else {
        header('Content-Length: '. filesize($pathToFile));
        readfile($pathToFile);
    }
}