<?php
/**
 * CAPTCHA class
 *
 * Currently there are two types of CAPTCHA forms, text and image
 * The default mode is "text", it can be changed in the priority:
 * 1 If mode is set through icms_form_elements_Captcha::setConfig("mode", $mode), take it
 * 2 Elseif mode is set though captcha/config.php, take it
 * 3 Else, take "text"
 *
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @category	ICMS
 * @package		Form
 * @subpackage	Elements
 * @author		Sina Asghari (aka stranger) <pesian_stranger@users.sourceforge.net>
 * @version		SVN: $Id: Object.php 20424 2010-11-20 19:16:00Z phoenyx $
 */

icms_loadLanguageFile('core', 'captcha');
/**
 * Creates the captcha object
 *
 * @category	ICMS
 * @package		Form
 * @subpackage	Elements
 *
 */
class icms_form_elements_captcha_Object {

	public $active	= TRUE;
	/** potential values: image, text */
	public $mode = "text";
	/** */
	public $config	= array();
	/** Logging error messages */
	public $message = array();

	/**
	 * Constructor
	 */
	public function __construct() {
		// Loading default preferences
		$this->config = @include dirname(__FILE__) . "/config.php";

		global $icmsConfigCaptcha;
		$this->setMode($icmsConfigCaptcha['captcha_mode']);
	}

	/**
	 * Creates instance of icms_form_elements_captcha_Object Object
	 * @return  object Reference to the icms_form_elements_captcha_Object Object
	 */
	static public function &instance() {
		static $instance;
		if (!isset($instance)) {
			$instance = new self();
		}
		return $instance;
	}

	/**
	 * Sets the Captcha Config
	 * @param   string $name Config Name
	 * @param   string $val Config Value
	 * @return  bool  Always returns true if the setting of the config has succeeded
	 */
	public function setConfig($name, $val) {
		if ($name == "mode") {
			$this->setMode($val);
		} elseif (isset($this->$name)) {
			$this->$name = $val;
		} else {
			$this->config[$name] = $val;
		}
		return TRUE;
	}

	/**
	 * Set CAPTCHA mode
	 *
	 * For future possible modes, right now force to use text or image
	 *
	 * @param string	$mode	if no mode is set, just verify current mode
	 */
	public function setMode($mode = NULL) {
		if (!empty($mode) && in_array($mode, array("text", "image"))) {
			$this->mode = $mode;

			if ($this->mode != "image") {
				return;
			}
		}

		// Disable image mode
		if (!extension_loaded('gd')) {
			$this->mode = "text";
		} else {
			$required_functions = array(
				"imagecreatetruecolor", "imagecolorallocate", "imagefilledrectangle",
				"imagejpeg", "imagedestroy", "imageftbbox"
			);
			foreach ($required_functions as $func) {
				if (!function_exists($func)) {
					$this->mode = "text";
					break;
				}
			}
		}

	}

	/**
	 * Initializing the CAPTCHA class
	 * @param   string  $name			 name of the instance
	 * @param   string  $skipmember	   Skip the captcha because the user is member / logged in
	 * @param   string  $num_chars		comes from config, just initializes the variable
	 * @param   string  $fontsize_min	 comes from config, just initializes the variable
	 * @param   string  $fontsize_max	 comes from config, just initializes the variable
	 * @param   string  $background_type  comes from config, just initializes the variable
	 * @param   string  $background_num   comes from config, just initializes the variable
	 */
	public function init(
			$name = 'icmscaptcha', $skipmember = NULL, $num_chars = NULL,
			$fontsize_min = NULL, $fontsize_max = NULL, $background_type = NULL,
			$background_num = NULL)
		{
		global $icmsConfigCaptcha;
		// Loading RUN-TIME settings
		foreach (array_keys($this->config) as $key) {
			if (isset(${$key}) && ${$key} !== NULL) {
				$this->config[$key] = ${$key};
			}
		}
		$this->config["name"] = $name;

		// Skip CAPTCHA for group
		//$gperm_handler = icms::handler('icms_member_groupperm');
		$groups = is_object(icms::$user) ? icms::$user->getGroups() : array(XOOPS_GROUP_ANONYMOUS);
		if (array_intersect($groups, $icmsConfigCaptcha['captcha_skipmember']) && is_object(icms::$user)) {
			$this->active = FALSE;
		} elseif ($icmsConfigCaptcha['captcha_mode'] =='none') {
			$this->active = FALSE;
		}
	}

