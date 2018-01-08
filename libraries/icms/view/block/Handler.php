<?php
/**
 * ImpressCMS Block Persistable Class
 *
 * @copyright 	The ImpressCMS Project <http://www.impresscms.org>
 * @license		GNU General Public License (GPL) <http://www.gnu.org/licenses/old-licenses/gpl-2.0.html>
 * @category	ICMS
 * @package		Block
 * @author		Gustavo Pilla (aka nekro) <nekro@impresscms.org>
 * @version		SVN: $Id: Handler.php 21562 2011-04-20 23:53:19Z skenow $
 */

defined('ICMS_ROOT_PATH') or die('ImpressCMS root path not defined');

/**
 * ImpressCMS Core Block Object Handler Class
 *
 * @copyright	The ImpressCMS Project <http://www.impresscms.org>
 * @license		GNU GPL v2
 * @category	ICMS
 * @package		Block
 * @since		ImpressCMS 1.2
 * @author		Gustavo Pilla (aka nekro) <nekro@impresscms.org>
 */
class icms_view_block_Handler extends icms_ipf_Handler {

	private $block_positions;
	private $modules_name;

	public function __construct(& $db) {
		parent::__construct($db, 'block', 'bid', 'title', 'content', 'icms');
		$this->className = 'icms_view_block_Object';
		$this->table = $this->db->prefix('newblocks');
	}

	// The next methods are for backwards compatibility

	/**
	 * getBlockPositions
	 *
	 * @param bool $full
	 * @return array
	 */
	public function getBlockPositions($full = false) {
		if (!count($this->block_positions)) {
			// TODO: Implement IPF for block_positions
			$icms_blockposition_handler = icms::handler('icms_view_block_position');
			//			$sql = 'SELECT * FROM '.$this->db->prefix('block_positions').' ORDER BY id ASC';
			//			$result = $this->db->query($sql);
			//			while ($row = $this->db->fetchArray($result)) {
			$block_positions = $icms_blockposition_handler->getObjects();
			foreach ($block_positions as $bp) {
				$this->block_positions[$bp->getVar('id')]['pname'] = $bp->getVar('pname');
				$this->block_positions[$bp->getVar('id')]['title'] = $bp->getVar('title');
				$this->block_positions[$bp->getVar('id')]['description'] = $bp->getVar('description');
				$this->block_positions[$bp->getVar('id')]['block_default'] = $bp->getVar('block_default');
				$this->block_positions[$bp->getVar('id')]['block_type'] = $bp->getVar('block_type');
			}
		}
		if (!$full) {
			foreach ($this->block_positions as $k => $block_position) {
				$rtn[ $k ] = $block_position['pname'];
			}
		} else {
			$rtn = $this->block_positions;
		}
		return $rtn;
	}

	/**
	 * getByModule
	 *
	 * @param unknown_type $mid
	 * @param boolean $asObject
	 * @return array
	 *
	 * @see $this->getObjects($criteria, false, $asObject);
	 * @todo Rewrite all the core to dont use any more this method.
	 */
	public function getByModule($mid, $asObject = true) {
		$mid = (int) $mid;
		$criteria = new icms_db_criteria_Compo();
		$criteria->add(new icms_db_criteria_Item('mid', $mid));
		$ret = $this->getObjects($criteria, false, $asObject);
		return $ret;
	}

