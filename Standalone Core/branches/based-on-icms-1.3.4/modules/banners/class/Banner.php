<?php
/**
* Class representing the banners banner objects
*
* @copyright	The ImpressCMS Project
* @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
* @since		1.0
* @author		Phoenyx <phoenyx@impresscms.de>
* @package		banners
* @version		$Id: Banner.php 20562 2010-12-19 18:23:02Z phoenyx $
*/
defined("ICMS_ROOT_PATH") or die("ICMS root path not defined");

class mod_banners_Banner extends icms_ipf_Object {
	public $skipSaveEvents = FALSE;
	public $backup = FALSE;

	/**
	 * Constructor
	 *
	 * @param object $handler BannersPostHandler object
	 */
	public function __construct(&$handler) {
		parent::__construct($handler);

		$this->quickInitVar('banner_id', XOBJ_DTYPE_INT, TRUE);
		$this->quickInitVar('client_id', XOBJ_DTYPE_INT, TRUE);
		$this->quickInitVar('description', XOBJ_DTYPE_TXTBOX, TRUE);
		$this->quickInitVar('type', XOBJ_DTYPE_INT, TRUE, FALSE, FALSE, BANNERS_BANNER_TYPE_IMAGE);
		$this->quickInitVar('filename', XOBJ_DTYPE_IMAGE, FALSE);
		$this->quickInitVar('link', XOBJ_DTYPE_TXTBOX, FALSE);
		$this->quickInitVar('target', XOBJ_DTYPE_INT, FALSE);
		$this->quickInitVar('source', XOBJ_DTYPE_TXTBOX, FALSE);
		$this->initNonPersistableVar('positions', XOBJ_DTYPE_OTHER, 'positions', FALSE, FALSE, FALSE, TRUE, TRUE);
		$this->quickInitVar('contract', XOBJ_DTYPE_INT, TRUE, FALSE, FALSE, BANNERS_BANNER_CONTRACT_TIME);
		$this->quickInitVar('begin', XOBJ_DTYPE_LTIME, FALSE);
		$this->quickInitVar('end', XOBJ_DTYPE_LTIME, FALSE);
		$this->quickInitVar('impressions_purchased', XOBJ_DTYPE_INT, FALSE);
		$this->quickInitVar('impressions_made', XOBJ_DTYPE_INT, FALSE);
		$this->quickInitVar('clicks', XOBJ_DTYPE_INT, FALSE);
		$this->initNonPersistableVar('clicks_percent', XOBJ_DTYPE_FLOAT);
		$this->quickInitVar('extra', XOBJ_DTYPE_TXTAREA, FALSE);
		$this->initNonPersistableVar('visiblein', XOBJ_DTYPE_OTHER, 'visiblein', FALSE, FALSE, FALSE, TRUE, TRUE);
		$this->quickInitVar('active', XOBJ_DTYPE_INT, FALSE, FALSE, FALSE, 1);
		$this->initNonPersistableVar('status', XOBJ_DTYPE_OTHER);

		$this->setControl('type', array(
			'itemHandler' => 'banner',
			'method'      => 'getTypeArray',
			'module'      => 'banners',
			'onSelect'    => 'submit'
		));
		$this->setControl('filename', 'image');
		$this->setControl('source', array('name' => 'source', 'syntax' => 'html'));
		$this->setControl('extra', 'dhtmltextarea');
		$this->setControl('visiblein', 'page');
		$this->setControl('active', 'yesno');
		$this->setControl('client_id', array(
			'itemHandler' => 'client',
			'method'      => 'getClientArray',
			'module'      => 'banners'
		));
		$this->setControl('positions', array(
			'name'        => 'selectmulti',
			'itemHandler' => 'position',
			'method'      => 'getPositionArray',
			'module'      => 'banners'
		));
		$this->setControl('contract', array(
			'itemHandler' => 'banner',
			'method'      => 'getContractArray',
			'module'      => 'banners',
			'onSelect'    => 'submit'
		));
		$this->setControl('target', array(
			'itemHandler' => 'banner',
			'method'      => 'getTargetArray',
			'module'      => 'banners'
		));

		$this->hideFieldFromForm(array('impressions_made', 'clicks', 'clicks_percent'));
	}

	/**
	 * Overwriting the IcmsPersistableObject::getVar method to assign a custom method on some
	 * specific fields to handle the value before returning it
	 *
	 * @param str $key key of the field
	 * @param str $format format that is requested
	 * @return mixed value of the field that is requested
	 */
	public function getVar($key, $format = 's') {
		if ($format == 's' && in_array($key, array('positions', 'visiblein', 'status'))) {
			return call_user_func(array($this, $key));
		}
		if ($format == 'e' && in_array($key, array('positions', 'visiblein'))) {
			return call_user_func(array($this, $key));
		}
		return parent::getVar($key, $format);
	}

