<?php
    //include "../../../mainfile.php";
    include "../class/tableInfo.php";
    include_once "../include/functions.php";

    
    /*
     * doExport function exports template files and current Formulize database state to a ".zip" archive
     * 
     * param archiveName        string representing name of new or existing zip file. path must have ".zip" extension
     */
    function doExport($archiveName, $tableNamesList){
        $csvFilePaths = createCSVsAndGetPaths($tableNamesList); // $tableNamesList is a string array of tables to pull data from
        $archivePath = createExportArchive($archiveName, $csvFilePaths);
        
        cleanupCSVs($csvFilePaths);

        // ideally this is what should return
        // TODO :: return array( "success" => $flag, "filepath" => $archivePath );
        return $archivePath;
    }

    /*
     * createCSVsAndGetPaths function gets data from Formulize database, writes to CSV files and
     * returns array of paths to the CSV files
     *
     * param tables             string array of table names to pull data from (and write to CSV)
     * return paths             string array containing paths of all CSV files written
     */
    function createCSVsAndGetPaths($tables){
        $paths = Array();
        $date = date_create();
        // create directory in the "export" directory that is unique to the time created. will store export CSVs
        $exportDir = XOOPS_ROOT_PATH . "/modules/formulize/export/" . date_format($date, 'Y-m-d (U)') . "/";
        if (!file_exists($exportDir) and !mkdir($exportDir)){
            die("Export folder could not be created.");
        }

        $tableObj = new tableInfo();
        foreach ($tables as $key => $t){
            // attempt to read table data and if that fails remove the table from the list
            //      -> this can happen if the table does not exist
            try {
                // get dataArray from tableObj and format it to be written to CSV
                $dataArray = formatDataArrayForCSV($tableObj->get($t));
                writeCSVFile($exportDir, $t . ".csv", $dataArray);
                array_push($paths, $exportDir.$t.".csv");
            }
            catch (\PDOException $e) {
                error_log('Synchronization export table does not exist: '.$t);
                unset($tables[$key]);
            }
        }

        // special case for PREFIX_group_permissions since we only want formulize module records from table
        $t = XOOPS_DB_PREFIX."_group_permission";
        $groupPermData = $tableObj->getWithFilter($t, 'gperm_modid', getFormulizeModId());
        writeCSVFile($exportDir, $t.".csv", $groupPermData);
        array_push($paths, $exportDir.$t.".csv");
        
        return $paths;
    }
    
    /*
     * formatDataArrayForCSV function formats the dataArray from tableInfo class get format to a format that
     * can be written to a CSV
     *
     * param dataArray          Object array (containing String and String[]) having format defined by tableInfo class, get method
     * return formattedData     Object array (containing String and String[]) having format that can be written to CSV
     */
    function formatDataArrayForCSV($dataArray){
        // preprocess dataArray into a 1D array to be written to csv
        $formattedData = array();
        
        array_push($formattedData, array($dataArray["name"])); // add table name as array
        
        $cols = array();
        $types = array();
        for($i = 0; $i < count($dataArray["columns"]); $i ++){
            array_push($cols, $dataArray["columns"][$i][0]); // add each column name
            array_push($types, $dataArray["types"][$i][0]); // add each column type
        }
        array_push($formattedData, $cols); // add column names array
        array_push($formattedData, $types); // add column types array
        
        // push each row of data into formattedData as arrays
        
        for($i = 0; $i < count($dataArray["records"]); $i ++){ // row index
            $row = array();
            for($j = 0; $j < count($dataArray["columns"]); $j ++){ // column index
                array_push($row, $dataArray["records"][$i][$j]); // add column name
            }
            array_push($formattedData, $row); // add data row array
        }
        
        return $formattedData;
    }
    
    /*
     * writeCSVFile function writes given array to CSV file having path filepath
     * if the file does not exist it will be created
     * 
     * param filepath       string representing path to save file location
     * param dataArray      Array of String arrays containing data to be written to CSV file, each array in the array will be written to a new line
     */
    function writeCSVFile($dirPath, $fileName, $dataArray){
        $filePath = $dirPath . $fileName;
        $fileHandle = fopen($filePath, 'w');
        
        foreach ($dataArray as $row) {
            fputcsv($fileHandle, $row);
        }
        fclose($fileHandle);
    }
    
    
    // NOT CURRENTLY USED
    /*
     * getTemplateFilePaths returns array containing all paths for template and custom_code files in Formulize directory
     *
     * return paths     string array containing paths for all template files
     */
    function getTemplateFilePaths(){
        $screensPath = XOOPS_ROOT_PATH . "/modules/formulize/templates/screens";
        $customCodePath = XOOPS_ROOT_PATH . "/modules/formulize/custom_code";
        $paths = Array();
        
        if (file_exists($screensPath)){
            // iterate $screenPath directory and store all file paths in $paths array
            foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($screensPath)) as $filename){
                if ($filename->isDir()) continue; // skip "." and ".."
                array_push($paths, $filename);
            }
        }
        
        if (file_exists($customCodePath)){
            // iterate $customCodePath directory and store all file paths in $paths array
            foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($customCodePath)) as $filename){
                if ($filename->isDir()) continue; // skip "." and ".."
                array_push($paths, $filename);
            }
        }
        
        return $paths;
    }
    
    /*
     * createExportArchive function used to create an archive (.zip) file and insert given files into it
     *
     * param archiveName        string representing path to new or existing zip file. path should have ".zip" extension
     * param listOfFiles        string array containing 1 path for each file to be inserted into archive
     * return archivePath       path to archive file
     */
    function createExportArchive($archiveName, $listOfFiles){
        $archivePath = XOOPS_ROOT_PATH . "/modules/formulize/export/".$archiveName; // path where archive is created
        
        // zip screens files
        zipFolder("screens", XOOPS_ROOT_PATH . "/modules/formulize/templates/screens", $archivePath, true);
        
        // zip custom_code files
        zipFolder("custom_code", XOOPS_ROOT_PATH . "/modules/formulize/custom_code", $archivePath, false);
        
        // zip csv files
        zipFileList("tables", $listOfFiles, $archivePath, false);
        
        return $archivePath;
    }
    
    
    
    /*
     * zipFolder function zips given directory and all its contents into a master folder if specified (creates or adds to existing zip file)
     *
     * param masterFolderName       String name of folder to be created in zip. all contents will be placed in this folder. If empty string,
     *                              given files will be placed in root of zip
     * param rootDirPath            String path to directory that is to be zipped. The contents of this directory will be added to zip file
     * param archiveName            String name of archive to be created or added to (if existing)
     * param overwrite              boolean representing whether to overwrite if zip is currently existing
     */
    function zipFolder($masterFolderName, $rootDirPath, $archivePath, $overwrite){
        
        $zip = new ZipArchive();
        // open archive object. ".zip" file is only created once a file has been added to it
        if ($overwrite){
            if ( $zip->open($archivePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
                die ("Could not open archive");
            }
        }else{
            if ($zip->open($archivePath, ZIPARCHIVE::CREATE) !== TRUE) {
                die ("Could not open archive");
            }
        }
        $zip->addEmptyDir($masterFolderName);
        
        // recursive directory iterator to get all folders & files in screens directory
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($rootDirPath), RecursiveIteratorIterator::LEAVES_ONLY);
        
        foreach ($files as $name => $file){
            // skip directories, they will be added automatically
            if (!$file->isDir()){
                $filePath = $file->getRealPath(); // used to grab actual file from file system
                if ($masterFolderName != ""){
                    $relativePath = $masterFolderName . "/" . substr($filePath, strlen($rootDirPath) + 1); // used to represent file in zip
                }else{
                    $relativePath = substr($filePath, strlen($rootDirPath) + 1); // used to represent file in zip
                }
                $zip->addFile($filePath, $relativePath);// add current file to archive
            }
        }
        $zip->close();
    }
    
    /*
     * zipFileList function zips each file in the given list of files into a master folder if specified (creates or adds to existing zip file)
     *
     * param masterFolderName       String name of folder to be created in zip. all contents will be placed in this folder. If empty string,
     *                              given files will be placed in root of zip
     * param listOfFiles            String array, each index containing a path to a file to be zipped
     * param archiveName            String name of archive to be created or added to (if existing)
     * param overwrite              boolean representing whether to overwrite if zip is currently existing
     */
    function zipFileList($masterFolderName, $listOfFiles, $archivePath, $overwrite){
        
        $zip = new ZipArchive(); // create ZipArchive object
        // open archive object. ".zip" file is only created once a file has been added to it
        if ($overwrite){
            if ( $zip->open($archivePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
                die ("Could not open archive");
            }
        }else{
            if ($zip->open($archivePath, ZIPARCHIVE::CREATE) !== TRUE) {
                die ("Could not open archive");
            }
        }
        
        $zip->addEmptyDir($masterFolderName);
        foreach($listOfFiles as $file){
            if ($masterFolderName != ""){ // add file to master folder
                $zip->addFile($file, $masterFolderName . "/" . basename($file)) or die ("ERROR: Could not add file: $file");
            }else{ // add file to root of zip
                $zip->addFile($file, basename($file)) or die ("ERROR: Could not add file: $file");
            }
        }
        
        $zip->close(); // close and save archive
    }
    /*
     * cleanupCSVs function deletes backup csv files created during export. Should be called after archive has been successfully created
     * 
     * param csvPaths       String array containing paths to all csv files used for export
     */
    function cleanupCSVs($csvPaths){
        foreach($csvPaths as $file){
            unlink($file);
        }
        if (count($csvPaths) != 0){
            rmdir(dirname($csvPaths[0]));
        }
    }
    
    //syncTablesList function returns a complete list of database tables that are required to be synced
    function syncDefaultTablesList() {
        // init with a few hardcoded tables that we need
        $tablesList = array("groups");

        // include the tables from modversion['tables'] from xoops_version
        $module_handler = xoops_gethandler('module');
        $formulizeModule = $module_handler->getByDirname("formulize");
        $metadata = $formulizeModule->getInfo();
        $tablesList = array_merge($tablesList, $metadata['tables']);

        // add prefix to all table names
        foreach ($tablesList as &$value) {
            $value = XOOPS_DB_PREFIX . '_' . $value;
        }

        return $tablesList;
    }

    //syncDataTablesList function returns a complete list of database tables that have been generated for forms
    function syncDataTablesList() {

        global $xoopsDB;
        $tablesList = array();

        $sql = "SELECT desc_form FROM " . XOOPS_DB_PREFIX . "_formulize_id;";
        $result = icms::$xoopsDB->query($sql);

        while ($row = $xoopsDB->fetchRow($result)) {
            // extract the form_handle from the data record row and add it to the list
            $handle = $row[0];
            array_push($tablesList, $handle);
        }

        return $tablesList;
    }

    //NOT CURRENTLY USED
    /*
     * writeJSONToFile function writes (exports) data to JSON file having path filepath
     * if the file does not exist it will be created
     * 
     * param filepath       string representing path to save file location
     * param data           <data type CURRENTLY UNKNOWN> object containing data to be written to JSON file
     */
    function writeJSONFile($filePath, $data){
        print "writing \"".$data."\" to file";
        $fileHandle = fopen($filePath, 'w');
        fwrite($fileHandle, json_encode($data));
        fclose($fileHandle);
    }
?>
