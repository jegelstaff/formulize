<?php

/**
* Classes responsible for managing profile pictures objects
*
* @copyright	GNU General Public License (GPL)
* @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
* @since		1.3
* @author		Sina Asghari (aka stranger) <pesian_stranger@users.sourceforge.net>
* @package		profile
* @version		$Id$
*/

if (!defined("ICMS_ROOT_PATH")) die("ICMS root path not defined");

// including the IcmsPersistabelSeoObject
include_once ICMS_ROOT_PATH . '/kernel/icmspersistableseoobject.php';
include_once(ICMS_ROOT_PATH . '/modules/profile/include/functions.php');

class ProfilePictures extends IcmsPersistableSeoObject {

	/**
	 * Constructor
	 *
	 * @param object $handler ProfilePictureHandler object
	 */
	public function __construct(& $handler) {
		global $icmsConfig;

		$this->IcmsPersistableObject($handler);

		$this->quickInitVar('pictures_id', XOBJ_DTYPE_INT, true);
		$this->quickInitVar('title', XOBJ_DTYPE_TXTBOX, true);
		$this->quickInitVar('creation_time', XOBJ_DTYPE_LTIME, false);
		$this->quickInitVar('update_time', XOBJ_DTYPE_TXTBOX, false);
		$this->quickInitVar('uid_owner', XOBJ_DTYPE_INT, true);
		$this->quickInitVar('url', XOBJ_DTYPE_IMAGE, true);
		$this->quickInitVar('private', XOBJ_DTYPE_TXTBOX, false);
		$this->initCommonVar('counter', false);
		$this->initCommonVar('dohtml', false, true);
		$this->initCommonVar('dobr', false, true);
		$this->initCommonVar('doimage', false, true);
		$this->initCommonVar('dosmiley', false, true);

		$this->setControl('uid_owner', 'user');
		$this->setControl('url', array('name' => 'image', 'nourl' => true));
		$this->setControl('private', 'yesno');
		$this->hideFieldFromForm('creation_time');
		$this->hideFieldFromForm('update_time');

		$this->IcmsPersistableSeoObject();
	}

	/**
	 * Overriding the IcmsPersistableObject::getVar method to assign a custom method on some
	 * specific fields to handle the value before returning it
	 *
	 * @param str $key key of the field
	 * @param str $format format that is requested
	 * @return mixed value of the field that is requested
	 */
	function getVar($key, $format = 's') {
		if ($format == 's' && in_array($key, array ())) {
			return call_user_func(array ($this,	$key));
		}
		return parent :: getVar($key, $format);
	}
	function getProfilePicture() {
		$ret = '<a href="' . ICMS_URL . '/uploads/profile/pictures/resized_' . $this->getVar ( 'url' ) . '" rel="lightbox" title="' . $this->getVar ( 'title' ) . '">
		  <img class="thumb" src="' . ICMS_URL . '/uploads/profile/pictures/thumb_' . $this->getVar ( 'url' ) . '" rel="lightbox" title="' . $this->getVar ( 'title' ) . '" />
		</a>';
		return $ret;
	}
	
	function getPictureSender() {
		return icms_getLinkedUnameFromId($this->getVar('uid_owner', 'e'));
	}

	/**
	 * return the picture title
	 *
	 * @return string picture title
	 */
	function getPictureTitle() {
		return $this->getVar('title');
	}

	/**
	 * Check to see wether the current user can edit or delete this picture
	 *
	 * @return bool true if he can, false if not
	 */
	function userCanEditAndDelete() {
		global $icmsUser, $profile_isAdmin;
		if (!is_object($icmsUser)) {
			return false;
		}
		if ($profile_isAdmin) {
			return true;
		}
		return $this->getVar('uid_owner', 'e') == $icmsUser->uid();
	}

