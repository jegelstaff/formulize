<?php
/**
 * Adding CAPTCHA
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
 * @version		$Id: formcaptcha.php 8685 2009-05-02 15:00:58Z pesianstranger $
 */

defined('ICMS_ROOT_PATH') or die("ImpressCMS root path not defined");

class IcmsFormCaptcha extends icms_form_elements_Captcha {
	private $_deprecated;
	public function __construct($caption = '', $name = 'icmscaptcha', $skipmember = null,
			$numchar = null, $minfontsize = null, $maxfontsize = null, $backgroundtype = null,
			$backgroundnum = null) {
		parent::__construct($caption, $name, $skipmember, $numchar, $minfontsize, $maxfontsize, $backgroundtype, $backgroundnum);
		$this->_deprecated = icms_core_Debug::setDeprecated('icms_form_elements_Captcha', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
	}
}

class XoopsFormCaptcha extends IcmsFormCaptcha { /* For backwards compatibility */ }