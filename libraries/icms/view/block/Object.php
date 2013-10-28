<?php
/**
 * ImpressCMS Block Persistable Class
 *
 * @copyright 	The ImpressCMS Project <http://www.impresscms.org>
 * @license		GNU General Public License (GPL) <http://www.gnu.org/licenses/old-licenses/gpl-2.0.html>
 * @category	ICMS
 * @package		View
 * @subpackage	Block
 * @author		Gustavo Pilla (aka nekro) <nekro@impresscms.org>
 * @version		SVN: $Id: Object.php 22431 2011-08-28 11:04:24Z phoenyx $
 */

defined('ICMS_ROOT_PATH') or die('ImpressCMS root path not defined');

/**
 * ImpressCMS Core Block Object Class
 *
 * @category	ICMS
 * @package		View
 * @subpackage	Block
 * @since		ImpressCMS 1.2
 * @author		Gustavo Pilla (aka nekro) <nekro@impresscms.org>
 */
class icms_view_block_Object extends icms_ipf_Object {

	/**
	 * Constructor for the block object
	 * @param $handler
	 */
	public function __construct(& $handler) {

		parent::__construct($handler);

		$this->quickInitVar('name', XOBJ_DTYPE_TXTBOX);
		$this->quickInitVar('bid', XOBJ_DTYPE_INT, TRUE);
		$this->quickInitVar('mid', XOBJ_DTYPE_INT, TRUE);
		$this->quickInitVar('func_num', XOBJ_DTYPE_INT);
		$this->quickInitVar('title', XOBJ_DTYPE_TXTBOX);
		$this->quickInitVar('content', XOBJ_DTYPE_TXTAREA);
		$this->quickInitVar('side', XOBJ_DTYPE_INT, TRUE);
		$this->quickInitVar('weight', XOBJ_DTYPE_INT, TRUE, FALSE, FALSE, 0);
		$this->quickInitVar('visible', XOBJ_DTYPE_INT, TRUE);
		/**
		 * @var string $block_type Holds the type of block
		 * 	S - System block
		 * 	M - block from a Module (other than system)
		 * 	C - Custom block (legacy type 'E')
		 * 	K - block cloned from another block (legacy type 'D')
		 */
		$this->quickInitVar('block_type', XOBJ_DTYPE_TXTBOX);
		/**
		 * @var	string	$c_type	The type of content in the block
		 * 	H - HTML
		 * 	P - PHP
		 * 	S - Auto Format (smilies and HTML enabled)
		 *  T - Auto Format (smilies and HTML disabled)
		 */
		$this->quickInitVar('c_type', XOBJ_DTYPE_TXTBOX, TRUE, FALSE, FALSE, "S");
		$this->quickInitVar('isactive', XOBJ_DTYPE_INT);
		$this->quickInitVar('dirname', XOBJ_DTYPE_TXTBOX);
		$this->quickInitVar('func_file', XOBJ_DTYPE_TXTBOX);
		$this->quickInitVar('show_func', XOBJ_DTYPE_TXTBOX);
		$this->quickInitVar('edit_func', XOBJ_DTYPE_TXTBOX);
		$this->quickInitVar('template', XOBJ_DTYPE_TXTBOX);
		$this->quickInitVar('bcachetime', XOBJ_DTYPE_INT);
		$this->quickInitVar('last_modified', XOBJ_DTYPE_INT);
		$this->quickInitVar('options', XOBJ_DTYPE_TXTBOX);
	}

	// The next Methods are for backward Compatibility

	public function getContent($format = 'S', $c_type = 'T') {
		switch ($format) {
			case 'S':
				if ($c_type == 'H') {
					return str_replace('{X_SITEURL}', ICMS_URL . '/', $this->getVar('content', 'n'));
				} elseif ($c_type == 'P') {
					ob_start();
					echo eval($this->getVar('content', 'n'));
					$content = ob_get_contents();
					ob_end_clean();
					return str_replace('{X_SITEURL}', ICMS_URL . '/', $content);
				} elseif ($c_type == 'S') {
					$myts =& icms_core_Textsanitizer::getInstance();
					$content = str_replace('{X_SITEURL}', ICMS_URL . '/', $this->getVar('content', 'n'));
					return $myts->displayTarea($content, 1, 1);
				} else {
					$content = str_replace('{X_SITEURL}', ICMS_URL . '/', $this->getVar('content', 'n'));
					return icms_core_DataFilter::checkVar($content, 'text', 'output');
				}
				break;

			case 'E':
				return $this->getVar('content', 'e');
				break;

			default:
				return $this->getVar('content', 'n');
				break;
		}
	}

