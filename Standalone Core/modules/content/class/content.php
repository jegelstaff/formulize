<?php
/**
 * ImpressCMS Conent Persistable Class
 * 
 * @since 		ImpressCMS 1.2
 * @copyright 	The ImpressCMS Project <http://www.impresscms.org>
 * @license		GNU General Public License (GPL) <http://www.gnu.org/licenses/old-licenses/gpl-2.0.html>
 * @author		Rodrigo P Lima (aka TheRplima) <therplima@impresscms.org>
 * @version		$Id$
 */

defined ( 'ICMS_ROOT_PATH' ) or die ( 'ImpressCMS root path not defined' );

include_once ICMS_ROOT_PATH . '/kernel/icmspersistableseoobject.php';

/**
 * Content status definitions
 */
define ( 'CONTENT_CONTENT_STATUS_PUBLISHED', 1 );
define ( 'CONTENT_CONTENT_STATUS_PENDING', 2 );
define ( 'CONTENT_CONTENT_STATUS_DRAFT', 3 );
define ( 'CONTENT_CONTENT_STATUS_PRIVATE', 4 );
define ( 'CONTENT_CONTENT_STATUS_EXPIRED', 5 );

define ( 'CONTENT_CONTENT_VISIBLE_MENUOLNY', 1 );
define ( 'CONTENT_CONTENT_VISIBLE_SUBSONLY', 2 );
define ( 'CONTENT_CONTENT_VISIBLE_MENUSUBS', 3 );
define ( 'CONTENT_CONTENT_VISIBLE_DONTSHOW', 4 );

/**
 * ImpressCMS Core Content Object Class
 * 
 * @since ImpressCMS 1.2
 * @author Rodrigo P Lima (aka TheRplima) <therplima@impresscms.org>
 */
class ContentContent extends IcmsPersistableSeoObject {
	
	private $poster_info = false;
	public $updating_counter = false;
	public $tags = false;
	public $categories = false;
	
	public function __construct(&$handler) {
		global $xoopsConfig, $contentConfig;
		
		$this->IcmsPersistableObject ( $handler );
		
		$this->quickInitVar ( 'content_id', XOBJ_DTYPE_INT, true );
		$this->quickInitVar ( 'content_pid', XOBJ_DTYPE_INT, false );
		$this->quickInitVar ( 'content_uid', XOBJ_DTYPE_INT, true, false, false, 1 );
		$this->quickInitVar ( 'content_title', XOBJ_DTYPE_TXTBOX, true );
		$this->quickInitVar ( 'content_body', XOBJ_DTYPE_TXTAREA );
		$this->quickInitVar ( 'content_css', XOBJ_DTYPE_TXTAREA );
		$this->quickInitVar ( 'content_tags', XOBJ_DTYPE_TXTAREA );
		$this->quickInitVar ( 'content_visibility', XOBJ_DTYPE_INT, true, false, false, CONTENT_CONTENT_VISIBLE_MENUSUBS );
		$this->quickInitVar ( 'content_published_date', XOBJ_DTYPE_LTIME, false );
		$this->quickInitVar ( 'content_updated_date', XOBJ_DTYPE_LTIME, false, false, false, time () );
		$this->quickInitVar ( 'content_weight', XOBJ_DTYPE_INT, true, false, false, 0 );
		$this->quickInitVar ( 'content_status', XOBJ_DTYPE_INT, true, false, false, CONTENT_CONTENT_STATUS_PUBLISHED );
		$this->quickInitVar ( 'content_makesymlink', XOBJ_DTYPE_INT, true, false, false, 1 );
		$this->quickInitVar ( 'content_showsubs', XOBJ_DTYPE_INT, false, false, false, $contentConfig ['show_relateds'] );
		$this->quickInitVar ( 'content_cancomment', XOBJ_DTYPE_INT, false, false, false, true );
		
		$this->quickInitVar ( 'content_comments', XOBJ_DTYPE_INT );
		$this->hideFieldFromForm ( 'content_comments' );
		$this->hideFieldFromSingleView ( 'content_comments' );
		
		$this->quickInitVar ( 'content_notification_sent', XOBJ_DTYPE_INT );
		$this->hideFieldFromForm ( 'content_notification_sent' );
		$this->hideFieldFromSingleView ( 'content_notification_sent' );
		
		$this->initCommonVar ( 'counter', false );
		$this->initCommonVar ( 'dohtml', false, true );
		$this->initCommonVar ( 'dobr', false, true );
		$this->initCommonVar ( 'doimage', false, true );
		$this->initCommonVar ( 'dosmiley', false, true );
		$this->initCommonVar ( 'doxcode', false, true );
		
		$this->initNonPersistableVar ( 'content_subs', XOBJ_DTYPE_INT );
		
		$this->setControl ( 'content_body', 'dhtmltextarea' );
		$this->setControl ( 'content_uid', 'user' );
		$this->setControl ( 'content_status', array ('itemHandler' => 'content', 'method' => 'getContent_statusArray', 'module' => 'content' ) );
		$this->setControl ( 'content_visibility', array ('itemHandler' => 'content', 'method' => 'getContent_visibleArray', 'module' => 'content' ) );
		$this->setControl ( 'content_pid', array ('itemHandler' => 'content', 'method' => 'getContentList', 'module' => 'content' ) );
		
		$this->setControl ( 'categories', array ('name' => 'categories', 'module' => 'imtagging' ) );
		
		$this->setControl ( 'content_makesymlink', 'yesno' );
		$this->setControl ( 'content_showsubs', 'yesno' );
		$this->setControl ( 'content_cancomment', 'yesno' );
		
		$this->IcmsPersistableSeoObject ();
	}
	
