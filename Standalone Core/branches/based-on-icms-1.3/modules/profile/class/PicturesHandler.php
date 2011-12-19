<?php
/**
 * Class responsible for managing profile picture objects
 *
 * @copyright	GNU General Public License (GPL)
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @since		1.4
 * @author		phoenyx
 * @package		profile
 * @version		$Id: PicturesHandler.php 20562 2010-12-19 18:26:36Z phoenyx $
 */

defined("ICMS_ROOT_PATH") or die("ICMS root path not defined");

class mod_profile_PicturesHandler extends icms_ipf_Handler {
	/**
	 * Constructor
	 *
	 * @param icms_db_legacy_Database $db database connection object
	 */
	public function __construct(&$db) {
		parent::__construct($db, 'pictures', 'pictures_id', 'title', '', basename(dirname(dirname(__FILE__))));
		$module = icms::handler("icms_module")->getByDirname(basename(dirname(dirname(__FILE__))), TRUE);
		$this->enableUpload(array('image/gif', 'image/jpeg', 'image/pjpeg', 'image/png'), $module->config['maxfilesize_picture'], $module->config['max_original_width'], $module->config['max_original_height']);
	}

	/**
	 * Create the criteria that will be used by getPictures and getPicturesCount
	 *
	 * @param int $start to which record to start
	 * @param int $limit limit of pictures to return
	 * @param int $uid_owner if specifid, only the pictures of this user will be returned
	 * @param int $picture_id ID of a single picture to retrieve
	 * @return icms_db_criteria_Compo $criteria
	 */
	private function getPicturesCriteria($start = 0, $limit = 0, $uid_owner = false, $picture_id = false) {
		$module = icms::handler("icms_module")->getByDirname(basename(dirname(dirname(__FILE__))), TRUE);
		$criteria = new icms_db_criteria_Compo();
		if ($start) $criteria->setStart((int)$start);
		if ($limit)	$criteria->setLimit((int)$limit);
		$criteria->setSort('creation_time');
		if ($module->config['images_order']) $criteria->setOrder('DESC');

		if (is_object(icms::$user)) {
			if (icms::$user->getVar('uid') != $uid_owner && !icms::$user->isAdmin()) {
				$criteria->add(new icms_db_criteria_Item('private', 0));
			}
		} else {
			$criteria->add(new icms_db_criteria_Item('private', 0));
		}

		if ($uid_owner) $criteria->add(new icms_db_criteria_Item('uid_owner', (int)$uid_owner));
		if ($picture_id) $criteria->add(new icms_db_criteria_Item('pictures_id', (int)$picture_id));

		return $criteria;
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
	public function getPictures($start = 0, $limit = 0, $uid_owner = false, $pictures_id = false) {
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
	* @return void
	*/
	private function imageResizer($img, $width=320, $height=240, $path_upload = ICMS_UPLOAD_PATH, $prefix = '') {
		$prefix = (isset($prefix) && $prefix != '') ? $prefix:time();
		$path = pathinfo($img);
		$img = WideImage::load($img);
		$img->resizeDown($width, $height)->saveToFile($path_upload.'/'.$prefix.'_'.$path['basename']);
	}

	/**
	* Resize a picture and save it to $path_upload
	*
	* @param text $img the path to the file
	* @param int $thumbwidth the width in pixels that the thumbnail will have
	* @param int $thumbheight the height in pixels that the thumbnail will have
	* @param int $pictwidth the width in pixels that the pic will have
	* @param int $pictheight the height in pixels that the pic will have
	* @return void
	*/
	private function resizeImage($img, $thumbwidth, $thumbheight, $pictwidth, $pictheight) {
		$this->imageResizer($img, $thumbwidth, $thumbheight, $this->getImagePath(), 'thumb');
		$this->imageResizer($img, $pictwidth, $pictheight, $this->getImagePath(), 'resized');
	}

	/**
	 * Resize a picture and save it to $path_upload
	 *
	 * @param int $pictures_id the id of the picture to set as avatar
	 * @global array $icmsConfigUser user configuration
	 * @return void
	 */
	public function makeAvatar($pictures_id) {
		global $icmsConfigUser;

		$picturesObj = $this->get($pictures_id);

		// check if picture exists
		if ($picturesObj->isNew()) redirect_header(icms_getPreviousPage('index.php'), 3, _MD_PROFILE_PICTURES_AVATAR_NOTEDITED);

		// the current user must be the owner of this picture, users must be allowed to upload avatars and we check for user posts
		if (!is_object(icms::$user) || icms::$user->getVar('uid') != $picturesObj->getVar('uid_owner')  || $icmsConfigUser['avatar_allow_upload'] == 0 || icms::$user->getVar('posts') < $icmsConfigUser['avatar_minposts']) redirect_header(icms_getPreviousPage('index.php'), 3, _NOPERM);

		$image = $this->getImagePath().$picturesObj->getVar('url');
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
		$avt_handler = icms::handler('icms_data_avatar');
		$avatarObj = $avt_handler->create();
		$avatarObj->setVar('avatar_file', $avatar);
		$avatarObj->setVar('avatar_name', icms::$user->getVar('uname'));
		$avatarObj->setVar('avatar_mimetype', $avatar_mimetype);
		$avatarObj->setVar('avatar_display', 1);
		$avatarObj->setVar('avatar_type', 'C');
		if(!$avt_handler->insert($avatarObj)) {
			unlink($imageAvatar);
			redirect_header(icms_getPreviousPage('index.php'), 3, _MD_PROFILE_PICTURES_AVATAR_NOTEDITED);
		} else {
			$oldavatar = icms::$user->getVar('user_avatar');
			if(!empty($oldavatar) && preg_match("/^cavt/", strtolower($oldavatar))) {
				$avatars = $avt_handler->getObjects(new icms_db_criteria_Item('avatar_file', $oldavatar));
				if(!empty($avatars) && count($avatars) == 1 && is_object($avatars[0])) {
					$avt_handler->delete($avatars[0]);
					$oldavatar_path = str_replace("\\", "/", realpath(ICMS_UPLOAD_PATH.'/'.$oldavatar));
					if (0 === strpos($oldavatar_path, ICMS_UPLOAD_PATH) && is_file($oldavatar_path)) {
						unlink($oldavatar_path);
					}
				}
			}

			icms::$user->setVar('user_avatar', $avatar);
			if (icms::handler('icms_member_user')->insert(icms::$user)) {
				$avt_handler->addUser($avatarObj->getVar('avatar_id'), (int)icms::$user->getVar('uid'));
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
	public function userCanSubmit() {
		return is_object(icms::$user);
	}

	/**
	 * Check whether the user has already reached the upload limit
	 *
	 * @return int number of pictures for the current user
	 */
	public function checkUploadLimit() {
		if (!is_object(icms::$user)) return false;
		$module = icms::handler("icms_module")->getByDirname(basename(dirname(dirname(__FILE__))), TRUE);
		if ($module->config['nb_pict'] == 0) return true;
		$count = $this->getCount(new icms_db_criteria_Compo(new icms_db_criteria_Item('uid_owner', icms::$user->getVar('uid'))));
		return ($count < $module->config['nb_pict']);
	}

	/**
	 * Update the counter field of the post object
	 *
	 * @param int $pictures_id
	 * @return bool
	 */
	public function updateCounter($pictures_id) {
		$picturesObj = $this->get($pictures_id);
		$picturesObj->setVar('counter', $picturesObj->getVar('counter') + 1);
		return $picturesObj->store();
	}

	/**
	 * AfterInsert event
	 *
	 * Event automatically triggered by IcmsPersistable Framework after the object is inserted
	 *
	 * @param mod_profile_Pictures $obj object
	 * @return true
	 */
	protected function afterInsert(&$obj) {
		$module = icms::handler("icms_module")->getByDirname(basename(dirname(dirname(__FILE__))), TRUE);
		$img = $this->getImagePath().$obj->getVar('url');
		$this->resizeImage($img, $module->config['thumb_width'], $module->config['thumb_height'], $module->config['resized_width'], $module->config['resized_height']);

		$thisUser = icms::handler('icms_member')->getUser($obj->getVar('uid_owner'));
		$tags['PICTURE_TITLE'] = $obj->getVar('title');
		$tags['PICTURE_OWNER'] = $thisUser->getVar('uname');
		$tags['PICTURE_URL'] = ICMS_URL.'/modules/'.basename(dirname(dirname(__FILE__))).'/pictures.php?uid='.$obj->getVar('uid_owner');
		icms::handler('icms_data_notification')->triggerEvent('pictures', $obj->getVar('uid_owner'), 'new_picture', $tags, array(), $module->getVar('mid'));

		return true;
	}

	/*
	 * afterDelete event
	 *
	 * Event automatically triggered by IcmsPersistable Framework after the object is deleted
	 *
	 * @param mod_profile_Pictures $obj object
	 * @return bool
	 */
	protected function afterDelete(&$obj) {
		$imgPath = $this->getImagePath();
		$imgUrl = $obj->getVar('url');
		$module = icms::handler("icms_module")->getByDirname(basename(dirname(dirname(__FILE__))), TRUE);

		if (!empty($imgUrl) && $module->config['physical_delete']) {
			unlink($imgPath.$imgUrl);
			unlink($imgPath.'thumb_'.$imgUrl);
			unlink($imgPath.'resized_'.$imgUrl);
		}

		return true;
	}
}
?>