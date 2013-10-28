<?php
/**
 * Form control creating an image upload element for an object derived from icms_ipf_Object
 *
 * @copyright	The ImpressCMS Project http://www.impresscms.org/
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @package		icms_ipf_Object
 * @since		1.1
 * @author		marcan <marcan@impresscms.org>
 * @version		$Id: icmsform.php 20491 2010-12-05 20:30:06Z skenow $
 */

defined('ICMS_ROOT_PATH') or die("ImpressCMS root path not defined");

/**
 * IcmsForm base class
 *
 * Base class representing a single form for a specific icms_ipf_Object
 *
 * @copyright	The ImpressCMS Project http://www.impresscms.org/
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @package		icms_ipf_Object
 * @since		  1.1
 * @author		  marcan <marcan@impresscms.org>
 * @version		$Id: icmsform.php 20491 2010-12-05 20:30:06Z skenow $
 */

defined("ICMS_ROOT_PATH") or die("ImpressCMS root path not defined");

class IcmsForm extends icms_ipf_form_Base {
	private $_deprecated;
	/**
	 * Constructor
	 * Sets all the values / variables for the IcmsForm class
	 * @param	string    &$target                  reference to targetobject (@todo, which object will be passed here?)
	 * @param	string    $form_name                the form name
	 * @param	string    $form_caption             the form caption
	 * @param	string    $form_action              the form action
	 * @param	string    $form_fields              the form fields
	 * @param	string    $submit_button_caption    whether to add a caption to the submit button
	 * @param	bool      $cancel_js_action         whether to invoke a javascript action when cancel button is clicked
	 * @param	bool      $captcha                  whether to add captcha
	 */
	public function __construct(&$target, $form_name, $form_caption, $form_action, $form_fields=null, $submit_button_caption = false, $cancel_js_action=false, $captcha=false) {
		parent::__construct(&$target, $form_name, $form_caption, $form_action, $form_fields, $submit_button_caption, $cancel_js_action, $captcha);
		$this->_deprecated = icms_core_Debug::setDeprecated('icms_ipf_form_Base', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
	}
}