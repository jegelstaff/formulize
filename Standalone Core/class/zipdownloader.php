<?php
/**
* Handles all functions related to downloading zipfiles within ImpressCMS
*
* @copyright	http://www.xoops.org/ The XOOPS Project
* @copyright	XOOPS_copyrights.txt
* @copyright	http://www.impresscms.org/ The ImpressCMS Project
* @license	LICENSE.txt
* @package	core
* @since	XOOPS
* @author	http://www.xoops.org The XOOPS Project
* @author	modified by UnderDog <underdog@impresscms.org>
* @version	$Id: zipdownloader.php 8662 2009-05-01 09:04:30Z pesianstranger $
*/

if (!defined('ICMS_ROOT_PATH')) {
	exit();
}
include_once ICMS_ROOT_PATH.'/class/downloader.php';
include_once ICMS_ROOT_PATH.'/class/class.zipfile.php';

class XoopsZipDownloader extends XoopsDownloader
{

	/**
	 * Constructor
	 *
	 * @param	string     $ext             extension of the file
	 * @param	string    $mimyType    the mimytype (mimetype) of the file
	 */
	function XoopsZipDownloader($ext = '.zip', $mimyType = 'application/x-zip')
	{
		$this->archiver = new zipfile();
		$this->ext      = trim($ext);
		$this->mimeType = trim($mimyType);
	}


	/**
	 * Adds file to the zip file
	 *
	 * @param	string    $filepath      path of the file to add
	 * @param	string    $newfilename    name of the newly created file
	 */
	function addFile($filepath, $newfilename=null)
	{
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
	function addBinaryFile($filepath, $newfilename=null)
	{
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
	function addFileData(&$data, $filename, $time=0)
	{
		$this->archiver->addFile($data, $filename, $time);
	}


	/**
	 * Adds binary file data to the zip file
	 *
	 * @param	string    &$data        data array
	 * @param	string    $filename     filename to add the data to
	 * @param	string    $time         timestamp
	 */
	function addBinaryFileData(&$data, $filename, $time=0)
	{
		$this->addFileData($data, $filename, $time);
	}


	/**
	 * downloads the file
	 *
	 * @param   string  $name     filename to download
	 * @param   bool    $gzip     turn on gzip compression
	 */
	function download($name, $gzip = true)
	{
		$this->_header($name.$this->ext);
		echo $this->archiver->file();
	}
}
?>