	/**
	 * Overridding IcmsPersistable::toArray() method to add a few info
	 *
	 * @return array of picture info
	 */
	function toArray() {
		$ret = parent :: toArray();
		$ret['creation_time'] = formatTimestamp($this->getVar('creation_time', 'e'), 'm');
		$ret['picture_content'] = $this->getProfilePicture();
		$ret['picture_title'] = $this->getVar('title','e');
		$ret['editItemLink'] = $this->getEditItemLink(false, true, true);
		$ret['deleteItemLink'] = $this->getDeleteItemLink(false, true, true);
		$ret['userCanEditAndDelete'] = $this->userCanEditAndDelete();
		$ret['picture_senderid'] = $this->getVar('uid_owner','e');
		$ret['picture_sender_link'] = $this->getPictureSender();
		return $ret;
	}
}
class ProfilePicturesHandler extends IcmsPersistableObjectHandler {

	/**
	 * Constructor
	 */
	public function __construct(& $db) {
		global $icmsModuleConfig;
		$this->IcmsPersistableObjectHandler($db, 'pictures', 'pictures_id', 'title', '', 'profile');
		$this->enableUpload(array('image/gif', 'image/jpeg', 'image/pjpeg', 'image/png'), $icmsModuleConfig['maxfilesize_picture'], $icmsModuleConfig['max_original_width'], $icmsModuleConfig['max_original_height']);
	}
	

	/**
	 * Create the criteria that will be used by getPictures and getPicturesCount
	 *
	 * @param int $start to which record to start
	 * @param int $limit limit of pictures to return
	 * @param int $uid_owner if specifid, only the pictures of this user will be returned
	 * @param int $picture_id ID of a single picture to retrieve
	 * @return CriteriaCompo $criteria
	 */
	function getPicturesCriteria($start = 0, $limit = 0, $uid_owner = false, $picture_id = false) {
		global $icmsUser, $icmsModuleConfig;

		$criteria = new CriteriaCompo();
		if ($start) {
			$criteria->setStart($start);
		}
		if ($limit) {
			$criteria->setLimit(intval($limit));
		}
		$criteria->setSort('creation_time');
		if ($icmsModuleConfig['images_order']) $criteria->setOrder('DESC');

		if (is_object($icmsUser)) {
			if ($icmsUser->getVar('uid') != $uid_owner && !$icmsUser->isAdmin()) {
				$criteria->add(new Criteria('private', 0));
			}
		} else {
			$criteria->add(new Criteria('private', 0));
		}
		
		if ($uid_owner) {
			$criteria->add(new Criteria('uid_owner', $uid_owner));
		}
		if ($picture_id) {
			$criteria->add(new Criteria('pictures_id', $picture_id));
		}
		return $criteria;
	}

	/**
	 * Get single picture object
	 *
	 * @param int $pictures_id
	 * @return object ProfilePicture object
	 */
	function getPicture($pictures_id=false, $uid_owner=false) {
		$ret = $this->getPictures(0, 0, $uid_owner, $pictures_id);
		return isset($ret[$pictures_id]) ? $ret[$pictures_id] : false;
	}

	/**
	 * Get pictures as array, ordered by creation_time DESC
	 *
	 * @param int $start to which record to start
	 * @param int $limit max pictures to display
	 * @param int $uid_owner if specifid, only the picture of this user will be returned
	 * @param int $pictures_id ID of a single picture to retrieve
	 * @return array of pictures
	 */
	function getPictures($start = 0, $limit = 0, $uid_owner = false, $pictures_id = false) {
		$criteria = $this->getPicturesCriteria($start, $limit, $uid_owner, $pictures_id);
		$ret = $this->getObjects($criteria, true, false);
		return $ret;
	}

	/**
	* Resize a picture and save it to $path_upload
	* 
	* @param text $img the path to the file
	* @param int $width the width in pixels that the pic will have
	* @param int $height the height in pixels that the pic will have
	* @param text $path_upload The path to where the files should be saved after resizing
	* @param text $prefix The prefix used to recognize files and avoid multiple files.
	* @return nothing
	*/	
	function imageResizer($img, $width=320, $height=240, $path_upload=ICMS_UPLOAD_PATH, $prefix='') {
		$prefix = (isset($prefix) && $prefix != '')?$prefix:time();
		$path = pathinfo($img);
		$img = WideImage::load($img);
		$img->resizeDown($width, $height)->saveToFile($path_upload.'/'.$prefix.'_'.$path['basename']);
	}
	
