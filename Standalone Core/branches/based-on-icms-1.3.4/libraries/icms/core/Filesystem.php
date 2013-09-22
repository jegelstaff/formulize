<?php
/**
 * A static class for file system functions
 *
 * Using a static class instead of a include file with global functions, along with
 * autoloading of classes, reduces the memory usage and only includes files when needed.
 *
 * @category	ICMS
 * @package     Core
 * @subpackage	Filesystem
 * @author		Steve Kenow <skenow@impresscms.org>
 * @copyright	(c) 2007-2008 The ImpressCMS Project - www.impresscms.org
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @version		SVN: $Id: Filesystem.php 11944 2012-08-22 17:33:11Z skenow $
 * @since		1.3
 */

/**
 * Perform filesystem actions
 */
class icms_core_Filesystem {

	/* Since all the methods are static, there is no __construct necessary	 */

	/**
	 *
	 * Change the permission of a file or folder
	 * Replaces icms_chmod()
	 *
	 * @author	Newbb2	developement team
	 * @param	string	$target	target file or folder
	 * @param	int		$mode	permission
	 * @return	bool	Returns true on success, false on failure
	 */
	static public function chmod($target, $mode = 0777) {
		return @chmod($target, $mode);
	}

	/**
	 *
	 * Safely create a folder and any folders in between
	 * Replaces icms_mkdir()
	 *
	 * @param string	$target		path to the folder to be created
	 * @param integer	$mode		permissions to set on the folder. This is affected by umask in effect
	 * @param string	$base		root location for the folder, ICMS_ROOT_PATH or ICMS_TRUST_PATH, for example
	 * @param array		$metachars	Characters to exclude from a valid path name
	 * @return boolean True if folder is created, False if it is not
	 */
	static public function mkdir($target, $mode = 0777, $base = ICMS_ROOT_PATH, $metachars = array()) {

		if (is_dir($target)) return TRUE;
		if (!isset($metachars)) {
			$metachars = array('[', '?', '"', '.', '<', '>', '|', ' ', ':');
		}

		$base = preg_replace ('/[\\|\/]/', DIRECTORY_SEPARATOR, $base);
		$target = preg_replace ('/[\\|\/]/', DIRECTORY_SEPARATOR, $target);
		if ($base !== '') {
			$target = str_ireplace($base . DIRECTORY_SEPARATOR, '', $target);
			$target = $base . DIRECTORY_SEPARATOR . str_replace($metachars , '_', $target);
		} else {
			$target = str_replace($metachars , '_', $target);
		}
		if (mkdir($target, $mode, TRUE)) {
			// create an index.html file in this directory
			if ($fh = @fopen($target . '/index.html', 'w')) {
				fwrite($fh, '<script>history.go(-1);</script>');
				@fclose($fh);
			}

			if (substr(decoct(fileperms($target)), 2) != $mode) {
				chmod($target, $mode);
			}
		}
		return is_dir($target);
	}

	/**
	 *
	 * Removes the content of a folder.
	 * Replaces icms_clean_folders()
	 * @todo	Rewrite with SPL Directory Iterators
	 *
	 * @author	Steve Kenow (aka skenow) <skenow@impresscms.org>
	 * @author	modified by Vaughan <vaughan@impresscms.org>
	 * @author	modified by Sina Asghari (aka stranger) <pesian_stranger@users.sourceforge.net>
	 * @param	string	$dir	The folder path to cleaned. Must be an array like: array('templates_c' => ICMS_COMPILE_PATH . "/");
	 * @param	bool  $remove_admin_cache	  True to remove admin cache, if required.
	 */
	static public function cleanFolders($dir, $remove_admin_cache = FALSE) {
		global $icmsConfig;
		foreach ($dir as $d) {
			$dd = opendir($d);
			while ($file = readdir($dd)) {
				$files_array = $remove_admin_cache
						? ($file != 'index.html' && $file != 'php.ini' && $file != '.htaccess'
							&& $file != '.svn')
						: ($file != 'index.html' && $file != 'php.ini' && $file != '.htaccess'
							&& $file != '.svn' && $file != 'adminmenu_' . $icmsConfig['language'] . '.php');
				if (is_file($d . $file) && $files_array) {
					unlink($d . $file);
				}
			}
			closedir($dd);
		}
		return true;
	}

