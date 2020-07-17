<?php
/**
 * The Tar files downloader class
 *
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license		LICENSE.txt
 * @category	ICMS
 * @package		File
 *
 * @version		SVN: $Id: TarDownloader.php 20322 2010-11-04 03:57:45Z skenow $
 */

defined('ICMS_ROOT_PATH') or exit();

/**
 * Send tar files through a http socket
 *
 * @category	ICMS
 * @package		File
 *
 * @author		Kazumi Ono 	<onokazu@xoops.org>
 */
class icms_file_TarDownloader extends icms_file_DownloadHandler {

	/**
	 * Constructor
	 *
	 * @param string $ext       file extension
	 * @param string $mimyType  Mimetype
	 **/
	public function __construct($ext = '.tar.gz', $mimyType = 'application/x-gzip') {
		$this->archiver = new icms_file_TarFileHandler();
		$this->ext = trim($ext);
		$this->mimeType = trim($mimyType);
	}

	/**
	 * Add a file to the archive
	 *
	 * @param   string  $filepath       Full path to the file
	 * @param   string  $newfilename    Filename (if you don't want to use the original)
	 **/
	public function addFile($filepath, $newfilename = null) {
		$this->archiver->addFile($filepath);
		if (isset($newfilename)) {
			// dirty, but no other way
			for ($i = 0; $i < $this->archiver->numFiles; $i++) {
				if ($this->archiver->files[$i]['name'] == $filepath) {
					$this->archiver->files[$i]['name'] = trim($newfilename);
					break;
				}
			}
		}
	}

	/**
	 * Add a binary file to the archive
	 *
	 * @param   string  $filepath       Full path to the file
	 * @param   string  $newfilename    Filename (if you don't want to use the original)
	 **/
	public function addBinaryFile($filepath, $newfilename = null) {
		$this->archiver->addFile($filepath, true);
		if (isset($newfilename)) {
			// dirty, but no other way
			for ($i = 0; $i < $this->archiver->numFiles; $i++) {
				if ($this->archiver->files[$i]['name'] == $filepath) {
					$this->archiver->files[$i]['name'] = trim($newfilename);
					break;
				}
			}
		}
	}

	/**
	 * Add a dummy file to the archive
	 *
	 * @param   string  $data       Data to write
	 * @param   string  $filename   Name for the file in the archive
	 * @param   integer $time
	 **/
	public function addFileData(&$data, $filename, $time=0) {
		$dummyfile = ICMS_CACHE_PATH . '/dummy_' . time() . '.html';
		$fp = fopen($dummyfile, 'w');
		fwrite($fp, $data);
		fclose($fp);
		$this->archiver->addFile($dummyfile);
		unlink($dummyfile);

		// dirty, but no other way
		for ($i = 0; $i < $this->archiver->numFiles; $i++) {
			if ($this->archiver->files[$i]['name'] == $dummyfile) {
				$this->archiver->files[$i]['name'] = $filename;
				if ($time != 0) {
					$this->archiver->files[$i]['time'] = $time;
				}
				break;
			}
		}
	}

	/**
	 * Add a binary dummy file to the archive
	 *
	 * @param   string  $data   Data to write
	 * @param   string  $filename   Name for the file in the archive
	 * @param   integer $time
	 **/
	public function addBinaryFileData(&$data, $filename, $time=0) {
		$dummyfile = ICMS_CACHE_PATH . '/dummy_' . time() . '.html';
		$fp = fopen($dummyfile, 'wb');
		fwrite($fp, $data);
		fclose($fp);
		$this->archiver->addFile($dummyfile, true);
		unlink($dummyfile);

		// dirty, but no other way
		for ($i = 0; $i < $this->archiver->numFiles; $i++) {
			if ($this->archiver->files[$i]['name'] == $dummyfile) {
				$this->archiver->files[$i]['name'] = $filename;
				if ($time != 0) {
					$this->archiver->files[$i]['time'] = $time;
				}
				break;
			}
		}
	}

	/**
	 * Send the file to the client
	 *
	 * @param   string  $name   Filename
	 * @param   boolean $gzip   Use GZ compression
	 **/
	public function download($name, $gzip = true) {
		$this->_header($name . $this->ext);
		echo $this->archiver->toTarOutput($name . $this->ext, $gzip);
	}
}
