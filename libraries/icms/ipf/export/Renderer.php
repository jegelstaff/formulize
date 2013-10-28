<?php
/**
 * icms_ipf_export_Renderer class
 *
 * Class that renders a set of data into a specific export format
 * @category	ICMS
 * @package		Ipf
 * @subpackage	Export
 * @author		marcan <marcan@smartfactory.ca>
 * @version		SVN: $Id: Renderer.php 19775 2010-07-11 18:54:25Z malanciault $
 */

/**
 * renders a set of data into a specific export format
 *
 * @category	ICMS
 * @package		Ipf
 * @subpackage	Export
 *
 */
class icms_ipf_export_Renderer {

	public $data;
	public $format;
	public $filename;
	public $filepath;
	public $options;

	/**
	 * Constructor
	 *
	 * @param array $data contains the data to be exported
	 * @param string $format format of the ouputed export. Currently only supports CSV
	 * @param string $filename name of the file in which the exported data will be saved
	 * @param string $filepath path where the file will be saved
	 * @param array $options options of the format to be exported in
	 */
	public function __construct($data, $filename=false, $filepath=false, $format='csv', $options=array('separator'=>';')) {
		$this->data = $data;
		$this->format = $format;
		$this->filename = $filename;
		$this->filepath = $filepath;
		$this->options = $options;
	}

	/**
	 * Converts an array to a comma-separated string
	 *
	 *
	 * @param arr $dataArray
	 * @param str $separator
	 * @param str $trim
	 * @param bool $removeEmptyLines
	 */
	public function arrayToCsvString($dataArray, $separator, $trim = 'both', $removeEmptyLines = TRUE) {
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
		$ret = array();
		foreach ($dataArray as $key=>$field) {
			$ret[$key] = $this->valToCsvHelper($field, $separator, $trimFunction);
		}

		return implode($separator, $ret);

	}

	/**
	 *
	 *
	 * @param str $val
	 * @param str $separator
	 * @param str $trimFunction
	 */
	public function valToCsvHelper($val, $separator, $trimFunction) {
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
			if ((strpos($val, "\n") !== FALSE) || (strpos($val, "\r") !== FALSE)) {
				// \r is for mac
				$needQuote = TRUE;
				break;
			}
		} while (FALSE);
		if ($needQuote) {
			$val = '"' . $val . '"';
		}
		return $val;
	}

	/**
	 *
	 */
	public function execute() {
		$exportFileData = '';

		switch ($this->format) {
			case 'csv':
				$separator = isset($this->options['separator']) ? $this->options['separator'] : ';';
				$firstRow = implode($separator, $this->data['columnsHeaders']);
				$exportFileData .= $firstRow . "\r\n";

				foreach ($this->data['rows'] as $cols) {
					$exportFileData .= $this->arrayToCsvString($cols, $separator) . "\r\n";
				}
				break;

			default:
				break;
		}
		$this->saveExportFile($exportFileData);
	}

	/**
	 *
	 *
	 * @param str $content
	 */
	public function saveExportFile($content) {
		switch ($this->format) {
			case 'csv':
				$this->saveCsv($content);
				break;

			default:
				break;
		}
	}

	/**
	 *
	 *
	 * @param str $content
	 */
	public function saveCsv($content) {
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
			$temp_name = strtolower(strrev(substr($file,0,strpos($file,"--"))));
			if ($temp_name == '') {
				$file_name = $this->filename;
			} else {
				$file_name = $temp_name;
			}
			$fullFileName = $this->filepath . stripslashes(trim($this->filename));

			if (ini_get('zlib.output_compression')) {
				ini_set('zlib.output_compression', 'Off');
			}

			header("Pragma: public");
			header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
			header("Cache-Control: private",false);
			header("Content-Transfer-Encoding: binary");
			if (isset($mimeType)) {
				header("Content-Type: " . $mimeType);
			}

			header("Content-Disposition: attachment; filename=" . $file_name);

			if (isset($mimeType) && strstr($mimeType, "text/")) {
				$fp = fopen($fullFileName, "r");
			} else {
				$fp = fopen($fullFileName, "rb");
			}
			fpassthru($fp);
			exit();
		}
		fclose($handle);
	}
}