	/**
	 *
	 * Clean up all writable folders
	 * Replaces icms_cleaning_write_folders()
	 *
	 */
	static public function cleanWriteFolders() {
		return self::cleanFolders(
			array(
				'templates_c' => ICMS_COMPILE_PATH . '/',
				'cache' => ICMS_CACHE_PATH . '/',
			)
		);
	}

	/**
	 *
	 * Copy a file, or a folder and its contents
	 * Replaces icms_copyr()
	 * @todo	Can be rewritten with SPL Directory Iterators
	 *
	 * @author	Aidan Lister <aidan@php.net>
	 * @param	string	$source	The source
	 * @param	string	$dest	The destination
	 * @return	boolean	Returns true on success, false on failure
	 */
	static public function copyRecursive($source, $dest) {
		// Simple copy for a file
		if (is_file($source)) {return copy($source, $dest);}

		// Make destination directory
		if (!is_dir($dest)) {
			self::mkdir($dest, 0777, '');
		}

		// Loop through the folder
		$dir = dir($source);
		while (false !== $entry = $dir->read()) {
			// Skip pointers
			if ($entry == '.' || $entry == '..') {continue;}
			// Deep copy directories
			if (is_dir("$source/$entry") && ($dest !== "$source/$entry")) {
				self::copyRecursive("$source/$entry", "$dest/$entry");
			} else {
				copy("$source/$entry", "$dest/$entry");
			}
		}
		// Clean up
		$dir->close();
		return true;
	}

	/**
	 *
	 * Deletes a file
	 * Replaces icms_deleteFile()
	 *
	 * @param string $dirname path of the file
	 * @return	The unlinked dirname
	 */
	static public function deleteFile($dirname) {
		// Simple delete for a file
		if (is_file($dirname)) {
			return unlink($dirname);
		}
	}

	/**
	 *
	 * Copy a file, or a folder and its contents from a website to your host
	 * Replaces icms_stream_copy()
	 *
	 * @author	Sina Asghari <stranger@impresscms.org>
	 * @author	nensa at zeec dot biz
	 * @param	string	$src	The source
	 * @param	string 	$dest	  The destination
	 * @return 	boolean	Returns stream_copy_to_stream($src, $dest) on success, false on failure
	 */
	static public function copyStream($src, $dest) {
		$len = false;
		if (@ini_get('allow_url_fopen')) {
			$fsrc = fopen($src, 'r');
			$fdest = fopen($dest, 'w+');
			$len = stream_copy_to_stream($fsrc, $fdest);
			fclose($fsrc);
			fclose($fdest);
		}
		return $len;
	}

	/**
	 *
	 * Recursively delete a directory
	 * Replaces icms_unlinkRecursive()
	 * @todo	Can be rewritten with SPL Directory Iterators
	 *
	 * @param string $dir Directory name
	 * @param bool $deleteRootToo Delete specified top-level directory as well
	 */
	static public function deleteRecursive($dir, $deleteRootToo=true) {
		if (!$dh = @opendir($dir)) {
			return;
		}
		while (false !== ($obj = readdir($dh))) {
			if ($obj == '.' || $obj == '..') {
				continue;
			}

			if (!@unlink($dir . '/' . $obj)) {
				self::deleteRecursive($dir . '/' . $obj, true);
			}
		}

		closedir($dh);

		if ($deleteRootToo) {
			@rmdir($dir);
		}

		return;
	}

	/**
	 * Writes index file
	 * Replaces xoops_write_index_file() from cp_functions.php
	 *
	 * @param string  $path  path to the file to write
	 * @return bool
	 * @todo use language constants for error messages
	 */
	static public function writeIndexFile($path = '') {
		if (empty($path)) {
			return false;
		}
		$path = substr($path, -1) == "/" ? substr($path, 0, -1) : $path;
		$filename = $path . '/index.html';
		if (file_exists($filename)) {
			return true;
		}
		if (! $file = fopen($filename, "w")) {
			echo 'failed open file';
			return false;
		}
		if (fwrite($file, "<script>history.go(-1);</script>") == FALSE) {
			echo 'failed write file';
			return false;
		}
		fclose($file);
		return true;
	}

