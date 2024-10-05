<?php
    
    include_once "../class/tableInfo.php";
    include_once "../include/synccompare.php";
    include_once "../include/functions.php";
    
    //global variables
    $successfulExport = 1;
    $successfulImport = 1;
    
    /*
     * doExport function exports template files and current Formulize database state to a ".zip" archive
     * 
     * param archiveName        String representing name of new or existing zip file. path must have ".zip" extension
     * param formsSelected      Array of form ids
     * return array             Key value array containing boolean success flag and String path to archive file created from export
     */
    function doExport($archiveName, $formsSelected){
        global $successfulExport;
        
        $csvFilePaths = createCSVsAndGetPaths(syncDataTablesList($formsSelected));
        $archivePath = createExportArchive($archiveName, $csvFilePaths);
        error_log(print_r($archivePath, true));

        cleanupCSVs($csvFilePaths);
        return array( "success" => $successfulExport, "filepath" => $archivePath );
    }
    

    /*
     * doImport function has been split into 3 functions:
     * 
     *      1. extractCSVs() - this function extracts the csv files to a temp location and returns its path
     *      2. csvToDB() - this function compares the data in the csv files with the database and performs necessary insert/removes
     *      3. extractTemplateFiles() - this function extracts the template and custom code files from the zip to the target system
     */
    
    
    
    
    
    /********************************************
     *          EXPORT FUNCTIONS                *
     ********************************************/
    

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
            $successfulExport = 0;
            error_log("Export folder could not be created.");
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
                $successfulExport = 0;
            }
        }

        // special case for PREFIX_group_permissions since we only want formulize module records from table
        $t = XOOPS_DB_PREFIX."_group_permission";
        $groupPermData = $tableObj->getWithFilter($t, 'gperm_modid', getFormulizeModId());
        $formattedGroupPermData = formatDataArrayForCSV($groupPermData);
        writeCSVFile($exportDir, $t.".csv", $formattedGroupPermData);
        array_push($paths, $exportDir.$t.".csv");
        
        return $paths;
    }
    
    /*
     * formatDataArrayForCSV function formats the dataArray from tableInfo class to a format that
     * can be written to a CSV
     *
     * param dataArray          Object array (containing String and String[]) having format defined by tableInfo class, get method
     * return formattedData     Object array (containing String and String[]) having format that can be written to CSV
     */
    function formatDataArrayForCSV($dataArray){
        // preprocess dataArray into a 1D array to be written to csv
        $formattedData = array();
        
        array_push($formattedData, array(removeFormulizeIdPrefix($dataArray["name"]))); // add table name as array
        
        $cols = array();
        $types = array();
        
        for($i = 0; $i < count((array) $dataArray["columns"]); $i ++){
            array_push($cols, $dataArray["columns"][$i][0]); // add each column name
            array_push($types, $dataArray["types"][$i][0]); // add each column type
        }
        array_push($formattedData, $cols); // add column names array
        array_push($formattedData, $types); // add column types array
        
        // push each row of data into formattedData as arrays
        for($i = 0; $i < count((array) $dataArray["records"]); $i ++){ // row index
            $row = array();
            foreach($dataArray["columns"] as $columnData) {
                array_push($row, $dataArray["records"][$i][$columnData[0]]); 
            }
            array_push($formattedData, $row); // add data row array
        }
        return $formattedData;
    }
    
    
    /*
     * removeFormulizeIdPrefix function removes the "i##[a-z][a-z][a-z]###_" prefix from the given string
     * by removing all characters up to and including the first underscore
     *
     * param string         String with formulize id prefix
     * return cleanString   String without formulize id prefix
     */
    function removeFormulizeIdPrefix($string){
        $cleanString = "";
        $i = 0;
        
        // skip prefix
        while($string[$i] != "_"){ $i ++; }
        
        // record all chars after prefix
        for ($i += 1; $i < strlen($string); $i ++){
            $cleanString .= $string[$i];
        }
        return $cleanString;
        //return substr($string, 10);
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

    /*
     * // NOT CURRENTLY USED
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
                $successfulExport = 0;
                error_log("Could not create archive file.");
            }
        }else{
            if ($zip->open($archivePath, ZIPARCHIVE::CREATE) !== TRUE) {
                $successfulExport = 0;
                error_log("Could not create archive file.");
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
                $successfulExport = 0;
                error_log("Could not create archive.");
            }
        }else{
            if ($zip->open($archivePath, ZIPARCHIVE::CREATE) !== TRUE) {
                $successfulExport = 0;
                error_log("Could not create archive.");
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
        if (count((array) $csvPaths) != 0){
            rmdir(dirname($csvPaths[0]));
        }
    }
    
    //syncDefaultTables List return a list of default tables used in exporting
    function syncDefaultTablesList() {
        // init with a few hardcoded tables that we need
        $tablesList = array("groups");
        // include the tables from modversion['tables'] from xoops_version
        $module_handler = xoops_gethandler('module');
        $formulizeModule = $module_handler->getByDirname("formulize");
        $metadata = $formulizeModule->getInfo();
        $tablesList = array_merge($tablesList, $metadata['formulize_exportable_tables']);
        // add prefix to all table names
        foreach ($tablesList as &$value) {
            $value = XOOPS_DB_PREFIX . '_' . $value;
        }
        return $tablesList;
    }
    
    function syncGroupsInCommonLists() {
        static $syncGroupsInCommonLists = array();
        if(count($syncGroupsInCommonLists)==0) {
            $module_handler = xoops_gethandler('module');
            $formulizeModule = $module_handler->getByDirname("formulize");
            $metadata = $formulizeModule->getInfo();
            $syncGroupsInCommonLists['group_id_fields'] = $metadata['formulize_group_id_fields'];
            $syncGroupsInCommonLists['group_tables'] = $metadata['formulize_group_tables'];
            $syncGroupsInCommonLists['group_id_embedded'] = $metadata['formulize_group_id_embedded'];
        }
        return $syncGroupsInCommonLists;
    }

    //syncDataTablesList function gets the default tables for export and add the user selected forms
    // and returns the complete list of tables to be exported
    function syncDataTablesList($formsSelected) {
        // get the default tables we need for export
        $tablesList = syncDefaultTablesList();

        // get the user selected form tables, if any were selected
        if (!empty($formsSelected)) {
            global $xoopsDB;
            $formTables = array();
            // query the db for the forms that were selected by the user, $formsChecked should consist of all the id_form numbers
            $sql = "SELECT form_handle FROM " . XOOPS_DB_PREFIX . "_formulize_id" . " WHERE id_form IN (" . implode(",", $formsSelected) . ");";
            $result = icms::$xoopsDB->query($sql);
            while ($row = $xoopsDB->fetchRow($result)) {
                // extract the form_handle from the data record row and add it to the list
                $handle = $row[0];
                array_push($formTables, "formulize_" . $handle);
            }
            // add prefix to all form handles
            foreach ($formTables as &$value) {
                array_push($tablesList, XOOPS_DB_PREFIX . '_' . $value);
            }
        }

        return $tablesList;
    }

    function getFormsInfo() {
        $forms_handler = xoops_getmodulehandler('forms', 'formulize');
        $forms = $forms_handler->getAllForms();

        $formdata = array();
        foreach($forms as $form) {
            $form_title = $form->getVar('title');
            $form_handle = $form->getVar('form_handle');
            $fid = $form->getVar('id_form');

            array_push($formdata, array("fid" => $fid, "title" => $form_title, "form_handle" => $form_handle));
        }
        return $formdata;
    }

    
    /********************************************
     *          IMPORT FUNCTIONS                *
     ********************************************/
    
    /*
     * csvToDB function sends each exported table to synccompare.php compareRecToDB() function
     *
     * param csvFolderPath      String path to folder containing exported CSV files
     * return array             Key value array containing boolean success flag
     */
    function csvToDB($csvFolderPath){
        global $successfulImport;
        if (file_exists($csvFolderPath)){
            $comparator = new SyncCompareCatalog();
            $comparator->loadCachedChanges();
            // iterate $csvFolderPath directory and import each file
            // only do three at a time for now
            $counter = 0;
            foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($csvFolderPath)) as $filePath){
                if ($filePath->isDir()) continue; // skip "." and ".."
                if (in_array($filePath->getPathName(), (array)$comparator->doneFilePaths)) continue;
                $counter++;
                $partialImport = 1;
                if($counter > 3) break; // only do three at a time
                $fileHandle = fopen($filePath, 'r');
                $tableName = fgetcsv($fileHandle);
                $tableName = $tableName[0];
                $cols = fgetcsv($fileHandle);
                fgetcsv($fileHandle); // skip column types
                while($data = fgetcsv($fileHandle)) {
                    $comparator->addRecord($tableName, $data, $cols);
            }
                fclose($fileHandle);
            $comparator->cacheChanges();
                $comparator->cacheFilePath($filePath->getPathName());
                $partialImport = 0;
            }
        }else{
            $successfulImport = 0;
            error_log("Path to extracted CSV files does not exist.");
        }
        return array( "success" => $successfulImport, "partial" => $partialImport);
    }

    /*
     * cacheExportFile function moves the zip being imported to the cache directory
     * and renames the zip by the current session id. This function is the last step of
     * the first import page on the front end
     * 
     * param  archivePath       String path to archive file
     */
    function cacheExportFile($archivePath){
        formulize_scandirAndClean(XOOPS_ROOT_PATH . "/modules/formulize/cache/", ".zip");
        // copies the archive file to the cache folder and renames the copied file to the session id
        $filepath = getCachedExportFilepath();
        rename($archivePath, $filepath);
        return $filepath;
    }

    /*
     * getCachedExportFilepath function just returns the filepath to the cached export zip
     *  with the current session id in the filename
     */
    function getCachedExportFilepath() {
        return XOOPS_ROOT_PATH . "/modules/formulize/cache/sync-export-".session_id().".zip";
    }
    
    /*
     * printArr utility function prints each element of given 1-D String array with comma separator
     *
     * param arr    String array to be printed
     */
    function printArr($arr){
        foreach($arr as $elem){
            echo $elem.", ";
        }
        echo "<br>";
    }
    
    /*
     * deleteFolder function deletes folder at given path and its contents
     *
     * param path   String path to folder to delete
     */
    function deleteDir($path){
        // delete all files in tables/
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path)) as $file){
            if ($file->isDir()){
                continue; // skip "." and ".."
            }
            unlink($file);
        }
        //delete empty dirs
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path)) as $dir){
            if ($dir->isDir()){
                // must be current directory so try to delete
                if (strcmp(basename($dir), ".") == 0){
                    rmdir($dir);
                }
            }
        }
        rmdir($path);
    }
    
    /*
     * extractCSVs function extracts the csv files in the given archive to a temp location, ready for comparison with DB
     * 
     * param  archivePath       String path to archive file
     * return array             Key value array containing boolean success flag and path to temp csv folder
     */
    function extractCSVs($archivePath){
        global $successfulImport;
        // create temporary folder to extract CSV files to. will be deleted later
        $tempFolderPath = XOOPS_ROOT_PATH . "/modules/formulize/temp/" . date_format(date_create(), '(U)');
         if (!file_exists($tempFolderPath) and !mkdir($tempFolderPath)){
            $successfulImport = 0;
            error_log("Extraction folder for CSV's could not be created.");
         }
        extractFolder($archivePath, "tables", $tempFolderPath);
        
        return array( "success" => $successfulImport, "csvPath" => $tempFolderPath);
    }
    
    /*
     * extractTemplateFiles function extracts the template and custom code files to the target system
     * WILL OVERWRITE EXISTING FILES
     * 
     * param  archivePath       String path to archive file
     * return array             Key value array containing boolean success flag
     */
    function extractTemplateFiles($archivePath){
        global $successfulImport;
        extractFolder($archivePath, "screens", XOOPS_ROOT_PATH . "/modules/formulize/templates/");
        extractFolder($archivePath, "custom_code", XOOPS_ROOT_PATH . "/modules/formulize/");
        
        return array( "success" => $successfulImport);
    }
    
    
    /*
     * extractFolder function extracts the given folder to the given extract location from a zip file
     *
     * param archivePath        String path to archive file to be extracted from
     * param folderToExtract    String name of folder within archive the is to be extracted
     * param extractToPath      String path to location to be extracted to
     */
    function extractFolder($archivePath, $folderToExtract, $extractToPath){
        $zip = new ZipArchive;
        if ($zip->open($archivePath) !== TRUE) {
            $successfulImport = 0;
            error_log("Could not open archive file at path: '" . $archivePath . "'extraction.");
        }
        
        $files = array();
        for($i = 0; $i < $zip->numFiles; $i++) {
            $entry = $zip->getNameIndex($i);
             // strpos() to check if the entry name contains the directory we want to extract
            if (strpos($entry, $folderToExtract."/") !== false) {
              $files[] = $entry;
            }
        }
        
        $zip->extractTo($extractToPath, $files); // extract all files/dirs in $files array to the $extractToPath
    }
    