<?php
/**
 *
 * Class To load plugins for modules.
 *
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @category	ICMS
 * @package		Plugins
 * @since		1.2
 * @author		ImpressCMS
 * @author		Sina Asghari (aka stranger) <pesian_stranger@users.sourceforge.net>
 * @version		$Id: Object.php 20450 2010-12-02 01:29:25Z skenow $
 */
 /**
  * Enter description here ...
  *
  */
class icms_plugins_Object {

	public $_infoArray;

	public function __construct($array) {
		$this->_infoArray = $array;
	}

	public function getItemInfo($item) {
		if (isset($this->_infoArray['items'][$item])) {
			return $this->_infoArray['items'][$item];
		} else {
			return false;
		}
	}

	public function getItemList() {
		$itemsArray = $this->_infoArray['items'];
		foreach ($itemsArray as $k=>$v) {
			$ret[$k] = $v['caption'];
		}
		return $ret;
	}

	public function getItem() {
		$ret = false;
		foreach($this->_infoArray['items'] as $k => $v) {
			$search_str = str_replace('%u', '', $v['url']);
			if (strpos($_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING'], $search_str) > 0) {
				$ret = $k;
				break;
			}
		}
		return $ret;
	}

	public function getItemIdForItem($item) {
		return $_REQUEST[$this->_infoArray['items'][$item]['request']];
	}
}


