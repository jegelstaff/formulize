<?php
/**
 * The uploader class of media files
 * @copyright    http://www.xoops.org/ The XOOPS Project
 * @copyright    XOOPS_copyrights.txt
 * @copyright    http://www.impresscms.org/ The ImpressCMS Project
 * @license      LICENSE.txt
 * @package      core
 * @since        XOOPS
 * @author       http://www.xoops.org The XOOPS Project
 * @version      $Id: uploader.php 20022 2010-08-25 14:38:08Z malanciault $
 * @deprecated	Use icms_file_MediaUploadHandler instead
 * @todo Remove in version 1.4
*/
class IcmsMediaUploader extends icms_file_MediaUploadHandler {
	private $_deprecated;
	public function __construct($uploadDir, $allowedMimeTypes, $maxFileSize = 0, $maxWidth = null, $maxHeight = null) {
		parent::__construct($uploadDir, $allowedMimeTypes, $maxFileSize, $maxWidth, $maxHeight);
		$this->_deprecated = icms_core_Debug::setDeprecated('icms_file_MediaUploadHandler', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
	}
}

/**
 * XoopsMediaUploader
 * @copyright    The XOOPS Project <http://www.xoops.org/>
 * @copyright    XOOPS_copyrights.txt
 * @license      LICENSE.txt
 * @since        XOOPS
 * @author       The XOOPS Project Community <http://www.xoops.org>
 * @deprecated
 */
class XoopsMediaUploader extends icms_file_MediaUploadHandler {

	private $_deprecated;
	/**
	 * @deprecated	Use icms_file_MediaUploadHandler, instead
	 * @todo		Remove in version 1.4
	 */
	function XoopsMediaUploader($uploadDir, $allowedMimeTypes, $maxFileSize = 0, $maxWidth = null, $maxHeight = null) {
		parent::__construct($uploadDir, $allowedMimeTypes, $maxFileSize, $maxWidth, $maxHeight);
		$this->_deprecated = icms_core_Debug::setDeprecated('icms_file_MediaUploadHandler', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
	}

}