	/**
	 * getAllBlocks
	 *
	 * @param string $rettype
	 * @param string $side
	 * @param bool $visible
	 * @param string $orderby
	 * @param bool $isactive
	 * @return array
	 *
	 * @todo Implement IPF for block_positions.
	 * @todo Rewrite all the core to dont use any more this method.
	 */
	public function getAllBlocks($rettype = "object", $side = null, $visible = null, $orderby = "side, weight, bid", $isactive = 1) {
		$ret = array();
		$where_query = " WHERE isactive='". (int) $isactive . "'";

		if (isset($side)) {
			// get both sides in sidebox? (some themes need this)
			$tp = ($side == -2)?'L':($side == -6)?'C':'';
			if ($tp != '') {
			 	$q_side = "";
				$icms_blockposition_handler = icms::handler('icms_view_block_position');
				$criteria = new icms_db_criteria_Compo();
				$criteria->add(new icms_db_criteria_Item('block_type', $tp));
				$blockpositions = $icms_blockposition_handler->getObjects($criteria);
				foreach ($blockpositions as $bp) {
					$q_side .= "side='". (int) $bp->getVar('id') . "' OR ";
				}
				$q_side = "('" . substr($q_side, 0, strlen($q_side)-4) . "')";
			} else {
				$q_side = "side='". (int) $side . "'";
			}
			$where_query .= " AND ". $q_side;
		}

		if (isset($visible)) {
			$where_query .= " AND visible='". (int) $visible . "'";
		}
		$where_query .= " ORDER BY $orderby";
		switch ($rettype) {
			case "object":
				$sql = "SELECT * FROM " . $this->db->prefix("newblocks") . "" . $where_query;
				$result = $this->db->query($sql);
				while ($myrow = $this->db->fetchArray($result)) {
					// @todo this is causing to many SQL queries. In case this section is still needed,
					// we should switch it just like it's done in the list case
					$ret[] = $this->get($myrow['bid']);
				}
				break;

			case "list":
				$sql = "SELECT * FROM " . $this->db->prefix("newblocks") . "" . $where_query;
				$result = $this->db->query($sql);
				if ($this->db->getRowsNum($result) > 0) {
					$blockids = array();
					while ($myrow = $this->db->fetchArray($result)) {
						$blockids[] = $myrow['bid'];
					}
					$criteria = new icms_db_criteria_Compo();
					$criteria->add(new icms_db_criteria_Item('bid', '(' . implode(',', $blockids) . ')', 'IN'));
					$blocks = $this->getObjects($criteria, true, true);
					foreach ($blocks as $block) {
						$ret[$block->getVar("bid")] = $block->getVar("title");
					}
					unset($blockids, $blocks);
				}
				break;

			case "id":
				$sql = "SELECT bid FROM " . $this->db->prefix("newblocks") . "" . $where_query;
				$result = $this->db->query($sql);
				while ($myrow = $this->db->fetchArray($result)) {
					$ret[] = $myrow['bid'];
				}
				break;

			default:
				break;
		}
		return $ret;
	}

	/**
	 * getAllByGroupModule gets all blocks visible on a page, based on group permissions
	 *
	 * @param unknown_type $groupid
	 * @param unknown_type $module_id
	 * @param unknown_type $toponlyblock
	 * @param unknown_type $visible
	 * @param unknown_type $orderby
	 * @param unknown_type $isactive
	 * @return unknown
	 *
	 * @todo rewrite
	 */
	public function getAllByGroupModule($groupid, $module_id = '0-0', $toponlyblock = false, $visible = null, $orderby = 'b.weight, b.bid', $isactive = 1) {
		// TODO: use $this->getObjects($criteria);

		$isactive = (int)$isactive;
		$ret = array();
		$sql = "SELECT DISTINCT gperm_itemid FROM " . $this->db->prefix('group_permission')
			. " WHERE gperm_name = 'block_read' AND gperm_modid = '1'";
		if (is_array($groupid)) {
			$gid = array_map(create_function('$a', '$r = "\'" . intval($a) . "\'"; return($r);'), $groupid);
			$sql .= " AND gperm_groupid IN (" . implode(',', $gid) . ")";
		} else {
			if ((int) $groupid > 0) {
				$sql .= " AND gperm_groupid='" . (int) $groupid . "'";
			}
		}
		$result = $this->db->query($sql);
		$blockids = array();
		while ($myrow = $this->db->fetchArray($result)) {
			$blockids[] = $myrow['gperm_itemid'];
		}

		if (!empty($blockids)) {
			$sql = "SELECT b.* FROM " . $this->db->prefix('newblocks') . " b, " . $this->db->prefix('block_module_link')
				. " m WHERE m.block_id=b.bid";
			$sql .= " AND b.isactive='" . $isactive."'";
			if (isset($visible)) {
				$sql .= " AND b.visible='" . (int) ($visible) . "'";
			}

			$arr = explode('-', $module_id);
			$module_id = (int) $arr[0];
			$page_id = (int) $arr[1];
			if ($module_id == 0) {
				//Entire Site
				if ($page_id == 0) {
					//All pages
					$sql .= " AND m.module_id='0' AND m.page_id=0";
				} elseif ($page_id == 1) { //Top Page
					$sql .= " AND ((m.module_id='0' AND m.page_id=0) OR (m.module_id='0' AND m.page_id=1))";
				}
			} else {
				//Specific Module (including system)
				if ($page_id == 0) {
					//All pages of this module
					$sql .= " AND ((m.module_id='0' AND m.page_id=0) OR (m.module_id='$module_id' AND m.page_id=0))";
				} else {
					//Specific Page of this module
					$sql .= " AND ((m.module_id='0' AND m.page_id=0) OR (m.module_id='$module_id' AND m.page_id=0) OR (m.module_id='$module_id' AND m.page_id=$page_id))";
				}
			}

			$sql .= " AND b.bid IN (" . implode(',', $blockids) . ")";
			$sql .= " ORDER BY " . $orderby;
			$result = $this->db->query($sql);

			// old method of gathering block data. Since this could result in a whole bunch of queries, a new method was introduced
			/*while ($myrow = $this->db->fetchArray($result)) {
				$block =& $this->get($myrow['bid']);
				$ret[$myrow['bid']] =& $block;
				unset($block);
			}*/

			if ($this->db->getRowsNum($result) > 0) {
				unset($blockids);
				while ($myrow = $this->db->fetchArray($result)) {
					$blockids[] = $myrow['bid'];
				}
				$ret = $this->getMultiple($blockids);
			}
		}
		return $ret;

	}

