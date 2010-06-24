<?php
/**
 * Classes responsible for managing core page objects
 *
 * @copyright	The ImpressCMS Project <http://www.impresscms.org/>
 * @license	LICENSE.txt
 * @package	core
 * @since	ImpressCMS 1.1
 * @author	modified by UnderDog <underdog@impresscms.org>
 * @author	Gustavo Pilla (aka nekro) <nekro@impresscms.org> <gpilla@nubee.com.ar>
 * @version	$Id: page.php 9662 2009-12-18 11:21:55Z nekro $
 */

defined('ICMS_ROOT_PATH') or die('ImpressCMS root path not defined');

include_once ICMS_ROOT_PATH . '/kernel/icmspersistableobject.php';

/**
 * ImpressCMS page class.
 *
 * @since	ImpressCMS 1.2
 * @author	Gustavo Pilla (aka nekro) <nekro@impresscms.org> <gpilla@nubee.com.ar>
 */
class IcmsPage extends IcmsPersistableObject {
	
	public function __construct( & $handler ){

		$this->IcmsPersistableObject( $handler );
		
		$this->quickInitVar('page_id', XOBJ_DTYPE_INT);
	        $this->quickInitVar('page_moduleid', XOBJ_DTYPE_INT, true);
        	$this->quickInitVar('page_title', XOBJ_DTYPE_TXTBOX, true);
	        $this->quickInitVar('page_url', XOBJ_DTYPE_TXTBOX, true);
	        $this->quickInitVar('page_status', XOBJ_DTYPE_INT, true);

	}
	
}

/**
 * ImpressCMS page handler class.
 *
 * @since	ImpressCMS 1.2
 * @author	Gustavo Pilla (aka nekro) <nekro@impresscms.org> <gpilla@nubee.com.ar>
 */
class IcmsPageHandler extends IcmsPersistableObjectHandler {
	
	public function __construct( & $db ){
		$this->IcmsPersistableObjectHandler($db, 'page' ,'page_id' ,'page_title', '' , 'icms');
		$this->table = $db->prefix('icmspage');
	}
	
	public function getList( $criteria = null, $limit = 0, $start = 0, $debug=false){
    		$rtn = array();
        	$pages =& $this->getObjects( $criteria, true );
        	foreach( $pages as $page ) {
			$rtn[$page->getVar('page_moduleid').'-'.$page->getVar('page_id')] = $page->getVar('page_title');
		}
		return $rtn;
	}
	
	public function getPageSelOptions($value=null){
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
    		$pages =& $this->getObjects($criteria);
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
    	$pages =& $this->getObjects($criteria);
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

/**
 * XOOPS page handler class.
 *
 * @todo 	Remove this class after ImpressCMS 1.5
 * @deprecated 
 */
class XoopsPage extends IcmsPage { /* For backwards compatibility */ }

/**
 * XOOPS page handler class.
 * 
 * @todo 	Remove this class after ImpressCMS 1.5
 * @deprecated 
 */
class XoopsPageHandler extends IcmsPageHandler { /* For backwards compatibility */ }

?>
