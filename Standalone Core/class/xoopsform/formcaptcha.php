<?php
/**
 * Adding CAPTCHA
 *
 * Currently there are two types of CAPTCHA forms, text and image
 * The default mode is "text", it can be changed in the priority:
 * 1 If mode is set through IcmsFormCaptcha::setMode(), take it
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
 * @version		$Id: formcaptcha.php 8685 2009-05-02 15:00:58Z pesianstranger $
*/

if (!defined('ICMS_ROOT_PATH')) {
	die("ImpressCMS root path not defined");
}

require_once ICMS_ROOT_PATH."/class/xoopsform/formelement.php";

/*
 * Usage
 *
 * For form creation:
 * 1 Add [include_once ICMS_ROOT_PATH."/class/captcha/formcaptcha.php";] to class/xoopsformloader.php, OR add to the file that uses CAPTCHA before calling IcmsFormCaptcha
 * 2 Add form element where proper: $xoopsform->addElement(new IcmsFormCaptcha($caption, $name, $skipmember, ...);
 *
 * For verification:
 *   if(@include_once ICMS_ROOT_PATH."/class/captcha/captcha.php") {
 *	    $icmsCaptcha = IcmsCaptcha::instance();
 *	    if(! $icmsCaptcha->verify() ) {
 *		    echo $icmsCaptcha->getMessage();
 *		    ...
 *	    }
 *  }
 *
 */

class IcmsFormCaptcha extends XoopsFormElement {

	var $_captchaHandler;

	/**
	 * @param string	$caption	Caption of the form element, default value is defined in captcha/language/
	 * @param string	$name		Name for the input box
	 * @param boolean	$skipmember	Skip CAPTCHA check for members
	 * @param int		$numchar	Number of characters in image mode, and input box size for text mode
	 * @param int		$minfontsize	Minimum font-size of characters in image mode
	 * @param int		$maxfontsize	Maximum font-size of characters in image mode
	 * @param int		$backgroundtype	Background type in image mode: 0 - bar; 1 - circle; 2 - line; 3 - rectangle; 4 - ellipse; 5 - polygon; 100 - generated from files
	 * @param int		$backgroundnum	Number of background images in image mode
	 *
	 */
	function IcmsFormCaptcha($caption = '', $name = 'icmscaptcha', $skipmember = null, $numchar = null, $minfontsize = null, $maxfontsize = null, $backgroundtype = null, $backgroundnum = null) {
		if(!class_exists("IcmsCaptcha")) {
			require_once ICMS_ROOT_PATH."/class/captcha/captcha.php";
		}

		$this->_captchaHandler =& IcmsCaptcha::instance();
		$this->_captchaHandler->init($name, $skipmember, $numchar, $minfontsize, $maxfontsize, $backgroundtype, $backgroundnum);
		if(!$this->_captchaHandler->active) {
			$this->setHidden();
		}else{
			$caption = !empty($caption) ? $caption : $this->_captchaHandler->getCaption();
			$this->setCaption($caption);
		}
	}


	/**
	 * Sets the Config
   * @param   string $name Config Name
   * @param   string $val Config Value
   * @return  object reference to the IcmsCaptcha Object (@link IcmsCaptcha)
	 */
	function setConfig($name, $val)
	{
		return $this->_captchaHandler->setConfig($name, $val);
	}

	function render()
	{
		if(!$this->isHidden()) {
			return $this->_captchaHandler->render();
		}
	}
}

class XoopsFormCaptcha extends IcmsFormCaptcha { /* For backwards compatibility */ }

?>