	/**
	* Resize a picture and save it to $path_upload
	* 
	* @param text $img the path to the file
	* @param text $path_upload The path to where the files should be saved after resizing
	* @param int $thumbwidth the width in pixels that the thumbnail will have
	* @param int $thumbheight the height in pixels that the thumbnail will have
	* @param int $pictwidth the width in pixels that the pic will have
	* @param int $pictheight the height in pixels that the pic will have
	* @return nothing
	*/	
	function resizeImage($img, $thumbwidth, $thumbheight, $pictwidth, $pictheight,$path_upload) {
		$this->imageResizer($img, $thumbwidth, $thumbheight, $path_upload, 'thumb');
		$this->imageResizer($img, $pictwidth, $pictheight, $path_upload, 'resized');
	}
	
	/**
	 * Resize a picture and save it to $path_upload
	 *
	 * @param int $pictures_id the id of the picture to set as avatar
	 * @return nothing
	 */
	function makeAvatar($pictures_id) {
		global $icmsUser, $icmsConfigUser;

		$picturesObj = $this->get($pictures_id);

		// check if picture exists
		if ($picturesObj->isNew()) {
			redirect_header(icms_getPreviousPage('index.php'), 3, _MD_PROFILE_PICTURES_AVATAR_NOTEDITED);
			exit();
		}

		// the current user must be the owner of this picture, users must be allowed to upload avatars and we check for user posts
		if (!is_object($icmsUser) || $icmsUser->getVar('uid') != $picturesObj->getVar('uid_owner')  || $icmsConfigUser['avatar_allow_upload'] == 0 || $icmsUser->getVar('posts') < $icmsConfigUser['avatar_minposts']) {
			redirect_header(icms_getPreviousPage('index.php'), 3, _NOPERM);
			exit();
		}
		$image = $picturesObj->_image_path.'thumb_'.$picturesObj->getVar('url');
		if (($ext = strrpos($picturesObj->getVar('url'), '.')) !== false) {
			$ext = strtolower(substr($picturesObj->getVar('url'), $ext +1));
		} else {
			$ext = 'jpg';
		}
		$avatar = 'cavt_'.time().'.'.$ext;
		$imageAvatar = ICMS_UPLOAD_PATH.'/'.$avatar;

		// resize picture and store as avatar
		$imgObj = WideImage::load($image);
		$imgObj->resizeDown($icmsConfigUser['avatar_width'], $icmsConfigUser['avatar_height'])->saveToFile($imageAvatar);

		// retrieve the mime type for the avatar
		if (function_exists('exif_imagetype')) {
			$avatar_mimetype = image_type_to_mime_type(exif_imagetype($imageAvatar));
		} else {
			$size = getimagesize($imageAvatar);
			$avatar_mimetype = isset($size['mime']) ? $size['mime'] : image_type_to_mime_type($size[2]);
		}

		// create new avatar object and delete the old one
		$avt_handler =& xoops_gethandler('avatar');
		$avatarObj =& $avt_handler->create();
		$avatarObj->setVar('avatar_file', $avatar);
		$avatarObj->setVar('avatar_name', $icmsUser->getVar('uname'));
		$avatarObj->setVar('avatar_mimetype', $avatar_mimetype);
		$avatarObj->setVar('avatar_display', 1);
		$avatarObj->setVar('avatar_type', 'C');
		if(!$avt_handler->insert($avatarObj)) {
			unlink($imageAvatar);
			redirect_header(icms_getPreviousPage('index.php'), 3, _MD_PROFILE_PICTURES_AVATAR_NOTEDITED);
			exit();
		} else {
			$oldavatar = $icmsUser->getVar('user_avatar');
			if(!empty($oldavatar) && preg_match("/^cavt/", strtolower($oldavatar))) {
				$avatars =& $avt_handler->getObjects(new Criteria('avatar_file', $oldavatar));
				if(!empty($avatars) && count($avatars) == 1 && is_object($avatars[0])) {
					$avt_handler->delete($avatars[0]);
					$oldavatar_path = str_replace("\\", "/", realpath(ICMS_UPLOAD_PATH.'/'.$oldavatar));
					if(0 === strpos($oldavatar_path, ICMS_UPLOAD_PATH) && is_file($oldavatar_path)) {
						unlink($oldavatar_path);
					}
				}
			}

			$icmsUser->setVar('user_avatar', $avatar);
			$user_handler =& xoops_gethandler('user');

			if($user_handler->insert($icmsUser)) {
				$avt_handler->addUser($avatarObj->getVar('avatar_id'), intval($icmsUser->getVar('uid')));
				redirect_header(icms_getPreviousPage('index.php'), 3, _MD_PROFILE_PICTURES_AVATAR_EDITED);
			} else {
				redirect_header(icms_getPreviousPage('index.php'), 3, _MD_PROFILE_PICTURES_AVATAR_NOTEDITED);
			}
		}
	}
	