	public function getVar($key, $format = 's') {
		if ($format == 's' && in_array ( $key, array ('content_pid', 'content_uid', 'content_status', 'content_visibility', 'content_subs', 'content_tags' ) )) {
			return call_user_func ( array ($this, $key ) );
		}
		return parent::getVar ( $key, $format );
	}
	
	/**
	 * Retrieving the title of the parent page, linked to that
	 *
	 * @return str title of the parent content
	 */
	function content_pid() {
		$ret = $this->getVar ( 'content_pid', 'e' );
		$content_pidArray = $this->handler->getContentList ();
		if ($ret > 0) {
			$ret = '<a href="' . $this->handler->_moduleUrl . $this->handler->_itemname . '.php?content_id=' . $ret . '">' . str_replace ( '-', '', $content_pidArray [$ret] ) . '</a>';
		} else {
			$ret = $content_pidArray [$ret];
		}
		
		return $ret;
	}
	
	/**
	 * Retrieving the name of the author of the content, linked to his profile
	 *
	 * @return str name of the author of the content
	 */
	function content_uid() {
		return icms_getLinkedUnameFromId ( $this->getVar ( 'content_uid', 'e' ) );
	}
	
	/**
	 * Retrieving the status of the content
	 *
	 * @param str status of the content
	 * @return mixed $content_statusArray[$ret] status of the content
	 */
	function content_status() {
		$ret = $this->getVar ( 'content_status', 'e' );
		$content_statusArray = $this->handler->getContent_statusArray ();
		return $content_statusArray [$ret];
	}
	
	/**
	 * Retrieving the visibility of the content
	 *
	 * @return mixed $content_visibleArray[$ret] visibility of the content
	 */
	function content_visibility() {
		$ret = $this->getVar ( 'content_visibility', 'e' );
		$content_visibleArray = $this->handler->getContent_visibleArray ();
		return $content_visibleArray [$ret];
	}
	
	function content_tags() {
		if ($this->getVar ( 'content_tags', 'e' ) != '') {
			$tags = explode ( ',', $this->getVar ( 'content_tags', 'e' ) );
			foreach ( $tags as $k => $tag ) {
				$tag = trim ( $tag );
				$tag = ' <a href="' . $this->handler->_moduleUrl . 'index.php?tag=' . $tag . '">' . $tag . '</a>';
				$tags [$k] = $tag;
			}
			return implode ( ',', $tags );
		} else {
			return false;
		}
	}
	
	/**
	 * Retrieving the count of sub-pages of this page 
	 *
	 * @return int number of sub-pages
	 */
	function content_subs() {
		$ret = $this->handler->getContentsSubsCount ( $this->getVar ( 'content_id', 'e' ) );
		
		if ($ret > 0) {
			$ret = '<a href="' . $this->handler->_moduleUrl . 'admin/' . $this->handler->_itemname . '.php?content_pid=' . $this->getVar ( 'content_id', 'e' ) . '">' . $ret . ' <img src="' . $this->handler->_moduleUrl . 'images/viewsubs.gif" align="absmiddle" /></a>';
		}
		
		return $ret;
	}
	
	function getReads() {
		return $this->getVar ( 'counter' );
	}
	
	function setReads($qtde = null) {
		$t = $this->getVar ( 'counter' );
		if (isset ( $qtde )) {
			$t += $qtde;
		} else {
			$t ++;
		}
		$this->setVar ( 'counter', $t );
	}
	
	/**
	 * Returns the need to br
	 *
	 * @return bool true | false
	 */
	function need_do_br() {
		global $xoopsConfig, $xoopsUser;
		
		$content_module = icms_getModuleInfo ( 'content' );
		$groups = $xoopsUser->getGroups ();
		
		$editor_default = $xoopsConfig ['editor_default'];
		$gperm_handler = xoops_getHandler ( 'groupperm' );
		if (file_exists ( ICMS_EDITOR_PATH . "/" . $editor_default . "/xoops_version.php" ) && $gperm_handler->checkRight ( 'use_wysiwygeditor', $content_module->mid (), $groups )) {
			return false;
		} else {
			return true;
		}
	}
	
