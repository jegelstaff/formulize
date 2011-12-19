<?php
/**
* Class to easily export data from IcmsPersistables
*
* @copyright	The ImpressCMS Project http://www.impresscms.org/
* @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
* @package		IcmsPersistableObject
* @since		1.2
* @author		marcan <marcan@impresscms.org>
* @author	    Sina Asghari (aka stranger) <pesian_stranger@users.sourceforge.net>
* @version		$Id: icmspersistableexport.php 8569 2009-04-11 13:34:58Z icmsunderdog $
*/

class IcmsPersistableExport {

	var $handler;
	var $criteria;
	var $fields;
	var $format;
	var $filename;
	var $filepath;
	var	$options;
	var $outputMethods=false;
	var $notDisplayFields;

	/**
	 * Constructor
	 *
	 * @param object $objectHandler IcmsPersistableHandler handling the data we want to export
	 * @param object $criteria containing the criteria of the query fetching the objects to be exported
	 * @param array $fields fields to be exported. If FALSE then all fields will be exported
	 * @param string $filename name of the file to be created
	 * @param string $filepath path where the file will be saved
	 * @param string $format format of the ouputed export. Currently only supports CSV
	 * @param array $options options of the format to be exported in
	 */
	function IcmsPersistableExport(&$objectHandler, $criteria=null, $fields=false, $filename=false, $filepath=false, $format='csv', $options=false) {
		$this->handler = $objectHandler;
		$this->criteria = $criteria;
		$this->fields = $fields;
		$this->filename = $filename;
		$this->format = $format;
		$this->options = $options;
		$this->notDisplayFields = false;
	}

	/**
	 * Renders the export
	 */
	function render($filename) {

		$this->filename = $filename;

		$objects = $this->handler->getObjects($this->criteria);
		$rows = array();
		$columnsHeaders = array();
		$firstObject = true;
		foreach ($objects as $object) {
			$row = array();
			foreach ($object->vars as $key=>$var) {
				if ((!$this->fields || in_array($key, $this->fields)) && !in_array($key, $this->notDisplayFields)) {
					if ($this->outputMethods && (isset($this->outputMethods[$key])) && (method_exists($object, $this->outputMethods[$key]))) {
						$method = $this->outputMethods[$key];
						$row[$key] = $object->$method();
					} else {
						$row[$key] = $object->getVar($key);
					}
					if ($firstObject) {
						// then set the columnsHeaders array as well
						$columnsHeaders[$key] = $var['form_caption'];
					}
				}
			}
			$firstObject = false;
			$rows[] = $row;
			unset($row);
		}
		$data = array();
		$data['rows'] = $rows;
		$data['columnsHeaders'] = $columnsHeaders;
		$smartExportRenderer = new IcmsExportRenderer($data, $this->filename, $this->filepath, $this->format, $this->options);
		$smartExportRenderer->execute();
	}

	/**
	 * Set an array contaning the alternate methods to use instead of the default getVar()
	 *
	 * $outputMethods array example : 'uid' => 'getUserName'...
	 */
	function setOuptutMethods($outputMethods) {
		$this->outputMethods = $outputMethods;
	}

	/*
	 * Set an array of fields that we don't want in export
	 */
	 function setNotDisplayFields($fields){
	 	if(!$this->notDisplayFields){
	 		if(is_array($fields)){
	 			$this->notDisplayFields = $fields;
	 		}else{
	 			$this->notDisplayFields = array($fields);
	 		}
	 	}else{
	 		if(is_array($fields)){
	 			$this->notDisplayFields = array_merge($this->notDisplayFields, $fields);
	 		}else{
	 			$this->notDisplayFields[] = $fields;
	 		}
	 	}
	 }
}

/**
 * IcmsExportRenderer class
 *
 * Class that renders a set of data into a specific export format
 *
 * @package IcmsPersistable
 * @author marcan <marcan@smartfactory.ca>
 * @link http://www.smartfactory.ca The SmartFactory
 */
class IcmsExportRenderer {

	var $data;
	var $format;
	var $filename;
	var $filepath;
	var $options;

