<?php
/**
 * Icms Download Handler Base Class
 *
 * Base class for the zipfile and tarfile downloads
 *
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license		LICENSE.txt
 * @category	ICMS
 * @package		File
 * @version		SVN: $Id: DownloadHandler.php 19978 2010-08-21 15:44:29Z skenow $
 */
/**
 * Sends non HTML files through a http socket
 *
 * @category	ICMS
 * @package     File
 *
 * @author	    Kazumi Ono	<onokazu@xoops.org>
 */
abstract class icms_file_DownloadHandler {

	/**#@+
	 * file information
	 */
	protected $mimetype;
	protected $ext;
	protected $archiver;
	/**#@-*/

	/**
	 * Constructor
	 */
	public function __construct() {
		//EMPTY
	}

	/**
	 * Send the HTTP header
	 *
	 * @param	string  $filename
	 *
	 */
	protected function _header($filename) {
		if (function_exists('mb_http_output')) {
			mb_http_output('pass');
		}
		header('Content-Type: ' . $this->mimetype);
		if (preg_match("/MSIE ([0-9]\.[0-9]{1,2})/", $_SERVER['HTTP_USER_AGENT'])) {
			header('Content-Disposition: attachment; filename="' . $filename . '"');
			header('Expires: 0');
			header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
			header('Pragma: public');
		} else {
			header('Content-Disposition: attachment; filename="' . $filename . '"');
			header('Expires: 0');
			header('Pragma: no-cache');
		}
	}

	/**
	 *
	 *
	 * @param   string    $filepath
	 * @param   string    $newfilename
	 **/
	abstract function addFile($filepath, $newfilename = NULL);

	/**
	 * addBinaryFile()
	 *
	 * @param   string    $filepath
	 * @param   string    $newfilename
	 **/
	abstract function addBinaryFile($filepath, $newfilename = NULL);

	/**
	 * addFileData()
	 *
	 * @param   mixed     $data
	 * @param   string    $filename
	 * @param   integer   $time
	 **/
	abstract function addFileData(&$data, $filename, $time = 0);

	/**
	 * addBinaryFileData()
	 *
	 * @param   mixed   $data
	 * @param   string  $filename
	 * @param   integer $time
	 **/
	abstract function addBinaryFileData(&$data, $filename, $time = 0);

	/**
	 * download()
	 *
	 * @param   string  $name
	 * @param   boolean $gzip
	 **/
	abstract function download($name, $gzip = true);
}