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
 * @author	   Sina Asghari (aka stranger) <pesian_stranger@users.sourceforge.net>
 * @version		$Id: image.php 8662 2009-05-01 09:04:30Z pesianstranger $
*/

class IcmsCaptchaImage {
	//var $config	= array();


	/**
	 * Constructor
	 */
	function IcmsCaptchaImage()
	{
		//$this->name = md5( session_id() );
	}


	/**
	 * Creates instance of icmsCaptchaImage
   * @return  object the IcmsCaptchaImage object
	 */
	function &instance()
	{
		static $instance;
		if(!isset($instance)) {
			$instance =& new IcmsCaptchaImage();
		}
		return $instance;
	}




	/**
	 * Loading configs from CAPTCHA class
   * @param   array $config the configuration array
	 */
	function loadConfig($config = array())
	{
		// Loading default preferences
		$this->config =& $config;
	}



	/**
	 * Renders the Captcha image Returns form with image in it
   * @return  string String that contains the Captcha Image form
	 */
	function render()
	{
		global $icmsConfigCaptcha;
		$form = "<input type='text' name='".$this->config["name"]."' id='".$this->config["name"]."' size='" . $icmsConfigCaptcha['captcha_num_chars'] . "' maxlength='" . $icmsConfigCaptcha['captcha_num_chars'] . "' value='' /> &nbsp; ". $this->loadImage();
		$rule = htmlspecialchars(ICMS_CAPTCHA_REFRESH, ENT_QUOTES);
		if($icmsConfigCaptcha['captcha_maxattempt']) {
			$rule .=  " | ". sprintf( constant("ICMS_CAPTCHA_MAXATTEMPTS"), $icmsConfigCaptcha['captcha_maxattempt'] );
		}
		$form .= "&nbsp;&nbsp;<small>{$rule}</small>";
		
		return $form;
	}


	/**
	 * Loads the Captcha Image
   * @return  string String that contains the Captcha image
	 */
	function loadImage()
	{
		global $icmsConfigCaptcha;
		$rule = $icmsConfigCaptcha['captcha_casesensitive'] ? constant("ICMS_CAPTCHA_RULE_CASESENSITIVE") : constant("ICMS_CAPTCHA_RULE_CASEINSENSITIVE");
		$ret = "<img id='captcha' src='" . ICMS_URL. "/class/captcha/scripts/img.php' onclick=\"this.src='" . ICMS_URL. "/class/captcha/scripts/img.php?refresh='+Math.random()"."\" style='cursor: pointer;margin-left: auto;margin-right: auto;text-align:center;' alt='".htmlspecialchars($rule, ENT_QUOTES)."' />";
		return $ret;
	}

}

?>