	/**
	 * getNonGroupedBlocks
	 *
	 * @param unknown_type $module_id
	 * @param unknown_type $toponlyblock
	 * @param unknown_type $visible
	 * @param unknown_type $orderby
	 * @param unknown_type $isactive
	 * @return unknown
	 *
	 * @todo remove - this is the only instance in the core
	 */
	public function getNonGroupedBlocks($module_id = 0, $toponlyblock = false, $visible = null, $orderby = 'b.weight, b.bid', $isactive = 1) {
		$ret = array();
		$bids = array();
		$sql = "SELECT DISTINCT(bid) from " . $this->db->prefix('newblocks');
		if ($result = $this->db->query($sql)) {
			while ($myrow = $this->db->fetchArray($result)) {
				$bids[] = $myrow['bid'];
			}
		}
		$sql = "SELECT DISTINCT(p.gperm_itemid) from " . $this->db->prefix('group_permission') . " p, "
			. $this->db->prefix('groups') . " g WHERE g.groupid=p.gperm_groupid AND p.gperm_name='block_read'";
		$grouped = array();
		if ($result = $this->db->query($sql)) {
			while ($myrow = $this->db->fetchArray($result)) {
				$grouped[] = $myrow['gperm_itemid'];
			}
		}
		$non_grouped = array_diff($bids, $grouped);
		if (!empty($non_grouped)) {
			$sql = "SELECT b.* FROM " . $this->db->prefix('newblocks') . " b, "
				. $this->db->prefix('block_module_link') . " m WHERE m.block_id=b.bid";
			$sql .= " AND b.isactive='". (int) $isactive . "'";
			if (isset($visible)) {
				$sql .= " AND b.visible='" .  (int) $visible . "'";
			}
			$module_id = (int) $module_id;
			if (!empty($module_id)) {
				$sql .= " AND m.module_id IN ('0', '" . (int) $module_id . "'";
				if ($toponlyblock) {
					$sql .= ",'-1'";
				}
				$sql .= ")";
			} else {
				if ($toponlyblock) {
					$sql .= " AND m.module_id IN ('0', '-1')";
				} else {
					$sql .= " AND m.module_id='0'";
				}
			}
			$sql .= " AND b.bid IN (" . implode(',', $non_grouped) . ")";
			$sql .= " ORDER BY " . $orderby;
			$result = $this->db->query($sql);

			// old method of gathering block data. Since this could result in a whole bunch of queries, a new method was introduced
			/*while ($myrow = $this->db->fetchArray($result)) {
				$block =& $this->get($myrow['bid']);
				$ret[$myrow['bid']] =& $block;
				unset($block);
			}*/

			if ($this->db->getRowsNum($result) > 0) {
				unset($blockids);
				while ($myrow = $this->db->fetchArray($result)) {
					$blockids[] = $myrow['bid'];
				}
				$ret = $this->getMultiple($blockids);
			}
		}
		return $ret;
	}

