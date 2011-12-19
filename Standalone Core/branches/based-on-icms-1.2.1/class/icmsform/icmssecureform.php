<?php
/**
* Form control creating a secure form
*
* @copyright	The ImpressCMS Project http://www.impresscms.org/
* @license	http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
* @package	IcmsPersistableObject
* @since	1.1
* @author	marcan <marcan@impresscms.org>
* @version	$Id: icmssecureform.php 9390 2009-09-13 12:45:03Z Phoenyx $
*/

if (!defined('ICMS_ROOT_PATH')) die("ImpressCMS root path not defined");

/**
 * Including the IcmsForm class
 */
include_once ICMS_ROOT_PATH . '/class/icmsform/icmsform.php';

/**
* IcmsSecureForm extending IcmsForm with the addition of the Security Token
*
* @copyright	The ImpressCMS Project http://www.impresscms.org/
* @license	http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
* @package	IcmsPersistableObject
* @since	1.1
* @author	marcan <marcan@impresscms.org>
* @version	$Id: icmssecureform.php 9390 2009-09-13 12:45:03Z Phoenyx $
*/
class IcmsSecureForm extends IcmsForm {

	/**
	 * Constructor
	 * Sets all the values / variables for the IcmsForm (@link IcmsForm) (parent) class
	 * @param	string    &$target                  reference to targetobject (@todo, which object will be passed here?)
	 * @param	string    $form_name                the form name
	 * @param	string    $form_caption             the form caption
	 * @param	string    $form_action              the form action
	 * @param	string    $form_fields              the form fields
	 * @param	string    $submit_button_caption    whether to add a caption to the submit button
	 * @param	bool      $cancel_js_action         whether to invoke a javascript action when cancel button is clicked
	 * @param	bool      $captcha                  whether to add captcha
	 */
	function IcmsSecureForm(&$target, $form_name, $form_caption, $form_action, $form_fields=null, $submit_button_caption = false, $cancel_js_action=false, $captcha=false) {
		parent::IcmsForm($target, $form_name, $form_caption, $form_action, $form_fields, $submit_button_caption, $cancel_js_action, $captcha);
		$this->addElement(new XoopsFormHiddenToken());
	}
}
?>