	/**
	 * Create a checksum file for your installation directory
	 * @author	Steve Kenow <skenow@impresscms.org>
	 *
	 */
	static public function generateChecksum() {
		$rootdir = preg_replace('#[\|/]#', DIRECTORY_SEPARATOR, ICMS_ROOT_PATH);
		$dir = new RecursiveDirectoryIterator($rootdir);
		$checkfile = preg_replace('#[\|/]#', DIRECTORY_SEPARATOR, ICMS_TRUST_PATH) . DIRECTORY_SEPARATOR . 'checkfile.sha1';

		$file = new SplFileObject($checkfile, 'w');
		$cache_dir = preg_replace('#[\|/]#', DIRECTORY_SEPARATOR, ICMS_CACHE_PATH);
		$templates_dir = preg_replace('#[\|/]#', DIRECTORY_SEPARATOR, ICMS_COMPILE_PATH);
		$newline = '';
		foreach (new RecursiveIteratorIterator($dir) as $name=>$item) {
			$itemPath = $item->getPath();
			$itemFilename = $item->getBasename();
			$itemPerms = $item->getPerms();
			/* exclude cache and templates_c directories */
			if ($itemPath != $cache_dir && $itemPath != $templates_dir) {
				$fileHash = sha1_file($name);
				echo _CORE_CHECKSUM_ADDING . ': ' . $name . _CORE_CHECKSUM_CHECKSUM . ' : <em>'. $fileHash .'</em>, ' ._CORE_CHECKSUM_PERMISSIONS .' : '. $itemPerms . '<br />';
				$file->fwrite($newline . $name . ';' .$fileHash . ';' . $itemPerms);
			}
			$newline = "\n";
		}
		unset($file);
		unset($item);
		unset($dir);

	}

	/**
	 * Validate the current installation directory against an existing checksum file
	 * This reports any changes to your installation directory - added, removed or changed files
	 *
	 * @author	Steve Kenow <skenow@impresscms.org>
	 *
	 */
	static public function validateChecksum() {
		$validationFile = new SplFileObject($checkfile);
		if ($validationFile->isReadable()) {
			$currentHash = $currentPerms = array();
			$cache_dir = preg_replace('#[\|/]#', DIRECTORY_SEPARATOR, ICMS_CACHE_PATH);
			$templates_dir = preg_replace('#[\|/]#', DIRECTORY_SEPARATOR, ICMS_COMPILE_PATH);
			foreach (new RecursiveIteratorIterator($dir) as $name=>$item) {
				$itemPath = $item->getPath();
				$itemFilename = $item->getBasename();
				$itemPerms = $item->getPerms();
				/* exclude cache and templates_c directories */
				if ($itemPath != $cache_dir && $itemPath != $templates_dir) {
					$fileHash = sha1_file($name);
					$currentHash[$name] = $fileHash;
					$currentPerms[$name] = $itemPerms;
				}
			}
			echo _CORE_CHECKSUM_CHECKFILE . $checkfile . '<br />';
			$validHash = $validPerms = array();
			while (! $validationFile->eof()) {
				list($filename, $checksum, $filePermissions) = $validationFile->fgetcsv(';');
				$validHash[$filename] = $checksum;
				$validPerms[$filename] = $filePermissions;
			}
			$hashVariations = array_diff_assoc($validHash, $currentHash); // changed or removed files
			$addedFiles = array_diff_key($currentHash, $validHash);
			$missingFiles = array_diff_key($validHash, $currentHash);
			$permVariations = array_diff_assoc($validPerms, $currentPerms); // changed permissions or removed files
			echo '<br /><strong>'. count($hashVariations) .  _CORE_CHECKSUM_ALTERED_REMOVED . '</strong><br />';
			foreach ($hashVariations as $file=>$check) {
				echo $file . '<br />';
			}
			echo '<br /><strong>' . count($addedFiles) . _CORE_CHECKSUM_FILES_ADDED . '</strong><br />';
			foreach ($addedFiles as $file=>$hash) {
				echo $file . '<br />';
			}
			echo '<br /><strong>' . count($missingFiles) . _CORE_CHECKSUM_FILES_REMOVED . '</strong><br />';
			foreach ($missingFiles as $file=>$hash) {
				echo $file . '<br />';
			}
			echo '<br /><strong>' . count($permVariations) . _CORE_CHECKSUM_PERMISSIONS_ALTERED . '</strong><br />';
			foreach ($permVariations as $file=>$perms) {
				echo $file . '<br />';
			}
		} else {
			echo _CORE_CHECKSUM_CHECKFILE_UNREADABLE;
		}
		unset($validationFile);
		unset($item);
		unset($dir);

	}