	/**
	 * Verify user submission
	 * @param bool	$skipMember	Skip Captcha because user is member / logged in
	 */
	public function verify($skipMember = NULL) {
		global $icmsConfig, $icmsConfigCaptcha;
		$sessionName	= @$_SESSION['icms_form_elements_captcha_Object_name'];
		$skipMember		= ($skipMember === NULL) ? @$_SESSION['icms_form_elements_captcha_Object_skipmember'] : $skipMember;
		$maxAttempts	= (int) (@$_SESSION['icms_form_elements_captcha_Object_maxattempts']);

		$is_valid = FALSE;

		$groups = is_object(icms::$user) ? icms::$user->getGroups() : array(XOOPS_GROUP_ANONYMOUS);
		if (array_intersect($groups, $icmsConfigCaptcha['captcha_skipmember']) && is_object(icms::$user)) {
			$is_valid = TRUE;
		} elseif (!empty($maxAttempts) && $_SESSION['icms_form_elements_captcha_Object_attempt_'.$sessionName] > $maxAttempts) {
			$this->message[] = ICMS_CAPTCHA_TOOMANYATTEMPTS;

			// Verify the code
		} elseif (!empty($_SESSION['icms_form_elements_captcha_Object_sessioncode'])) {
			$func = ($icmsConfigCaptcha['captcha_casesensitive']) ? "strcmp" : "strcasecmp";
			$is_valid = ! $func(trim(@$_POST[$sessionName]), $_SESSION['icms_form_elements_captcha_Object_sessioncode']);
		}

		if (!empty($maxAttempts)) {
			if (!$is_valid) {
				// Increase the attempt records on failure
				$_SESSION['icms_form_elements_captcha_Object_attempt_'.$sessionName]++;
				// Log the error message
				$this->message[] = ICMS_CAPTCHA_INVALID_CODE;

			} else {
				// reset attempt records on success
				$_SESSION['icms_form_elements_captcha_Object_attempt_'.$sessionName] = NULL;
			}
		}

		$this->destroyGarbage(TRUE);

		return $is_valid;
	}

	/**
	 * Get Caption
	 * @return string	The Caption Constant
	 */
	public function getCaption() {
		return defined("ICMS_CAPTCHA_CAPTION") ? constant("ICMS_CAPTCHA_CAPTION") : "";
	}

	/**
	 * Set Message
	 * @return string	The message
	 */
	public function getMessage() {
		return implode("<br />", $this->message);
	}

	/**
	 * Destory historical stuff
	 * @param bool	$clearSession	also clear session variables?
	 * @return bool True if destroying succeeded
	 */
	public function destroyGarbage($clearSession = FALSE) {
		$class = "icms_form_elements_captcha_" . ucfirst($this->mode);
		$captcha_handler = new $class();
		if (method_exists($captcha_handler, "destroyGarbage")) {
			$captcha_handler->loadConfig($this->config);
			$captcha_handler->destroyGarbage();
		}

		if ($clearSession) {
			$_SESSION['icms_form_elements_captcha_Object_name'] = NULL;
			$_SESSION['icms_form_elements_captcha_Object_skipmember'] = NULL;
			$_SESSION['icms_form_elements_captcha_Object_sessioncode'] = NULL;
			$_SESSION['icms_form_elements_captcha_Object_maxattempts'] = NULL;
		}

		return TRUE;
	}

	/**
	 * Render
	 * @return  string  the rendered form
	 */
	public function render() {
		global $icmsConfigCaptcha;
		$form = "";

		if (!$this->active || empty($this->config["name"])) {
			return $form;
		}
		$_SESSION['icms_form_elements_captcha_Object_name'] = $this->config["name"];
		$_SESSION['icms_form_elements_captcha_Object_skipmember'] = $icmsConfigCaptcha['captcha_skipmember'];
		$maxAttempts = $icmsConfigCaptcha['captcha_maxattempt'];
		$_SESSION['icms_form_elements_captcha_Object_maxattempts'] = $maxAttempts;

		 if (!empty($maxAttempts)) {
			$_SESSION['icms_form_elements_captcha_Object_maxattempts_'. $_SESSION['icms_form_elements_captcha_Object_name']] = $maxAttempts;
		}


		// Fail on too many attempts
		if (!empty($maxAttempts) && @$_SESSION['icms_form_elements_captcha_Object_attempt_' . $this->config["name"]] > $maxAttempts) {
			$form = ICMS_CAPTCHA_TOOMANYATTEMPTS;
			// Load the form element
		} else {
			$form = $this->loadForm();
		}

		return $form;
	}

	/**
	 * Load Form
	 * @return string	The Loaded Captcha Form
	 */
	public function loadForm() {
		$class = "icms_form_elements_captcha_" . ucfirst($this->mode);
		$captcha_handler = new $class();
		$captcha_handler->loadConfig($this->config);

		$form = $captcha_handler->render();
		return $form;
	}
}
