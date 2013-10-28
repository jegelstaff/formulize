<?php
/**
 * Image Creation script
 *
 * @copyright	http://www.xoops.org/ The XOOPS Project
 * @copyright	XOOPS_copyrights.txt
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @package		FormCaptcha
 * @since		XOOPS
 * @author		http://www.xoops.org/ The XOOPS Project
 * @author		Taiwen Jiang (phppp or D.J.) <php_pp@hotmail.com>
 * @author	   Sina Asghari (aka stranger) <pesian_stranger@users.sourceforge.net>
 */

include "../../../mainfile.php";
error_reporting(0);
icms::$logger->activated = false;

if(empty($_SERVER['HTTP_REFERER']) || !preg_match("/^".preg_quote(ICMS_URL, '/')."/", $_SERVER['HTTP_REFERER'])) {
	exit();
}

/**
 * Captcha Image Handler
 *
 * @package     kernel
 * @subpackage  captcha
 * @author
 * @copyright
 */
class IcmsCaptchaImageHandler extends icms_form_elements_captcha_ImageHandler{
	private $_deprecated;
	public function __construct() {
		parent::__construct();
		$this->_deprecated = icms_core_Debug::setDeprecated('icms_form_elements_captcha_ImageHandler', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
	}
}
$image_handler = new IcmsCaptchaImageHandler();
$image_handler->loadImage();

?>