	/**
	 * Check is user has access to view this content page
	 *
	 * User will be able to view the page if
	 *	- the status of the page is Published OR
	 *	- he is an admin OR
	 * 	  - he is the poster of this page
	 *
	 * @return bool true if user can view this page, false if not
	 */
	function accessGranted() {
		global $xoopsUser;
		
		$gperm_handler = & xoops_gethandler ( 'groupperm' );
		$groups = is_object ( $xoopsUser ) ? $xoopsUser->getGroups () : array (XOOPS_GROUP_ANONYMOUS );
		
		$module_handler = xoops_gethandler ( 'module' );
		$module = $module_handler->getByDirname ( 'content' );
		
		$agroups = $gperm_handler->getGroupIds ( 'module_admin', $module->mid () );
		$allowed_groups = array_intersect ( $groups, $agroups );
		
		$viewperm = $gperm_handler->checkRight ( 'content_read', $this->getVar ( 'content_id', 'e' ), $groups, $module->mid () );
		
		if (is_object ( $xoopsUser ) && $xoopsUser->uid () == $this->getVar ( 'content_uid', 'e' )) {
			return true;
		}
		
		if ($viewperm && $this->getVar ( 'content_status', 'e' ) == CONTENT_CONTENT_STATUS_PUBLISHED) {
			return true;
		}
		
		if ($viewperm && count ( $allowed_groups ) > 0) {
			return true;
		}
		return false;
	}
	
	/**
	 * Get the poster
	 *
	 * @param bool $link with link or not
	 * @return str poster name linked on his module poster page, or simply poster name
	 */
	function getPoster($link = false) {
		if (! $this->poster_info) {
			$member_handler = xoops_getHandler ( 'member' );
			$poster_uid = $this->getVar ( 'content_uid', 'e' );
			$userObj = $member_handler->getuser ( $poster_uid );
			
			/**
			 * We need to make sure the poster is a valid user object. It is possible the user no longer
			 * exists if, for example, he was previously deleted. In that case, we will return Anonymous
			 */
			if (is_object ( $userObj )) {
				$this->poster_info ['uid'] = $poster_uid;
				$this->poster_info ['uname'] = $userObj->getVar ( 'uname' );
				$this->poster_info ['link'] = '<a href="' . $this->handler->_moduleUrl . 'index.php?uid=' . $this->poster_info ['uid'] . '">' . $this->poster_info ['uname'] . '</a>';
			} else {
				global $xoopsConfig;
				$this->poster_info ['uid'] = 0;
				$this->poster_info ['uname'] = $xoopsConfig ['anonymous'];
			}
		}
		if ($link && $this->poster_info ['uid']) {
			return $this->poster_info ['link'];
		} else {
			return $this->poster_info ['uname'];
		}
	}
	
	/**
	 * Retrieve content info (author and date)
	 *
	 * @return str content info
	 */
	function getContentInfo() {
		$ret = sprintf ( _CO_CONTENT_CONTENT_INFO, $this->getPoster ( true ), $this->getVar ( 'content_published_date' ), $this->getVar ( 'counter' ) );
		return $ret;
	}
	
	/**
	 * Check to see wether the current user can edit or delete this page
	 *
	 * @return bool true if he can, false if not
	 */
	function userCanEditAndDelete() {
		global $xoopsUser, $content_isAdmin;
		if (! is_object ( $xoopsUser )) {
			return false;
		}
		if ($content_isAdmin) {
			return true;
		}
		return $this->getVar ( 'content_uid', 'e' ) == $xoopsUser->uid ();
	}
	
	function getPreviewItemLink() {
		$seo = $this->handler->makelink ( $this );
		$ret = '<a href="' . $this->handler->_moduleUrl . $this->handler->_itemname . '.php?page=' . $seo . '" title="' . _AM_CONTENT_PREVIEW . '" target="_blank">' . $this->getVar ( 'content_title' ) . '</a>';
		
		return $ret;
	}
	
	function getCloneItemLink() {
		$ret = '<a href="' . $this->handler->_moduleUrl . 'admin/' . $this->handler->_itemname . '.php?op=clone&amp;content_id=' . $this->getVar ( 'content_id', 'e' ) . '" title="' . _AM_CONTENT_CONTENT_CLONE . '"><img src="' . ICMS_IMAGES_SET_URL . '/actions/filesaveas2.png" /></a>';
		
		return $ret;
	}
	
	function getViewItemLink() {
		$ret = '<a href="' . $this->handler->_moduleUrl . 'admin/' . $this->handler->_itemname . '.php?op=view&amp;content_id=' . $this->getVar ( 'content_id', 'e' ) . '" title="' . _AM_CONTENT_VIEW . '"><img src="' . ICMS_IMAGES_SET_URL . '/actions/viewmag.png" /></a>';
		
		return $ret;
	}
	
	function getContentSubs($toarray) {
		return $this->handler->getContentSubs ( $this->getVar ( 'content_id', 'e' ), $toarray );
	}
	
