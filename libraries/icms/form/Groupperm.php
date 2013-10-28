<?php

/**
* Creates a group permission form
*
* @copyright	http://www.impresscms.org/ The ImpressCMS Project
* @license		LICENSE.txt
* @category		ICMS
* @package		Form
* @version		SVN: $Id: Groupperm.php 20322 2010-11-04 03:57:45Z skenow $
*/

defined('ICMS_ROOT_PATH') or die('ImpressCMS root path not defined');

/**
 * Renders a form for setting module specific group permissions
 *
 * @author		Kazumi Ono <onokazu@myweb.ne.jp>
 * @category	ICMS
 * @package		Form
 *
 */
class icms_form_Groupperm extends icms_form_Base {
	/**
	 * Module ID
	 *
	 * @var int
	 */
	private $_modid;
	/**
	 * Tree structure of items
	 *
	 * @var array
	 */
	private $_itemTree;
	/**
	 * Name of permission
	 *
	 * @var string
	 */
	private $_permName;
	/**
	 * Description of permission
	 *
	 * @var string
	 */
	private $_permDesc;

	/**
	 * Constructor
	 */
	public function __construct($title, $modid, $permname, $permdesc, $url = "") {
		parent::__construct($title, 'groupperm_form', ICMS_URL . '/modules/system/admin/groupperm.php', 'post');
		$this->_modid = (int) $modid;
		$this->_permName = $permname;
		$this->_permDesc = $permdesc;
		$this->addElement(new icms_form_elements_Hidden('modid', $this->_modid));
		if ($url != "") {
			$this->addElement(new icms_form_elements_Hidden('redirect_url', $url));
		}
	}

	/**
	 * Adds an item to which permission will be assigned
	 *
	 * @param string $itemName
	 * @param int $itemId
	 * @param int $itemParent
	 * @access public
	 */
	public function addItem($itemId, $itemName, $itemParent = 0) {
		$this->_itemTree[$itemParent]['children'][] = $itemId;
		$this->_itemTree[$itemId]['parent'] = $itemParent;
		$this->_itemTree[$itemId]['name'] = $itemName;
		$this->_itemTree[$itemId]['id'] = $itemId;
	}

	/**
	 * Loads all child ids for an item to be used in javascript
	 *
	 * @param int $itemId
	 * @param array $childIds
	 * @access private
	 */
	private function _loadAllChildItemIds($itemId, & $childIds) {
		if (!empty ($this->_itemTree[$itemId]['children'])) {
			$first_child = $this->_itemTree[$itemId]['children'];
			foreach ($first_child as $fcid) {
				array_push($childIds, $fcid);
				if (!empty ($this->_itemTree[$fcid]['children'])) {
					foreach ($this->_itemTree[$fcid]['children'] as $_fcid) {
						array_push($childIds, $_fcid);
						$this->_loadAllChildItemIds($_fcid, $childIds);
					}
				}
			}
		}
	}

	/**
	 * Renders the form
	 *
	 * @return string
	 * @access public
	 */
	public function render() {
		// load all child ids for javascript codes
		foreach (array_keys($this->_itemTree) as $item_id) {
			$this->_itemTree[$item_id]['allchild'] = array ();
			$this->_loadAllChildItemIds($item_id, $this->_itemTree[$item_id]['allchild']);
		}
		$gperm_handler = icms::handler('icms_member_groupperm');
		$member_handler = icms::handler('icms_member');
		$glist = & $member_handler->getGroupList();
		foreach (array_keys($glist) as $i) {
			// get selected item id(s) for each group
			$selected = $gperm_handler->getItemIds($this->_permName, $i, $this->_modid);
			$ele = new icms_form_elements_Groupperm($glist[$i], 'perms[' . $this->_permName . ']', $i, $selected);
			$ele->setOptionTree($this->_itemTree);
			$this->addElement($ele);
			unset ($ele);
		}
		$tray = new icms_form_elements_Tray('');
		$tray->addElement(new icms_form_elements_Button('', 'submit', _SUBMIT, 'submit'));
		$tray->addElement(new icms_form_elements_Button('', 'reset', _CANCEL, 'reset'));
		$this->addElement($tray);
		$ret = '<h4>' . $this->getTitle() . '</h4>' . $this->_permDesc . '<br />';
		$ret .= "<form name='" . $this->getName()
			. "' id='" . $this->getName()
			. "' action='" . $this->getAction()
			. "' method='" . $this->getMethod()
			. "'" . $this->getExtra()
			. ">\n<table width='100%' class='outer' cellspacing='1' valign='top'>\n";
		$elements = $this->getElements();
		$hidden = '';
		foreach (array_keys($elements) as $i) {
			if (!is_object($elements[$i])) {
				$ret .= $elements[$i];
			}
			elseif (!$elements[$i]->isHidden()) {
				$ret .= "<tr valign='top' align='" . _GLOBAL_LEFT . "'><td class='head'>" . $elements[$i]->getCaption();
				if ($elements[$i]->getDescription() != '') {
					$ret .= '<br /><br /><span style="font-weight: normal;">' . $elements[$i]->getDescription() . '</span>';
				}
				$ret .= "</td>\n<td class='even'>\n" . $elements[$i]->render() . "\n</td></tr>\n";
			} else {
				$hidden .= $elements[$i]->render();
			}
		}
		$ret .= "</table>$hidden</form>";
		$ret .= $this->renderValidationJS(true);
		return $ret;
	}

	/**
	 * This method is required - this method in the parent (abstract) class is also abstract
	 * @param string $extra
	 */
	public function insertBreak($extra = null) {
	}
}
