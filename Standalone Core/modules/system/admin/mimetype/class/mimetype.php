<?php
/**
* ImpressCMS Mimetypes
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
icms_loadLanguageFile('system', 'mimetype', true);

class SystemMimetype extends IcmsPersistableObject {
	
	public $content = false;
	
	function SystemMimetype(&$handler) {
		$this->IcmsPersistableObject($handler);
		
		$this->quickInitVar ( 'mimetypeid', XOBJ_DTYPE_INT, true );
		$this->quickInitVar ( 'extension', XOBJ_DTYPE_TXTBOX, true, _CO_ICMS_MIMETYPE_EXTENSION, _CO_ICMS_MIMETYPE_EXTENSION_DSC );
		$this->quickInitVar ( 'types', XOBJ_DTYPE_TXTAREA, true, _CO_ICMS_MIMETYPE_TYPES, _CO_ICMS_MIMETYPE_TYPES_DSC );
		$this->quickInitVar ( 'name', XOBJ_DTYPE_TXTBOX, true, _CO_ICMS_MIMETYPE_NAME, _CO_ICMS_MIMETYPE_NAME_DSC );
		$this->quickInitVar ( 'dirname', XOBJ_DTYPE_SIMPLE_ARRAY, true, _CO_ICMS_MIMETYPE_DIRNAME );
		$this->setControl ( 'dirname', array('name' => 'select_multi', 'handler' => 'mimetype', 'method' => 'getModuleList'));
		
	}
	
	function getVar($key, $format = 's') {
		if ($format == 's' && in_array ( $key, array ( ) )) {
			return call_user_func ( array ($this, $key ) );
		}
		return parent::getVar ( $key, $format );
	}
	
	
	function emptyString($var) {
		return (strlen ( $var ) > 0);
	}
	
	function getMimetypeName() {
		$ret = $this->getVar ( 'name' );
		return $ret;
	}
	function getMimetypeType() {
		$ret = $this->getVar ( 'types' );
		return $ret;
	}
	function getMimetypeId() {
		$ret = $this->getVar ( 'mimetypeid' );
		return $ret;
	}
}

class SystemMimetypeHandler extends IcmsPersistableObjectHandler {
	
	public $objects = false;
	
	function SystemMimetypeHandler($db) {
		$this->IcmsPersistableObjectHandler ( $db, 'mimetype', 'mimetypeid', 'mimetypeid', 'name', 'system' );
		$this->addPermission ( 'use_extension', _CO_ICMS_MIMETYPE_PERMISSION_VIEW, _CO_ICMS_MIMETYPE_PERMISSION_VIEW_DSC );
	}
	
	function UserCanUpload() {
		$handler = new IcmsPersistablePermissionHandler($this);
		return $handler->getGrantedItems('use_extension');
	}
	
	function AllowedMimeTypes() {
		$GrantedItems =  $this->UserCanUpload();
		$array = array();
		$grantedItemValues = array_values($GrantedItems);
		if(!empty($grantedItemValues)){
			$sql = "SELECT types " ."FROM " . $this->table . " WHERE (mimetypeid='";
			if (count($grantedItemValues)>1){
				foreach($grantedItemValues as $grantedItemValue){
					$sql .= ($grantedItemValue != $grantedItemValues[0])?$grantedItemValue."' OR mimetypeid='":"";
				}
			}
			$sql .= $grantedItemValues[0]."')";
			$Qvalues = $this->query($sql, false);
			for ($i = 0; $i < count($Qvalues); $i++) {
			$values[]= explode(' ', $Qvalues[$i]['types']);
			}
			foreach($values as $item=>$value){
				$array = array_merge($array, $value);
			}
		}
		return $array;
	}
	function getModuleList() {
		include_once(XOOPS_ROOT_PATH . "/class/xoopslists.php");
		$IcmsList = new IcmsLists;
		return $IcmsList->getActiveModulesList();
	}
	
	function AllowedModules($mimetype, $module) {
		$mimetypeid_allowed = $dirname_allowed = false;
		$GrantedItems =  $this->UserCanUpload();
		$criteria = new CriteriaCompo();
		$criteria->add(new Criteria('types', '%'.$mimetype.'%', 'LIKE'));

		$sql = 'SELECT mimetypeid, dirname, types FROM ' . $this->table;
		$rows = $this->query($sql, $criteria);
		if(count($rows)>1){
			for ($i = 0; $i < count($rows); $i++) {
			$mimetypeids[]= $rows[$i]['mimetypeid'];
			$dirname[]= explode('|', $rows[$i]['dirname']);
			$types[]= $rows[$i]['types'];
			}

			foreach($mimetypeids as $mimetypeid){
				if(in_array($mimetypeid, $GrantedItems)){
					$mimetypeid_allowed = true;
				}
			}
			foreach($dirname as $dir){
				if(!empty($module) && in_array($module, $dir)){
					$dirname_allowed = true;
				}
			}
		}else{
			$mimetypeid= $rows[0]['mimetypeid'];
			$dirname= explode('|', $rows[0]['dirname']);
			$types= $rows[0]['types'];
			if(in_array($mimetypeid, $GrantedItems)){
				$mimetypeid_allowed = true;
			}
			if(!empty($module) && in_array($module, $dirname)){
				$dirname_allowed = true;
			}
		}
		if($mimetypeid_allowed && $dirname_allowed){
			return true;
		}else{
			return false;
		}
	}
	
}

?>