	function getContent_visibleControl() {
		include_once ICMS_ROOT_PATH . '/class/xoopsformloader.php';
		$control = new XoopsFormSelect ( '', 'content_visibility[]', $this->getVar ( 'content_visibility', 'e' ) );
		$content_visibleArray = $this->handler->getContent_visibleArray ();
		$control->addOptionArray ( $content_visibleArray );
		
		return $control->render ();
	}
	
	function getContent_statusControl() {
		include_once ICMS_ROOT_PATH . '/class/xoopsformloader.php';
		$control = new XoopsFormSelect ( '', 'content_status[]', $this->getVar ( 'content_status', 'e' ) );
		$content_statusArray = $this->handler->getContent_statusArray ();
		$control->addOptionArray ( $content_statusArray );
		
		return $control->render ();
	}
	
	/**
	 * Retrieve content comment info (number of comments)
	 *
	 * @return str content comment info
	 */
	function getCommentsInfo() {
		$content_comments = $this->getVar ( 'content_comments' );
		if ($content_comments) {
			return '<a href="' . $this->getItemLink ( true ) . '#comments_container">' . sprintf ( _CO_CONTENT_CONTENT_COMMENTS_INFO, $content_comments ) . '</a>';
		} else {
			return _CO_CONTENT_CONTENT_NO_COMMENT;
		}
	}
	
	/**
	 * Retrieve content lead, which is everything before the [more] tag
	 *
	 * @return str content lead
	 */
	function getContentLead() {
		$ret = $this->getVar ( 'content_body' );
		$ret = icms_substr ( icms_cleanTags ( $ret, array ( ) ), 0, 300 );
		return $ret;
	}
	
	/**
	 * Sending the notification related to a content being published
	 *
	 * @return VOID
	 */
	function sendNotifContentPublished() {
		
		$module_handler = xoops_getHandler('module');
		$module = $module_handler->getByDirname('content');
		$module_id = $module->getVar ( 'mid' );
		$notification_handler = xoops_getHandler ( 'notification' );
		
		$tags ['CONTENT_TITLE'] = $this->getVar ( 'content_title' );
		$tags ['CONTENT_URL'] = $this->getItemLink ( true );
		
		$notification_handler->triggerEvent ( 'global', 0, 'content_published', $tags, array ( ), $module_id );
	}
	
	function getItemLink() {
		$seo = $this->handler->makelink ( $this );
		$ret = '<a href="' . $this->handler->_moduleUrl . $this->handler->_itemname . '.php?page=' . $seo . '" title="">' . $this->getVar ( 'content_title' ) . '</a>';
		
		return $ret;
	}
	
	/**
	 * Overridding IcmsPersistable::toArray() method to add a few info
	 *
	 * @return array of article info
	 */
	function toArray() {
		$ret = parent::toArray ();
		
		$ret ['content_info'] = $this->getContentInfo ();
		$ret ['content_lead'] = $this->getContentLead ();
		$ret ['content_comment_info'] = $this->getCommentsInfo ();
		$ret ['content_css'] = $this->getVar ( 'content_css', 'e' );
		$ret ['content_subs'] = $this->getContentSubs ( $this->getVar ( 'content_id', 'e' ),true );
		$ret ['content_hassubs'] = (count ( $ret ['content_subs'] ) > 0) ? true : false;
		$ret ['editItemLink'] = $this->getEditItemLink ( false, true, true );
		$ret ['deleteItemLink'] = $this->getDeleteItemLink ( false, true, true );
		$ret ['userCanEditAndDelete'] = $this->userCanEditAndDelete ();
		$ret ['content_posterid'] = $this->getVar ( 'content_uid', 'e' );
		$ret ['itemLink'] = $this->getItemLink ();
		$ret ['accessgranted'] = $this->accessGranted();
		
		return $ret;
	}
}

/**
 * ImpressCMS Core Content Object Handler Class
 * 
 * @copyright The ImpressCMS Project <http://www.impresscms.org>
 * @license GNU GPL v2
 * 
 * @since ImpressCMS 1.2
 * @author Rodrigo P Lima (aka TheRplima) <therplima@impresscms.org>
 */
class ContentContentHandler extends IcmsPersistableObjectHandler {
	
	/**
	 * @public array of status
	 */
	public $_content_statusArray = array ( );
	
	/**
	 * @public array of status
	 */
	public $_content_visibleArray = array ( );
	
	/**
	 * @public array of tags
	 */
	public $_content_tagsArray = array ( );
	
	public function __construct(& $db) {
		$this->IcmsPersistableObjectHandler ( $db, 'content', 'content_id', 'content_title', 'content_body', 'content' );

		icms_loadLanguageFile('content', 'common');
		$this->addPermission ( 'content_read', _CO_CONTENT_CONTENT_READ, _CO_CONTENT_CONTENT_READ_DSC );
	}
	
