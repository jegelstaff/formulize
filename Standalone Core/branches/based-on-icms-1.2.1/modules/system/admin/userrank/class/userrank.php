<?php
/**
* ImpressCMS Userranks
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

class SystemUserrank extends IcmsPersistableObject {

	public $content = false;

	function SystemUserrank(&$handler) {
		$this->IcmsPersistableObject($handler);

		$this->quickInitVar('rank_id', XOBJ_DTYPE_INT, true);
		$this->quickInitVar('rank_title', XOBJ_DTYPE_TXTBOX, true, _CO_ICMS_USERRANK_RANK_TITLE, _CO_ICMS_USERRANK_RANK_TITLE_DSC);
		$this->quickInitVar('rank_min', XOBJ_DTYPE_INT, true, _CO_ICMS_USERRANK_RANK_MIN, _CO_ICMS_USERRANK_RANK_MIN_DSC);
		$this->quickInitVar('rank_max', XOBJ_DTYPE_INT, true, _CO_ICMS_USERRANK_RANK_MAX, _CO_ICMS_USERRANK_RANK_MAX_DSC);
		$this->quickInitVar('rank_special', XOBJ_DTYPE_INT, true, _CO_ICMS_USERRANK_RANK_SPECIAL, _CO_ICMS_USERRANK_RANK_SPECIAL_DSC);
		$this->quickInitVar('rank_image', XOBJ_DTYPE_TXTBOX, true, _CO_ICMS_USERRANK_RANK_IMAGE, _CO_ICMS_USERRANK_RANK_IMAGE_DSC);

		$this->setControl('rank_special', 'yesno');
		$this->setControl('rank_image', 'image');
	}

	function getVar($key, $format = 's') {
		if ($format == 's' && in_array ( $key, array ( ) )) {
			return call_user_func ( array ($this, $key ) );
		}
		return parent::getVar ( $key, $format );
	}

	function getCloneLink() {
		$ret = '<a href="' . ICMS_URL . '/modules/system/admin.php?fct=userrank&amp;op=clone&amp;rank_id=' . $this->id () . '"><img src="' . ICMS_IMAGES_SET_URL . '/actions/editcopy.png" style="vertical-align: middle;" alt="' . _CO_ICMS_CUSTOMTAG_CLONE . '" title="' . _CO_ICMS_CUSTOMTAG_CLONE . '" /></a>';
		return $ret;
	}

	function getRankPicture() {
		$ret = '<img src="' . ICMS_URL . '/uploads/system/userrank/' . $this->getVar ( 'rank_image' ) . '" />';
		return $ret;
	}

	function emptyString($var) {
		return (strlen ( $var ) > 0);
	}

	function getUserrankName() {
		$ret = $this->getVar ( 'rank_title' );
		return $ret;
	}
}

class SystemUserrankHandler extends IcmsPersistableObjectHandler {

	public $objects=false;

	function SystemUserrankHandler($db) {
		global $icmsConfigUser;
		$this->IcmsPersistableObjectHandler ( $db, 'userrank', 'rank_id', 'rank_title', '', 'system' );
		$this->table = $this->db->prefix('ranks');
		$this->setUploaderConfig(false, array('image/gif', 'image/jpeg', 'image/pjpeg', 'image/x-png', 'image/png'), $icmsConfigUser['rank_maxsize'], $icmsConfigUser['rank_width'], $icmsConfigUser['rank_height']);
	}

	function MoveAllRanksImagesToProperPath(){
		$sql = 'SELECT rank_image FROM '. $this->table;
		$Query = $this->query($sql, false);
		for ($i = 0; $i < count($Query); $i++) {
			$values[]= $Query[$i]['rank_image'];
		}

		foreach($values as $value){
			if(file_exists(ICMS_UPLOAD_PATH.'/'.$value)){
				icms_copyr(ICMS_UPLOAD_PATH.'/'.$value, ICMS_UPLOAD_PATH.'/system/userrank/'.$value);
			}
		}

		return true;
	}

}

?>