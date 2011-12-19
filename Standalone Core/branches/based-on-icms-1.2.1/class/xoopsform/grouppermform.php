<?php 
/**
* Creates a group permission form
*
* @copyright	http://www.xoops.org/ The XOOPS Project
* @copyright	XOOPS_copyrights.txt
* @copyright	http://www.impresscms.org/ The ImpressCMS Project
* @license	LICENSE.txt
* @package	XoopsForms
* @since	XOOPS
* @author	http://www.xoops.org The XOOPS Project
* @author	modified by UnderDog <underdog@impresscms.org>
* @version	$Id: grouppermform.php 8662 2009-05-01 09:04:30Z pesianstranger $
*/

/**
 * @package     kernel
 * @subpackage  form
 * 
 * @author	    Kazumi Ono	<onokazu@xoops.org>
 * @copyright	copyright (c) 2000-2003 XOOPS.org
 */
 
if (!defined('ICMS_ROOT_PATH')) {
	die("ImpressCMS root path not defined");
}

require_once ICMS_ROOT_PATH . '/class/xoopsform/formelement.php';
require_once ICMS_ROOT_PATH . '/class/xoopsform/formhidden.php';
require_once ICMS_ROOT_PATH . '/class/xoopsform/formbutton.php';
require_once ICMS_ROOT_PATH . '/class/xoopsform/formelementtray.php';
require_once ICMS_ROOT_PATH . '/class/xoopsform/form.php';


/**
 * Renders a form for setting module specific group permissions
 * 
 * @author Kazumi Ono <onokazu@myweb.ne.jp> 
 * @copyright copyright (c) 2000-2003 XOOPS.org
 * @package kernel
 * @subpackage form
 */
class XoopsGroupPermForm extends XoopsForm
{
    /**
     * Module ID
     * 
     * @var int 
     */
    var $_modid;
    /**
     * Tree structure of items
     * 
     * @var array 
     */
    var $_itemTree;
    /**
     * Name of permission
     * 
     * @var string 
     */
    var $_permName;
    /**
     * Description of permission
     * 
     * @var string 
     */
    var $_permDesc;

