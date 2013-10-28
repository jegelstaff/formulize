<?php
/**
 * The Tar files downloader class
 *
 * @copyright	http://www.xoops.org/ The XOOPS Project
 * @copyright	XOOPS_copyrights.txt
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license	LICENSE.txt
 * @package	core
 * @since	XOOPS
 * @author	http://www.xoops.org The XOOPS Project
 * @author	modified by UnderDog <underdog@impresscms.org>
 * @version	$Id: tardownloader.php 20314 2010-11-03 17:26:56Z skenow $
 */

if (!defined('ICMS_ROOT_PATH')) {
	exit();
}

/**
 * Send tar files through a http socket
 *
 * @package		kernel
 * @subpackage	core
 *
 * @author		Kazumi Ono 	<onokazu@xoops.org>
 * @deprecated	Use icms_file_TarDownloader, instead
 * @todo		Remove in 1.4
 */
class XoopsTarDownloader extends icms_file_TarDownloader {

	private $_deprecated;

	/**
	 * Constructor
	 *
	 * @param string $ext       file extension
	 * @param string $mimyType  Mimetype
	 **/
	public function __construct($ext = '.tar.gz', $mimyType = 'application/x-gzip') {
		parent::__construct($ext, $mimyType);
		$this->_deprecated = icms_core_Debug::setDeprecated('icms_file_TarDownloader', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
	}

}
