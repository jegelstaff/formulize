<?php
/**
 * Adding CAPTCHA
 *
 * Currently there are two types of CAPTCHA forms, text and image
 * The default mode is "text", it can be changed in the priority:
 * 1 If mode is set through icms_form_elements_Captcha::setMode(), take it
 * 2 Elseif mode is set though captcha/config.php, take it
 * 3 Else, take "text"
 *
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @category	ICMS
 * @package		Form
 * @subpackage	Elements
 * @author		Sina Asghari (aka stranger) <pesian_stranger@users.sourceforge.net>
 * @version		SVN: $Id: Captcha.php 20509 2010-12-11 12:02:57Z phoenyx $
 */

defined('ICMS_ROOT_PATH') or die("ImpressCMS root path not defined");

/*
 * Usage
 *
 * For form creation:
 * Add form element where proper: $form->addElement(new icms_form_elements_Captcha($caption, $name, $skipmember, ...);
 *
 * For verification:
 * $icmsCaptcha = icms_form_elements_captcha_Object::instance();
 * if (!$icmsCaptcha->verify()) {
 *   echo $icmsCaptcha->getMessage();
 *   ...
 * }
 */
/**
 * CAPTCHA form element
 *
 * @category	ICMS
 * @package		Form
 * @subpackage	Elements
 *
 */
class icms_form_elements_Captcha extends icms_form_Element {
	private $_captchaHandler;

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
	public function __construct($caption = '', $name = 'icmscaptcha', $skipmember = null,
			$numchar = null, $minfontsize = null, $maxfontsize = null, $backgroundtype = null,
			$backgroundnum = null
	) {
		$this->_captchaHandler =& icms_form_elements_captcha_Object::instance();
		$this->_captchaHandler->init(
			$name, $skipmember, $numchar, $minfontsize, $maxfontsize, $backgroundtype, $backgroundnum
		);
		if (!$this->_captchaHandler->active) {
			$this->setHidden();
		} else {
			$caption = !empty($caption) ? $caption : $this->_captchaHandler->getCaption();
			$this->setCaption($caption);
		}
	}

	/**
	 * Sets the Config
	 * @param   string $name Config Name
	 * @param   string $val Config Value
	 * @return  object reference to the icms_form_elements_captcha_Object Object (@link icms_form_elements_captcha_Object)
	 */
	public function setConfig($name, $val) {
		return $this->_captchaHandler->setConfig($name, $val);
	}

	/**
	 *
	 * @see htdocs/libraries/icms/form/icms_form_Element::render()
	 */
	public function render() {
		if (!$this->isHidden()) {
			return $this->_captchaHandler->render();
		}
	}
}

