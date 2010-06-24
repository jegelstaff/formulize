<?php
/**
* ImpressCMS Ratings
*
* @copyright	The ImpressCMS Project http://www.impresscms.org/
* @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
* @package		Administration
* @since		1.2
* @author		Sina Asghari (aka stranger) <pesian_stranger@users.sourceforge.net>
* @version		$Id$
*/

if (! defined ( "ICMS_ROOT_PATH" ))
	die ( "ImpressCMS root path not defined" );

include_once ICMS_ROOT_PATH . "/kernel/icmspersistableobject.php";
include_once ICMS_ROOT_PATH . "/class/plugins.php";

class SystemRating extends IcmsPersistableObject {
	
	public $_modulePlugin=false;
	
	function SystemRating(&$handler) {
		$this->IcmsPersistableObject($handler);
		
		$this->quickInitVar('ratingid', XOBJ_DTYPE_INT, true);
		$this->quickInitVar('dirname', XOBJ_DTYPE_TXTBOX, true, _CO_ICMS_RATING_DIRNAME);
		$this->quickInitVar('item', XOBJ_DTYPE_TXTBOX, true, _CO_ICMS_RATING_ITEM);
		$this->quickInitVar('itemid', XOBJ_DTYPE_INT, true, _CO_ICMS_RATING_ITEMID);
		$this->quickInitVar('uid', XOBJ_DTYPE_INT, true, _CO_ICMS_RATING_UID);
		$this->quickInitVar('date', XOBJ_DTYPE_LTIME, true, _CO_ICMS_RATING_DATE);
		$this->quickInitVar('rate', XOBJ_DTYPE_INT, true, _CO_ICMS_RATING_RATE);

		$this->initNonPersistableVar('name', XOBJ_DTYPE_TXTBOX, 'user', _CO_ICMS_RATING_NAME);
		$this->setControl('dirname', array( 'handler' => 'rating', 'method' => 'getModuleList', 'onSelect' => 'submit'));
		$this->setControl('item', array( 'object' => &$this, 'method' => 'getItemList'));
		$this->setControl('uid', 'user');
		$this->setControl('rate', array( 'handler' => 'rating', 'method' => 'getRateList'));
	}
	
	function getVar($key, $format = 's') {
		if ($format == 's' && in_array ( $key, array ( ) )) {
			return call_user_func ( array ($this, $key ) );
		}
		return parent::getVar ( $key, $format );
	}
	
	function name() {
		$ret = icms_getLinkedUnameFromId($this->getVar('uid', 'e'), true, array());

		return $ret;
	}

	function dirname() {
		global $icms_rating_handler;
		$moduleArray = $icms_rating_handler->getModuleList();
		return $moduleArray[$this->getVar('dirname', 'n')];
	}

	function getItemList() {
		$plugin = $this->getModulePlugin();
		return $plugin->getItemList();
	}

	function getItemValue() {
		$moduleUrl = XOOPS_URL . '/modules/' . $this->getVar('dirname', 'n') . '/';
		$plugin = $this->getModulePlugin();
		$pluginItemInfo = $plugin->getItemInfo($this->getVar('item'));
		if (!$pluginItemInfo) {
			return '';
		}
		$itemPath = sprintf($pluginItemInfo['url'], $this->getVar('itemid'));
		$ret = '<a href="' . $moduleUrl . $itemPath . '">' . $pluginItemInfo['caption'] . '</a>';
		return $ret;
	}

	function getRateValue() {
		return $this->getVar('rate');
	}
	function getUnameValue() {
		return icms_getLinkedUnameFromId($this->getVar('uid'));
	}

	function getModulePlugin() {
		if (!$this->_modulePlugin) {
			global $icms_rating_handler;
			$this->_modulePlugin = $icms_rating_handler->pluginsObject->getPlugin('rating', $this->getVar('dirname', 'n'));
		}
		return $this->_modulePlugin;
	}
}

class SystemRatingHandler extends IcmsPersistableObjectHandler {
	
	public $_rateOptions=array();
	public $_moduleList=false;
	public $pluginsObject;
	
	function SystemRatingHandler($db) {
		$this->IcmsPersistableObjectHandler ( $db, 'rating', 'ratingid', 'rate', '', 'system' );
		$this->generalSQL = 'SELECT * FROM ' . $this->table . ' AS ' . $this->_itemname . ' INNER JOIN ' . $this->db->prefix('users') . ' AS user ON ' . $this->_itemname . '.uid=user.uid';

		$this->_rateOptions[1] = 1;
		$this->_rateOptions[2] = 2;
		$this->_rateOptions[3] = 3;
		$this->_rateOptions[4] = 4;
		$this->_rateOptions[5] = 5;

		$this->pluginsObject = new IcmsPluginsHandler();
	}
	
	function getModuleList() {
		if (!$this->_moduleList) {
			$moduleArray = $this->pluginsObject->getPluginsArray('rating');
			$this->_moduleList[0] = _CO_ICMS_MAKE_SELECTION;
			foreach ($moduleArray as $k=>$v) {
				$this->_moduleList[$k] = $v;
			}
		}
		return $this->_moduleList;
	}

	function getRateList() {
		return $this->_rateOptions;
	}

	function getRatingAverageByItemId($itemid, $dirname, $item) {
		$sql = "SELECT AVG(rate), COUNT(ratingid) FROM " . $this->table . " WHERE itemid=$itemid AND dirname='$dirname' AND item='$item' GROUP BY itemid";
		$result = $this->db->query($sql);
		if (!$result) {
			return 0;
		}
		list($average, $sum) = $this->db->fetchRow($result);
		$ret['average'] = isset($average) ? $average : 0;
		$ret['sum'] = isset($sum) ? $sum : 0;
		return $ret;
	}
	function already_rated($item, $itemid, $dirname, $uid){

		$criteria = new CriteriaCompo();
		$criteria->add(new Criteria('item',$item ));
		$criteria->add(new Criteria('itemid',$itemid ));
		$criteria->add(new Criteria('dirname', $dirname));
		$criteria->add(new Criteria('user.uid', $uid));

		$ret = $this->getObjects($criteria);

		if(!$ret){
			return false;
		}else{
			return $ret[0];
		}
	}
}

?>