	/**
	 * Constructor
	 *
	 * @param array $data contains the data to be exported
	 * @param string $format format of the ouputed export. Currently only supports CSV
	 * @param string $filename name of the file in which the exported data will be saved
	 * @param string $filepath path where the file will be saved
	 * @param array $options options of the format to be exported in
	 */
	function IcmsExportRenderer($data, $filename=false, $filepath=false, $format='csv', $options=array('separator'=>';')) {
		$this->data = $data;
		$this->format = $format;
		$this->filename = $filename;
		$this->filepath = $filepath;
		$this->options = $options;
	}

	function arrayToCsvString($dataArray, $separator, $trim = 'both', $removeEmptyLines = TRUE) {
		if (!is_array($dataArray) || empty ($dataArray))
			return '';
		switch ($trim) {
			case 'none' :
				$trimFunction = FALSE;
				break;
			case 'left' :
				$trimFunction = 'ltrim';
				break;
			case 'right' :
				$trimFunction = 'rtrim';
				break;
			default : //'both':
				$trimFunction = 'trim';
				break;
		}
		$ret = array ();
		foreach($dataArray as $key=>$field){
			$ret[$key] = $this->valToCsvHelper($field, $separator, $trimFunction);
		}

		return implode($separator, $ret);

	}
	function valToCsvHelper($val, $separator, $trimFunction) {
		if ($trimFunction)
			$val = $trimFunction ($val);
		//If there is a separator (;) or a quote (") or a linebreak in the string, we need to quote it.
		$needQuote = FALSE;
		do {
			if (strpos($val, '"') !== FALSE) {
				$val = str_replace('"', '""', $val);
				$needQuote = TRUE;
				break;
			}
			if (strpos($val, $separator) !== FALSE) {
				$needQuote = TRUE;
				break;
			}
			if ((strpos($val, "\n") !== FALSE) || (strpos($val, "\r") !== FALSE)) { // \r is for mac
				$needQuote = TRUE;
				break;
			}
		} while (FALSE);
		if ($needQuote) {
			$val = '"' . $val . '"';
		}
		return $val;
	}


	function execute() {
		$exportFileData = '';

		switch ($this->format) {
			case 'csv':
				$separator = isset($this->options['separator']) ? $this->options['separator'] : ';';
				$firstRow = implode($separator, $this->data['columnsHeaders']);
				$exportFileData .= $firstRow . "\r\n";

				foreach($this->data['rows'] as $cols) {
					$exportFileData .= $this->arrayToCsvString($cols, $separator) . "\r\n";
				}
			break;
		}
		$this->saveExportFile($exportFileData);
	}

	function saveExportFile($content) {
		switch ($this->format) {
			case 'csv':
				$this->saveCsv($content);
			break;
		}
	}

	function saveCsv($content) {
		if (!$this->filepath) {
			$this->filepath = ICMS_UPLOAD_PATH . '/';
		}
		if (!$this->filename) {
			$this->filename .= time();
			$this->filename .= '.csv';
		}

		$fullFileName = $this->filepath . $this->filename;

		if (!$handle = fopen($fullFileName, 'a+')) {
			trigger_error('Unable to open ' . $fullFileName, E_USER_WARNING);
		} elseif (fwrite($handle, $content) === FALSE) {
			trigger_error('Unable to write in ' . $fullFileName, E_USER_WARNING);
		} else {
			$mimeType = 'text/csv';
		    $file = strrev($this->filename);
		    $temp_name = strtolower(strrev(substr($file,0,strpos($file,"--"))) );
			if ($temp_name == '') {
				$file_name = $this->filename;
			} else {
				$file_name = $temp_name;
			}
		    $fullFileName = $this->filepath . stripslashes(trim($this->filename));

		    if(ini_get('zlib.output_compression')) {
		        ini_set('zlib.output_compression', 'Off');
		    }

		    header("Pragma: public");
		    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
		    header("Cache-Control: private",false);
		    header("Content-Transfer-Encoding: binary");
		    if(isset($mimeType)) {
		        header("Content-Type: " . $mimeType);
		    }

		    header("Content-Disposition: attachment; filename=" . $file_name);

		    if(isset($mimeType) && strstr($mimeType, "text/")) {
		        $fp = fopen($fullFileName, "r");
		    }
		    else {
		        $fp = fopen($fullFileName, "rb");
		    }
		    fpassthru($fp);
		    exit();
		}
		fclose($handle);
	}
}

?>