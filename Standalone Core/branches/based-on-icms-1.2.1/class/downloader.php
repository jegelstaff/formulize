<?php
/**
* XoopsDownloader Base Class
*
* Base class for the zipfile and tarfile downloads
*
* @copyright	http://www.xoops.org/ The XOOPS Project
* @copyright	XOOPS_copyrights.txt
* @copyright	http://www.impresscms.org/ The ImpressCMS Project
* @license	LICENSE.txt
* @package	core
* @since	XOOPS
* @author	http://www.xoops.org The XOOPS Project
* @author	modified by UnderDog <underdog@impresscms.org>
* @version	$Id: downloader.php 8656 2009-05-01 01:01:39Z skenow $
*/
/**
 * Sends non HTML files through a http socket
 * 
 * @package     kernel
 * @subpackage  core
 * 
 * @author	    Kazumi Ono	<onokazu@xoops.org>
 * @copyright	copyright (c) 2000-2003 XOOPS.org
 */
class XoopsDownloader
{

	/**#@+
	 * file information
	 */
	var $mimetype;
	var $ext;
	var $archiver;
    /**#@-*/

	/**
	 * Constructor
	 */
	function XoopsDownloader()
	{
		//EMPTY
	}

	/**
	 * Send the HTTP header
   * 
   * @param	string  $filename
   * 
   * @access	private
	 */
	function _header($filename)
	{
		if (function_exists('mb_http_output')) {
			mb_http_output('pass');
		}
		header('Content-Type: '.$this->mimetype);
		if (preg_match("/MSIE ([0-9]\.[0-9]{1,2})/", $_SERVER['HTTP_USER_AGENT'])) {
			header('Content-Disposition: attachment; filename="'.$filename.'"');
			header('Expires: 0');
			header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
			header('Pragma: public');
		} else {
			header('Content-Disposition: attachment; filename="'.$filename.'"');
			header('Expires: 0');
			header('Pragma: no-cache');
		}
	}

	/**
	 * XoopsDownloader::addFile()
	 * 
	 * @param   string    $filepath
	 * @param   string    $newfilename
	 **/
	function addFile($filepath, $newfilename=null)
	{
		//EMPTY
	}

	/**
	 * XoopsDownloader::addBinaryFile()
	 * 
	 * @param   string    $filepath
	 * @param   string    $newfilename
	 **/
	function addBinaryFile($filepath, $newfilename=null)
	{
		//EMPTY
	}

	/**
	 * XoopsDownloader::addFileData()
	 * 
	 * @param   mixed     $data
	 * @param   string    $filename
	 * @param   integer   $time
	 **/
	function addFileData(&$data, $filename, $time=0)
	{
		//EMPTY
	}

	/**
	 * XoopsDownloader::addBinaryFileData()
	 * 
	 * @param   mixed   $data
	 * @param   string  $filename
	 * @param   integer $time
	 **/
	function addBinaryFileData(&$data, $filename, $time=0)
	{
		//EMPTY
	}

	/**
	 * XoopsDownloader::download()
	 * 
	 * @param   string  $name
	 * @param   boolean $gzip
	 **/
	function download($name, $gzip = true)
	{
		//EMPTY
	}
}
?>