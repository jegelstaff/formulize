<?php
/**
 * CAPTCHA class For XOOPS
 *
 * Currently there are two types of CAPTCHA forms, text and image
 * The default mode is "text", it can be changed in the priority:
 * 1 If mode is set through icms_form_elements_Captcha::setConfig("mode", $mode), take it
 * 2 Elseif mode is set though captcha/config.php, take it
 * 3 Else, take "text"
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
 * @version		$Id: captcha.php 10679 2010-09-25 17:16:09Z skenow $
 */

class IcmsCaptcha extends icms_form_elements_captcha_Object {
	private $_deprecated;
	public function __construct() {
		parent::__construct();
		$this->_deprecated = icms_core_Debug::setDeprecated('icms_form_elements_captcha_Object', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
	}
}
class XoopsCaptcha extends IcmsCaptcha { /* For backwards compatibility */ }
?>