    /**
     * Constructor
     */
    function XoopsGroupPermForm($title, $modid, $permname, $permdesc, $url = "")
    {
        $this->XoopsForm($title, 'groupperm_form', ICMS_URL . '/modules/system/admin/groupperm.php', 'post');
        $this->_modid = intval($modid);
        $this->_permName = $permname;
        $this->_permDesc = $permdesc;
        $this->addElement(new XoopsFormHidden('modid', $this->_modid));
        if ($url != "") {
            $this->addElement(new XoopsFormHidden('redirect_url', $url));
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
    function addItem($itemId, $itemName, $itemParent = 0)
    {
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
    function _loadAllChildItemIds($itemId, &$childIds)
    {
        if (!empty($this->_itemTree[$itemId]['children'])) {
            $first_child = $this->_itemTree[$itemId]['children'];
            foreach ($first_child as $fcid) {
                array_push($childIds, $fcid);
                if (!empty($this->_itemTree[$fcid]['children'])) {
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
    function render()
    { 
        // load all child ids for javascript codes
        foreach (array_keys($this->_itemTree)as $item_id) {
            $this->_itemTree[$item_id]['allchild'] = array();
            $this->_loadAllChildItemIds($item_id, $this->_itemTree[$item_id]['allchild']);
        }
        $gperm_handler =& xoops_gethandler('groupperm');
        $member_handler =& xoops_gethandler('member');
        $glist =& $member_handler->getGroupList();
        foreach (array_keys($glist) as $i) {
            // get selected item id(s) for each group
            $selected = $gperm_handler->getItemIds($this->_permName, $i, $this->_modid);
            $ele = new XoopsGroupFormCheckBox($glist[$i], 'perms[' . $this->_permName . ']', $i, $selected);
            $ele->setOptionTree($this->_itemTree);
            $this->addElement($ele);
            unset($ele);
        } 
        $tray = new XoopsFormElementTray('');
        $tray->addElement(new XoopsFormButton('', 'submit', _SUBMIT, 'submit'));
        $tray->addElement(new XoopsFormButton('', 'reset', _CANCEL, 'reset'));
        $this->addElement($tray);
        $ret = '<h4>' . $this->getTitle() . '</h4>' . $this->_permDesc . '<br />';
        $ret .= "<form name='" . $this->getName() . "' id='" . $this->getName() . "' action='" . $this->getAction() . "' method='" . $this->getMethod() . "'" . $this->getExtra() . ">\n<table width='100%' class='outer' cellspacing='1' valign='top'>\n";
        $elements = $this->getElements();
        $hidden = '';
        foreach (array_keys($elements) as $i) {
            if (!is_object($elements[$i])) {
                $ret .= $elements[$i];
            } elseif (!$elements[$i]->isHidden()) {
                $ret .= "<tr valign='top' align='"._GLOBAL_LEFT."'><td class='head'>" . $elements[$i]->getCaption();
                if ($elements[$i]->getDescription() != '') {
                    $ret .= '<br /><br /><span style="font-weight: normal;">' . $elements[$i]->getDescription() . '</span>';
                }
                $ret .= "</td>\n<td class='even'>\n" . $elements[$i]->render() . "\n</td></tr>\n";
            } else {
                $hidden .= $elements[$i]->render();
            }
        }
        $ret .= "</table>$hidden</form>";
        $ret .= $this->renderValidationJS( true );
        return $ret;
    }
}





/**
 * Renders checkbox options for a group permission form
 * 
 * @author Kazumi Ono <onokazu@myweb.ne.jp> 
 * @copyright copyright (c) 2000-2003 XOOPS.org
 * @package kernel
 * @subpackage form
 */
class XoopsGroupFormCheckBox extends XoopsFormElement
{
    /**
     * Pre-selected value(s)
     * 
     * @var array;
     */
    var $_value = array();
    /**
     * Group ID
     * 
     * @var int 
     */
    var $_groupId;
    /**
     * Option tree
     * 
     * @var array 
     */
    var $_optionTree;

    /**
     * Constructor
     */
    function XoopsGroupFormCheckBox($caption, $name, $groupId, $values = null)
    {
        $this->setCaption($caption);
        $this->setName($name);
        if (isset($values)) {
            $this->setValue($values);
        }
        $this->_groupId = $groupId;
    }

    /**
     * Sets pre-selected values
     * 
     * @param mixed $value A group ID or an array of group IDs
     * @access public 
     */
    function setValue($value)
    {
        if (is_array($value)) {
            foreach ($value as $v) {
                $this->setValue($v);
            }
        } else {
            $this->_value[] = $value;
        }
    }

    /**
     * Sets the tree structure of items
     * 
     * @param array $optionTree 
     * @access public 
     */
    function setOptionTree(&$optionTree)
    {
        $this->_optionTree =& $optionTree;
    }

    /**
     * Renders checkbox options for this group
     * 
     * @return string 
     * @access public 
     */
    function render()
    {
    	$ele_name = $this->getName();
		$ret = '<table class="outer"><tr><td class="odd"><table><tr>';
		$cols = 1;
		foreach ($this->_optionTree[0]['children'] as $topitem) {
			if ($cols > 4) {
				$ret .= '</tr><tr>';
				$cols = 1;
			}
			$tree = '<td valign="top">';
			$prefix = '';
			$this->_renderOptionTree($tree, $this->_optionTree[$topitem], $prefix);
			$ret .= $tree.'</td>';
			$cols++;
		}
		$ret .= '</tr></table></td><td class="even" valign="top">';
		foreach (array_keys($this->_optionTree) as $id) {
			if (!empty($id)) {
				$option_ids[] = "'".$ele_name.'[groups]['.$this->_groupId.']['.$id.']'."'";
			}
		}
		$checkallbtn_id = $ele_name.'[checkallbtn]['.$this->_groupId.']';
		$option_ids_str = implode(', ', $option_ids);
		$ret .= _ALL." <input id=\"".$checkallbtn_id."\" type=\"checkbox\" value=\"\" onclick=\"var optionids = new Array(".$option_ids_str."); xoopsCheckAllElements(optionids, '".$checkallbtn_id."');\" />";
		$ret .= '</td></tr></table>';
		return $ret;
    } 

    /**
     * Renders checkbox options for an item tree
     * 
     * @param string $tree 
     * @param array $option 
     * @param string $prefix 
     * @param array $parentIds 
     * @access private 
     */
    function _renderOptionTree(&$tree, $option, $prefix, $parentIds = array())
    {
    	$ele_name = $this->getName();
      $tree .= $prefix . "<input type=\"checkbox\" name=\"" . $ele_name . "[groups][" . $this->_groupId . "][" . $option['id'] . "]\" id=\"" . $ele_name . "[groups][" . $this->_groupId . "][" . $option['id'] . "]\" onclick=\""; 
      // If there are parent elements, add javascript that will
      // make them selecteded when this element is checked to make
      // sure permissions to parent items are added as well.
      foreach ($parentIds as $pid) {
          $parent_ele = $ele_name . '[groups][' . $this->_groupId . '][' . $pid . ']';
          $tree .= "var ele = xoopsGetElementById('" . $parent_ele . "'); if(ele.checked != true) {ele.checked = this.checked;}";
      } 
      // If there are child elements, add javascript that will
      // make them unchecked when this element is unchecked to make
      // sure permissions to child items are not added when there
      // is no permission to this item.
      foreach ($option['allchild'] as $cid) {
          $child_ele = $ele_name . '[groups][' . $this->_groupId . '][' . $cid . ']';
          $tree .= "var ele = xoopsGetElementById('" . $child_ele . "'); if(this.checked != true) {ele.checked = false;}";
      } 
      $tree .= '" value="1"';
      if (in_array($option['id'], $this->_value)) {
          $tree .= ' checked="checked"';
      } 
      $tree .= " />" . $option['name'] . "<input type=\"hidden\" name=\"" . $ele_name . "[parents][" . $option['id'] . "]\" value=\"" . implode(':', $parentIds). "\" /><input type=\"hidden\" name=\"" . $ele_name . "[itemname][" . $option['id'] . "]\" value=\"" . htmlspecialchars($option['name']). "\" /><br />\n";
      if (isset($option['children'])) {
        foreach ($option['children'] as $child) {
          array_push($parentIds, $option['id']);
          $this->_renderOptionTree($tree, $this->_optionTree[$child], $prefix . '&nbsp;-', $parentIds);
        }
      }
    }
}


?>