	/**
	 * (HTML-) form for setting the options of the block
	 *
	 * @return string|FALSE $edit_form is HTML for the form, FALSE if no options defined for this block
	 */
	public function getOptions() {
		if ($this->getVar('block_type') != 'C') {
			$edit_func = $this->getVar('edit_func');
			if (!$edit_func) {
				return FALSE;
			}
			icms_loadLanguageFile($this->getVar('dirname'), 'blocks');
			include_once ICMS_ROOT_PATH . '/modules/' . $this->getVar('dirname') . '/blocks/' . $this->getVar('func_file');
			$options = explode('|', $this->getVar('options'));
			$edit_form = $edit_func($options);
			if (!$edit_form) {
				return FALSE;
			}
			return $edit_form;
		} else {
			return FALSE;
		}
	}

	/**
	 * For backward compatibility
	 *
	 * @todo improve with IPF
	 * @return unknown
	 */
	public function isCustom() {
		if ($this->getVar("block_type") == "C" || $this->getVar("block_type") == "E") {
			return TRUE;
		}
		return FALSE;
	}

	/**
	 * Builds the block
	 *
	 * @return array $block the block information array
	 *
	 * @todo improve with IPF
	 */
	public function buildBlock() {
		global $icmsConfig, $xoopsOption;
		$block = array();
		// M for module block, S for system block C for Custom
		if ($this->isCustom()) {
			// it is a custom block, so just return the contents
			$block['content'] = $this->getContent("S", $this->getVar("c_type"));
			if (empty($block['content'])) {
				return FALSE;
			}
		} else {
			// get block display function
			$show_func = $this->getVar('show_func');
			if (!$show_func) {
				return FALSE;
			}
			// Must get lang files before execution of the function.
			if (!file_exists(ICMS_ROOT_PATH . "/modules/" . $this->getVar('dirname') . "/blocks/" . $this->getVar('func_file'))) {
				return FALSE;
			} else {
				icms_loadLanguageFile($this->getVar('dirname'), 'blocks');
				include_once ICMS_ROOT_PATH . "/modules/" . $this->getVar('dirname') . "/blocks/" . $this->getVar('func_file');
				$options = explode("|", $this->getVar("options"));
				if (!function_exists($show_func)) {
					return FALSE;
				} else {
					// execute the function
					$block = $show_func($options);
					if (!$block) {
						return FALSE;
					}
				}
			}
		}
		return $block;
	}

	/**
	 * Aligns the content of a block
	 * If position is 0, content in DB is positioned
	 * before the original content
	 * If position is 1, content in DB is positioned
	 * after the original content
	 *
	 * @todo remove this? It is not found anywhere else in the core
	 */
	public function buildContent($position, $content = "", $contentdb = "") {
		if ($position == 0) {
			$ret = $contentdb . $content;
		} elseif ($position == 1) {
			$ret = $content . $contentdb;
		}
		return $ret;
	}

	/**
	 * Build Block Title
	 *
	 * @param string $originaltitle
	 * @param string $newtitle
	 * @return string
	 *
	 * @todo remove this? it is not found anywhere else in the core
	 */
	public function buildTitle($originaltitle, $newtitle = "") {
		if ($newtitle != "") {
			$ret = $newtitle;
		} else {
			$ret = $originaltitle;
		}
		return $ret;
	}

