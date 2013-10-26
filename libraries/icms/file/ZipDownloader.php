<?php
/**
 * Handles all functions related to downloading zipfiles within ImpressCMS
 *
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license		LICENSE.txt
 * @category	ICMS
 * @package		File
 * @version		SVN: $Id: ZipDownloader.php 19978 2010-08-21 15:44:29Z skenow $
 */
defined('ICMS_ROOT_PATH') or exit();
/**
 * Handles compression of files in zip format and sending to the browser for download
 *
 * @category	ICMS
 * @package		File
 */
class icms_file_ZipDownloader extends icms_file_DownloadHandler {
	/**
	 * Constructor
	 *
	 * @param	string     $ext             extension of the file
	 * @param	string    $mimyType    the mimytype (mimetype) of the file
	 */
	public function __construct($ext = '.zip', $mimyType = 'application/x-zip') {
		$this->archiver = new icms_file_ZipFileHandler();
		$this->ext      = trim($ext);
		$this->mimeType = trim($mimyType);
	}

	/**
	 * Adds file to the zip file
	 *
	 * @param	string    $filepath      path of the file to add
	 * @param	string    $newfilename    name of the newly created file
	 */
	public function addFile($filepath, $newfilename=null) {
		// Read in the file's contents
		$fp = fopen($filepath, "r");
		$data = fread($fp, filesize($filepath));
		fclose($fp);
		$filename = (isset($newfilename) && trim($newfilename) != '') ? trim($newfilename) : $filepath;
		$this->archiver->addFile($data, $filename, filemtime($filename));
	}

	/**
	 * Adds binary file to the zip file
	 *
	 * @param	string    $filepath      path of the file to add
	 * @param	string    $newfilename    name of the newly created file
	 */
	public function addBinaryFile($filepath, $newfilename=null) {
		// Read in the file's contents
		$fp = fopen($filepath, "rb");
		$data = fread($fp, filesize($filepath));
		fclose($fp);
		$filename = (isset($newfilename) && trim($newfilename) != '') ? trim($newfilename) : $filepath;
		$this->archiver->addFile($data, $filename, filemtime($filename));
	}

	/**
	 * Adds file data to the zip file
	 *
	 * @param	string    &$data        data array
	 * @param	string    $filename     filename to add the data to
	 * @param	string    $time         timestamp
	 */
	public function addFileData(&$data, $filename, $time=0) {
		$this->archiver->addFile($data, $filename, $time);
	}

	/**
	 * Adds binary file data to the zip file
	 *
	 * @param	string    &$data        data array
	 * @param	string    $filename     filename to add the data to
	 * @param	string    $time         timestamp
	 */
	public function addBinaryFileData(&$data, $filename, $time=0) {
		self::addFileData($data, $filename, $time);
	}

	/**
	 * downloads the file
	 *
	 * @param   string  $name     filename to download
	 * @param   bool    $gzip     turn on gzip compression
	 */
	public function download($name, $gzip = true) {
		parent::_header($name . $this->ext);
		echo $this->archiver->file();
	}
}