	/**
	 * Retreive the possible status of a content object
	 *
	 * @return array of status
	 */
	function getContent_statusArray() {
		if (! $this->_content_statusArray) {
			$this->_content_statusArray [CONTENT_CONTENT_STATUS_PUBLISHED] = _CO_CONTENT_CONTENT_STATUS_PUBLISHED;
			$this->_content_statusArray [CONTENT_CONTENT_STATUS_PENDING] = _CO_CONTENT_CONTENT_STATUS_PENDING;
			$this->_content_statusArray [CONTENT_CONTENT_STATUS_DRAFT] = _CO_CONTENT_CONTENT_STATUS_DRAFT;
			$this->_content_statusArray [CONTENT_CONTENT_STATUS_PRIVATE] = _CO_CONTENT_CONTENT_STATUS_PRIVATE;
			$this->_content_statusArray [CONTENT_CONTENT_STATUS_EXPIRED] = _CO_CONTENT_CONTENT_STATUS_EXPIRED;
		}
		return $this->_content_statusArray;
	}
	
	/**
	 * Retreive the possible visibility of a content object
	 *
	 * @return array of visibility
	 */
	function getContent_visibleArray() {
		if (! $this->_content_visibleArray) {
			$this->_content_visibleArray [CONTENT_CONTENT_VISIBLE_MENUOLNY] = _CO_CONTENT_CONTENT_VISIBLE_MENUOLNY;
			$this->_content_visibleArray [CONTENT_CONTENT_VISIBLE_SUBSONLY] = _CO_CONTENT_CONTENT_VISIBLE_SUBSONLY;
			$this->_content_visibleArray [CONTENT_CONTENT_VISIBLE_MENUSUBS] = _CO_CONTENT_CONTENT_VISIBLE_MENUSUBS;
			$this->_content_visibleArray [CONTENT_CONTENT_VISIBLE_DONTSHOW] = _CO_CONTENT_CONTENT_VISIBLE_DONTSHOW;
		}
		return $this->_content_visibleArray;
	}
	

	/**
	 * Retreive the tags of the content object
	 *
	 * @return array of tags
	 */
	function getContent_tagsArray() {
		if (! $this->_content_tagsArray) {
			$ret = array();
			$contents = $this->getObjects();
			foreach($contents as $content){
				$tags = $content->getVar('content_tags','e');
				$tag_arr = explode(",",$tags);
				foreach ($tag_arr as $tag){
					$tag = trim($tag);
					if (isset($ret[$tag])){
						$ret[$tag]++;
					}else{
						$ret[$tag] = 1;
					}
				}
			}
			foreach($ret as $k=>$v){
				if ($k != ''){
					$ret[$k] = $k.'('.$v.')';
				}else{
					unset($ret[$k]);
				}
			}
			$this->_content_tagsArray = $ret;
		}
		return $this->_content_tagsArray;
	}
	
	/**
	 * Create the criteria that will be used by getContents and getContentsCount
	 *
	 * @param int $start to which record to start
	 * @param int $limit limit of contents to return
	 * @param int $content_uid if specifid, only the content of this user will be returned
	 * @param int $cid if specifid, only the content related to this category will be returned
	 * @param int $year of contents to display
	 * @param int $month of contents to display
	 * @param int $content_id ID of a single content to retrieve
	 * @return CriteriaCompo $criteria
	 */
	function getContentsCriteria($start = 0, $limit = 0, $content_uid = false, $content_tags=false, $content_id = false,  $content_pid = false, $order = 'content_published_date', $sort = 'DESC') {
		global $xoopsUser;
		
		$criteria = new CriteriaCompo ( );
		if ($start) {
			$criteria->setStart ( $start );
		}
		if ($limit) {
			$criteria->setLimit ( intval ( $limit ) );
		}
		$criteria->setSort ( $order );
		$criteria->setOrder ( $sort );
		
		$criteria->add ( new Criteria ( 'content_status', CONTENT_CONTENT_STATUS_PUBLISHED ) );
		
		if ($content_uid) {
			$criteria->add ( new Criteria ( 'content_uid', $content_uid ) );
		}
		
		if ($content_tags){
			$criteria->add ( new Criteria ( 'content_tags', '%'.$content_tags.'%', 'LIKE' ) );
		}
		
		if ($content_id) {
			$crit = new CriteriaCompo(new Criteria('short_url', $content_id,'LIKE'));
			$alt_content_id = str_replace('-',' ',$content_id);
			$crit->add(new Criteria('short_url', $alt_content_id),'OR'); //Added for backward compatiblity in case short_url contains spaces instead of dashes.
			$crit->add(new Criteria('content_id', $content_id),'OR');
			$criteria->add($crit);	
		}
		
		if ($content_pid !== false){
			$criteria->add ( new Criteria ( 'content_pid', $content_pid ) );
		}
		return $criteria;
	}
	

