<?php
/**
* Class responsible for managing banners banner objects
*
* @copyright	The ImpressCMS Project
* @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
* @since		1.0
* @author		Phoenyx <phoenyx@impresscms.de>
* @package		banners
* @version		$Id: BannerHandler.php 20562 2010-12-19 18:23:02Z phoenyx $
*/
defined("ICMS_ROOT_PATH") or die("ICMS root path not defined");

define("BANNERS_BANNER_CONTRACT_TIME", 1);
define("BANNERS_BANNER_CONTRACT_IMPRESSIONS", 2);
define("BANNERS_BANNER_TARGET_BLANK", 1);
define("BANNERS_BANNER_TARGET_SELF", 2);
define("BANNERS_BANNER_TYPE_IMAGE", 1);
define("BANNERS_BANNER_TYPE_HTML", 2);
define("BANNERS_BANNER_TYPE_FLASH", 3);

// load blocksadmin language file for visiblein select option
icms_loadLanguageFile('system', 'blocksadmin', TRUE);

class mod_banners_BannerHandler extends icms_ipf_Handler {
	private $_types;
	private $_typesForClient;
	private $_contracts;
	private $_targets;
	private $_allowedMimeTypesImage = array('image/gif', 'image/jpeg', 'image/pjpeg', 'image/png');
	private $_allowedMimeTypesFlash = array('application/x-shockwave-flash');

	/**
	 * Constructor
	 *
	 * @param object $db database object
	 */
	public function __construct(&$db) {
		parent::__construct($db, 'banner', 'banner_id', 'description', 'client_id', 'banners');
		$module = icms::handler("icms_module")->getByDirname(basename(dirname(dirname(__FILE__))), TRUE);
		$this->enableUpload(array(), $module->config['maxfilesize'], 1000, 1000);
	}

	/**
	 * create and return banner types array for value list
	 *
	 * @return array of types
	 */
	public function getTypeArray() {
		if (!count($this->_types)) {
			$this->_types[BANNERS_BANNER_TYPE_IMAGE] = _CO_BANNERS_BANNER_TYPE_IMAGE;
			$this->_types[BANNERS_BANNER_TYPE_HTML] = _CO_BANNERS_BANNER_TYPE_HTML;
			$this->_types[BANNERS_BANNER_TYPE_FLASH] = _CO_BANNERS_BANNER_TYPE_FLASH;
		}
		return $this->_types;
	}

	/**
	 * create and return banner types array for value list
	 * this is only used for the frontend
	 *
	 * @return array of types
	 */
	public function getTypeArrayForClient() {
		if (!count($this->_typesForClient)) {
			$types = $this->getTypeArray();
			$module = icms::handler("icms_module")->getByDirname(basename(dirname(dirname(__FILE__))), TRUE);
			foreach ($types as $key => $value) {
				if (in_array($key, $module->config['client_banner_types'])) $this->_typesForClient[$key] = $value;
			}
		}
		return $this->_typesForClient;
	}

	/**
	 * create and return client array for value list
	 * use mod_banners_ClientHandler->getClientArray() whenever possible
	 *
	 * @return array clients
	 */
	public function getClientArray() {
		$banners_client_handler = icms_getModuleHandler('client', basename(dirname(dirname(__FILE__))), 'banners');
		return $banners_client_handler->getClientArray();
	}

	/**
	 * create and return contract type array for value list
	 *
	 * @return array contract types
	 */
	public function getContractArray() {
		if (!count($this->_contracts)) {
			$this->_contracts[BANNERS_BANNER_CONTRACT_TIME] = _CO_BANNERS_BANNER_CONTRACT_TIME;
			$this->_contracts[BANNERS_BANNER_CONTRACT_IMPRESSIONS] = _CO_BANNERS_BANNER_CONTRACT_IMPRESSIONS;
		}
		return $this->_contracts;
	}

	/**
	 * create and return target array for value list
	 *
	 * @return array targets
	 */
	public function getTargetArray() {
		if (!count($this->_targets)) {
			$this->_targets[BANNERS_BANNER_TARGET_BLANK] = _CO_BANNERS_BANNER_TARGET_BLANK;
			$this->_targets[BANNERS_BANNER_TARGET_SELF] = _CO_BANNERS_BANNER_TARGET_SELF;
		}
		return $this->_targets;
	}

	/**
	 * setup mimetype handling according to banner type
	 *
	 * @param int $type banner type
	 */
	public function setAllowedMimetypes($type) {
		if ($type == BANNERS_BANNER_TYPE_IMAGE) {
			$this->enableUpload($this->_allowedMimeTypesImage);
		} elseif ($type == BANNERS_BANNER_TYPE_FLASH) {
			$this->enableUpload($this->_allowedMimeTypesFlash);
		}
	}

	/*
	 * beforeFileUnlink event
	 *
	 * Event automatically triggered by IcmsPersistable Framework before a file is unlinked
	 *
	 * @param object $obj mod_banners_Banner object
	 * @return bool TRUE
	 */
	public function beforeFileUnlink(&$obj) {
		// only create a backup when a new banner is uplaoded
		if (isset($_POST['url_filename']) && $_POST['url_filename'] != '') return TRUE;

		$source = $obj->getImageDir(TRUE) . $obj->getVar('filename', 'e');
		$dest = $obj->getImageDir(TRUE) . "backup_" . time() . substr($obj->getVar('filename', 'e'), strrpos($obj->getVar('filename', 'e'), "."));
		if (is_file($source)) copy($source, $dest);
		$obj->backup = array('tmp' => $dest, 'old' => $source);

		return TRUE;
	}