	/**
	 * custom getVar function for positions
	 *
	 * @return array position_ids
	 */
	private function positions() {
		if (is_array($this->getVar('positions', 'show')) && count($this->getVar('positions', 'show')) > 0) return $this->getVar('positions', 'show');

		$ret = array();
		if ($this->isNew()) return $ret;
		$banners_positionlink_handler = icms_getModuleHandler('positionlink', basename(dirname(dirname(__FILE__))), 'banners');
		$positionlinks = $banners_positionlink_handler->getObjects(icms_buildCriteria(array('banner_id' => $this->getVar('banner_id'))));
		foreach ($positionlinks as $positionlink) {
			$ret[] = $positionlink->getVar('position_id');
		}
		return $ret;
	}

	/**
	 * custom getVar function for visiblein
	 *
	 * @return array visiblein_keys
	 */
	private function visiblein() {
		if (is_array($this->getVar('visiblein', 'show')) && count($this->getVar('visiblein', 'show')) > 0) return $this->getVar('visiblein', 'show');

		$ret = array();
		if ($this->isNew()) return $ret;
		$banners_visiblein_handler = icms_getModuleHandler('visiblein', basename(dirname(dirname(__FILE__))), 'banners');
		$visibleins = $banners_visiblein_handler->getObjects(icms_buildCriteria(array('banner_id' => $this->getVar('banner_id'))));
		foreach ($visibleins as $visiblein) {
			$ret[] = $visiblein->getVar('module') . '-' . $visiblein->getVar('page');
		}
		return $ret;
	}

	/**
	 * calculate status (banner visible or not)
	 *
	 * @return bool
	 */
	private function status() {
		if (!$this->getVar('active')) return FALSE;
		if ($this->getVar('contract') == BANNERS_BANNER_CONTRACT_TIME) {
			if (!(strtotime($this->getVar('begin')) <= time() && time() <= strtotime($this->getVar('end')))) {
				return FALSE;
			}
		} elseif ($this->getVar('contract') == BANNERS_BANNER_CONTRACT_IMPRESSIONS) {
			if (!($this->getVar('impressions_purchased') == 0 || $this->getVar('impressions_made') < $this->getVar('impressions_purchased'))) {
				return FALSE;
			}
		}
		$registry = icms_ipf_registry_Handler::getInstance();
		$client = $registry->getSingleObject('client', $this->getVar('client_id'), 'banners');
		if (!$client->getVar('active')) return FALSE;

		return TRUE;
	}

	/**
	 * generate html to show a small icon
	 *
	 * @return str html code
	 */
	public function getStatusForTableDisplay() {
		if ($this->getVar('status')) {
			$img = ICMS_IMAGES_SET_URL . "/actions/button_ok.png";
		} else {
			$img = ICMS_IMAGES_SET_URL . "/actions/button_cancel.png";
		}

		return '<img src="' . $img . '" />';
	}

	/**
	 * get full client name for display on table
	 *
	 * @return str full client name
	 */
	public function getClientForTableDisplay() {
		$registry = icms_ipf_registry_Handler::getInstance();
		$client = $registry->getSingleObject('client', $this->getVar('client_id'), 'banners');
		return $client->getFullClientName();
	}

	/**
	 * get description for display on table
	 *
	 * @return str description
	 */
	public function getDescriptionForTableDisplay() {
		return $this->getVar('description');
	}

	/**
	 * get begin for display on table
	 *
	 * @return str begin if applicable
	 */
	public function getBeginForTableDisplay() {
		if ($this->getVar('contract') == BANNERS_BANNER_CONTRACT_IMPRESSIONS) return "n/a";
		return $this->getVar('begin');
	}

	/**
	 * get end for display on table
	 *
	 * @return str end if applicable
	 */
	public function getEndForTableDisplay() {
		if ($this->getVar('contract') == BANNERS_BANNER_CONTRACT_IMPRESSIONS) return "n/a";
		return $this->getVar('end');
	}

	/**
	 * get impressions purchased for display on table
	 *
	 * @return str impressions purchased if applicable
	 */
	public function getImpressionsPurchasedForTableDisplay() {
		if ($this->getVar('contract') == BANNERS_BANNER_CONTRACT_TIME) return "n/a";
		return $this->getVar('impressions_purchased');
	}

	public function getClicksForTableDisplay() {
		if ($this->getVar('type') == BANNERS_BANNER_TYPE_HTML) return "n/a";
		return $this->getVar('clicks');
	}

