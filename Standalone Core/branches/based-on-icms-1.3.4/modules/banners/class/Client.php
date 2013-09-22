<?php
/**
* Class representing the banners client objects
*
* @copyright	The ImpressCMS Project
* @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
* @since		1.0
* @author		Phoenyx <phoenyx@impresscms.de>
* @package		banners
* @version		$Id: Client.php 23948 2012-03-24 12:45:08Z qm-b $
*/
defined("ICMS_ROOT_PATH") or die("ICMS root path not defined");

class mod_banners_Client extends icms_ipf_Object {
	/**
	 * Constructor
	 *
	 * @param object $handler BannersPostHandler object
	 */
	public function __construct(&$handler) {
		parent::__construct($handler);

		$this->quickInitVar('client_id', XOBJ_DTYPE_INT, TRUE);
		$this->quickInitVar('company', XOBJ_DTYPE_TXTBOX, FALSE);
		$this->quickInitVar('first_name', XOBJ_DTYPE_TXTBOX, TRUE);
		$this->quickInitVar('last_name', XOBJ_DTYPE_TXTBOX, TRUE);
		$this->quickInitVar('street', XOBJ_DTYPE_TXTBOX, FALSE);
		$this->quickInitVar('street_number', XOBJ_DTYPE_INT, FALSE);
		$this->quickInitVar('zip_code', XOBJ_DTYPE_INT, FALSE);
		$this->quickInitVar('city', XOBJ_DTYPE_TXTBOX, FALSE);
		$this->quickInitVar('state', XOBJ_DTYPE_TXTBOX, FALSE);
		$this->quickInitVar('country', XOBJ_DTYPE_TXTBOX, TRUE);
		$this->quickInitVar('since', XOBJ_DTYPE_STIME, TRUE);
		$this->quickInitVar('email', XOBJ_DTYPE_TXTBOX, TRUE);
		$this->quickInitVar('phone', XOBJ_DTYPE_TXTBOX, FALSE);
		$this->quickInitVar('uid', XOBJ_DTYPE_INT, FALSE);
		$this->quickInitVar('extra', XOBJ_DTYPE_TXTAREA, FALSE);
		$this->initNonPersistableVar('banner_count', XOBJ_DTYPE_INT);
		$this->quickInitVar('active', XOBJ_DTYPE_INT, FALSE, FALSE, FALSE, 0);

		$this->setControl('extra', 'dhtmltextarea');
		$this->setControl('uid', 'user');
		$this->setControl('country', 'country');
		$this->setControl('since', 'date');
		$this->setControl('active', 'yesno');
	}

	/**
	 * Overriding the IcmsPersistableObject::getVar method to assign a custom method on some
	 * specific fields to handle the value before returning it
	 *
	 * @param str $key key of the field
	 * @param str $format format that is requested
	 * @return mixed value of the field that is requested
	 */
	public function getVar($key, $format = 's') {
		if ($format == 's' && in_array($key, array('banner_count'))) {
			return call_user_func(array ($this,	$key));
		}
		return parent::getVar($key, $format);
	}

	/**
	 * custom getVar function to get the amount of banners for this client
	 * 
	 * @return int number ob banners
	 */
	private function banner_count() {
		$banners_banner_handler = icms_getModuleHandler('banner', basename(dirname(dirname(__FILE__))), 'banners');
		return $banners_banner_handler->getCount(new icms_db_criteria_Compo(new icms_db_criteria_Item('client_id', $this->getVar('client_id'))));
	}

	/**
	 * concatenate client variables for display
	 *
	 * @return str full client name
	 */
	public function getFullClientName() {
		$ret = $this->getVar('first_name') . ' ' . $this->getVar('last_name');
		if ($this->getVar('company') != '') $ret .= ' (' . $this->getVar('company') . ')';
		return $ret;
	}


	public function getActiveForTableDisplay() {
		if ($this->getVar('active')) {
			$img = ICMS_IMAGES_SET_URL . "/actions/button_ok.png";
		} else {
			$img = ICMS_IMAGES_SET_URL . "/actions/button_cancel.png";
		}

		return '<img src="' . $img . '" />';
	}

	/**
	 * get last name for display on table
	 *
	 * @return str last name
	 */
	public function getLastNameForTableDisplay() {
		return $this->getVar('last_name');
	}

	/**
	 * get username for display on table
	 *
	 * @return str linked username
	 */
	public function getUsernameForTableDisplay() {
		if ($this->getVar('uid') == '') return 'n/a';
		return icms_member_user_Handler::getUserLink($this->getVar('uid'));
	}

	/**
	 * send email to webmaster (triggered after a user has registered as a new client)
	 *
	 * @global array $icmsConfig CMS configurations
	 * @return bool result of sending the email
	 */
	public function notifyWebmaster() {
		global $icmsConfig;

		$module = icms::handler("icms_module")->getByDirname(basename(dirname(dirname(__FILE__))), TRUE);
		if (!$module->config['email_new_client']) return FALSE;

		$controller = new icms_ipf_Controller($this->handler);

		$icmsMailer = new icms_messaging_Handler();
		$icmsMailer->useMail();
		$icmsMailer->setTemplateDir($this->handler->_modulePath . "language/" . $icmsConfig['language'] . "/mail_template/");
		$icmsMailer->setTemplate('client_new.tpl');
		$icmsMailer->assign('CLIENT_FULLNAME', $this->getFullClientName());
		$icmsMailer->assign('CLIENT_ADMIN_URL', str_replace("&amp;", "&", $controller->getEditItemLink($this, TRUE, FALSE)));
		$icmsMailer->setToGroups(icms::handler('icms_member')->getGroup(ICMS_GROUP_ADMIN));
		$icmsMailer->setFromEmail($icmsConfig['adminmail']);
		$icmsMailer->setFromName($icmsConfig['sitename']);
		$icmsMailer->setSubject($module->config['email_new_client_subject']);

		return $icmsMailer->send();
	}
}