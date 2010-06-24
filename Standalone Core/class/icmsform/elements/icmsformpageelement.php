<?php
/**
* Form control creating a page element for an object derived from IcmsPersistableObject
*
* @copyright	The ImpressCMS Project http://www.impresscms.org/
* @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
* @package		IcmsPersistableObject
* @since		  1.1
* @author		  marcan <marcan@impresscms.org>
* @version		$Id: icmsformpageelement.php 8718 2009-05-03 17:07:43Z sato $
*/

class IcmsFormPageElement extends XoopsFormElementTray {
	
	public function __construct($object, $key) {
		$this->XoopsFormElementTray(_AM_VISIBLEIN , ' ', $key . '_password_tray');
    	$icms_page_handler = & xoops_gethandler('page');
		$visible_label = new XoopsFormLabel('', '<select name="visiblein[]" id="visiblein[]" multiple="multiple" size="10">'.$this->getPageSelOptions($icms_page_handler, $object->getVar('visiblein')).'</select>');
		$this->addElement($visible_label);
  	}
  	
 	private function getPageSelOptions($icmsObj, $value=null){
    	if (!is_array($value)){
    		$value = array($value);
    	}
    	$module_handler =& xoops_gethandler('module');
    	$criteria = new CriteriaCompo(new Criteria('hasmain', 1));
    	$criteria->add(new Criteria('isactive', 1));
    	$module_list =& $module_handler->getObjects($criteria);
    	$mods = '';
    	foreach ($module_list as $module){
    		$mods .= '<optgroup label="'.$module->getVar('name').'">';
    		$criteria = new CriteriaCompo(new Criteria('page_moduleid', $module->getVar('mid')));
    		$criteria->add(new Criteria('page_status', 1));
    		$pages = $icmsObj->getObjects($criteria);
    		$sel = '';
    		if (in_array($module->getVar('mid').'-0',$value)){
    			$sel = ' selected=selected';
    		}
    		$mods .= '<option value="'.$module->getVar('mid').'-0"'.$sel.'>'._AM_ALLPAGES.'</option>';
    		foreach ($pages as $page){
    			$sel = '';
    			if (in_array($module->getVar('mid').'-'.$page->getVar('page_id'),$value)){
    				$sel = ' selected=selected';
    			}
    			$mods .= '<option value="'.$module->getVar('mid').'-'.$page->getVar('page_id').'"'.$sel.'>'.$page->getVar('page_title').'</option>';
    		}
    		$mods .= '</optgroup>';
    	}

    	$module = $module_handler->get(1);
    	$criteria = new CriteriaCompo(new Criteria('page_moduleid', 1));
    	$criteria->add(new Criteria('page_status', 1));
    	$pages = $icmsObj->getObjects($criteria);
    	$cont = '';
    	if (count($pages) > 0){
    		$cont = '<optgroup label="'.$module->getVar('name').'">';
    		$sel = '';
    		if (in_array($module->getVar('mid').'-0',$value)){
    			$sel = ' selected=selected';
    		}
    		$cont .= '<option value="'.$module->getVar('mid').'-0"'.$sel.'>'._AM_ALLPAGES.'</option>';
    		foreach ($pages as $page){
    			$sel = '';
    			if (in_array($module->getVar('mid').'-'.$page->getVar('page_id'),$value)){
    				$sel = ' selected=selected';
    			}
    			$cont .= '<option value="'.$module->getVar('mid').'-'.$page->getVar('page_id').'"'.$sel.'>'.$page->getVar('page_title').'</option>';
    		}
    		$cont .= '</optgroup>';
    	}
    	$sel = $sel1 = '';
    	if (in_array('0-1',$value)){
    		$sel = ' selected=selected';
    	}
    	if (in_array('0-0',$value)){
    		$sel1 = ' selected=selected';
    	}
    	$ret = '<option value="0-1"'.$sel.'>'._AM_TOPPAGE.'</option><option value="0-0"'.$sel1.'>'._AM_ALLPAGES.'</option>';
    	$ret .= $cont.$mods;
    	
        return $ret;
    }
  	
}

?>