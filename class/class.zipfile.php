<?php
/**
 * Creates Zipfiles
 *
 * @copyright	http://www.xoops.org/ The XOOPS Project
 * @copyright	XOOPS_copyrights.txt
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license	LICENSE.txt
 * @package	core
 * @since	XOOPS
 * @author	http://www.xoops.org The XOOPS Project
 * @version	$Id: class.zipfile.php 20021 2010-08-25 14:35:25Z malanciault $
 * @deprecated	Use icms_file_ZipFileHandler instead
 * @todo Remove in version 1.4
*/
class zipfile extends icms_file_ZipFileHandler {
	private $_deprecated;
	public function __construct() {
		$this->_deprecated = icms_core_Debug::setDeprecated('icms_file_ZipFileHandler', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
	}
}