	/**
	 * Gets a list of all directories within a path
	 *
	 * @param	string $dirname A path to a directory
	 * @param	array	$ignore	A list of folders to ignore
	 * @param	boolean	$hideDot	Hide folders starting with a dot?
	 * @return	array An array of directory names
	 */
	static public function getDirList($dirname, array $ignore = array('cvs', '_darcs', '.svn'), $hideDot = TRUE) {
		$dirList = array();
		$iterator = new DirectoryIterator($dirname);
		foreach ($iterator as $file) {
			if ($file->isDir() && !$file->isDot()) {
				$filename = $file->getFilename();
				if (!$hideDot || substr($filename, 0, 1) != '.') {
					$dirList[$filename] = $filename;
				}
			}
		}
		asort($dirList);
		return array_diff($dirList, $ignore);
	}

	/**
	 * Get a list of files in a directory
	 *
	 * This can be used for several different situations -
	 * 		To retrieve an array of images, use getFileList($dirname, $prefix, array('gif', 'jpg', 'png'))
	 * 		To retrieve an array of fonts, use getFileList($dirname, $prefix, array('ttf'))
	 * 		To retrieve an array of HTML files, use getFileList($dirname, $prefix, array('html', 'htm', 'xhtml'))
	 *
	 *
	 * @param	string	$dirname	A path to a directory
	 * @param	string	$prefix		A prefix to add to the beginning of the file names
	 * @param	array	$extension	Filter the list by these extensions
	 * @param	bool	$hideDot	Hide files starting with a dot?
	 * @return	array	$fileList	A list of files in a directory
	 */
	static public function getFileList($dirname, $prefix = '', array $extension = array(), $hideDot = FALSE) {
		if (!is_dir($dirname)) return array();
		$fileList = array();
		if (empty($extension)) {
			$extList = '';
		} else {
			$extList = implode('|\.', $extension);
		}
		$iterator = new DirectoryIterator($dirname);
		foreach ($iterator as $file) {
			if ($file->isFile() && !$file->isDot()) {
				$filename = $file->getFilename();
				if (!$hideDot || substr($filename, 0, 1) != '.') {
					if ($extList == '') {
						$file = $prefix . $filename;
						$fileList[$file] = $file;
					} elseif (preg_match("/(\." . $extList . ")$/i", $filename)) {
							$file = $prefix . $filename;
							$fileList[$file] = $file;
					}
				}
			}
		}
		asort($fileList);
		return $fileList;
	}

	static public function writeFile($contents, $filename, $extension = '', $location = ICMS_TRUST_PATH) {
		if ($extension == '') $extension = 'php';
		if (DIRECTORY_SEPARATOR !== "/") $location = str_replace(DIRECTORY_SEPARATOR, "/", $location);
		$file = $location . '/' . $filename . '.' . $extension;
		if ($fp = fopen($file, "wt")) {
			if (fwrite($fp, $contents) == FALSE) {
				echo 'failed write file';
				return FALSE;
			}
			fclose($fp);
		}
	}

/* These will not be in the final release, but are only placeholders while the refactoring
 * is being completed
 */
	static public function getImgList($dirname, $prefix = '', $extension = array('gif', 'jpg', 'png')) {
		return self::getFileList($dirname, $prefix, $extension);
	}

	static public function getFontList($dirname, $prefix = '', $extension = array('ttf')) {
		return self::getFileList($dirname, $prefix, $extension);
	}

	static public function getPhpFiles($dirname, $prefix = '', $extension = array('php')) {
		return self::getFileList($dirname, $prefix, $extension);
	}

	static public function getHtmlFiles($dirname, $prefix = '', $extension = array('htm', 'html', 'xhtml')) {
		return self::getFileList($dirname, $prefix, $extension);
	}
/* The above will be removed */

}