	/**
	 * Check wether the current user can submit a new picture or not
	 *
	 * @return bool true if he can false if not
	 */
	function userCanSubmit() {
		global $icmsUser;

		if (!is_object($icmsUser)) return false;
		return true;
	}

	/**
	 * Check whether the user has already reached the upload limit
	 *
	 * @global array $icmsModuleConfig module configuration
	 * @return int number of pictures for the current user (icmsUser)
	 */
	function checkUploadLimit() {
		global $icmsUser, $icmsModuleConfig;

		if (!is_object($icmsUser)) return false;
		if ($icmsModuleConfig['nb_pict'] == 0) return true;
		$count = $this->getCount(new CriteriaCompo(new Criteria('uid_owner', $icmsUser->getVar('uid'))));
		return ($count < $icmsModuleConfig['nb_pict']);
	}

	/**
	 * Update the counter field of the post object
	 *
	 * @todo add this in directly in the IPF
	 * @param int $post_id
	 *
	 * @return VOID
	 */
	function updateCounter($id) {
		$sql = 'UPDATE ' . $this->table . ' SET counter = counter + 1 WHERE ' . $this->keyName . ' = ' . $id;
		$this->query($sql, null, true);
	}

	/**
	 * AfterSave event
	 *
	 * Event automatically triggered by IcmsPersistable Framework after the object is inserted or updated
	 *
	 * @param object $obj ProfilePictures object
	 * @return true
	 */
	function afterSave(& $obj) {
		global $icmsModuleConfig;
		// Resizing Images!
		$imgPath = ICMS_UPLOAD_PATH.'/profile/pictures/';
		$img = $imgPath . $obj->getVar('url');
		$this->resizeImage($img, $icmsModuleConfig['thumb_width'], $icmsModuleConfig['thumb_height'], $icmsModuleConfig['resized_width'], $icmsModuleConfig['resized_height'],$imgPath);
		return true;
	}

	/*
	 * afterDelete event
	 *
	 * Event automatically triggered by IcmsPersistable Framework after the object is deleted
	 *
	 * @param object $obj ProfilePictures object
	 * @return bool
	 */
	function afterDelete(&$obj) {
		global $icmsModuleConfig;

		$imgPath = ICMS_UPLOAD_PATH.'/profile/pictures/';
		$imgUrl = $obj->getVar('url');

		if (!empty($imgUrl) && $icmsModuleConfig['physical_delete']) {
			unlink($imgPath.$imgUrl);
			unlink($imgPath.'thumb_'.$imgUrl);
			unlink($imgPath.'resized_'.$imgUrl);
		}

		return true;
	}
}
?>