	/**
	 * beforeSave event
	 *
	 * Event automatically triggered by IcmsPersistable Framework before the object is inserted or updated
	 *
	 * @param object $obj mod_banners_Banner object
	 * @return bool result of dimension check for the image object and the positions selected
	 */
	public function beforeSave(&$obj) {
		// skip beforeSave handling if requested
		if ($obj->skipSaveEvents) return TRUE;

		// verify image dimensions for all positions selected (only for type IMAGE and FLASH)
		if (in_array($obj->getVar('type'), array(BANNERS_BANNER_TYPE_IMAGE, BANNERS_BANNER_TYPE_FLASH))) {
			// we cannot check dimensions for files we don't host
			if (substr($obj->getVar('filename', 'e'), 0, 4) == 'http' || substr($obj->getVar('filename', 'e'), 0, 10) == '{ICMS_URL}') return TRUE;
			// check if file exists
			if (!is_file($obj->getImageDir(TRUE) . $obj->getVar('filename', 'e'))) return TRUE;
			// get image dimensions
			$imagesize = getimagesize($obj->getImageDir(TRUE) . $obj->getVar('filename', 'e'));

			$banners_position_handler = icms_getModuleHandler('position', basename(dirname(dirname(__FILE__))), 'banners');
			$positions = $banners_position_handler->getObjects(new icms_db_criteria_Compo(new icms_db_criteria_Item('position_id', "(" . implode(',', $obj->getVar('positions', 'show')) . ")", 'IN')));
			foreach ($positions as $position) {
				if ($imagesize[0] > $position->getVar('width') || $imagesize[1] > $position->getVar('height')) {
					if (($obj->isNew() || is_array($obj->backup)) && is_file($obj->getImageDir(TRUE) . $obj->getVar('filename', 'e'))) unlink($obj->getImageDir(TRUE) . $obj->getVar('filename', 'e'));
					if (!$obj->isNew() && is_array($obj->backup)) {
						rename($obj->backup['tmp'], $obj->backup['old']);
						$obj->backup = FALSE;
					}
					$obj->setErrors(sprintf(_CO_BANNERS_BANNER_DIMENSIONCHECK, $position->getVar('title'), $position->getVar('width'), $imagesize[0], $position->getVar('height'), $imagesize[1]));

					return FALSE;
				}
			}
		}

		// delete temporary file if there is any
		if (is_array($obj->backup) && is_file($obj->backup['tmp'])) unlink($obj->backup['tmp']);
		$obj->backup = FALSE;

		return TRUE;
	}

	/**
	 * afterSave event
	 *
	 * Event automatically triggered by IcmsPersistable Framework after the object is inserted or updated
	 *
	 * @param object $obj mod_banners_Banner object
	 * @return TRUE
	 */
	public function afterSave(&$obj) {
		// skip afterSave handling if requested
		if ($obj->skipSaveEvents) return TRUE;

		// store position links to the positionlink table
		$banners_positionlink_handler = icms_getModuleHandler('positionlink', basename(dirname(dirname(__FILE__))), 'banners');
		$positions = $obj->getVar('positions', 'show');
		if (is_array($positions) && count($positions) > 0) {
			// delete all existing links
			$banners_positionlink_handler->deleteAll(icms_buildCriteria(array('banner_id' => $obj->getVar('banner_id'))));

			// (re)create the links
			foreach ($positions as $position) {
				$positionObj = $banners_positionlink_handler->get(0);
				$positionObj->setVar('banner_id', $obj->getVar('banner_id'));
				$positionObj->setVar('position_id', $position);
				$positionObj->store();
			}
		}

		// store visiblein selections to the visiblein table
		$banners_visiblein_handler = icms_getModuleHandler('visiblein', basename(dirname(dirname(__FILE__))), 'banners');
		$visibleins = $obj->getVar('visiblein', 'show');
		if (is_array($visibleins) && count($visibleins) > 0) {
			// delete all existing links
			$banners_visiblein_handler->deleteAll(icms_buildCriteria(array('banner_id' => $obj->getVar('banner_id'))));

			// (re)create the links
			foreach ($visibleins as $visiblein) {
				$page = explode('-', $visiblein);
				$visibleinObj = $banners_visiblein_handler->get(0);
				$visibleinObj->setVar('banner_id', $obj->getVar('banner_id'));
				$visibleinObj->setVar('module', $page[0]);
				$visibleinObj->setVar('page', $page[1]);
				$visibleinObj->store();
			}
		}

		return TRUE;
	}

	/*
	 * afterDelete event
	 *
	 * Event automatically triggered by IcmsPersistable Framework after the object is deleted
	 *
	 * @param object $obj mod_banners_Banner object
	 * @return bool TRUE
	 */
	public function afterDelete(&$obj) {
		$banners_positionlink_handler = icms_getModuleHandler('positionlink', basename(dirname(dirname(__FILE__))), 'banners');
		$banners_positionlink_handler->deleteAll(icms_buildCriteria(array('banner_id' => $obj->getVar('banner_id'))));
		$banners_visiblein_handler = icms_getModuleHandler('visiblein', basename(dirname(dirname(__FILE__))), 'banners');
		$banners_visiblein_handler->deleteAll(icms_buildCriteria(array('banner_id' => $obj->getVar('banner_id'))));

		if (is_file($obj->getImageDir(TRUE) . $obj->getVar('filename'))) {
			unlink($obj->getImageDir(TRUE) . $obj->getVar('filename'));
		}
		return TRUE;
	}
}