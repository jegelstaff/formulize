<html>
    <head><title>Test</title></head>
    <body>
    <?php
        include "../../../mainfile.php";
        //include_once "../class/PDO_Conn.php";
        /*
         * TO DO:
         *          
         *      1. createArchive - assert .zip extension for $archiveName parameter
         *
         *      2. getTemplateFilePaths - populate paths array
         *
         *      3. createCSVs - integrate with Andrew's code by creating and extracting data from a data object
         *
         *  NOTES:
         *      1. assuming syncTablesList works correctly. On my test environment I get hung up on the "$conn = new Connection();" line
         *          Andrew has tested it correctly on his environment however
         */
        
        
        /*
         * doExport function exports template files and current Formulize database state to a ".zip" archive
         * 
         * param archiveName        string representing path to new or ???existing??? zip file. path should have ".zip" extension
         * param dataArray          string array containing data to be written to CSV file
         */
        function doExport($archiveName, $dataArray){
            // consider asserting ".zip" extension here. If $archiveName does not have .zip extension, add it
            $csvFilePaths = createCSVsAndGetPaths(syncTablesList()); // syncTablesList() returns string array of tables to pull data from
            $templateFilePaths = getTemplateFilePaths();
            
            createArchive($archiveName, array_merge($jsonFilePaths, $templateFilePaths));
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
            foreach ($tables as $t){
                print $t."<br>";
            }
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
                    print "pushing ".$filename."<br>";
                    array_push($paths, $filename);
                }
            }
            
            if (file_exists($customCodePath)){
                // iterate $customCodePath directory and store all file paths in $paths array
                foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($customCodePath)) as $filename){
                    if ($filename->isDir()) continue; // skip "." and ".."
                    print "pushing ".$filename."<br>";
                    array_push($paths, $filename);
                }
            }
            
            return $paths;
        }
        
        /*
         * createArchive function used to create an archive (.zip) file and insert given files into it
         *
         * param archiveName        string representing path to new or existing zip file. path should have ".zip" extension
         * param listOfFiles        string array containing 1 path for each file to be inserted into archive
         * return archivePath       path to archive file
         */
        function createArchive($archiveName, $listOfFiles){
            //$archivePath = "C:/xampp/htdocs/".$archiveName;
            $archivePath = XOOPS_ROOT_PATH . "/modules/formulize/export"; // path where archive is created
            
            $zip = new ZipArchive(); // create ZipArchive object
            
            // open archive object. ".zip" file is only created once a file has been added to it
            if ($zip->open($archivePath, ZIPARCHIVE::CREATE) !== TRUE) {
                die ("Could not open archive");
            }
            
            foreach($listOfFiles as $file){
                $zip->addFile($file) or die ("ERROR: Could not add file: $file");
            }
            
            $zip->close(); // close and save archive
            
            return $archivePath;
        }
        
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
            $sql = "SELECT form_handle FROM ".$xoopsDB->prefix('formulize_')."id;";
            $result = icms::$xoopsDB->query($sql);
            $handles = array();

            while ($row = icms::$xoopsDB->fetchArray($result)) {
                array_push($handles, $row);
            }

            // using the handles generate the list table names
            foreach ($handles as $key => $handleRec) {
                $handle = $handleRec[0];
                $handles[$key] = $xoopsDB->prefix('formulize_').$handle;
            }

            // add prefix to metadata table names
            array_walk($metadata['tables'], function(&$value, $key) {
                global $xoopsDB;
                $value = $xoopsDB->prefix('formulize_')."_".$value;
            });

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