	/**
	 * Get single content object
	 *
	 * @param int $content_id
	 * @return object ImreportingContent object
	 */
	function getContent($content_id) {
		$ret = $this->getContents ( 0, 0, false, false, $content_id );
		return isset ( $ret [$content_id] ) ? $ret [$content_id] : false;
	}
	
	
	/**
	 * Get contents as array, ordered by content_published_date DESC
	 *
	 * @param int $start to which record to start
	 * @param int $limit max contents to display
	 * @param int $content_uid if specifid, only the content of this user will be returned
	 * @param int $cid if specifid, only the content related to this category will be returned
	 * @param int $year of contents to display
	 * @param int $month of contents to display
	 * @param int $content_id ID of a single content to retrieve
	 * @return array of contents
	 */
	function getContents($start = 0, $limit = 0, $content_uid = false, $content_tags = false, $content_id = false,  $content_pid = false, $order = 'content_published_date', $sort = 'DESC') {
		$criteria = $this->getContentsCriteria ( $start, $limit, $content_uid, $content_tags, $content_id,  $content_pid, $order, $sort );
		$contents = $this->getObjects ( $criteria, true, false );
		$ret = array();
		foreach ($contents as $content){
			if ($content['accessgranted']){
				$ret[$content['content_id']] = $content;
			}
		}
		return $ret;
	}
	
	
	/**
	 * Get a list of users
	 *
	 * @return array list of users
	 */
	function getPostersArray() {
		$member_handler = xoops_getHandler ( 'member' );
		return $member_handler->getUserList ();
	}
	
	
	/**
	 * Get contents count
	 *
	 * @param int $content_uid if specifid, only the content of this user will be returned
	 * @param int $cid if specifid, only the content related to this category will be returned
	 * @return array of contents
	 */
	function getContentsCount($content_uid) {
		$criteria = $this->getContentsCriteria ( false, false, $content_uid );
		return $this->getCount ( $criteria );
	}
	
	
	/**
	 * Get Contents requested by the global search feature
	 *
	 * @param array $queryarray array containing the searched keywords
	 * @param bool $andor wether the keywords should be searched with AND or OR
	 * @param int $limit maximum results returned
	 * @param int $offset where to start in the resulting dataset
	 * @param int $userid should we return contents by specific contenter ?
	 * @return array array of contents
	 */
	function getContentsForSearch($queryarray, $andor, $limit, $offset, $userid) {
		$criteria = new CriteriaCompo ( );
		
		$criteria->setStart ( $offset );
		$criteria->setLimit ( $limit );
		
		if ($userid != 0) {
			$criteria->add ( new Criteria ( 'content_uid', $userid ) );
		}
		if ($queryarray) {
			$criteriaKeywords = new CriteriaCompo ( );
			for($i = 0; $i < count ( $queryarray ); $i ++) {
				$criteriaKeyword = new CriteriaCompo ( );
				$criteriaKeyword->add ( new Criteria ( 'content_title', '%' . $queryarray [$i] . '%', 'LIKE' ), 'OR' );
				$criteriaKeyword->add ( new Criteria ( 'content_body', '%' . $queryarray [$i] . '%', 'LIKE' ), 'OR' );
				$criteriaKeywords->add ( $criteriaKeyword, $andor );
				unset ( $criteriaKeyword );
			}
			$criteria->add ( $criteriaKeywords );
		}
		$criteria->add ( new Criteria ( 'content_status', CONTENT_CONTENT_STATUS_PUBLISHED ) );
		return $this->getObjects ( $criteria, true, false );
	}
	
	/**
	 * Check wether the current user can submit a new content or not
	 *
	 * @return bool true if he can false if not
	 */
	function userCanSubmit() {
		global $xoopsUser, $content_isAdmin;
		if (! is_object ( $xoopsUser )) {

			return false;
		}
		if ($content_isAdmin) {
			return true;
		}
		$user_groups = $xoopsUser->getGroups ();
	
		return count ( array_intersect ( $xoopsModuleConfig ['poster_group'], $user_groups ) ) > 0;
	}
	
	

	/**
	 * Update the counter field of the content object
	 *
	 * @param int $content_id
	 *
	 * @return VOID
	 */
	function updateCounter($id) {
		global $xoopsUser, $content_isAdmin;
		
		$contentObj = $this->get ( $id );
		if (! is_object ( $contentObj )) {
			return false;
		}
		if (!is_object($xoopsUser) || (!$content_isAdmin && $contentObj->getVar ( 'content_uid', 'e' ) != $xoopsUser->uid ())) {
			$contentObj->updating_counter = true;
			$contentObj->setVar ( 'counter', $contentObj->getVar ( 'counter', 'n' ) + 1 );
			$this->insert ( $contentObj, true );
		}
		
		return true;
	}
	

	/**
	 * Get contents count
	 *
	 * @param int $content_uid if specifid, only the content of this user will be returned
	 * @return array of contents
	 */
	function getContentsSubsCount($content_id = 0) {
		$criteria = $this->getContentsCriteria ();
		$criteria->add ( new Criteria ( 'content_pid', $content_id ) );
		return $this->getCount ( $criteria );
	}
	