	/**
	 * Save a icms_view_block_Object Object
	 *
	 * Overwrited Method
	 *
	 * @param unknown_type $obj
	 * @param unknown_type $force
	 * @param unknown_type $checkObject
	 * @param unknown_type $debug
	 * @return unknown
	 */
	public function insert(& $obj, $force = false, $checkObject = true, $debug = false) {
		$new = $obj->isNew();
		$obj->setVar('last_modified', time());
		$obj->setVar('isactive', true);
		if (!$new) {
			$sql = sprintf("DELETE FROM %s WHERE block_id = '%u'",
				$this->db->prefix('block_module_link'), (int) $obj->getVar('bid'));
			if (false != $force) {
				$this->db->queryF($sql);
			} else {
				$this->db->query($sql);
			}
		} else {
			icms_loadLanguageFile('system', 'blocksadmin', true);
			if ($obj->getVar('block_type') == 'K') {
				$obj->setVar('name', _AM_CLONE);
			} else {
				switch ($obj->getVar('c_type')) {
					case 'H':
						$obj->setVar('name', _AM_CUSTOMHTML);
						break;

					case 'P':
						$obj->setVar('name', _AM_CUSTOMPHP);
						break;

					case 'S':
						$obj->setVar('name', _AM_CUSTOMSMILE);
						break;

					case 'T':
						$obj->setVar('name', _AM_CUSTOMNOSMILE);
						break;
					default:
						break;
				}
			}
		}
		$status = parent::insert($obj, $force, $checkObject, $debug);
		// TODO: Make something to no query here... implement IPF for block_module_link
		$page = $obj->getVar('visiblein', 'e');
		if (!empty($page)) {
			if (is_array($obj->getVar('visiblein', 'e'))) {
				foreach ($obj->getVar('visiblein', 'e') as $bmid) {
					$page = explode('-', $bmid);
					$mid = $page[0];
					$pageid = $page[1];
					$sql = "INSERT INTO " . $this->db->prefix('block_module_link')
						. " (block_id, module_id, page_id) VALUES ('"
						. (int) $obj->getVar("bid") . "', '"
						. (int) $mid . "', '"
						. (int) $pageid . "')";
					if (false != $force) {
						$this->db->queryF($sql);
					} else {
						$this->db->query($sql);
					}
				}
			} else {
				$page = explode('-', $obj->getVar('visiblein', 'e'));
				$mid = $page[0];
				$pageid = $page[1];
				$sql = "INSERT INTO " . $this->db->prefix('block_module_link') . " (block_id, module_id, page_id) VALUES ('"
					. (int) $obj->getVar("bid") . "', '"
					. (int) $mid . "', '"
					. (int) $pageid . "')";
				if (false != $force) {
					$this->db->queryF($sql);
				} else {
					$this->db->query($sql);
				}
			}
		}
		return $status;

	}

	public function &get($id, $as_object = true, $debug = false, $criteria = false) {
		$obj = parent::get($id, $as_object, $debug, $criteria);
		$sql = "SELECT module_id, page_id FROM " . $this->db->prefix('block_module_link')
			. " WHERE block_id='" . (int) $obj->getVar('bid') . "'";
		$result = $this->db->query($sql);
		$modules = $bcustomp = array();
		while ($row = $this->db->fetchArray($result)) {
			$modules[] = (int) $row['module_id'] . '-' . (int) $row['page_id'];
		}
		$obj->setVar('visiblein', $modules);
		return $obj;
	}

	/**
	 * Get block data for multiple block ids
	 *
	 * @param array $blockids
	 *
	 * @todo can be removed together with getAllByGroupModule and getNonGroupedBlocks. (used in theme_blocks)
	 */
	private function &getMultiple($blockids) {
		$criteria = new icms_db_criteria_Compo();
		$criteria->add(new icms_db_criteria_Item('bid', '(' . implode(',', $blockids) . ')', 'IN'));
		$criteria->setSort('weight');
		$ret = $this->getObjects($criteria, true, true);
		$sql = "SELECT block_id, module_id, page_id FROM " . $this->db->prefix('block_module_link')
			. " WHERE block_id IN (" . implode(',', array_keys($ret)) . ") ORDER BY block_id";
		$result = $this->db->query($sql);
		$modules = array();
		$last_block_id = 0;
		while ($row = $this->db->fetchArray($result)) {
			$modules[] = (int)($row['module_id']) . '-' . (int)($row['page_id']);
			$ret[$row['block_id']]->setVar('visiblein', $modules);
			if ($row['block_id'] != $last_block_id) $modules = array();
			$last_block_id = $row['block_id'];
		}
		return $ret;
	}

