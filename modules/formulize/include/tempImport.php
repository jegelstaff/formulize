<html>
    <body>
        <?php
            include "../../../mainfile.php";
            
            /*
             * TO DO:
             *
             * 1. Extract folders from archive
             */
            
            //doImport(XOOPS_ROOT_PATH . "/modules/formulize/export/test.zip");
            
            /*
             * doImport function imports template files and current Formulize database state from a ".zip" archive
             *
             * param archivePath        String path to archive file. file should have ".zip" extension
             */
            function doImport($archivePath){
                   extractArchiveFolders($archivePath);
            }
            
            /*
             * extractArchiveFolders function calls extractFolder for the folders in the archive
             *
             * param  archivePath        String path to archive file
             */
            function extractArchiveFolders($archivePath){
                extractFolder($archivePath, "screens", XOOPS_ROOT_PATH . "/modules/formulize/templates/screens");
                extractFolder($archivePath, "custom_code", XOOPS_ROOT_PATH . "/modules/formulize/custom_code");
                
                // create temporary folder to extract CSV files to. will be deleted later
                $tempFolderPath = XOOPS_ROOT_PATH . "/modules/formulize/temp" . date_format(date_create(), '(U)');
                 if (!file_exists($tempFolderPath) and !mkdir($tempFolderPath)){
                     die("Export folder could not be created.");
                 }
                extractFolder($archivePath, "tables", $tempFolderPath);
            }
            
            
            
            /*
             * extractFolder function extracts the given folder to the given extract location from a zip file
             *
             * param archivePath        String path to archive file to be extracted from
             * param folderToExtract    String name of folder within archive the is to be extracted
             * param extractToPath      String path to location to be extracted to
             */
            function extractFolder($archivePath, $folderToExtract, $extractToPath){
                if ($zip->open($archivePath, ZIPARCHIVE::_______) !== TRUE) {
                    die ("Could not open archive");
                }
                
                
            }
            
            /*
             * getDataRowCSV function parses the given CSV file and returns the desired row of data (1 is the first line, not 0)
             *
             * param filePath       String path to CSV file to parse
             * param line           int line number to return
             * return dataRow       String array containing row of data
             */
            function getDataRowCSV($filePath, $line){
                $dataRow = array();
                $fileHandle = fopen($filePath, 'r');
                
                fgetcsv($fileHandle); // skip table name
                fgetcsv($fileHandle); // skip column names
                fgetcsv($fileHandle); // skip column types
                
                for ($i = 0; $i < $line; $i ++){
                    $dataRow = fgetcsv($fileHandle);
                }
                
                fclose($fileHandle);
                return $dataRow;
            }
            
            
            
            /*
             * getTableColsCSV function parses the given CSV file and returns an array of column names
             *
             * param filePath       String path to CSV file to parse
             * return cols          String array of column names
             */
            function getTableColsCSV($filePath){
                $fileHandle = fopen($filePath, 'r');
                
                fgetcsv($fileHandle); // skip table name
                $cols = fgetcsv($fileHandle); // get column names
                
                fclose($fileHandle);
                return $cols;
            }
            
            /*
             * getTableColTypesCSV function parses the given CSV file and returns an array of column types
             *
             * param filePath       String path to CSV file to parse
             * return colTypes      String array of column types
             */
            function getTableColTypesCSV($filePath){
                $fileHandle = fopen($filePath, 'r');
                
                fgetcsv($fileHandle); // skip table name
                fgetcsv($fileHandle); // skip column names
                $colTypes = fgetcsv($fileHandle); // get column types
                
                fclose($fileHandle);
                return $colTypes;
            }
            
            /*
             * getNumDataRowsCSV function parses the given CSV file and returns number of data rows
             *
             * param filePath       String path to CSV file to parse
             * return numRows       int number of data rows in file
             */
            function getNumDataRowsCSV($filePath){
                $numRows = 0;
                $fileHandle = fopen($filePath, 'r');
                
                fgetcsv($fileHandle); // skip table name
                fgetcsv($fileHandle); // skip column names
                fgetcsv($fileHandle); // skip column types
                while(!feof($fileHandle)){ // iterate through file until eof
                    $content = fgets($fileHandle);
                    if($content){ // line is non empty, must be data so increment number of rows
                        $numRows++;
                    }
              }
                
                fclose($fileHandle);
                return $numRows;
            }
        ?>
    </body>
</html>