	/**
	 * Get the subpages of the page
	 *
	 * @return array of contents
	 */
	function getContentSubs($content_id = 0, $toarray=false) {
		$criteria = $this->getContentsCriteria();
		$criteria->add( new Criteria ( 'content_pid', $content_id ) );
		$crit = new CriteriaCompo(new Criteria('content_visibility', 2));
		$crit->add(new Criteria('content_visibility', 3),'OR');
		$criteria->add($crit);
		$contents = $this->getObjects($criteria);
		if (!$toarray){
			return $contents;
		}
		$ret = array();
		foreach ( array_keys ( $contents ) as $i ) {
			if ($contents[$i]->accessGranted()){
				$ret[$i] = $contents[$i]->toArray();
				$ret[$i]['content_body'] = icms_substr(icms_cleanTags($contents[$i]->getVar('content_body','n'),array()),0,300);
				$ret[$i]['content_url'] = $contents[$i]->getItemLink();
			}
		}
		return $ret;
	}
	

	function getList($content_status = null) {
		$criteria = new CriteriaCompo ( );

		if (isset ( $content_status )) {
			$criteria->add ( new Criteria ( 'content_status', intval ( $content_status ) ) );
		}
		$contents = & $this->getObjects ( $criteria, true );
		foreach ( array_keys ( $contents ) as $i ) {
			$ret [$contents [$i]->getVar ( 'content_id' )] = $contents [$i]->getVar ( 'content_title' );
		}
		return $ret;
	}
	
	
	function getContentList($groups = array(), $perm = 'content_read', $status = null, $content_id = null, $showNull = true) {
		$criteria = new CriteriaCompo ( );
		if (is_array ( $groups ) && ! empty ( $groups )) {
			$criteriaTray = new CriteriaCompo ( );
			foreach ( $groups as $gid ) {
				$criteriaTray->add ( new Criteria ( 'gperm_groupid', $gid ), 'OR' );
			}
			$criteria->add ( $criteriaTray );
			if ($perm == 'content_read' || $perm == 'content_admin') {
				$criteria->add ( new Criteria ( 'gperm_name', $perm ) );
				$criteria->add ( new Criteria ( 'gperm_modid', 1 ) );
			}
		}
		if (isset ( $status )) {
			$criteria->add ( new Criteria ( 'content_status', intval ( $status ) ) );
		}
		if (is_null ( $content_id ))
			$content_id = 0;
		$criteria->add ( new Criteria ( 'content_pid', $content_id ) );

		$contents = & $this->getObjects ( $criteria, true );
		$ret = array ( );
		if ($showNull) {
			$ret [0] = '-----------------------';
		}
		foreach ( array_keys ( $contents ) as $i ) {
			$ret [$i] = $contents [$i]->getVar ( 'content_title' );
			$subccontents = $this->getContentList ( $groups, $perm, $status, $contents [$i]->getVar ( 'content_id' ), $showNull );
			foreach ( array_keys ( $subccontents ) as $j ) {
				$ret [$j] = '-' . $subccontents [$j];
			}
		}
		
		return $ret;
	}
	
	
	function makeLink($content,$onlyUrl=false) {
		$count = $this->getCount ( new Criteria ( "short_url", $content->getVar ( "short_url" ) ) );
		
		if ($count > 1) {
			return $content->getVar ( 'content_id' );
		} else {
			$seo = str_replace ( " ", "-", $content->getVar ( 'short_url' ) );
			return $seo;
		}
	}
	
	function hasPage($user) {
		$gperm_handler = & xoops_gethandler ( 'groupperm' );
		$groups = is_object ( $user ) ? $user->getGroups () : XOOPS_GROUP_ANONYMOUS;
		$criteria = new CriteriaCompo ( new Criteria ( 'content_status', 1 ) );
		$cont_arr = $this->getObjects ( $criteria );
		if (count ( $cont_arr ) > 0) {
			$perm = array ( );
			foreach ( $cont_arr as $cont ) {
				if ($gperm_handler->checkRight ( 'content_read', $cont->getVar ( 'content_id' ), $groups )) {
					$perm [] = $cont->getVar ( 'content_id' );
				}
			}
			if (count ( $perm ) > 0) {
				if ($xoopsModuleConfig ['default_page'] != 0) {
					if (! in_array ( $xoopsModuleConfig ['default_page'], $perm )) {
						return false;
					}
				}
				return true;
			} else {
				return false;
			}
		} else {
			return false;
		}
	}

	function getLastestCreated($asObj=true){
		$criteria = $this->getContentsCriteria (0,1);
		$criteria->setSort ( 'content_id' );
		$criteria->setOrder ( 'DESC' );
		$ret = $this->getObjects ( $criteria, false, $asObj );
		if ($asObj){
			return $ret[0];
		}else{
			return $ret[0]['content_id'];
		}
	}