	/**
	 * get clicks percent for display on table
	 *
	 * @return float clicks compared to impressions made
	 */
	public function getClicksPercentForTableDisplay() {
		if ($this->getVar('type') == BANNERS_BANNER_TYPE_HTML) return "n/a";
		if ($this->getVar('impressions_made') == 0) return 0;
		return round(($this->getVar('clicks') / $this->getVar('impressions_made') * 100), 4);
	}

	/**
	 * function to increment the impressions made
	 *
	 * @return bool result for storing the object
	 */
	public function incrementImpressions() {
		$this->setVar('impressions_made', $this->getVar('impressions_made') + 1);
		$this->skipSaveEvents = TRUE;
		$rtn = $this->store(TRUE);
		$this->skipSaveEvents = FALSE;
		return $rtn;
	}

	/**
	 * function to increment the clicks
	 *
	 * @return bool result for storing the object
	 */
	public function incrementClicks() {
		// only increment once per session
		if (!isset($_SESSION['banners_clicked'])) $_SESSION['banners_clicked'] = array();
		if (in_array($this->getVar('banner_id'), $_SESSION['banners_clicked'])) {
			return FALSE;
		} else {
			$_SESSION['banners_clicked'][] = $this->getVar('banner_id');
		}

		$this->setVar('clicks', $this->getVar('clicks') + 1);
		$this->skipSaveEvents = TRUE;
		$rtn = $this->store(TRUE);
		$this->skipSaveEvents = FALSE;

		return $rtn;
	}

	/**
	 * construct the clickable banner (html code)
	 *
	 * @return str html code to display the banner
	 */
	public function render() {
		if ($this->getVar("type") == BANNERS_BANNER_TYPE_HTML) {
			return $this->getVar("source", "n");
		}

		$rtn = "";
		if ($this->getVar('link') != "") {
			$controller = new icms_ipf_Controller($this->handler);
			$rtn .= "<a href='" . $controller->getViewItemLink($this, TRUE, FALSE, TRUE) . "&amp;op=view' target='";
			$rtn .= ($this->getVar('target') == BANNERS_BANNER_TARGET_BLANK) ? "_blank" : "_self";
			$rtn .= "'>";
		}
		if (substr($this->getVar('filename', 'e'), 0, 4) == 'http' || substr($this->getVar('filename', 'e'), 0, 10) == '{ICMS_URL}') {
			$filename = str_replace('{ICMS_URL}', ICMS_URL, $this->getVar('filename', 'e'));
		} else {
			$filename = $this->getImageDir() . $this->getVar('filename', 'e');
		}
		if ($this->getVar("type") == BANNERS_BANNER_TYPE_IMAGE) {
			$rtn .= "<img src='" . $filename . "' alt='' />";
		} elseif ($this->getVar("type") == BANNERS_BANNER_TYPE_FLASH) {
			$imagesize = getimagesize($this->getImageDir(TRUE) . $this->getVar('filename', 'e'));
			$rtn .= "<object type='application/x-shockwave-flash' data='" . $filename . "' width='" . $imagesize[0] . "' height='" . $imagesize[1] . "'>";
			$rtn .= "<param name='movie' value='" . $filename . "'></param>";
			$rtn .= "<param name='quality' value='high'></param>";
			$rtn .= "</object>";
		}
		if ($this->getVar('link') != "") {
			$rtn .= "</a>";
		}

		return $rtn;
	}

	/**
	 * send email to webmaster (triggered after a new banner was submitted by a client)
	 *
	 * @global array $icmsConfig CMS configurations
	 * @return bool result of sending the email
	 */
	public function notifyWebmaster() {
		global $icmsConfig;

		$module = icms::handler("icms_module")->getByDirname(basename(dirname(dirname(__FILE__))), TRUE);
		if (!$module->config['email_new_banner']) return FALSE;
		
		$controller = new icms_ipf_Controller($this->handler);

		$icmsMailer = new icms_messaging_Handler();
		$icmsMailer->useMail();
		$icmsMailer->setTemplateDir($this->handler->_modulePath . "language/" . $icmsConfig['language'] . "/mail_template/");
		$icmsMailer->setTemplate('banner_new.tpl');
		$icmsMailer->assign('CLIENT_FULLNAME', $this->getClientForTableDisplay());
		$icmsMailer->assign('BANNER_ADMIN_URL', str_replace("&amp;", "&", $controller->getEditItemLink($this, TRUE, FALSE)));
		$icmsMailer->setToGroups(icms::handler('icms_member')->getGroup(ICMS_GROUP_ADMIN));
		$icmsMailer->setFromEmail($icmsConfig['adminmail']);
		$icmsMailer->setFromName($icmsConfig['sitename']);
		$icmsMailer->setSubject($module->config['email_new_banner_subject']);

		return $icmsMailer->send();
	}
}