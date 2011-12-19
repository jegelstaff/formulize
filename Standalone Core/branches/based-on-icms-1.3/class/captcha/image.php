<?php
/**
 * Image Creation class for CAPTCHA
 *
 * @copyright	http://www.xoops.org/ The XOOPS Project
 * @copyright	XOOPS_copyrights.txt
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @package		FormCaptcha
 * @since		XOOPS
 * @author		http://www.xoops.org/ The XOOPS Project
 * @author		Taiwen Jiang (phppp or D.J.) <php_pp@hotmail.com>
 * @author		Sina Asghari (aka stranger) <pesian_stranger@users.sourceforge.net>
 * @version		$Id: image.php 9864 2010-02-26 17:44:26Z skenow $
 */

class IcmsCaptchaImage extends icms_form_elements_captcha_Image{
	private $_deprecated;
	public function __construct() {
		parent::__construct();
		$this->_deprecated = icms_core_Debug::setDeprecated('icms_form_elements_captcha_Image', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
	}
}
?>