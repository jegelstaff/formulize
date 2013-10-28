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
 * @version	$Id: downloader.php 19951 2010-08-13 04:58:51Z m0nty_ $
 */
/**
 * Sends non HTML files through a http socket
 *
 * @package     kernel
 * @subpackage  core
 *
 * @author	    Kazumi Ono	<onokazu@xoops.org>
 * @copyright	copyright (c) 2000-2003 XOOPS.org
 * @deprecated	Use icms_file_DownloadHandler instead
 * @todo Remove in version 1.4
 */
class XoopsDownloader extends icms_file_DownloadHandler {
	private $_deprecated;
	public function __construct() {
		parent::__construct();
		$this->_deprecated = icms_core_Debug::setDeprecated('icms_file_DownloadHandler', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
	}
}