	public function getCountSimilarBlocks($moduleId, $funcNum, $showFunc = null) {
		$funcNum = (int) $funcNum;
		$moduleId = (int) $moduleId;
		if ($funcNum < 1 || $moduleId < 1) {
			return 0;
		}
		$criteria = new icms_db_criteria_Compo();
		if (isset($showFunc)) {
			// showFunc is set for more strict comparison
			$criteria->add(new icms_db_criteria_Item('mid', $moduleId));
			$criteria->add(new icms_db_criteria_Item('func_num', $funcNum));
			$criteria->add(new icms_db_criteria_Item('show_func', $showFunc));
		} else {
			$criteria->add(new icms_db_criteria_Item('mid', $moduleId));
			$criteria->add(new icms_db_criteria_Item('func_num', $funcNum));
		}
		$count = $this->handler->getCount($criteria);
		return $count;

	}

	/**
	 * get all the blocks that match the supplied parameters
	 *
	 * This is added back in for backwards compatibility. Note - this only selects blocks form
	 * the system module
	 *
	 * @param $side   0: sideblock - left
	 *        1: sideblock - right
	 *        2: sideblock - left and right
	 *        3: centerblock - left
	 *        4: centerblock - right
	 *        5: centerblock - center
	 *        6: centerblock - left, right, center
	 * @param $groupid   groupid (can be an array)
	 * @param $visible   0: not visible 1: visible
	 * @param $orderby   order of the blocks
	 * @return 		array of block objects
	 * @deprecated	Use getObjects() instead
	 * @todo		Remove in version 1.4
	 */
	public function getAllBlocksByGroup($groupid, $asobject = TRUE, $side = NULL, $visible = NULL, $orderby = "b.weight,b.bid", $isactive = 1) {
		$ret = array();

		if (!$asobject) {
			$sql = "SELECT b.bid ";
		} else {
			$sql = "SELECT b.* ";
		}
		$sql .= "FROM " . $this->db->prefix("newblocks")
			. " b LEFT JOIN " . $this->db->prefix("group_permission")
			. " l ON l.gperm_itemid=b.bid WHERE gperm_name = 'block_read' AND gperm_modid = '1'";

		if (is_array($groupid)) {
			$sql .= " AND (l.gperm_groupid='" . (int) $groupid[0] . "'";
			$size = count($groupid);
			if ($size  > 1) {
				for ($i = 1; $i < $size; $i++) {
					$sql .= " OR l.gperm_groupid='" . (int) $groupid[$i] . "'";
				}
			}
			$sql .= ")";
		} else {
			$sql .= " AND l.gperm_groupid='" . (int) $groupid . "'";
		}
		$sql .= " AND b.isactive='" . (int) $isactive . "'";

		if (isset($side)) {
			// get both sides in sidebox? (some themes need this)
			$tp = ($side == -2)
				? 'L'
				: ($side == -6) ? 'C' : '';
			if ($tp != '') {
				$side = "";
				$s1 = "SELECT id FROM " . $this->db->prefix('block_positions') . " WHERE block_type='" . $tp . "' ORDER BY id ASC";
				$res = $this->db->query($s1);
				while ($myrow = $this->db->fetchArray($res)) {
					$side .= "side='" . (int) $myrow['id'] . "' OR ";
				}
				$side = "('" . substr($side, 0, strlen($side) - 4) . "')";
			} else {
				$side = "side='" . (int) $side . "'";
			}
			$where_query .= " AND '" . (int) $side . "'";
		}

		if (isset($visible)) {
			$sql .= " AND b.visible='" . (int) $visible . "'";
		}
		$sql .= " ORDER BY $orderby";
		$result = $this->db->query($sql);
		$added = array();
		while ($myrow = $this->db->fetchArray($result)) {
			if (!in_array($myrow['bid'], $added)) {
				if (!$asobject) {
					$ret[] = $myrow['bid'];
				} else {
					$ret[] = $this->get($myrow['bid']);
				}
				array_push($added, $myrow['bid']);
			}
		}
		return $ret;
	}
}

