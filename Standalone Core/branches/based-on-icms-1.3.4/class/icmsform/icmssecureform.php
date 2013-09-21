<?php
/**
 * Form control creating a secure form
 *
 * @copyright	The ImpressCMS Project http://www.impresscms.org/
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @package		icms_ipf_Object
 * @since		1.1
 * @author		marcan <marcan@impresscms.org>
 * @version		$Id: icmssecureform.php 10850 2010-12-05 19:10:48Z phoenyx $
 */

defined("ICMS_ROOT_PATH") or die("ImpressCMS root path not defined");

class IcmsSecureForm extends icms_ipf_form_Secure {
	private $_deprecated;

	public function __construct(&$target, $form_name, $form_caption, $form_action, $form_fields=null, $submit_button_caption = false, $cancel_js_action=false, $captcha=false) {
		parent::__construct(&$target, $form_name, $form_caption, $form_action, $form_fields, $submit_button_caption, $cancel_js_action, $captcha);
		$this->_deprecated = icms_core_Debug::setDeprecated('icms_ipf_form_Secure', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
	}
}