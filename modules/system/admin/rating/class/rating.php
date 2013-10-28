<?php
/**
 * ImpressCMS Ratings
 *
 * @copyright	The ImpressCMS Project http://www.impresscms.org/
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @package		System
 * @subpackage	Ratings
 * @since		1.2
 * @author		Sina Asghari (aka stranger) <pesian_stranger@users.sourceforge.net>
 * @version		SVN: $Id: rating.php 21840 2011-06-23 14:44:34Z phoenyx $
 */

/**
 * Rating object
 * @package		System
 * @subpackage	Ratings
 */
class SystemRating extends icms_ipf_Object {

	/** */
	public $_modulePlugin = FALSE;

	/**
	 * Constructor for the ratings object
	 * @param object $handler
	 */
	public function __construct(&$handler) {
		parent::__construct($handler);

		$this->quickInitVar('ratingid', XOBJ_DTYPE_INT, TRUE);
		$this->quickInitVar('dirname', XOBJ_DTYPE_TXTBOX, TRUE, _CO_ICMS_RATING_DIRNAME);
		$this->quickInitVar('item', XOBJ_DTYPE_TXTBOX, TRUE, _CO_ICMS_RATING_ITEM);
		$this->quickInitVar('itemid', XOBJ_DTYPE_INT, TRUE, _CO_ICMS_RATING_ITEMID);
		$this->quickInitVar('uid', XOBJ_DTYPE_INT, TRUE, _CO_ICMS_RATING_UID);
		$this->quickInitVar('date', XOBJ_DTYPE_LTIME, TRUE, _CO_ICMS_RATING_DATE);
		$this->quickInitVar('rate', XOBJ_DTYPE_INT, TRUE, _CO_ICMS_RATING_RATE);

		$this->initNonPersistableVar('name', XOBJ_DTYPE_TXTBOX, 'user', _CO_ICMS_RATING_NAME);
		$this->setControl('dirname', array('method' => 'getModuleList', 'onSelect' => 'submit'));
		$this->setControl('item', array('object' => &$this, 'method' => 'getItemList'));
		$this->setControl('uid', 'user');
		$this->setControl('rate', array('method' => 'getRateList'));
	}

	/**
	 * Custom accessors for properties
	 * 
	 * @param	string $key
	 * @param	string $format
	 * @return	mixed
	 */
	public function getVar($key, $format = 's') {
		if ($format == 's' && in_array($key, array())) {
			return call_user_func(array($this, $key));
		}
		return parent::getVar($key, $format);
	}

	/**
	 * Retrieve the username associated with a rating 
	 * @return	string
	 */
	public function name() {
		return icms_member_user_Handler::getUserLink($this->getVar('uid', 'e'), TRUE, array());
	}

	/**
	 * Accessor for the dirname property
	 * @return	string
	 */
	public function dirname() {
		$moduleArray = $this->handler->getModuleList();
		return $moduleArray[$this->getVar('dirname', 'n')];
	}

	/**
	 * Enter description here ...
	 * @return
	 */
	public function getItemList() {
		$plugin = $this->getModulePlugin();
		return $plugin->getItemList();
	}

	/**
	 * Retrieve the value of the rating as a link
	 * @return	string
	 */
	public function getItemValue() {
		$moduleUrl = ICMS_MODULES_URL . '/' . $this->getVar('dirname', 'n') . '/';
		$plugin = $this->getModulePlugin();
		$pluginItemInfo = $plugin->getItemInfo($this->getVar('item'));
		if (!$pluginItemInfo) {
			return '';
		}
		$itemPath = sprintf($pluginItemInfo['url'], $this->getVar('itemid'));
		$ret = '<a href="' . $moduleUrl . $itemPath . '">' . $pluginItemInfo['caption'] . '</a>';
		return $ret;
	}

	/**
	 * Accessor for the rate property
	 * @return	int
	 */
	public function getRateValue() {
		return $this->getVar('rate');
	}
	
	/**
	 * Create a link to the user profile associated with the rating
	 * 
	 * @return	string
	 * @see	icms_member_user_Handler::getUserLink
	 */
	public function getUnameValue() {
		return icms_member_user_Handler::getUserLink($this->getVar('uid'));
	}

	/**
	 * Enter description here ...
	 */
	public function getModulePlugin() {
		if (!$this->_modulePlugin) {
			$this->_modulePlugin = $this->handler->pluginsObject->getPlugin('rating', $this->getVar('dirname', 'n'));
		}
		return $this->_modulePlugin;
	}
}

/**
 * Handler for the ratings object
 * @package		System
 * @subpackage	Ratings
 */
class SystemRatingHandler extends icms_ipf_Handler {

	public $_rateOptions = array();
	public $_moduleList = FALSE;
	public $pluginsObject;

	/**
	 * Constructor for the ratings handler
	 * 
	 * @param object $db
	 */
	public function __construct($db) {
		parent::__construct($db, 'rating', 'ratingid', 'rate', '', 'system');
		$this->generalSQL = 'SELECT * FROM ' . $this->table . ' AS ' . $this->_itemname . ' INNER JOIN ' . $this->db->prefix('users') . ' AS user ON ' . $this->_itemname . '.uid=user.uid';

		$this->_rateOptions[1] = 1;
		$this->_rateOptions[2] = 2;
		$this->_rateOptions[3] = 3;
		$this->_rateOptions[4] = 4;
		$this->_rateOptions[5] = 5;

		$this->pluginsObject = new icms_plugins_Handler();
	}

	/**
	 * Retrieve a list of modules enabling ratings
	 * @return	array
	 */
	public function getModuleList() {
		if (!$this->_moduleList) {
			$moduleArray = $this->pluginsObject->getPluginsArray('rating');
			$this->_moduleList[0] = _CO_ICMS_MAKE_SELECTION;
			foreach ($moduleArray as $k=>$v) {
				$this->_moduleList[$k] = $v;
			}
		}
		return $this->_moduleList;
	}

	/**
	 * Accessor for the rate property
	 * @return	array	Rating options
	 */
	public function getRateList() {
		return $this->_rateOptions;
	}

	/**
	 * Get the average rating for an item
	 * 
	 * @param int $itemid
	 * @param str $dirname
	 * @param str $item
	 * @return	int|array	0 if there is no rating; an array containing the average and the total ratings for the item
	 */
	public function getRatingAverageByItemId($itemid, $dirname, $item) {
		$itemid = (int) $itemid;
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
	
	/**
	 * Determine if a user has already rated an item
	 * 
	 * @param	str	$item
	 * @param	int	$itemid
	 * @param	str	$dirname
	 * @param	int	$uid
	 * @return	bool|array
	 */
	public function already_rated($item, $itemid, $dirname, $uid) {

		$criteria = new icms_db_criteria_Compo();
		$criteria->add(new icms_db_criteria_Item('item', $item));
		$criteria->add(new icms_db_criteria_Item('itemid', (int) $itemid));
		$criteria->add(new icms_db_criteria_Item('dirname', $dirname));
		$criteria->add(new icms_db_criteria_Item('user.uid', (int) $uid));

		$ret = $this->getObjects($criteria);

		if (!$ret) {
			return FALSE;
		} else {
			return $ret[0];
		}
	}
}