	/**
 	 * Function to create a navigation menu in content pages.
 	 * This function was based on the function that do the same in mastop publish module
 	 * 
 	 * @param integer $content_id
 	 * @return string
 	 */
	function getBreadcrumbForPid($content_id, $userside=false){
		$url = $_SERVER['PHP_SELF'];
		$ret = false;
		
		if ($content_id == false) {
			return $ret;
		}else{
			if ($content_id > 0) {
				$content = $this->get($content_id);
				if ($content->getVar('content_id','e') > 0) {
					if (!$userside){
						$ret = "<a href='".$url."?content_pid=".$content->getVar('content_id','e')."'>".$content->getVar('content_title','e')."</a>";
					}else{
						$ret = "<a href='".$url."?page=".$this->makeLink($content)."'>".$content->getVar('content_title','e')."</a>";
					}
					if ($content->getVar('content_pid','e') == 0) {
						if (!$userside){
							return "<a href='".$url."?content_pid=0'>"._MI_CONTENT_CONTENTS."</a> &gt; ".$ret;
						}else{
							return $ret;
						}
					}elseif ($content->getVar('content_pid','e') > 0){
						$ret = $this->getBreadcrumbForPid($content->getVar('content_pid','e'), $userside)." &gt; ". $ret;
					}
				}
			}else{
				return $ret;
			}
		}
		return $ret;
	}

	/**
	 * Update number of comments on a content
	 *
	 * This method is triggered by imcontent_com_update in include/functions.php which is
	 * called by ImpressCMS when updating comments
	 *
	 * @param int $content_id id of the content to update
	 * @param int $total_num total number of comments so far in this content
	 * @return VOID
	 */
	function updateComments($content_id, $total_num) {
		$contentObj = $this->get ( $content_id );
		if ($contentObj && ! $contentObj->isNew ()) {
			$contentObj->setVar ( 'content_comments', $total_num );
			$this->insert ( $contentObj, true );
		}
	}
	
	/**
	 * BeforeSave event
	 *
	 * Event automatically triggered by IcmsPersistable Framework before the object is inserted or updated.
	 *
	 * @param object $obj Content object
	 * @return true
	 */
	function beforeSave(& $obj) {
		if ($obj->updating_counter)
			return true;
		
		$obj->setVar ( 'dobr', $obj->need_do_br () );
		
		//Prevent that the page is defined as parent page of yourself.
		if ($obj->getVar('content_pid','e') == $obj->getVar('content_id','e')){
			$obj->setVar('content_pid', 0);
		}
		
		return true;
	}
	
	/**
	 * AfterSave event
	 *
	 * Event automatically triggered by IcmsPersistable Framework after the object is inserted or updated
	 *
	 * @param object $obj Content object
	 * @return true
	 */
	function afterSave(& $obj) {
		if ($obj->updating_counter)
			return true;
			
		if (! $obj->getVar ( 'content_notification_sent' ) && $obj->getVar ( 'content_status', 'e' ) == CONTENT_CONTENT_STATUS_PUBLISHED) {
			$obj->sendNotifContentPublished ();
			$obj->setVar ( 'content_notification_sent', true );
			$this->insert ( $obj );
		}
			
		if ($obj->getVar('content_makesymlink') == 1){
			$module_handler = xoops_gethandler('module');
			$module = $module_handler->getByDirname('content');
			
			$seo = $obj->handler->makelink($obj);
			$url = str_replace(ICMS_URL.'/','',$obj->handler->_moduleUrl.$obj->handler->_itemname.'.php?page='.$seo);
			
			$symlink_handler = xoops_getmodulehandler('pages','system');
			$criteria = new CriteriaCompo(new Criteria('page_url','%'.$seo,'LIKE'));
			$criteria->add(new Criteria('page_moduleid',$module->mid()));
			$ct = $symlink_handler->getObjects($criteria);
			if (count($ct) <= 0){
				$symlink = $symlink_handler->create(true);
				$symlink->setVar('page_moduleid',$module->mid());
				$symlink->setVar('page_title',$obj->getVar('content_title'));
				$symlink->setVar('page_url',$url);
				$symlink->setVar('page_status',1);
				$symlink_handler->insert($symlink);
			}
		}
		return true;
	}
	
	/**
	 * AfterDelete event
	 *
	 * Event automatically triggered by IcmsPersistable Framework after the object is deleted
	 *
	 * @param object $obj Content object
	 * @return true
	 */
	function afterDelete(& $obj) {
		$seo = $obj->handler->makelink($obj);
		$url = str_replace(ICMS_URL.'/','',$obj->handler->_moduleUrl.$obj->handler->_itemname.'.php?page='.$seo);
		$module_handler = xoops_gethandler('module');
		$module = $module_handler->getByDirname('content');
		
		$symlink_handler = xoops_getmodulehandler('pages','system');
		$criteria = new CriteriaCompo(new Criteria('page_url',$url));
		$criteria->add(new Criteria('page_moduleid',$module->mid()));
		$symlink_handler->deleteAll($criteria);
		
		return true;
	}
}

?>