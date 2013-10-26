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
 * @version	$Id: zipdownloader.php 19951 2010-08-13 04:58:51Z m0nty_ $
 * @deprecated	Use icms_file_DownloadHandler instead
 * @todo Remove in version 1.4
*/
class XoopsZipDownloader extends icms_file_DownloadHandler {
	private $_deprecated;
	public function __construct() {
		parent::__construct();
		$this->_deprecated = icms_core_Debug::setDeprecated('icms_file_DownloadHandler', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
	}
}