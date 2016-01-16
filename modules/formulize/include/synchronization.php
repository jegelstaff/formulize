<html>
    <head><title>Test</title></head>
    <body>
    <?php
        /*
         * TO DO:
         *      1. createArchive - verify that current implementation can insert files into existing
         *                          zip file
         *          
         *      2. createArchive - assert .zip extension for $archiveName parameter
         *
         *      3. getTemplateFilePaths - populate paths array
         *
         *      4. createCSVs - integrate with Andrew's code by creating and extracting data from a data object
         */
        

        /*
         * doExport function exports template files and current Formulize database state to a ".zip" archive
         *
         * param archiveName        string representing path to new or ???existing??? zip file. path should have ".zip" extension
         * param dataArray          string array containing data to be written to CSV file
         */
        function doExport($archiveName, $dataArray){
            // consider asserting ".zip" extension here. If $archiveName does not have .zip extension, add it
            $csvFilePaths = createCSVs($dataArray);
            $templateFilePaths = getTemplateFilePaths();

            createArchive($archiveName, array_merge($jsonFilePaths, $templateFilePaths));
        }

        /*
         * createCSVs function gets data from Formulize database, writes to CSV files and
         * returns array of paths to the CSV files
         *
         * param dataArray          string array containing data to be written to CSV file
         * return paths             string array containing paths of all CSV files written
         */
        function createCSVs($dataArray){
            $paths = Array();
            /* create query~ object and call method to get data
             * for each dataArray
             *      array_push($csvFilePaths, writeCSVFile(pathToFile, $dataArray));
             */
            return $paths;
        }

        /*
         * writeCSVFile function writes given array to CSV file having path filepath
         * if the file does not exist it will be created
         *
         * param filepath       string representing path to save file location
         * param dataArray      string array containing data to be written to CSV file
         */
        function writeCSVFile($filePath, $dataArray){
            print "writing \"".implode(",", $dataArray)."\" to file"; // used for testing
            $fileHandle = fopen($filePath, 'w');
            fputcsv($fileHandle, $dataArray);
            fclose($fileHandle);
        }

        /*
         * getTemplateFilePaths returns array containing all paths for template files of Formulize
         *
         * return paths     string array containing paths for all template files
         */
        function getTemplateFilePaths(){
            $paths = Array();
            // populate paths array
            // recursively gather all file paths in XOOPS_ROOT_PATH . "/modules/formulize/templates/screens";
            //    and at least one more file - TBD
            // initialize an Iterator object and pass it the directory containing all export files
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($archivePath));

            // iterate over the directory
            // add each file found to the archive
            foreach ($iterator as $key=>$value) {
                $zip->addFile(realpath($key), $key) or die ("ERROR: Could not add file: $key");
            }*/
            return $paths;
        }

        /*
         * createArchive function used to create an archive (.zip) file and insert given files into it
         *
         * param archiveName        string representing path to new or ???existing??? zip file. path should have ".zip" extension
         * param listOfFiles        string array containing 1 path for each file to be inserted into archive
         * return archivePath       path to archive file
         */
        function createArchive($archiveName, $listOfFiles){

            $archivePath = XOOPS_ROOT_PATH . "/modules/formulize/export"; // path where archive is created

            $zip = new ZipArchive(); // create ZipArchive object

            // open archive. ???If it does not already exist??? it is created
            if ($zip->open($archivePath, ZIPARCHIVE::CREATE) !== TRUE) {
                die ("Could not open archive");
            }


            // initialize an Iterator object and pass it the directory containing all export files
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($archivePath));

            // iterate over the directory
            // add each file found to the archive
            foreach ($iterator as $key=>$value) {
                $zip->addFile(realpath($key), $key) or die ("ERROR: Could not add file: $key");
            }*/

            // close and save archive
            $zip->close();
            echo "Archive created successfully.";

            return exportZipPath;
        }

        include_once "../class/PDO_Conn.php";

        /*
         * syncTablesList function returns a complete list of database tables that are required to be synced
         */
        function syncTablesList() {
            global $xoopsDB;

            // include the tables from modversion['tables'] from xoops_version
            $module_handler = xoops_gethandler('module');
            $formulizeModule = $module_handler->getByDirname("formulize");
            $metadata = $formulizeModule->getInfo();

            // check through forms table to find any generated database table handles that we need
            $handlesQuery = "SELECT form_handle FROM ".$xoopsDB->prefix('formulize_')."id;";
            $conn = new Connection();
            $handles = $conn->connect()->query($handlesQuery)->fetchAll();

            // using the handles generate the list table names
            foreach ($handles as $key => $handleRec) {
                $handle = $handleRec[0];
                $handles[$key] = $xoopsDB->prefix('formulize_').$handle;
            }

            // add prefix to metadata tables
            array_walk($metadata['tables'], function(&$value, $key) { $value = Prefix."_".$value; });

            return array_merge($metadata['tables'], $handles);
        }
        
        
        
        
        //PROBABLY DON'T NEED writeJSONFile FUNCTION
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
    </body>
</html>