	/**
	 * Get Block Positions
	 *
	 * @param boolean $full
	 * @return array
	 *
	 * @deprecated use the handler method, instead
	 * @todo	Remove in version 1.4
	 */
	public function getBlockPositions($full = FALSE) {
		icms_core_Debug::setDeprecated('icms_view_block_Handler->getBlockPositions', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
		return $this->handler->getBlockPositions($full);
	}

	/**
	 * Load a Block
	 *
	 * @param integer $id
	 *
	 * @deprecated use the handler method, instead
	 * @todo	Remove in version 1.4
	 */
	public function load($id) {
		icms_core_Debug::setDeprecated('icms_view_block_Handler->get', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
		$this->$this->handler->get($id);
	}

	/**
	 * Save this block
	 *
	 * @return integer
	 *
	 * @deprecated use the handler method 'insert', instead
	 * @todo	Remove in version 1.4
	 */
	public function store() {
		icms_core_Debug::setDeprecated('icms_view_block_Handler->insert', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
		$this->handler->insert($this);
		return $this->getVar('bid');
	}

	/**
	 * Delete this block
	 *
	 * @return boolean
	 *
	 * @deprecated use the handler method, instead
	 * @todo	Remove in version 1.4
	 */
	public function delete() {
		icms_core_Debug::setDeprecated('icms_view_block_Handler->delete', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
		return $this->handler->delete($this);
	}

	/**
	 * Get all the blocks that match the supplied parameters
	 *
	 * @param $side   0: sideblock - left
	 *		1: sideblock - right
	 *		2: sideblock - left and right
	 *		3: centerblock - left
	 *		4: centerblock - right
	 *		5: centerblock - center
	 *		6: centerblock - left, right, center
	 * @param $groupid   groupid (can be an array)
	 * @param $visible   0: not visible 1: visible
	 * @param $orderby   order of the blocks
	 * @return array of block objects
	 *
	 * @deprecated use icms_view_block_Handler->getObjects, instead
	 * @todo	Remove in version 1.4
	 */
	public function getAllBlocksByGroup($groupid, $asobject = TRUE, $side = NULL, $visible = NULL, $orderby = "b.weight, b.bid", $isactive = 1) {
		icms_core_Debug::setDeprecated('icms_view_block_Handler->getObjects', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
		return $this->handler->getAllBlocksByGroup($groupid, $asobject, $side, $visible, $orderby, $isactive);
	}

	/**
	 * Get All Blocks
	 *
	 * @since XOOPS
	 *
	 * @param unknown_type $rettype
	 * @param unknown_type $side
	 * @param unknown_type $visible
	 * @param unknown_type $orderby
	 * @param unknown_type $isactive
	 * @return unknown
	 *
	 * @deprecated use the handler method, instead
	 * @todo	Remove in version 1.4
	 */
	public function getAllBlocks($rettype = "object", $side = NULL, $visible = NULL, $orderby = "side, weight, bid", $isactive = 1) {
		icms_core_Debug::setDeprecated('icms_view_block_Handler->getAllBlocks', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
		return $this->handler->getAllBlocks($rettype, $side, $visible, $orderby, $isactive);
	}

	/**
	 * Get Block By Module ID (mid)
	 *
	 * @since XOOPS
	 *
	 * @param integer $moduleid
	 * @param boolean $asobject
	 * @return unknown
	 *
	 * @deprecated use the handler method, instead
	 * @todo	Remove in version 1.4
	 */
	public function getByModule($moduleid, $asobject = TRUE) {
		icms_core_Debug::setDeprecated('icms_view_block_Handler->getByModule', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
		return $this->handler->getByModule($moduleid, $asobject);
	}

	/**
	 * Get All Blocks By Group and Module
	 *
	 * @since XOOPS
	 *
	 * @param integer $groupid
	 * @param integer $module_id
	 * @param boolean $toponlyblock
	 * @param boolean $visible
	 * @param string $orderby
	 * @param booelan $isactive
	 * @return unknown
	 *
	 * @deprecated use the handler method, instead
	 * @todo	Remove in version 1.4
	 */
	public function getAllByGroupModule($groupid, $module_id = '0-0', $toponlyblock = FALSE, $visible = NULL, $orderby = 'b.weight, b.bid', $isactive = 1) {
		icms_core_Debug::setDeprecated('icms_view_block_Handler->getAllByGroupModule', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
		return $this->handler->getAllByGroupModule($groupid, $module_id, $toponlyblock, $visible, $orderby, $isactive);
	}

	/**
	 * Get Non Grouped Blocks
	 *
	 * @param integer $module_id
	 * @param unknown_type $toponlyblock
	 * @param boolean $visible
	 * @param string $orderby
	 * @param boolean $isactive
	 * @return array
	 *
	 * @deprecated use the handler method, instead
	 * @todo	Remove in version 1.4
	 */
	public function getNonGroupedBlocks($module_id = 0, $toponlyblock = FALSE, $visible = NULL, $orderby = 'b.weight, b.bid', $isactive = 1) {
		icms_core_Debug::setDeprecated('icms_view_block_Handler->getNonGroupedBlocks', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
		return $this->handler->getNonGroupedBlocks($module_id, $toponlyblock, $visible, $orderby, $isactive);
	}

	/**
	 * Count Similar Blocks
	 *
	 * This method has been implemented in the block handler, because is was thought as usefull.
	 *
	 * @since XOOPS
	 *
	 * @param integer $moduleId
	 * @param integer $funcNum
	 * @param string $showFunc
	 *
	 * @return integer
	 *
	 * @deprecated use the handler method, instead
	 * @todo	Remove in version 1.4
	 */
	public function countSimilarBlocks($moduleId, $funcNum, $showFunc = NULL) {
		icms_core_Debug::setDeprecated('icms_view_block_Handler->getCountSimilarBlocks', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
		return $this->handler->getCountSimilarBlocks($moduleId, $funcNum, $showFunc);
	}

}

